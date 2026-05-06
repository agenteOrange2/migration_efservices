<?php

namespace App\Imports;

use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Hos\HosEntry;
use App\Models\User;
use App\Models\UserDriverDetail;
use App\Services\Hos\HosCalculationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class HosEntriesImport extends BaseImport
{
    /**
     * Driver+date pairs that need their HosDailyLog totals recalculated after
     * this batch finishes. Reports (Daily Log PDF, Monthly Summary, FMCSA
     * Document Monthly) read from hos_daily_logs; without this recalculation
     * the imported entries are in the DB but every total stays at zero and
     * the PDFs come out blank — matching the user-reported bug.
     *
     * Keys are "driverId|Y-m-d" so we recalculate each day exactly once.
     */
    protected array $daysToRecalculate = [];

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

            // Resolve start_time / end_time, combining the `date` column when
            // the time cell is partial (time-only, Excel fraction, or missing).
            // This is the fix for "imports come without hours" — previously a
            // value like "2025-01-15" or "08:00" was silently parsed as
            // 00:00:00 or wrong date.
            $startTimeStr = $this->resolveDateTime(
                $rowData['date'] ?? null,
                $rowData['start_time'] ?? null
            );

            if (!$startTimeStr) {
                $this->addSkippedRow(
                    $rowNumber,
                    'Invalid or missing start_time. Provide a full datetime (e.g. "2025-01-15 08:00") or a "date" column plus "start_time" as "HH:MM".',
                    $rowData
                );
                continue;
            }

            $endTimeStr = !empty($rowData['end_time'])
                ? $this->resolveDateTime($rowData['date'] ?? null, $rowData['end_time'], $startTimeStr)
                : null;

            // end_time is allowed to be empty (open entry); only error if it
            // was provided but unparseable, since silently dropping it loses
            // user data.
            if (!empty($rowData['end_time']) && !$endTimeStr) {
                $this->addSkippedRow(
                    $rowNumber,
                    'Invalid end_time. Provide a full datetime or "HH:MM" together with the "date" column.',
                    $rowData
                );
                continue;
            }

            // Check for duplicate
            $existingEntry = $this->findDuplicateEntry($driver->id, $rowData, $startTimeStr);

            if ($existingEntry) {
                if ($this->shouldUpdateDuplicates()) {
                    // Update existing record
                    try {
                        $this->updateHosEntry($existingEntry, $rowData, $vehicle->id, $startTimeStr, $endTimeStr);
                        $this->addUpdatedRow($existingEntry->id);
                        $this->markDayForRecalc($driver->id, $startTimeStr, $endTimeStr);

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
                // Derive the date column from start_time so the stored row is
                // self-consistent (date column always matches start_time's day).
                $dateOnly = Carbon::parse($startTimeStr)->format('Y-m-d');

                $entry = HosEntry::create([
                    'user_driver_detail_id' => $driver->id,
                    'carrier_id' => $this->carrierId,
                    'vehicle_id' => $vehicle->id,
                    'status' => $this->normalizeStatus($rowData['status'] ?? 'off_duty'),
                    'start_time' => $startTimeStr,
                    'end_time' => $endTimeStr,
                    'date' => $dateOnly,
                    'formatted_address' => trim($rowData['location'] ?? '') ?: null,
                    'location_available' => !empty($rowData['location']),
                    'is_manual_entry' => true,
                    'manual_entry_reason' => 'Imported from CSV',
                    'created_by' => $this->importedBy,
                ]);

                $this->addImportedRow($entry->id);
                $this->markDayForRecalc($driver->id, $startTimeStr, $endTimeStr);

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

        // After every row is in the DB, recalculate the affected daily logs
        // so the totals (driving / on_duty / off_duty minutes) reflect the
        // imported entries. Without this step the HOS Document PDFs and
        // monthly summaries would render blank rows for these dates.
        $this->recalculateAffectedDailyLogs();
    }

    /**
     * Track the driver+date(s) that an imported/updated entry touches.
     * If the entry crosses midnight, both days need to be recalculated.
     */
    protected function markDayForRecalc(int $driverId, string $startTimeStr, ?string $endTimeStr): void
    {
        $start = Carbon::parse($startTimeStr);
        $this->daysToRecalculate[$driverId . '|' . $start->format('Y-m-d')] = [
            'driver_id' => $driverId,
            'date' => $start->format('Y-m-d'),
        ];

        if ($endTimeStr) {
            $end = Carbon::parse($endTimeStr);
            if ($end->format('Y-m-d') !== $start->format('Y-m-d')) {
                $this->daysToRecalculate[$driverId . '|' . $end->format('Y-m-d')] = [
                    'driver_id' => $driverId,
                    'date' => $end->format('Y-m-d'),
                ];
            }
        }
    }

    /**
     * Run HosCalculationService::recalculateDailyLog for every (driver, date)
     * pair we touched. Failures are logged but do not abort the import — the
     * raw entries are already saved and a manual recalc can be triggered
     * later if needed.
     */
    protected function recalculateAffectedDailyLogs(): void
    {
        if (empty($this->daysToRecalculate)) {
            return;
        }

        $service = app(HosCalculationService::class);

        foreach ($this->daysToRecalculate as $pair) {
            try {
                $service->recalculateDailyLog($pair['driver_id'], Carbon::parse($pair['date']));
            } catch (\Exception $e) {
                Log::warning('Failed to recalculate HOS daily log after import', [
                    'driver_id' => $pair['driver_id'],
                    'date'      => $pair['date'],
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        Log::info('HOS daily logs recalculated after import', [
            'days_recalculated' => count($this->daysToRecalculate),
        ]);
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
     * Find vehicle by unit_number or VIN, tolerant of formatting differences
     * users introduce in spreadsheets (leading zeros, surrounding whitespace,
     * mixed case, decimals from Excel exporting "125" as 125.0, etc.).
     *
     * Order:
     *   1. Exact match on company_unit_number
     *   2. Trimmed/normalized match on company_unit_number
     *   3. Match on VIN if the cell looks like a VIN
     *
     * Without this fallback the legacy CSVs (which use just "125") were being
     * skipped because the DB stored values like "125 " or "0125" that didn't
     * pass a strict equality check.
     */
    protected function findVehicle(array $row): ?Vehicle
    {
        $raw = $row['vehicle_unit_number'] ?? $row['unit_number'] ?? null;

        if ($raw === null || $raw === '') {
            return null;
        }

        // Excel often turns "125" into the numeric 125.0; strip the trailing
        // ".0" and any surrounding whitespace before searching.
        $needle = trim((string) $raw);
        if (preg_match('/^(\d+)\.0+$/', $needle, $m)) {
            $needle = $m[1];
        }

        if ($needle === '') {
            return null;
        }

        // 1. Exact match
        $vehicle = Vehicle::where('carrier_id', $this->carrierId)
            ->where('company_unit_number', $needle)
            ->first();
        if ($vehicle) {
            return $vehicle;
        }

        // 2. Case-insensitive / trimmed match — handle stored values like
        //    "125 ", "  125", "0125" vs the CSV's "125".
        $vehicle = Vehicle::where('carrier_id', $this->carrierId)
            ->whereRaw('TRIM(company_unit_number) = ?', [$needle])
            ->first();
        if ($vehicle) {
            return $vehicle;
        }

        // Strip leading zeros for things like "0125" → "125" and vice versa.
        $stripped = ltrim($needle, '0');
        if ($stripped !== '' && $stripped !== $needle) {
            $vehicle = Vehicle::where('carrier_id', $this->carrierId)
                ->where(function ($q) use ($needle, $stripped) {
                    $q->where('company_unit_number', $stripped)
                      ->orWhere('company_unit_number', $needle);
                })
                ->first();
            if ($vehicle) {
                return $vehicle;
            }
        }

        // 3. Try VIN as a last resort if the cell smells like one.
        if (preg_match('/^[A-HJ-NPR-Z0-9]{17}$/i', $needle)) {
            return Vehicle::where('carrier_id', $this->carrierId)
                ->where('vin', strtoupper($needle))
                ->first();
        }

        return null;
    }

    /**
     * Find duplicate HOS entry for update. Caller passes the already-resolved
     * start_time so we don't re-parse and risk a different value than the one
     * about to be written.
     */
    protected function findDuplicateEntry(int $driverId, array $row, ?string $startTimeStr = null): ?HosEntry
    {
        $startTime = $startTimeStr ?? $this->resolveDateTime($row['date'] ?? null, $row['start_time'] ?? null);

        if (!$startTime) {
            return null;
        }

        $date = Carbon::parse($startTime)->format('Y-m-d');

        // Match by driver + date + status + start_time to allow multiple
        // entries with the same status on the same day (different time blocks)
        $query = HosEntry::where('user_driver_detail_id', $driverId)
            ->where('carrier_id', $this->carrierId)
            ->whereDate('date', $date);

        $status = $this->normalizeStatus($row['status'] ?? '');
        if (!empty($status)) {
            $query->where('status', $status);
        }

        $query->where('start_time', $startTime);

        return $query->first();
    }

    /**
     * Update an existing HOS entry with new data.
     */
    protected function updateHosEntry(
        HosEntry $entry,
        array $row,
        int $vehicleId,
        ?string $startTimeStr = null,
        ?string $endTimeStr = null
    ): void {
        $entry->update([
            'vehicle_id' => $vehicleId,
            'status' => $this->normalizeStatus($row['status'] ?? $entry->status),
            'start_time' => $startTimeStr ?? $entry->start_time,
            'end_time' => $endTimeStr ?? $entry->end_time,
            'formatted_address' => trim($row['location'] ?? '') ?: $entry->formatted_address,
            'location_available' => !empty($row['location']) || $entry->location_available,
            'is_manual_entry' => true,
            'manual_entry_reason' => 'Updated from CSV import',
        ]);
    }

    /**
     * Resolve a HOS time cell to a full datetime string, combining the row's
     * `date` column when the time cell is partial.
     *
     * Accepted shapes for $timeValue:
     *   - Full datetime (`2025-01-15 08:00`, `2025-01-15T08:00:00Z`, etc.)
     *   - Time-only (`08:00`, `8:00 AM`, `13:30:00`)
     *   - Excel time fraction (numeric < 1, e.g. 0.333... = 8:00 AM)
     *   - Excel datetime serial (numeric >= 1)
     *   - Empty / null → returns null
     *
     * For partial time-only inputs we anchor the result to $dateValue (or, as
     * a last resort, $fallbackDateTimeStr).
     */
    protected function resolveDateTime($dateValue, $timeValue, ?string $fallbackDateTimeStr = null): ?string
    {
        if ($timeValue === null || $timeValue === '') {
            return null;
        }

        // Excel fraction: anything in [0, 1) is purely a time component.
        if (is_numeric($timeValue) && (float) $timeValue < 1 && (float) $timeValue >= 0) {
            $seconds = (int) round(((float) $timeValue) * 86400);
            $h = intdiv($seconds, 3600);
            $m = intdiv($seconds % 3600, 60);
            $s = $seconds % 60;
            $time = sprintf('%02d:%02d:%02d', $h, $m, $s);
            return $this->anchorTimeToDate($dateValue, $time, $fallbackDateTimeStr);
        }

        // Numeric Excel datetime serial (>= 1): trust parseDateTime.
        if (is_numeric($timeValue)) {
            return $this->parseDateTime($timeValue);
        }

        $raw = trim((string) $timeValue);
        if ($raw === '') {
            return null;
        }

        // Time-only input → anchor with the row's date column.
        if ($this->looksLikeTimeOnly($raw)) {
            try {
                $time = Carbon::parse($raw)->format('H:i:s');
            } catch (\Exception $e) {
                return null;
            }
            return $this->anchorTimeToDate($dateValue, $time, $fallbackDateTimeStr);
        }

        // Otherwise expect a full datetime; let the base parser handle it.
        $parsed = $this->parseDateTime($raw);
        if (!$parsed) {
            return null;
        }

        // Guard against the Carbon::parse fallback turning a date-only string
        // into "YYYY-MM-DD 00:00:00". If the input had no time digits at all,
        // we treat it as missing rather than silently storing midnight.
        if (!preg_match('/\d:\d/', $raw)) {
            return null;
        }

        return $parsed;
    }

    /**
     * Combine a date cell with a HH:MM[:SS] string into a full datetime.
     */
    protected function anchorTimeToDate($dateValue, string $time, ?string $fallbackDateTimeStr = null): ?string
    {
        $date = $this->parseDate($dateValue);

        if (!$date && $fallbackDateTimeStr) {
            try {
                $date = Carbon::parse($fallbackDateTimeStr)->format('Y-m-d');
            } catch (\Exception $e) {
                $date = null;
            }
        }

        if (!$date) {
            return null;
        }

        // Ensure HH:MM:SS shape
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            $time .= ':00';
        }

        try {
            return Carbon::parse($date . ' ' . $time)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Heuristic: does this string look like a time-only value (no date)?
     */
    protected function looksLikeTimeOnly(string $value): bool
    {
        // Examples: "08:00", "8:00", "13:45", "08:00:00", "8:00 AM", "1:30 pm"
        return (bool) preg_match('/^\d{1,2}:\d{2}(:\d{2})?(\s*(am|pm|AM|PM))?$/', $value);
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
