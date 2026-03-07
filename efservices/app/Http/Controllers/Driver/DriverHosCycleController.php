<?php

namespace App\Http\Controllers\Driver;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\UserDriverDetail;
use App\Services\Hos\HosWeeklyCycleService;
use Illuminate\Support\Facades\Auth;

class DriverHosCycleController extends Controller
{
    protected HosWeeklyCycleService $weeklyCycleService;

    public function __construct(HosWeeklyCycleService $weeklyCycleService)
    {
        $this->weeklyCycleService = $weeklyCycleService;
    }

    /**
     * Show current cycle settings and status.
     */
    public function index()
    {
        $driver = Auth::user()->driverDetail;
        
        if (!$driver) {
            abort(403, 'Driver profile not found.');
        }

        // Get current cycle status
        $cycleStatus = $this->weeklyCycleService->getWeeklyCycleStatus($driver->id);
        
        // Get driver's effective cycle type
        $currentCycleType = $driver->getEffectiveHosCycleType();
        
        // Check for pending request
        $hasPendingRequest = $driver->hasPendingCycleChangeRequest();
        $pendingRequestTo = $driver->hos_cycle_change_requested_to;
        $pendingRequestAt = $driver->hos_cycle_change_requested_at;

        return view('driver.hos.cycle-settings', [
            'driver' => $driver,
            'cycleStatus' => $cycleStatus,
            'currentCycleType' => $currentCycleType,
            'hasPendingRequest' => $hasPendingRequest,
            'pendingRequestTo' => $pendingRequestTo,
            'pendingRequestAt' => $pendingRequestAt,
        ]);
    }

    /**
     * Request a cycle type change.
     */
    public function requestChange(Request $request)
    {
        $driver = Auth::user()->driverDetail;
        
        if (!$driver) {
            abort(403, 'Driver profile not found.');
        }

        // Validate request
        $request->validate([
            'new_cycle_type' => 'required|in:60_7,70_8',
        ]);

        $newCycleType = $request->input('new_cycle_type');

        // Check if already has a pending request
        if ($driver->hasPendingCycleChangeRequest()) {
            return back()->with('error', 'You already have a pending cycle change request. Please wait for approval or contact your carrier.');
        }

        // Check if requesting same cycle
        if ($driver->getEffectiveHosCycleType() === $newCycleType) {
            return back()->with('error', 'You are already on this cycle type.');
        }

        // Create the request
        $success = $driver->requestCycleChange($newCycleType);

        if ($success) {
            // TODO: Send notification to carrier
            return back()->with('success', 'Your cycle change request has been submitted. Please wait for your carrier to approve it.');
        }

        return back()->with('error', 'Unable to submit cycle change request. Please try again.');
    }

    /**
     * Cancel a pending cycle change request.
     */
    public function cancelRequest()
    {
        $driver = Auth::user()->driverDetail;
        
        if (!$driver) {
            abort(403, 'Driver profile not found.');
        }

        if (!$driver->hasPendingCycleChangeRequest()) {
            return back()->with('error', 'You do not have a pending cycle change request.');
        }

        $driver->rejectCycleChange();

        return back()->with('success', 'Your cycle change request has been cancelled.');
    }
}
