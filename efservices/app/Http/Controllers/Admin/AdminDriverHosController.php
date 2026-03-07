<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\UserDriverDetail;
use App\Models\Carrier;
use Illuminate\Support\Facades\Auth;

class AdminDriverHosController extends Controller
{
    /**
     * Show all drivers with their HOS cycle settings.
     */
    public function index(Request $request)
    {
        // Get all active drivers with their relationships
        $drivers = UserDriverDetail::where('status', UserDriverDetail::STATUS_ACTIVE)
            ->with(['user', 'carrier'])
            ->orderBy('hos_cycle_change_requested', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Count pending requests
        $pendingCount = $drivers->where('hos_cycle_change_requested', true)->count();

        // Get carriers for filter
        $carriers = Carrier::orderBy('name')->get(['id', 'name']);

        return view('admin.drivers.hos-settings', [
            'drivers' => $drivers,
            'pendingCount' => $pendingCount,
            'currentFilter' => 'all',
            'carriers' => $carriers,
            'selectedCarrierId' => null,
        ]);
    }

    /**
     * Update a driver's HOS cycle type directly.
     */
    public function update(Request $request, UserDriverDetail $driver)
    {
        $request->validate([
            'hos_cycle_type' => 'required|in:60_7,70_8',
        ]);

        $driver->update([
            'hos_cycle_type' => $request->input('hos_cycle_type'),
            'hos_cycle_change_requested' => false,
            'hos_cycle_change_requested_to' => null,
            'hos_cycle_change_approved_at' => now(),
            'hos_cycle_change_approved_by' => Auth::id(),
        ]);

        return back()->with('success', "Driver's HOS cycle type has been updated to " . $this->getCycleTypeName($request->input('hos_cycle_type')) . ".");
    }

    /**
     * Approve a driver's cycle change request.
     */
    public function approveRequest(UserDriverDetail $driver)
    {
        if (!$driver->hasPendingCycleChangeRequest()) {
            return back()->with('error', 'This driver does not have a pending cycle change request.');
        }

        $newCycleType = $driver->hos_cycle_change_requested_to;
        $driver->approveCycleChange(Auth::id());

        return back()->with('success', "The cycle change request for {$driver->full_name} has been approved. New cycle: " . $this->getCycleTypeName($newCycleType) . ".");
    }

    /**
     * Reject a driver's cycle change request.
     */
    public function rejectRequest(UserDriverDetail $driver)
    {
        if (!$driver->hasPendingCycleChangeRequest()) {
            return back()->with('error', 'This driver does not have a pending cycle change request.');
        }

        $driver->rejectCycleChange();

        return back()->with('success', "The cycle change request for {$driver->full_name} has been rejected.");
    }

    /**
     * Get human-readable cycle type name.
     */
    private function getCycleTypeName(string $cycleType): string
    {
        return match($cycleType) {
            '60_7' => '60 hours / 7 days',
            '70_8' => '70 hours / 8 days',
            default => $cycleType,
        };
    }
}
