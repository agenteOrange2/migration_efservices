<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Admin\HosController as AdminHosController;
use App\Http\Controllers\Carrier\Concerns\ResolvesCarrierContext;
use App\Models\Hos\HosDailyLog;
use App\Models\Hos\HosEntry;
use App\Models\Hos\HosViolation;
use App\Models\UserDriverDetail;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CarrierHosController extends AdminHosController
{
    use ResolvesCarrierContext;

    public function dashboard(): InertiaResponse
    {
        $carrier = $this->resolveCarrier();
        $today = now()->startOfDay();

        $drivers = UserDriverDetail::query()
            ->with(['user:id,name,email'])
            ->where('carrier_id', $carrier->id)
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

        $config = $this->configurationService->getConfiguration((int) $carrier->id);

        return Inertia::render('carrier/hos/Dashboard', [
            'carrier' => [
                'id' => $carrier->id,
                'name' => $carrier->name,
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
                'violation_threshold_minutes' => (int) ($config->violation_threshold_minutes ?? 0),
                'weekly_limit_60_minutes' => (int) ($config->weekly_limit_60_minutes ?? 3600),
                'weekly_limit_70_minutes' => (int) ($config->weekly_limit_70_minutes ?? 4200),
                'require_30_min_break' => (bool) $config->require_30_min_break,
                'break_after_hours' => (int) ($config->break_after_hours ?? 8),
                'fmcsa_texas_mode' => (bool) $config->fmcsa_texas_mode,
                'allow_24_hour_reset' => (bool) $config->allow_24_hour_reset,
                'enable_ghost_log_detection' => (bool) $config->enable_ghost_log_detection,
                'ghost_log_threshold_minutes' => (int) ($config->ghost_log_threshold_minutes ?? 30),
            ],
            'driverSummaries' => $driverSummaries,
        ]);
    }

    public function driverLog(Request $request, int $driver): InertiaResponse
    {
        $carrier = $this->resolveCarrier();
        $driverModel = UserDriverDetail::query()
            ->with(['user:id,name,email', 'carrier:id,name'])
            ->where('carrier_id', $carrier->id)
            ->findOrFail($driver);

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
                'start_time' => $entry->start_time?->format('n/j/Y g:i A'),
                'end_time' => $entry->end_time?->format('n/j/Y g:i A'),
                'edit_start_time' => $entry->start_time?->format('Y-m-d\TH:i'),
                'edit_end_time' => $entry->end_time?->format('Y-m-d\TH:i'),
                'duration' => $entry->formatted_duration,
                'location' => $entry->location_display,
                'formatted_address' => $entry->formatted_address,
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
                'preview_url' => route('carrier.hos.documents.preview', $media),
                'download_url' => route('carrier.hos.documents.download', $media),
            ])
            ->values();

        $currentStatus = $this->hosService->getDriverCurrentStatus($driverModel->id);

        return Inertia::render('carrier/hos/DriverLog', [
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

    public function violations(Request $request): InertiaResponse
    {
        $carrier = $this->resolveCarrier();
        $filters = [
            'carrier_id' => (string) $carrier->id,
            'driver_id' => (string) $request->input('driver_id', ''),
            'violation_type' => (string) $request->input('violation_type', ''),
            'severity' => (string) $request->input('severity', ''),
            'date_from' => (string) $request->input('date_from', now()->subDays(30)->format('n/j/Y')),
            'date_to' => (string) $request->input('date_to', now()->format('n/j/Y')),
            'acknowledged' => (string) $request->input('acknowledged', ''),
        ];

        $query = HosViolation::query()
            ->with(['driver.user:id,name,email', 'carrier:id,name', 'trip:id,trip_number'])
            ->where('carrier_id', $carrier->id);

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

        return Inertia::render('carrier/hos/Violations', [
            'filters' => $filters,
            'violations' => $violations,
            'stats' => [
                'total' => (clone $statsBase)->count(),
                'acknowledged' => (clone $statsBase)->where('acknowledged', true)->count(),
                'unacknowledged' => (clone $statsBase)->where('acknowledged', false)->count(),
                'forgiven' => (clone $statsBase)->where('is_forgiven', true)->count(),
            ],
            'carriers' => [[
                'id' => $carrier->id,
                'name' => $carrier->name,
            ]],
            'drivers' => $this->driverOptions([
                'is_superadmin' => false,
                'carrier_id' => (int) $carrier->id,
            ], (string) $carrier->id, false),
            'violationTypes' => collect(HosViolation::VIOLATION_TYPES)->map(fn (string $type) => [
                'value' => $type,
                'label' => str($type)->replace('_', ' ')->title()->toString(),
            ])->values(),
            'severities' => collect(HosViolation::SEVERITIES)->map(fn (string $severity) => [
                'value' => $severity,
                'label' => str($severity)->title()->toString(),
            ])->values(),
            'canFilterCarriers' => false,
        ]);
    }

    public function violationShow(HosViolation $violation): InertiaResponse
    {
        $carrier = $this->resolveCarrier();
        abort_unless((int) $violation->carrier_id === (int) $carrier->id, 403);

        $violation->load([
            'driver.user:id,name,email',
            'carrier:id,name',
            'vehicle:id,company_unit_number,year,make,model',
            'trip:id,trip_number,actual_start_time,actual_end_time',
            'acknowledgedByUser:id,name',
            'forgivenByUser:id,name',
        ]);

        return Inertia::render('carrier/hos/ViolationShow', [
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

    public function configuration(): InertiaResponse
    {
        $carrier = $this->resolveCarrier();
        $config = $this->configurationService->getConfiguration((int) $carrier->id);
        $defaults = $this->configurationService->getDefaults();

        return Inertia::render('carrier/hos/Configuration', [
            'carrier' => [
                'id' => $carrier->id,
                'name' => $carrier->name,
            ],
            'config' => [
                'max_driving_hours' => (float) $config->max_driving_hours,
                'max_duty_hours' => (float) $config->max_duty_hours,
                'warning_threshold_minutes' => (int) $config->warning_threshold_minutes,
                'violation_threshold_minutes' => (int) ($config->violation_threshold_minutes ?? 0),
                'fmcsa_texas_mode' => (bool) $config->fmcsa_texas_mode,
                'allow_24_hour_reset' => (bool) $config->allow_24_hour_reset,
                'require_30_min_break' => (bool) $config->require_30_min_break,
                'break_after_hours' => (int) ($config->break_after_hours ?? 8),
                'weekly_limit_60_hours' => (int) round(($config->weekly_limit_60_minutes ?? 3600) / 60),
                'weekly_limit_70_hours' => (int) round(($config->weekly_limit_70_minutes ?? 4200) / 60),
                'enable_ghost_log_detection' => (bool) $config->enable_ghost_log_detection,
                'ghost_log_threshold_minutes' => (int) ($config->ghost_log_threshold_minutes ?? 30),
                'is_active' => (bool) $config->is_active,
            ],
            'defaults' => [
                'max_driving_hours' => (float) ($defaults['max_driving_hours'] ?? 12),
                'max_duty_hours' => (float) ($defaults['max_duty_hours'] ?? 14),
                'warning_threshold_minutes' => (int) ($defaults['warning_threshold_minutes'] ?? 60),
                'violation_threshold_minutes' => (int) ($defaults['violation_threshold_minutes'] ?? 0),
                'weekly_limit_60_hours' => (int) round(($defaults['weekly_limit_60_minutes'] ?? 3600) / 60),
                'weekly_limit_70_hours' => (int) round(($defaults['weekly_limit_70_minutes'] ?? 4200) / 60),
                'break_after_hours' => (int) ($defaults['break_after_hours'] ?? 8),
                'ghost_log_threshold_minutes' => (int) ($defaults['ghost_log_threshold_minutes'] ?? 30),
            ],
        ]);
    }

    public function updateConfiguration(Request $request): RedirectResponse
    {
        $carrier = $this->resolveCarrier();
        $validated = $request->validate([
            'max_driving_hours' => ['required', 'numeric', 'min:1', 'max:24'],
            'max_duty_hours' => ['required', 'numeric', 'min:1', 'max:24'],
            'warning_threshold_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'violation_threshold_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'break_after_hours' => ['required', 'integer', 'min:1', 'max:24'],
            'weekly_limit_60_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'weekly_limit_70_hours' => ['required', 'integer', 'min:1', 'max:192'],
            'ghost_log_threshold_minutes' => ['required', 'integer', 'min:5', 'max:240'],
        ]);

        if ((float) $validated['max_driving_hours'] > (float) $validated['max_duty_hours']) {
            return back()->withErrors([
                'max_driving_hours' => 'Driving hours cannot exceed duty hours.',
            ]);
        }

        $config = $this->configurationService->getConfiguration((int) $carrier->id);
        $config->update([
            'max_driving_hours' => (float) $validated['max_driving_hours'],
            'max_duty_hours' => (float) $validated['max_duty_hours'],
            'warning_threshold_minutes' => (int) $validated['warning_threshold_minutes'],
            'violation_threshold_minutes' => (int) $validated['violation_threshold_minutes'],
            'fmcsa_texas_mode' => $request->boolean('fmcsa_texas_mode'),
            'allow_24_hour_reset' => $request->boolean('allow_24_hour_reset'),
            'require_30_min_break' => $request->boolean('require_30_min_break'),
            'break_after_hours' => (int) $validated['break_after_hours'],
            'weekly_limit_60_minutes' => ((int) $validated['weekly_limit_60_hours']) * 60,
            'weekly_limit_70_minutes' => ((int) $validated['weekly_limit_70_hours']) * 60,
            'enable_ghost_log_detection' => $request->boolean('enable_ghost_log_detection'),
            'ghost_log_threshold_minutes' => (int) $validated['ghost_log_threshold_minutes'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('carrier.hos.fmcsa.configuration')
            ->with('success', 'FMCSA configuration updated successfully.');
    }
}
