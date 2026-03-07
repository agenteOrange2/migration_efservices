<?php

namespace App\Services\Hos;

use Carbon\Carbon;
use App\Models\User;
use App\Models\UserDriverDetail;
use App\Models\Hos\HosEntry;
use App\Models\Hos\HosEntryAuditLog;
use App\Models\Hos\HosConfiguration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class HosService
{
    protected HosCalculationService $calculationService;
    protected HosAlertService $alertService;

    public function __construct(
        HosCalculationService $calculationService,
        HosAlertService $alertService
    ) {
        $this->calculationService = $calculationService;
        $this->alertService = $alertService;
    }

    /**
     * Create a new HOS entry for a driver.
     *
     * @param int $driverId
     * @param string $status
     * @param array|null $location ['latitude' => float, 'longitude' => float, 'address' => string|null]
     * @param int|null $createdBy User ID who created the entry (null for driver self-entry)
     * @return HosEntry
     * @throws InvalidArgumentException
     */
    public function createEntry(int $driverId, string $status, ?array $location = null, ?int $createdBy = null): HosEntry
    {
        // Validate status
        if (!in_array($status, HosEntry::STATUSES)) {
            throw new InvalidArgumentException("Invalid status: {$status}");
        }

        $driver = UserDriverDetail::findOrFail($driverId);

        // Check for active vehicle assignment
        $vehicleAssignment = $driver->activeVehicleAssignment;
        if (!$vehicleAssignment) {
            throw new InvalidArgumentException("Driver must have an active vehicle assignment to record HOS entries");
        }

        $now = Carbon::now();

        return DB::transaction(function () use ($driver, $status, $location, $createdBy, $now, $vehicleAssignment) {
            // Close any open entry
            $this->closeOpenEntry($driver->id, $now);

            // Determine location availability
            $locationAvailable = !empty($location['latitude']) && !empty($location['longitude']);

            // Create new entry
            $entry = HosEntry::create([
                'user_driver_detail_id' => $driver->id,
                'vehicle_id' => $vehicleAssignment->vehicle_id,
                'carrier_id' => $driver->carrier_id,
                'status' => $status,
                'start_time' => $now,
                'end_time' => null,
                'latitude' => $location['latitude'] ?? null,
                'longitude' => $location['longitude'] ?? null,
                'formatted_address' => $location['address'] ?? null,
                'location_available' => $locationAvailable,
                'is_manual_entry' => !is_null($createdBy) && $createdBy !== $driver->user_id,
                'manual_entry_reason' => null,
                'created_by' => $createdBy,
                'date' => $now->format('Y-m-d'),
            ]);

            // Create audit log
            HosEntryAuditLog::logCreation($entry, $createdBy ?? $driver->user_id);

            // Recalculate daily totals
            $this->calculationService->recalculateDailyLog($driver->id, $now);

            // Check for alerts
            $this->alertService->checkAndSendAlerts($driver->id, $now);

            return $entry;
        });
    }

    /**
     * Close the current open entry for a driver.
     *
     * @param int $driverId
     * @param Carbon|null $endTime
     * @return HosEntry|null
     */
    public function closeOpenEntry(int $driverId, ?Carbon $endTime = null): ?HosEntry
    {
        $endTime = $endTime ?? Carbon::now();

        $openEntry = HosEntry::forDriver($driverId)
            ->open()
            ->first();

        if ($openEntry) {
            $openEntry->update(['end_time' => $endTime]);
            
            // If entry spans midnight, recalculate both days
            if (!$openEntry->start_time->isSameDay($endTime)) {
                $this->calculationService->recalculateDailyLog($driverId, $openEntry->start_time);
            }
        }

        return $openEntry;
    }

    /**
     * Update an existing HOS entry.
     *
     * @param HosEntry $entry
     * @param array $data
     * @param int $modifiedBy
     * @param string $reason
     * @return HosEntry
     */
    public function updateEntry(HosEntry $entry, array $data, int $modifiedBy, string $reason): HosEntry
    {
        return DB::transaction(function () use ($entry, $data, $modifiedBy, $reason) {
            $originalValues = $entry->toArray();

            // Update allowed fields
            $allowedFields = [
                'status', 'start_time', 'end_time', 
                'latitude', 'longitude', 'formatted_address',
                'location_available', 'manual_entry_reason'
            ];

            $updateData = array_intersect_key($data, array_flip($allowedFields));
            
            // Update date if start_time changed
            if (isset($updateData['start_time'])) {
                $startTime = $updateData['start_time'] instanceof Carbon 
                    ? $updateData['start_time'] 
                    : Carbon::parse($updateData['start_time']);
                $updateData['date'] = $startTime->format('Y-m-d');
            }

            $entry->update($updateData);

            // Create audit log
            HosEntryAuditLog::logUpdate($entry, $originalValues, $modifiedBy, $reason);

            // Recalculate daily totals for affected dates
            $this->calculationService->recalculateDailyLog($entry->user_driver_detail_id, $entry->date);
            
            // If original date was different, recalculate that too
            $originalDate = Carbon::parse($originalValues['date']);
            if (!$originalDate->isSameDay($entry->date)) {
                $this->calculationService->recalculateDailyLog($entry->user_driver_detail_id, $originalDate);
            }

            return $entry->fresh();
        });
    }

    /**
     * Create a manual entry (by carrier/admin).
     *
     * @param int $driverId
     * @param string $status
     * @param Carbon $startTime
     * @param Carbon|null $endTime
     * @param array|null $location
     * @param int $createdBy
     * @param string $reason
     * @return HosEntry
     */
    public function createManualEntry(
        int $driverId,
        string $status,
        Carbon $startTime,
        ?Carbon $endTime,
        ?array $location,
        int $createdBy,
        string $reason
    ): HosEntry {
        if (empty($reason)) {
            throw new InvalidArgumentException("Reason is required for manual entries");
        }

        if (!in_array($status, HosEntry::STATUSES)) {
            throw new InvalidArgumentException("Invalid status: {$status}");
        }

        $driver = UserDriverDetail::findOrFail($driverId);
        $vehicleAssignment = $driver->activeVehicleAssignment;

        if (!$vehicleAssignment) {
            throw new InvalidArgumentException("Driver must have an active vehicle assignment");
        }

        return DB::transaction(function () use ($driver, $status, $startTime, $endTime, $location, $createdBy, $reason, $vehicleAssignment) {
            $locationAvailable = !empty($location['latitude']) && !empty($location['longitude']);

            $entry = HosEntry::create([
                'user_driver_detail_id' => $driver->id,
                'vehicle_id' => $vehicleAssignment->vehicle_id,
                'carrier_id' => $driver->carrier_id,
                'status' => $status,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'latitude' => $location['latitude'] ?? null,
                'longitude' => $location['longitude'] ?? null,
                'formatted_address' => $location['address'] ?? null,
                'location_available' => $locationAvailable,
                'is_manual_entry' => true,
                'manual_entry_reason' => $reason,
                'created_by' => $createdBy,
                'date' => $startTime->format('Y-m-d'),
            ]);

            // Create audit log
            HosEntryAuditLog::logCreation($entry, $createdBy, "Manual entry: {$reason}");

            // Recalculate daily totals
            $this->calculationService->recalculateDailyLog($driver->id, $startTime);

            // If entry spans multiple days, recalculate those too
            if ($endTime && !$startTime->isSameDay($endTime)) {
                $current = $startTime->copy()->addDay()->startOfDay();
                while ($current->lte($endTime)) {
                    $this->calculationService->recalculateDailyLog($driver->id, $current);
                    $current->addDay();
                }
            }

            return $entry;
        });
    }

    /**
     * Get driver's current status.
     *
     * @param int $driverId
     * @return HosEntry|null
     */
    public function getDriverCurrentStatus(int $driverId): ?HosEntry
    {
        return HosEntry::forDriver($driverId)
            ->open()
            ->first();
    }

    /**
     * Get driver's entries for a specific date.
     *
     * @param int $driverId
     * @param Carbon|string $date
     * @return Collection
     */
    public function getDriverEntriesForDate(int $driverId, $date): Collection
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        
        return HosEntry::forDriver($driverId)
            ->forDate($date)
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Get driver's entries for a date range.
     *
     * @param int $driverId
     * @param Carbon|string $startDate
     * @param Carbon|string $endDate
     * @return Collection
     */
    public function getDriverEntriesForDateRange(int $driverId, $startDate, $endDate): Collection
    {
        $startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        return HosEntry::forDriver($driverId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Get driver's dashboard data.
     *
     * @param int $driverId
     * @return array
     */
    public function getDriverDashboard(int $driverId): array
    {
        $driver = UserDriverDetail::findOrFail($driverId);
        $today = Carbon::today();
        
        $currentEntry = $this->getDriverCurrentStatus($driverId);
        $todayEntries = $this->getDriverEntriesForDate($driverId, $today);
        $dailyTotals = $this->calculationService->calculateDailyTotals($driverId, $today);
        $remaining = $this->calculationService->calculateRemainingHours($driverId, $today);
        $alerts = $this->alertService->getActiveAlerts($driverId);

        return [
            'driver' => $driver,
            'current_status' => $currentEntry,
            'today_entries' => $todayEntries,
            'daily_totals' => $dailyTotals,
            'remaining' => $remaining,
            'alerts' => $alerts,
        ];
    }
}
