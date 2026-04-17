<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\UserDriverDetail;
use App\Services\Hos\HosWeeklyCycleService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DriverHosCycleController extends Controller
{
    public function __construct(protected HosWeeklyCycleService $weeklyCycleService)
    {
    }

    public function index(): InertiaResponse
    {
        $driver = $this->resolveDriver();

        return Inertia::render('driver/hos/Cycle', [
            'driver' => [
                'id' => $driver->id,
                'full_name' => $driver->full_name ?: ($driver->user?->name ?: 'Driver'),
                'carrier_name' => $driver->carrier?->name,
            ],
            'currentCycleType' => $driver->getEffectiveHosCycleType(),
            'cycleStatus' => $this->weeklyCycleService->getWeeklyCycleStatus($driver->id),
            'dailyBreakdown' => $this->weeklyCycleService->getDailyBreakdown($driver->id, $driver->getEffectiveHosCycleType() === UserDriverDetail::HOS_CYCLE_70_8 ? 8 : 7),
            'pendingRequest' => $driver->hasPendingCycleChangeRequest() ? [
                'requested_to' => $driver->hos_cycle_change_requested_to,
                'requested_to_label' => $driver->hos_cycle_change_requested_to === UserDriverDetail::HOS_CYCLE_60_7
                    ? '60 hours / 7 days'
                    : '70 hours / 8 days',
                'requested_at' => $driver->hos_cycle_change_requested_at?->format('n/j/Y g:i A'),
            ] : null,
        ]);
    }

    public function requestChange(Request $request): RedirectResponse
    {
        $driver = $this->resolveDriver();

        $validated = $request->validate([
            'new_cycle_type' => ['required', 'in:' . UserDriverDetail::HOS_CYCLE_60_7 . ',' . UserDriverDetail::HOS_CYCLE_70_8],
        ]);

        if ($driver->hasPendingCycleChangeRequest()) {
            return back()->with('error', 'You already have a pending cycle change request.');
        }

        if ($driver->getEffectiveHosCycleType() === $validated['new_cycle_type']) {
            return back()->with('error', 'You are already using that cycle.');
        }

        if (! $driver->requestCycleChange($validated['new_cycle_type'])) {
            return back()->with('error', 'Unable to submit the cycle change request right now.');
        }

        return back()->with('success', 'Your cycle change request was submitted successfully.');
    }

    public function cancelRequest(): RedirectResponse
    {
        $driver = $this->resolveDriver();

        if (! $driver->hasPendingCycleChangeRequest()) {
            return back()->with('error', 'There is no pending cycle change request to cancel.');
        }

        $driver->rejectCycleChange();

        return back()->with('success', 'Your cycle change request was cancelled.');
    }

    protected function resolveDriver(): UserDriverDetail
    {
        $user = auth()->user();
        $driver = $user?->driverDetails ?? $user?->driverDetail;

        abort_unless($driver instanceof UserDriverDetail, 403, 'Driver profile not found.');

        return $driver->loadMissing(['user:id,name,email', 'carrier:id,name']);
    }
}
