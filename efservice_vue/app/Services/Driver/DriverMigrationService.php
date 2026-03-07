<?php

namespace App\Services\Driver;

use App\Events\DriverMigrationCompleted;
use App\Events\DriverMigrationRolledBack;
use App\Exceptions\ImmutableRecordException;
use App\Models\Carrier;
use App\Models\DriverArchive;
use App\Models\MigrationRecord;
use App\Models\User;
use App\Models\UserDriverDetail;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing driver migrations between carriers.
 * 
 * Orchestrates the complete migration process including validation,
 * archiving, transfer, and notifications.
 */
class DriverMigrationService
{
    public function __construct(
        protected DriverArchiveService $archiveService
    ) {}

    /**
     * Get available target carriers for migration.
     * Excludes the driver's current carrier and carriers at max capacity.
     */
    public function getAvailableTargetCarriers(UserDriverDetail $driver): Collection
    {
        $currentCarrierId = $driver->carrier_id;

        return Carrier::active()
            ->where('id', '!=', $currentCarrierId)
            ->with('membership')
            ->get()
            ->filter(function ($carrier) {
                return $this->carrierHasAvailableSlots($carrier);
            })
            ->values();
    }

    /**
     * Check if a carrier has available driver slots.
     */
    public function carrierHasAvailableSlots(Carrier $carrier): bool
    {
        if (!$carrier->membership) {
            return false;
        }

        $maxDrivers = $carrier->membership->max_drivers ?? 0;
        $currentDrivers = $carrier->userDrivers()->active()->count();

        return $currentDrivers < $maxDrivers;
    }

    /**
     * Validate migration eligibility.
     * Returns validation result with errors and warnings.
     */
    public function validateMigrationEligibility(
        UserDriverDetail $driver,
        Carrier $targetCarrier
    ): MigrationValidationResult {
        $errors = [];
        $warnings = [];

        // Check driver is active
        if ($driver->status !== UserDriverDetail::STATUS_ACTIVE) {
            $errors[] = 'Driver must have active status to be migrated.';
        }

        // Check not same carrier
        if ($driver->carrier_id === $targetCarrier->id) {
            $errors[] = 'Cannot migrate driver to the same carrier.';
        }

        // Check target carrier has slots
        if (!$this->carrierHasAvailableSlots($targetCarrier)) {
            $errors[] = 'Target carrier has reached maximum driver capacity.';
        }

        // Check for active trips (if trip model exists)
        if ($this->driverHasActiveTrip($driver)) {
            $errors[] = 'Driver is currently on an active trip and cannot be migrated.';
        }

        // Check for pending compliance issues
        $complianceIssues = $this->getComplianceIssues($driver);
        if (!empty($complianceIssues['blocking'])) {
            foreach ($complianceIssues['blocking'] as $issue) {
                $errors[] = $issue;
            }
        }

        // Add warnings for non-blocking issues
        if (!empty($complianceIssues['warnings'])) {
            $warnings = array_merge($warnings, $complianceIssues['warnings']);
        }

        return new MigrationValidationResult(
            isValid: empty($errors),
            errors: $errors,
            warnings: $warnings
        );
    }

    /**
     * Check if driver has an active trip.
     */
    protected function driverHasActiveTrip(UserDriverDetail $driver): bool
    {
        // Check if Trip model exists and driver has active trips
        if (class_exists(\App\Models\Trip::class)) {
            return \App\Models\Trip::where('user_driver_detail_id', $driver->id)
                ->where('status', 'in_progress')
                ->exists();
        }
        return false;
    }

    /**
     * Get compliance issues for the driver.
     */
    protected function getComplianceIssues(UserDriverDetail $driver): array
    {
        $blocking = [];
        $warnings = [];

        // Check medical qualification expiration
        $medical = $driver->medicalQualification;
        if ($medical && $medical->expiration_date) {
            if ($medical->expiration_date->isPast()) {
                $warnings[] = 'Driver medical qualification has expired.';
            } elseif (now()->diffInDays($medical->expiration_date) <= 30) {
                $warnings[] = 'Driver medical qualification expires within 30 days.';
            }
        }

        // Check license expiration
        $license = $driver->primaryLicense;
        if ($license && $license->expiration_date) {
            if ($license->expiration_date->isPast()) {
                $warnings[] = 'Driver license has expired.';
            } elseif (now()->diffInDays($license->expiration_date) <= 30) {
                $warnings[] = 'Driver license expires within 30 days.';
            }
        }

        // Check for pending drug tests
        if ($driver->testings()->exists()) {
            $pendingTests = $driver->testings()
                ->where(function($query) {
                    $query->where('status', 'pending')
                          ->orWhere('test_result', 'Pending');
                })
                ->exists();
            if ($pendingTests) {
                $warnings[] = 'Driver has pending drug/alcohol test results.';
            }
        }

        return ['blocking' => $blocking, 'warnings' => $warnings];
    }

    /**
     * Execute the complete migration process.
     */
    public function migrate(
        UserDriverDetail $driver,
        Carrier $targetCarrier,
        User $performedBy,
        ?string $reason = null,
        ?string $notes = null
    ): MigrationResult {
        // Validate first
        $validation = $this->validateMigrationEligibility($driver, $targetCarrier);
        if (!$validation->isValid) {
            return new MigrationResult(
                success: false,
                errors: $validation->errors
            );
        }

        $sourceCarrier = $driver->carrier;
        $migrationDate = now();

        try {
            return DB::transaction(function () use (
                $driver, $sourceCarrier, $targetCarrier, $performedBy, 
                $reason, $notes, $migrationDate
            ) {
                // 1. Generate driver snapshot for migration record
                $driverSnapshot = $this->archiveService->generateDriverSnapshot($driver);

                // 2. Create migration record first (needed for archive)
                $migrationRecord = MigrationRecord::create([
                    'driver_user_id' => $driver->user_id,
                    'source_carrier_id' => $sourceCarrier->id,
                    'target_carrier_id' => $targetCarrier->id,
                    'migrated_at' => $migrationDate,
                    'migrated_by_user_id' => $performedBy->id,
                    'reason' => $reason,
                    'notes' => $notes,
                    'driver_snapshot' => $driverSnapshot,
                    'status' => MigrationRecord::STATUS_COMPLETED,
                ]);

                // 3. Create archive in source carrier
                $archive = $this->archiveService->createArchive(
                    $driver,
                    $migrationRecord,
                    DriverArchive::REASON_MIGRATION
                );

                // 4. End all active vehicle assignments
                $this->endVehicleAssignments($driver, $migrationDate);

                // 5. Update driver to new carrier
                $driver->update([
                    'carrier_id' => $targetCarrier->id,
                    'status' => UserDriverDetail::STATUS_PENDING,
                ]);

                // 6. Dispatch event
                event(new DriverMigrationCompleted($migrationRecord, $archive));

                Log::info('Driver migration completed', [
                    'migration_record_id' => $migrationRecord->id,
                    'driver_id' => $driver->id,
                    'source_carrier_id' => $sourceCarrier->id,
                    'target_carrier_id' => $targetCarrier->id,
                    'performed_by' => $performedBy->id,
                ]);

                return new MigrationResult(
                    success: true,
                    migrationRecord: $migrationRecord,
                    archive: $archive
                );
            });
        } catch (\Exception $e) {
            Log::error('Driver migration failed', [
                'driver_id' => $driver->id,
                'target_carrier_id' => $targetCarrier->id,
                'error' => $e->getMessage(),
            ]);

            return new MigrationResult(
                success: false,
                errors: ['Migration failed: ' . $e->getMessage()]
            );
        }
    }

    /**
     * End all active vehicle assignments for the driver.
     */
    protected function endVehicleAssignments(UserDriverDetail $driver, $endDate): void
    {
        VehicleDriverAssignment::where('user_driver_detail_id', $driver->id)
            ->where('status', 'active')
            ->update([
                'status' => 'inactive',
                'end_date' => $endDate,
                'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [Ended by migration]')"),
            ]);
    }

    /**
     * Rollback a migration within the grace period.
     */
    public function rollback(
        MigrationRecord $record,
        User $performedBy,
        string $reason
    ): RollbackResult {
        if (!$record->canRollback()) {
            return new RollbackResult(
                success: false,
                error: 'Rollback period has expired (24 hours). Manual intervention required.'
            );
        }

        try {
            return DB::transaction(function () use ($record, $performedBy, $reason) {
                // 1. Get the archive
                $archive = $record->driverArchive;
                if (!$archive) {
                    throw new \Exception('Archive not found for migration record.');
                }

                // 2. Restore driver from archive
                $driver = $this->archiveService->restoreFromArchive($archive);

                // 3. Update migration record
                $record->update([
                    'status' => MigrationRecord::STATUS_ROLLED_BACK,
                    'rolled_back_at' => now(),
                    'rolled_back_by_user_id' => $performedBy->id,
                    'rollback_reason' => $reason,
                ]);

                // 4. Dispatch event
                event(new DriverMigrationRolledBack($record, $performedBy));

                Log::info('Driver migration rolled back', [
                    'migration_record_id' => $record->id,
                    'driver_id' => $driver->id,
                    'rolled_back_by' => $performedBy->id,
                ]);

                return new RollbackResult(
                    success: true,
                    driver: $driver
                );
            });
        } catch (\Exception $e) {
            Log::error('Migration rollback failed', [
                'migration_record_id' => $record->id,
                'error' => $e->getMessage(),
            ]);

            return new RollbackResult(
                success: false,
                error: 'Rollback failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get migrations that can still be rolled back.
     */
    public function getPendingRollbackMigrations(): Collection
    {
        return MigrationRecord::canRollback()
            ->with(['sourceCarrier', 'targetCarrier', 'driverUser'])
            ->orderBy('migrated_at', 'desc')
            ->get();
    }

    /**
     * Get migration statistics for reporting.
     */
    public function getMigrationStatistics(
        ?int $carrierId = null,
        ?\Carbon\Carbon $startDate = null,
        ?\Carbon\Carbon $endDate = null
    ): array {
        $query = MigrationRecord::query();

        if ($carrierId) {
            $query->where(function ($q) use ($carrierId) {
                $q->where('source_carrier_id', $carrierId)
                  ->orWhere('target_carrier_id', $carrierId);
            });
        }

        if ($startDate && $endDate) {
            $query->betweenDates($startDate, $endDate);
        }

        $total = $query->count();
        $completed = (clone $query)->completed()->count();
        $rolledBack = (clone $query)->rolledBack()->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'rolled_back' => $rolledBack,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
        ];
    }
}
