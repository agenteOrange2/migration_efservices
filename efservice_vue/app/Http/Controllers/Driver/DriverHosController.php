<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Hos\HosEntry;
use App\Models\Hos\HosViolation;
use App\Models\UserDriverDetail;
use App\Services\Hos\HosAlertService;
use App\Services\Hos\HosCalculationService;
use App\Services\Hos\HosService;
use App\Services\Hos\HosWeeklyCycleService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DriverHosController extends Controller
{
    public function __construct(
        protected HosService $hosService,
        protected HosCalculationService $calculationService,
        protected HosAlertService $alertService,
        protected HosWeeklyCycleService $weeklyCycleService,
    ) {
    }

    public function dashboard(): InertiaResponse
    {
        $driver = $this->resolveDriver();
        $today = now()->startOfDay();
        $dashboard = $this->hosService->getDriverDashboard($driver->id);
        $cycleStatus = $this->weeklyCycleService->getWeeklyCycleStatus($driver->id);

        $todayEntries = collect($dashboard['today_entries'] ?? [])->map(fn (HosEntry $entry) => [
            'id' => $entry->id,
            'status' => $entry->status,
            'status_label' => $entry->status_name,
            'start_time' => $entry->start_time?->format('n/j/Y g:i A'),
            'end_time' => $entry->end_time?->format('n/j/Y g:i A'),
            'duration' => $entry->formatted_duration,
            'location' => $entry->location_display,
            'is_manual_entry' => (bool) $entry->is_manual_entry,
            'is_ghost_log' => (bool) $entry->is_ghost_log,
        ])->values();

        $recentViolations = HosViolation::query()
            ->where('user_driver_detail_id', $driver->id)
            ->latest('violation_date')
            ->limit(5)
            ->get()
            ->map(fn (HosViolation $violation) => [
                'id' => $violation->id,
                'date' => $violation->violation_date?->format('n/j/Y'),
                'type' => $violation->violation_type_name,
                'severity' => $violation->severity_name,
                'hours_exceeded' => (float) $violation->hours_exceeded,
                'acknowledged' => (bool) $violation->acknowledged,
                'is_forgiven' => (bool) $violation->is_forgiven,
            ])
            ->values();

        return Inertia::render('driver/hos/Dashboard', [
            'driver' => $this->driverPayload($driver),
            'currentStatus' => $dashboard['current_status'] ? [
                'status' => $dashboard['current_status']->status,
                'status_label' => $dashboard['current_status']->status_name,
                'start_time' => $dashboard['current_status']->start_time?->format('n/j/Y g:i A'),
                'duration' => $dashboard['current_status']->formatted_duration,
                'location' => $dashboard['current_status']->location_display,
            ] : null,
            'totals' => $dashboard['daily_totals'],
            'remaining' => $dashboard['remaining'],
            'alerts' => collect($dashboard['alerts'] ?? [])->values(),
            'cycleStatus' => $cycleStatus,
            'dailyBreakdown' => $this->weeklyCycleService->getDailyBreakdown($driver->id, 7),
            'todayEntries' => $todayEntries,
            'recentViolations' => $recentViolations,
            'stats' => [
                'today_entries' => $todayEntries->count(),
                'today_violations' => $recentViolations->where('date', $today->format('n/j/Y'))->count(),
                'documents' => $driver->getAllHosDocuments()->count() + $driver->getMedia('inspection_reports')->count(),
                'hours_remaining' => round((float) (($dashboard['remaining']['remaining_duty_minutes'] ?? 0) / 60), 2),
            ],
            'documentsSummary' => [
                'trip_reports' => $driver->getMedia('trip_reports')->count(),
                'inspection_reports' => $driver->getMedia('inspection_reports')->count(),
                'daily_logs' => $driver->getMedia('daily_logs')->count(),
                'monthly_summaries' => $driver->getMedia('monthly_summaries')
                    ->filter(fn ($media) => $media->getCustomProperty('document_type') !== 'fmcsa_monthly')
                    ->count(),
                'fmcsa_monthly' => $driver->getMedia('monthly_summaries')
                    ->filter(fn ($media) => $media->getCustomProperty('document_type') === 'fmcsa_monthly')
                    ->count(),
            ],
            'statusOptions' => [
                ['value' => HosEntry::STATUS_OFF_DUTY, 'label' => 'Off Duty'],
                ['value' => HosEntry::STATUS_ON_DUTY_NOT_DRIVING, 'label' => 'On Duty - Not Driving'],
                ['value' => HosEntry::STATUS_ON_DUTY_DRIVING, 'label' => 'On Duty - Driving'],
            ],
        ]);
    }

    public function changeStatus(Request $request): RedirectResponse
    {
        $driver = $this->resolveDriver();

        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', HosEntry::STATUSES)],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $location = null;
            if (! empty($validated['latitude']) && ! empty($validated['longitude'])) {
                $location = [
                    'latitude' => (float) $validated['latitude'],
                    'longitude' => (float) $validated['longitude'],
                    'address' => $validated['address'] ?? null,
                ];
            }

            $this->hosService->createEntry($driver->id, $validated['status'], $location, auth()->id());
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'HOS status updated successfully.');
    }

    public function history(Request $request): InertiaResponse
    {
        $driver = $this->resolveDriver();
        $date = $this->parseUsDate((string) $request->input('date'), now()) ?? now();
        $date = $date->startOfDay();

        $entries = $this->hosService->getDriverEntriesForDate($driver->id, $date)
            ->loadMissing(['vehicle:id,company_unit_number,year,make,model', 'trip:id,trip_number'])
            ->map(fn (HosEntry $entry) => [
                'id' => $entry->id,
                'status' => $entry->status,
                'status_label' => $entry->status_name,
                'start_time' => $entry->start_time?->format('n/j/Y g:i A'),
                'end_time' => $entry->end_time?->format('n/j/Y g:i A'),
                'duration' => $entry->formatted_duration,
                'location' => $entry->location_display,
                'formatted_address' => $entry->formatted_address,
                'is_manual_entry' => (bool) $entry->is_manual_entry,
                'is_ghost_log' => (bool) $entry->is_ghost_log,
                'manual_entry_reason' => $entry->manual_entry_reason,
                'ghost_log_reason' => $entry->ghost_log_reason,
                'edit_start_time' => $entry->start_time?->format('Y-m-d\TH:i'),
                'edit_end_time' => $entry->end_time?->format('Y-m-d\TH:i'),
                'trip_number' => $entry->trip?->trip_number,
                'vehicle_label' => $entry->vehicle
                    ? trim(collect([
                        $entry->vehicle->company_unit_number ? 'Unit #' . $entry->vehicle->company_unit_number : null,
                        trim(($entry->vehicle->year ?: '') . ' ' . ($entry->vehicle->make ?: '') . ' ' . ($entry->vehicle->model ?: '')),
                    ])->filter()->implode(' - '))
                    : null,
            ])
            ->values();

        $totals = $this->calculationService->calculateDailyTotals($driver->id, $date);
        $violations = HosViolation::query()
            ->where('user_driver_detail_id', $driver->id)
            ->whereDate('violation_date', $date)
            ->latest('violation_date')
            ->get()
            ->map(fn (HosViolation $violation) => [
                'id' => $violation->id,
                'type' => $violation->violation_type_name,
                'severity' => $violation->severity_name,
                'hours_exceeded' => $violation->formatted_hours_exceeded,
                'acknowledged' => (bool) $violation->acknowledged,
            ])
            ->values();

        return Inertia::render('driver/hos/History', [
            'driver' => $this->driverPayload($driver),
            'date' => $date->format('n/j/Y'),
            'displayDate' => $date->format('l, F j, Y'),
            'previousDate' => $date->copy()->subDay()->format('n/j/Y'),
            'nextDate' => $date->copy()->addDay()->format('n/j/Y'),
            'totals' => $totals,
            'entries' => $entries,
            'violations' => $violations,
            'statusOptions' => [
                ['value' => HosEntry::STATUS_ON_DUTY_DRIVING, 'label' => 'On Duty - Driving'],
                ['value' => HosEntry::STATUS_ON_DUTY_NOT_DRIVING, 'label' => 'On Duty - Not Driving'],
                ['value' => HosEntry::STATUS_OFF_DUTY, 'label' => 'Off Duty'],
            ],
        ]);
    }

    public function updateEntry(Request $request, HosEntry $entry): RedirectResponse
    {
        $driver = $this->resolveDriver();
        abort_unless((int) $entry->user_driver_detail_id === (int) $driver->id, 403);

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
                'Driver updated entry via HOS history.',
            );
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'HOS entry updated successfully.');
    }

    public function deleteEntry(HosEntry $entry): RedirectResponse
    {
        $driver = $this->resolveDriver();
        abort_unless((int) $entry->user_driver_detail_id === (int) $driver->id, 403);

        $entryDate = $entry->date?->copy() ?? now();
        $entry->delete();
        $this->calculationService->recalculateDailyLog($driver->id, $entryDate);

        return back()->with('success', 'HOS entry deleted successfully.');
    }

    public function bulkDeleteEntries(Request $request): RedirectResponse
    {
        $driver = $this->resolveDriver();

        $validated = $request->validate([
            'entry_ids' => ['required', 'array', 'min:1'],
            'entry_ids.*' => ['integer', 'exists:hos_entries,id'],
        ]);

        $entries = HosEntry::query()
            ->where('user_driver_detail_id', $driver->id)
            ->whereIn('id', $validated['entry_ids'])
            ->get();

        if ($entries->count() !== count($validated['entry_ids'])) {
            abort(403);
        }

        $recalculateDates = $entries->map(fn (HosEntry $entry) => $entry->date?->copy() ?? now());

        HosEntry::query()->whereIn('id', $validated['entry_ids'])->delete();

        foreach ($recalculateDates as $date) {
            $this->calculationService->recalculateDailyLog($driver->id, $date);
        }

        return back()->with('success', count($validated['entry_ids']) . ' HOS entries deleted successfully.');
    }

    protected function resolveDriver(): UserDriverDetail
    {
        $user = auth()->user();
        $driver = $user?->driverDetails ?? $user?->driverDetail;

        abort_unless($driver instanceof UserDriverDetail, 403, 'Driver profile not found.');

        return $driver->loadMissing(['user:id,name,email', 'carrier:id,name']);
    }

    protected function driverPayload(UserDriverDetail $driver): array
    {
        return [
            'id' => $driver->id,
            'full_name' => $driver->full_name ?: ($driver->user?->name ?: 'Driver'),
            'carrier_name' => $driver->carrier?->name,
            'email' => $driver->user?->email,
            'current_cycle' => $driver->getEffectiveHosCycleType(),
        ];
    }

    protected function parseUsDate(string $value, ?Carbon $fallback = null): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }

        foreach (['n/j/Y', 'm/d/Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
