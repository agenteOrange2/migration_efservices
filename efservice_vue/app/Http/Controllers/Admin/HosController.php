<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminScope;
use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\Hos\HosDailyLog;
use App\Models\Hos\HosEntry;
use App\Models\Hos\HosViolation;
use App\Models\UserDriverDetail;
use App\Services\Hos\HosCalculationService;
use App\Services\Hos\HosConfigurationService;
use App\Services\Hos\HosPdfService;
use App\Services\Hos\HosService;
use App\Services\Hos\HosViolationForgivenessService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class HosController extends Controller
{
    use InteractsWithAdminScope;

    public function __construct(
        protected HosService $hosService,
        protected HosCalculationService $calculationService,
        protected HosConfigurationService $configurationService,
        protected HosViolationForgivenessService $forgivenessService,
        protected HosPdfService $pdfService,
    ) {
    }

    public function index(): InertiaResponse
    {
        $scope = $this->scopeContext();
        $today = now()->startOfDay();

        $carriers = Carrier::query();
        $this->applyCarrierScope($carriers, $scope);

        $carrierSummaries = $carriers->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (Carrier $carrier) use ($today) {
                $activeDrivers = UserDriverDetail::query()
                    ->where('carrier_id', $carrier->id)
                    ->where('status', UserDriverDetail::STATUS_ACTIVE)
                    ->count();

                $todayViolations = HosViolation::query()
                    ->where('carrier_id', $carrier->id)
                    ->whereDate('violation_date', $today)
                    ->count();

                $monthViolations = HosViolation::query()
                    ->where('carrier_id', $carrier->id)
                    ->whereYear('violation_date', $today->year)
                    ->whereMonth('violation_date', $today->month)
                    ->count();

                $config = $this->configurationService->getConfiguration($carrier->id);

                return [
                    'id' => $carrier->id,
                    'name' => $carrier->name,
                    'active_drivers' => $activeDrivers,
                    'today_violations' => $todayViolations,
                    'month_violations' => $monthViolations,
                    'max_driving_hours' => (float) $config->max_driving_hours,
                    'max_duty_hours' => (float) $config->max_duty_hours,
                    'warning_threshold_minutes' => (int) $config->warning_threshold_minutes,
                ];
            })
            ->values();

        return Inertia::render('admin/hos/Dashboard', [
            'stats' => [
                'carrier_count' => $carrierSummaries->count(),
                'active_drivers' => $carrierSummaries->sum('active_drivers'),
                'today_violations' => $carrierSummaries->sum('today_violations'),
                'month_violations' => $carrierSummaries->sum('month_violations'),
            ],
            'carrierSummaries' => $carrierSummaries,
            'isSuperadmin' => $scope['is_superadmin'],
        ]);
    }

    public function carrierDetail(int $carrier): InertiaResponse
    {
        $scope = $this->scopeContext();
        $carrierModel = Carrier::query()->findOrFail($carrier);
        $this->ensureAllowedCarrier((int) $carrierModel->id, $scope);

        $today = now()->startOfDay();
        $drivers = UserDriverDetail::query()
            ->with(['user:id,name,email'])
            ->where('carrier_id', $carrierModel->id)
            ->where('status', UserDriverDetail::STATUS_ACTIVE)
            ->orderBy('last_name')
            ->get();

        $driverSummaries = $drivers->map(function (UserDriverDetail $driver) use ($today) {
            $currentEntry = $this->hosService->getDriverCurrentStatus($driver->id);
            $totals = $this->calculationService->calculateDailyTotals($driver->id, $today);
            $remaining = $this->calculationService->calculateRemainingHours($driver->id, $today);
            $todayViolations = HosViolation::query()
                ->where('user_driver_detail_id', $driver->id)
                ->whereDate('violation_date', $today)
                ->count();

            return [
                'id' => $driver->id,
                'name' => $driver->full_name ?: ($driver->user?->name ?: 'Unknown Driver'),
                'email' => $driver->user?->email,
                'cycle_type' => $driver->getEffectiveHosCycleType(),
                'current_status' => $currentEntry?->status_name ?? 'No active status',
                'driving_today' => $totals['driving_formatted'],
                'on_duty_today' => $totals['on_duty_formatted'],
                'off_duty_today' => $totals['off_duty_formatted'],
                'remaining_driving' => $remaining['remaining_driving_formatted'],
                'remaining_duty' => $remaining['remaining_duty_formatted'],
                'today_violations' => $todayViolations,
            ];
        })->values();

        $config = $this->configurationService->getConfiguration($carrierModel->id);

        return Inertia::render('admin/hos/CarrierDetail', [
            'carrier' => [
                'id' => $carrierModel->id,
                'name' => $carrierModel->name,
            ],
            'stats' => [
                'active_drivers' => $driverSummaries->count(),
                'drivers_with_violations' => $driverSummaries->where('today_violations', '>', 0)->count(),
                'drivers_driving_now' => $driverSummaries->where('current_status', 'On Duty - Driving')->count(),
            ],
            'config' => [
                'max_driving_hours' => (float) $config->max_driving_hours,
                'max_duty_hours' => (float) $config->max_duty_hours,
                'warning_threshold_minutes' => (int) $config->warning_threshold_minutes,
                'weekly_limit_60_minutes' => (int) ($config->weekly_limit_60_minutes ?? 3600),
                'weekly_limit_70_minutes' => (int) ($config->weekly_limit_70_minutes ?? 4200),
                'require_30_min_break' => (bool) $config->require_30_min_break,
                'break_after_hours' => (int) ($config->break_after_hours ?? 8),
            ],
            'driverSummaries' => $driverSummaries,
        ]);
    }

    public function driverLog(Request $request, int $driver): InertiaResponse
    {
        $scope = $this->scopeContext();
        $driverModel = UserDriverDetail::query()
            ->with(['user:id,name,email', 'carrier:id,name'])
            ->findOrFail($driver);
        $this->ensureAllowedCarrier((int) $driverModel->carrier_id, $scope);

        $startDate = $this->parseUsDate((string) $request->input('start_date'), now()->subDays(7))?->startOfDay() ?? now()->subDays(7)->startOfDay();
        $endDate = $this->parseUsDate((string) $request->input('end_date'), now())?->endOfDay() ?? now()->endOfDay();

        if ($endDate->lt($startDate)) {
            $endDate = $startDate->copy()->endOfDay();
        }

        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $this->calculationService->recalculateDailyLog($driverModel->id, $current);
            $current->addDay();
        }

        $entries = $this->hosService->getDriverEntriesForDateRange($driverModel->id, $startDate, $endDate)
            ->loadMissing(['vehicle:id,company_unit_number,year,make,model', 'trip:id,trip_number', 'creator:id,name'])
            ->map(fn (HosEntry $entry) => [
                'id' => $entry->id,
                'status' => $entry->status,
                'status_label' => $entry->status_name,
                'date' => $entry->date?->format('M j'),
                'start_time' => $entry->start_time?->format('n/j/Y g:i A'),
                'start_time_short' => $entry->start_time?->format('H:i'),
                'end_time' => $entry->end_time?->format('n/j/Y g:i A'),
                'end_time_short' => $entry->end_time?->format('H:i'),
                'edit_start_time' => $entry->start_time?->format('Y-m-d\TH:i'),
                'edit_end_time' => $entry->end_time?->format('Y-m-d\TH:i'),
                'duration' => $entry->formatted_duration,
                'location' => $entry->location_display,
                'formatted_address' => $entry->formatted_address,
                'latitude' => $entry->latitude ? (float) $entry->latitude : null,
                'longitude' => $entry->longitude ? (float) $entry->longitude : null,
                'maps_url' => $entry->latitude && $entry->longitude
                    ? "https://www.google.com/maps?q={$entry->latitude},{$entry->longitude}"
                    : ($entry->formatted_address
                        ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($entry->formatted_address)
                        : null),
                'is_manual_entry' => (bool) $entry->is_manual_entry,
                'manual_entry_reason' => $entry->manual_entry_reason,
                'is_ghost_log' => (bool) $entry->is_ghost_log,
                'ghost_log_reason' => $entry->ghost_log_reason,
                'trip_number' => $entry->trip?->trip_number,
                'vehicle_label' => $entry->vehicle
                    ? trim(collect([
                        $entry->vehicle->company_unit_number ? 'Unit #' . $entry->vehicle->company_unit_number : null,
                        trim(($entry->vehicle->year ?: '') . ' ' . ($entry->vehicle->make ?: '') . ' ' . ($entry->vehicle->model ?: '')),
                    ])->filter()->implode(' - '))
                    : null,
                'created_by_name' => $entry->creator?->name,
            ])
            ->values();

        $dailyLogs = HosDailyLog::query()
            ->where('user_driver_detail_id', $driverModel->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderByDesc('date')
            ->get()
            ->map(fn (HosDailyLog $log) => [
                'id' => $log->id,
                'date' => $log->date?->format('n/j/Y'),
                'driving_time' => $log->formatted_driving_time,
                'on_duty_time' => $log->formatted_on_duty_time,
                'off_duty_time' => $log->formatted_off_duty_time,
                'has_violations' => (bool) $log->has_violations,
                'signed_at' => $log->signed_at?->format('n/j/Y g:i A'),
                'duty_period_start' => $log->duty_period_start?->format('n/j/Y g:i A'),
                'duty_period_end' => $log->duty_period_end?->format('n/j/Y g:i A'),
            ])
            ->values();

        $violations = HosViolation::query()
            ->with(['trip:id,trip_number'])
            ->where('user_driver_detail_id', $driverModel->id)
            ->whereBetween('violation_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderByDesc('violation_date')
            ->get()
            ->map(fn (HosViolation $violation) => [
                'id' => $violation->id,
                'date' => $violation->violation_date?->format('n/j/Y'),
                'type' => $violation->violation_type_name,
                'severity' => $violation->severity_name,
                'hours_exceeded' => (float) $violation->hours_exceeded,
                'acknowledged' => (bool) $violation->acknowledged,
                'is_forgiven' => (bool) $violation->is_forgiven,
                'trip_number' => $violation->trip?->trip_number,
            ])
            ->values();

        $documents = collect()
            ->merge($driverModel->getMedia('trip_reports'))
            ->merge($driverModel->getMedia('inspection_reports'))
            ->merge($driverModel->getMedia('daily_logs'))
            ->merge($driverModel->getMedia('monthly_summaries'))
            ->sortByDesc('created_at')
            ->map(fn (Media $media) => [
                'id' => $media->id,
                'type' => $this->documentTypeLabel($media),
                'file_name' => $media->file_name,
                'size_label' => $this->formatBytes((int) $media->size),
                'document_date' => $this->documentDateForMedia($media)?->format('n/j/Y'),
                'created_at' => $media->created_at?->format('n/j/Y g:i A'),
                'preview_url' => route('admin.hos.documents.preview', $media),
                'download_url' => route('admin.hos.documents.download', $media),
            ])
            ->values();

        $currentStatus = $this->hosService->getDriverCurrentStatus($driverModel->id);

        return Inertia::render('admin/hos/DriverLog', [
            'driver' => [
                'id' => $driverModel->id,
                'name' => $driverModel->full_name ?: ($driverModel->user?->name ?: 'Unknown Driver'),
                'email' => $driverModel->user?->email,
                'carrier_name' => $driverModel->carrier?->name,
                'current_cycle' => $driverModel->getEffectiveHosCycleType(),
            ],
            'filters' => [
                'start_date' => $startDate->format('n/j/Y'),
                'end_date' => $endDate->format('n/j/Y'),
            ],
            'stats' => [
                'current_status' => $currentStatus?->status_name ?? 'No active status',
                'entries_count' => $entries->count(),
                'daily_logs_count' => $dailyLogs->count(),
                'violations_count' => $violations->count(),
                'documents_count' => $documents->count(),
            ],
            'statusOptions' => collect(HosEntry::STATUSES)->map(fn (string $status) => [
                'value' => $status,
                'label' => match ($status) {
                    HosEntry::STATUS_ON_DUTY_DRIVING => 'On Duty - Driving',
                    HosEntry::STATUS_ON_DUTY_NOT_DRIVING => 'On Duty - Not Driving',
                    HosEntry::STATUS_OFF_DUTY => 'Off Duty',
                    default => str($status)->replace('_', ' ')->title()->toString(),
                },
            ])->values(),
            'entries' => $entries,
            'dailyLogs' => $dailyLogs,
            'violations' => $violations,
            'documents' => $documents,
        ]);
    }

    public function updateEntry(Request $request, HosEntry $entry): RedirectResponse
    {
        $scope = $this->scopeContext();
        $this->ensureAllowedCarrier((int) $entry->carrier_id, $scope);

        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', HosEntry::STATUSES)],
            'start_time' => ['required', 'date'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
            'formatted_address' => ['nullable', 'string', 'max:255'],
            'manual_entry_reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->hosService->updateEntry(
                $entry,
                [
                    'status' => $validated['status'],
                    'start_time' => Carbon::parse($validated['start_time']),
                    'end_time' => ! empty($validated['end_time']) ? Carbon::parse($validated['end_time']) : null,
                    'formatted_address' => $validated['formatted_address'] ?? null,
                    'manual_entry_reason' => $validated['manual_entry_reason'] ?? null,
                ],
                (int) auth()->id(),
                'Admin updated entry via HOS log.'
            );
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'HOS entry updated successfully.');
    }

    public function deleteEntry(HosEntry $entry): RedirectResponse
    {
        $scope = $this->scopeContext();
        $this->ensureAllowedCarrier((int) $entry->carrier_id, $scope);

        $driverId = (int) $entry->user_driver_detail_id;
        $entryDate = $entry->date?->copy() ?? now();
        $entry->delete();
        $this->calculationService->recalculateDailyLog($driverId, $entryDate);

        return back()->with('success', 'HOS entry deleted successfully.');
    }

    public function bulkDeleteEntries(Request $request): RedirectResponse
    {
        $scope = $this->scopeContext();
        $validated = $request->validate([
            'entry_ids' => ['required', 'array', 'min:1'],
            'entry_ids.*' => ['integer', 'exists:hos_entries,id'],
        ]);

        $entries = HosEntry::query()->whereIn('id', $validated['entry_ids'])->get();
        foreach ($entries as $entry) {
            $this->ensureAllowedCarrier((int) $entry->carrier_id, $scope);
        }

        $recalculate = $entries->map(fn (HosEntry $entry) => [
            'driver_id' => (int) $entry->user_driver_detail_id,
            'date' => $entry->date?->copy() ?? now(),
        ]);

        HosEntry::query()->whereIn('id', $validated['entry_ids'])->delete();

        foreach ($recalculate as $item) {
            $this->calculationService->recalculateDailyLog($item['driver_id'], $item['date']);
        }

        return back()->with('success', count($validated['entry_ids']) . ' HOS entries deleted successfully.');
    }

    public function violations(Request $request): InertiaResponse
    {
        $scope = $this->scopeContext();
        $filters = [
            'carrier_id' => $scope['is_superadmin'] ? (string) $request->input('carrier_id', '') : (string) ($scope['carrier_id'] ?? ''),
            'driver_id' => (string) $request->input('driver_id', ''),
            'violation_type' => (string) $request->input('violation_type', ''),
            'severity' => (string) $request->input('severity', ''),
            'date_from' => (string) $request->input('date_from', now()->subDays(30)->format('n/j/Y')),
            'date_to' => (string) $request->input('date_to', now()->format('n/j/Y')),
            'acknowledged' => (string) $request->input('acknowledged', ''),
        ];

        $query = HosViolation::query()->with(['driver.user:id,name,email', 'carrier:id,name', 'trip:id,trip_number']);

        if ($filters['carrier_id'] !== '') {
            $this->ensureAllowedCarrier((int) $filters['carrier_id'], $scope);
            $query->where('carrier_id', (int) $filters['carrier_id']);
        } elseif (! $scope['is_superadmin']) {
            $query->where('carrier_id', $scope['carrier_id'] ?: 0);
        }

        if ($filters['driver_id'] !== '') {
            $query->where('user_driver_detail_id', (int) $filters['driver_id']);
        }

        if ($filters['violation_type'] !== '') {
            $query->where('violation_type', $filters['violation_type']);
        }

        if ($filters['severity'] !== '') {
            $query->where('violation_severity', $filters['severity']);
        }

        if ($filters['acknowledged'] === 'yes') {
            $query->where('acknowledged', true);
        } elseif ($filters['acknowledged'] === 'no') {
            $query->where('acknowledged', false);
        }

        if ($from = $this->parseUsDate($filters['date_from'])) {
            $query->whereDate('violation_date', '>=', $from->toDateString());
        }
        if ($to = $this->parseUsDate($filters['date_to'])) {
            $query->whereDate('violation_date', '<=', $to->toDateString());
        }

        $violations = $query->orderByDesc('violation_date')->paginate(20)->withQueryString();
        $violations->through(fn (HosViolation $violation) => [
            'id' => $violation->id,
            'driver_name' => $violation->driver?->full_name ?: ($violation->driver?->user?->name ?: 'Unknown Driver'),
            'carrier_name' => $violation->carrier?->name,
            'trip_number' => $violation->trip?->trip_number,
            'violation_type' => $violation->violation_type_name,
            'severity' => $violation->severity_name,
            'date' => $violation->violation_date?->format('n/j/Y'),
            'hours_exceeded' => (float) $violation->hours_exceeded,
            'acknowledged' => (bool) $violation->acknowledged,
            'is_forgiven' => (bool) $violation->is_forgiven,
            'has_penalty' => (bool) $violation->has_penalty,
        ]);

        $statsBase = clone $query;

        return Inertia::render('admin/hos/Violations', [
            'filters' => $filters,
            'violations' => $violations,
            'stats' => [
                'total' => (clone $statsBase)->count(),
                'acknowledged' => (clone $statsBase)->where('acknowledged', true)->count(),
                'unacknowledged' => (clone $statsBase)->where('acknowledged', false)->count(),
                'forgiven' => (clone $statsBase)->where('is_forgiven', true)->count(),
            ],
            'carriers' => $this->carrierOptions($scope),
            'drivers' => $this->driverOptions($scope, $filters['carrier_id'], false),
            'violationTypes' => collect(HosViolation::VIOLATION_TYPES)->map(fn (string $type) => [
                'value' => $type,
                'label' => str($type)->replace('_', ' ')->title()->toString(),
            ])->values(),
            'severities' => collect(HosViolation::SEVERITIES)->map(fn (string $severity) => [
                'value' => $severity,
                'label' => str($severity)->title()->toString(),
            ])->values(),
            'canFilterCarriers' => $scope['is_superadmin'],
        ]);
    }

    public function violationShow(HosViolation $violation): InertiaResponse
    {
        $scope = $this->scopeContext();
        $violation->load([
            'driver.user:id,name,email',
            'carrier:id,name',
            'vehicle:id,company_unit_number,year,make,model',
            'trip:id,trip_number,actual_start_time,actual_end_time',
            'acknowledgedByUser:id,name',
            'forgivenByUser:id,name',
        ]);
        $this->ensureAllowedCarrier((int) $violation->carrier_id, $scope);

        return Inertia::render('admin/hos/ViolationShow', [
            'violation' => [
                'id' => $violation->id,
                'driver_name' => $violation->driver?->full_name ?: ($violation->driver?->user?->name ?: 'Unknown Driver'),
                'driver_email' => $violation->driver?->user?->email,
                'carrier_name' => $violation->carrier?->name,
                'vehicle_label' => $violation->vehicle
                    ? trim(collect([
                        $violation->vehicle->company_unit_number ? 'Unit #' . $violation->vehicle->company_unit_number : null,
                        trim(($violation->vehicle->year ?: '') . ' ' . ($violation->vehicle->make ?: '') . ' ' . ($violation->vehicle->model ?: '')),
                    ])->filter()->implode(' - '))
                    : 'N/A',
                'trip_number' => $violation->trip?->trip_number,
                'trip_actual_start' => $violation->trip?->actual_start_time?->format('n/j/Y g:i A'),
                'trip_actual_end' => $violation->trip?->actual_end_time?->format('n/j/Y g:i A'),
                'type' => $violation->violation_type_name,
                'severity' => $violation->severity_name,
                'date' => $violation->violation_date?->format('n/j/Y'),
                'hours_exceeded' => (float) $violation->hours_exceeded,
                'formatted_hours_exceeded' => $violation->formatted_hours_exceeded,
                'fmcsa_rule_reference' => $violation->fmcsa_rule_reference,
                'acknowledged' => (bool) $violation->acknowledged,
                'acknowledged_at' => $violation->acknowledged_at?->format('n/j/Y g:i A'),
                'acknowledged_by' => $violation->acknowledgedByUser?->name,
                'has_penalty' => (bool) $violation->has_penalty,
                'penalty_type' => $violation->penalty_type,
                'penalty_start' => $violation->penalty_start?->format('n/j/Y g:i A'),
                'penalty_end' => $violation->penalty_end?->format('n/j/Y g:i A'),
                'penalty_notes' => $violation->penalty_notes,
                'is_forgiven' => (bool) $violation->is_forgiven,
                'forgiven_at' => $violation->forgiven_at?->format('n/j/Y g:i A'),
                'forgiven_by' => $violation->forgivenByUser?->name,
                'forgiveness_reason' => $violation->forgiveness_reason,
                'adjusted_trip_end_time' => $violation->adjusted_trip_end_time?->format('n/j/Y g:i A'),
                'original_trip_end_time' => $violation->original_trip_end_time?->format('n/j/Y g:i A'),
                'can_acknowledge' => ! $violation->acknowledged,
                'can_forgive' => ! $violation->is_forgiven,
            ],
        ]);
    }

    public function violationAcknowledge(HosViolation $violation): RedirectResponse
    {
        $scope = $this->scopeContext();
        $this->ensureAllowedCarrier((int) $violation->carrier_id, $scope);
        $violation->acknowledge((int) auth()->id());

        return back()->with('success', 'Violation acknowledged successfully.');
    }

    public function violationForgive(Request $request, HosViolation $violation): RedirectResponse
    {
        $scope = $this->scopeContext();
        $this->ensureAllowedCarrier((int) $violation->carrier_id, $scope);

        $validated = $request->validate([
            'forgiveness_reason' => ['required', 'string', 'min:10'],
            'adjusted_end_time' => ['nullable', 'date'],
        ]);

        try {
            $this->forgivenessService->forgiveViolation(
                $violation,
                (int) auth()->id(),
                $validated['forgiveness_reason'],
                ! empty($validated['adjusted_end_time']) ? Carbon::parse($validated['adjusted_end_time']) : null,
            );

            if (method_exists($this->pdfService, 'generateViolationReport')) {
                $this->pdfService->generateViolationReport($violation->fresh());
            }
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Violation forgiven successfully.');
    }

    protected function documentDateForMedia(Media $media): ?Carbon
    {
        $documentDate = $media->getCustomProperty('document_date');
        if ($documentDate) {
            return Carbon::parse($documentDate);
        }

        $yearMonth = $media->getCustomProperty('year_month');
        if ($yearMonth) {
            return Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
        }

        return $media->created_at?->copy();
    }

    protected function documentTypeLabel(Media $media): string
    {
        if ($media->getCustomProperty('document_type') === 'fmcsa_monthly') {
            return 'FMCSA Monthly';
        }

        return match ($media->collection_name) {
            'trip_reports' => 'Trip Report',
            'inspection_reports' => 'Inspection Report',
            'daily_logs' => 'Daily Log',
            'monthly_summaries' => 'Monthly Summary',
            default => str($media->collection_name)->replace('_', ' ')->title()->toString(),
        };
    }
}
