<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\MigrationRecord;
use App\Models\UserDriverDetail;
use App\Services\Driver\DriverMigrationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DriverMigrationController extends Controller
{
    public function __construct(
        protected DriverMigrationService $migrationService
    ) {}

    /**
     * Show the migration wizard page with all data pre-loaded.
     */
    public function wizard(UserDriverDetail $driver): Response|RedirectResponse
    {
        if ($driver->status !== UserDriverDetail::STATUS_ACTIVE) {
            return redirect()->route('admin.drivers.show', $driver->id)
                ->with('error', 'Only active drivers can be migrated.');
        }

        $driver->load(['user:id,name,email', 'carrier:id,name', 'medicalQualification', 'licenses', 'primaryLicense']);

        $carriers = $this->migrationService->getAvailableTargetCarriers($driver);

        $availableCarriers = $carriers->map(fn ($c) => [
            'id'              => $c->id,
            'name'            => $c->name,
            'dot_number'      => $c->dot_number,
            'mc_number'       => $c->mc_number,
            'state'           => $c->state,
            'address'         => $c->address,
            'current_drivers' => $c->userDrivers()->active()->count(),
            'max_drivers'     => $c->membership?->max_drivers ?? 0,
        ])->values()->all();

        // Pre-compute warnings that the JS can show client-side
        $driverWarnings = [];
        $med = $driver->medicalQualification;
        if ($med?->medical_card_expiration_date) {
            $expiry = Carbon::parse($med->medical_card_expiration_date);
            if ($expiry->isPast()) {
                $driverWarnings[] = 'Driver medical qualification has expired.';
            } elseif (now()->diffInDays($expiry) <= 30) {
                $driverWarnings[] = 'Driver medical qualification expires within 30 days.';
            }
        }
        $lic = $driver->primaryLicense;
        if ($lic?->expiration_date) {
            $expiry = Carbon::parse($lic->expiration_date);
            if ($expiry->isPast()) {
                $driverWarnings[] = 'Driver license has expired.';
            } elseif (now()->diffInDays($expiry) <= 30) {
                $driverWarnings[] = 'Driver license expires within 30 days.';
            }
        }
        $hasPendingTests = $driver->testings()
            ->where(fn ($q) => $q->where('status', 'pending')->orWhere('test_result', 'Pending'))
            ->exists();
        if ($hasPendingTests) {
            $driverWarnings[] = 'Driver has pending drug/alcohol test results.';
        }

        return Inertia::render('admin/drivers/migration/Wizard', [
            'driver' => [
                'id'             => $driver->id,
                'full_name'      => trim(($driver->user?->name ?? '') . ' ' . ($driver->last_name ?? '')),
                'email'          => $driver->user?->email ?? '',
                'status'         => $driver->status,
                'carrier_name'   => $driver->carrier?->name ?? 'N/A',
            ],
            'availableCarriers' => $availableCarriers,
            'driverWarnings'    => $driverWarnings,
        ]);
    }

    /**
     * Execute the migration (called from Wizard page).
     */
    public function execute(Request $request, UserDriverDetail $driver): RedirectResponse
    {
        $request->validate([
            'carrier_id' => 'required|integer|exists:carriers,id',
            'reason'     => 'nullable|string|max:1000',
            'notes'      => 'nullable|string|max:2000',
        ]);

        $targetCarrier = Carrier::findOrFail($request->carrier_id);

        // Run validation first
        $validation = $this->migrationService->validateMigrationEligibility($driver, $targetCarrier);
        if (! $validation->isValid) {
            return back()->withErrors(['migration' => implode(' ', $validation->errors)]);
        }

        $result = $this->migrationService->migrate(
            driver: $driver,
            targetCarrier: $targetCarrier,
            performedBy: auth()->user(),
            reason: $request->reason,
            notes: $request->notes,
        );

        if (! $result->success) {
            return back()->withErrors(['migration' => implode(' ', $result->errors)]);
        }

        return redirect()
            ->route('admin.drivers.show', $driver->id)
            ->with('success', "Driver successfully migrated to {$targetCarrier->name}. Migration record #{$result->migrationRecord->id}.");
    }

    /**
     * Rollback a migration within the 24-hour grace period.
     */
    public function rollback(Request $request, MigrationRecord $record): RedirectResponse
    {
        $request->validate(['rollback_reason' => 'required|string|max:1000']);

        $result = $this->migrationService->rollback(
            record: $record,
            performedBy: auth()->user(),
            reason: $request->rollback_reason,
        );

        if (! $result->success) {
            return back()->withErrors(['rollback' => $result->error]);
        }

        return redirect()
            ->route('admin.drivers.show', $result->driver->id)
            ->with('success', 'Migration successfully rolled back. Driver restored to original carrier.');
    }
}
