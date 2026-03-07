<?php

namespace App\Imports;

use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Hos\HosEntry;
use App\Models\User;
use App\Models\UserDriverDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class HosEntriesImport extends BaseImport
{
    /**
     * Process the collection of rows.
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowData = $row->toArray();
            $rowNumber = $index + 2;

            // Find driver by email
            $driver = $this->findDriver($rowData);

            if (!$driver) {
                $this->addSkippedRow(
                    $rowNumber,
                    'Driver not found: ' . ($rowData['driver_email'] ?? 'N/A'),
                    $rowData
                );
                continue;
            }

            // Find vehicle (required)
            $vehicle = $this->findVehicle($rowData);

            if (!$vehicle) {
                $this->addSkippedRow(
                    $rowNumber,
                    'Vehicle not found: ' . ($rowData['vehicle_unit_number'] ?? 'N/A'),
                    $rowData
                );
                continue;
            }

            // Check for duplicate
            $existingEntry = $this->findDuplicateEntry($driver->id, $rowData);

            if ($existingEntry) {
                if ($this->shouldUpdateDuplicates()) {
                    // Update existing record
                    try {
                        $this->updateHosEntry($existingEntry, $rowData, $vehicle->id);
                        $this->addUpdatedRow($existingEntry->id);

                        Log::info('HOS entry updated', [
                            'row' => $rowNumber,
                            'entry_id' => $existingEntry->id,
                            'driver_id' => $driver->id,
                        ]);
                    } catch (\Exception $e) {
                        $this->addSkippedRow($rowNumber, 'Update failed: ' . $e->getMessage(), $rowData);
                    }
                } else {
                    // Skip duplicate
                    $this->addSkippedRow($rowNumber, 'Duplicate HOS entry', $rowData);
                }
                continue;
            }

            try {
                $entry = HosEntry::create([
                    'user_driver_detail_id' => $driver->id,
                    'carrier_id' => $this->carrierId,
                    'vehicle_id' => $vehicle->id,
                    'status' => $this->normalizeStatus($rowData['status'] ?? 'off_duty'),
                    'start_time' => $this->parseDateTime($rowData['start_time'] ?? null),
                    'end_time' => $this->parseDateTime($rowData['end_time'] ?? null),
                    'date' => $this->parseDate($rowData['date'] ?? $rowData['start_time'] ?? null),
                    'formatted_address' => trim($rowData['location'] ?? '') ?: null,
                    'location_available' => !empty($rowData['location']),
                    'is_manual_entry' => true,
                    'manual_entry_reason' => 'Imported from CSV',
                    'created_by' => $this->importedBy,
                ]);

                $this->addImportedRow($entry->id);

                Log::info('HOS entry imported', [
                    'row' => $rowNumber,
                    'entry_id' => $entry->id,
                    'driver_id' => $driver->id,
                ]);
            } catch (\Exception $e) {
                $this->addSkippedRow($rowNumber, $e->getMessage(), $rowData);

                Log::error('HOS entry import failed', [
                    'row' => $rowNumber,
                    'error' => $e->getMessage(),
                    'data' => $rowData,
                ]);
            }
        }
    }

    /**
     * Get validation rules.
     */
    public function rules(): array
    {
        return [
            'driver_email' => 'required|email',
            'date' => 'required',
            'status' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'nullable',
            'location' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get the unique key for a row.
     */
    protected function getUniqueKey(array $row): string
    {
        $email = $row['driver_email'] ?? '';
        $date = $row['date'] ?? '';
        $startTime = $row['start_time'] ?? '';

        return md5($email . '|' . $date . '|' . $startTime);
    }

    /**
     * Check if a row is a duplicate.
     */
    protected function isDuplicate(array $row): bool
    {
        return false;
    }

    /**
     * Find driver by email.
     */
    protected function findDriver(array $row): ?UserDriverDetail
    {
        if (empty($row['driver_email'])) {
            return null;
        }

        $user = User::where('email', trim($row['driver_email']))->first();

        if (!$user) {
            return null;
        }

        return UserDriverDetail::where('user_id', $user->id)
            ->where('carrier_id', $this->carrierId)
            ->first();
    }

    /**
     * Find vehicle by unit_number.
     */
    protected function findVehicle(array $row): ?Vehicle
    {
        if (empty($row['vehicle_unit_number'])) {
            return null;
        }

        return Vehicle::where('carrier_id', $this->carrierId)
            ->where('company_unit_number', trim($row['vehicle_unit_number']))
            ->first();
    }

    /**
     * Find duplicate HOS entry for update.
     */
    protected function findDuplicateEntry(int $driverId, array $row): ?HosEntry
    {
        $date = $this->parseDate($row['date'] ?? $row['start_time'] ?? null);

        if (!$date) {
            return null;
        }

        $startTime = $this->parseDateTime($row['start_time'] ?? null);

        // Match by driver + date + status + start_time to allow multiple
        // entries with the same status on the same day (different time blocks)
        $query = HosEntry::where('user_driver_detail_id', $driverId)
            ->where('carrier_id', $this->carrierId)
            ->whereDate('date', $date);

        $status = $this->normalizeStatus($row['status'] ?? '');
        if (!empty($status)) {
            $query->where('status', $status);
        }

        if ($startTime) {
            $query->where('start_time', $startTime);
        }

        return $query->first();
    }

    /**
     * Update an existing HOS entry with new data.
     */
    protected function updateHosEntry(HosEntry $entry, array $row, int $vehicleId): void
    {
        $entry->update([
            'vehicle_id' => $vehicleId,
            'status' => $this->normalizeStatus($row['status'] ?? $entry->status),
            'start_time' => $this->parseDateTime($row['start_time'] ?? null) ?? $entry->start_time,
            'end_time' => $this->parseDateTime($row['end_time'] ?? null) ?? $entry->end_time,
            'formatted_address' => trim($row['location'] ?? '') ?: $entry->formatted_address,
            'location_available' => !empty($row['location']) || $entry->location_available,
            'is_manual_entry' => true,
            'manual_entry_reason' => 'Updated from CSV import',
        ]);
    }

    /**
     * Normalize status value.
     */
    protected function normalizeStatus(?string $value): string
    {
        if (empty($value)) {
            return HosEntry::STATUS_OFF_DUTY;
        }

        $value = strtolower(trim($value));

        $mapping = [
            'on_duty_driving' => HosEntry::STATUS_ON_DUTY_DRIVING,
            'on-duty-driving' => HosEntry::STATUS_ON_DUTY_DRIVING,
            'driving' => HosEntry::STATUS_ON_DUTY_DRIVING,
            'd' => HosEntry::STATUS_ON_DUTY_DRIVING,
            'on_duty_not_driving' => HosEntry::STATUS_ON_DUTY_NOT_DRIVING,
            'on-duty-not-driving' => HosEntry::STATUS_ON_DUTY_NOT_DRIVING,
            'on_duty' => HosEntry::STATUS_ON_DUTY_NOT_DRIVING,
            'on-duty' => HosEntry::STATUS_ON_DUTY_NOT_DRIVING,
            'od' => HosEntry::STATUS_ON_DUTY_NOT_DRIVING,
            'off_duty' => HosEntry::STATUS_OFF_DUTY,
            'off-duty' => HosEntry::STATUS_OFF_DUTY,
            'off' => HosEntry::STATUS_OFF_DUTY,
        ];

        return $mapping[$value] ?? HosEntry::STATUS_OFF_DUTY;
    }
}
