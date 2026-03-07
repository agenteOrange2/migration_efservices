<?php

namespace App\Http\Controllers\Carrier;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\UserDriverDetail;
use App\Models\Carrier;
use Illuminate\Support\Facades\Auth;

class CarrierDriverHosController extends Controller
{
    /**
     * Show all drivers with their HOS cycle settings.
     */
    public function index(Request $request)
    {
        $carrierDetails = Auth::user()->carrierDetails;
        
        if (!$carrierDetails) {
            abort(403, 'Carrier profile not found.');
        }

        $carrier = $carrierDetails->carrier;

        // Get filter
        $filter = $request->get('filter', 'all');

        // Get all drivers for this carrier
        $query = UserDriverDetail::where('carrier_id', $carrier->id)
            ->where('status', UserDriverDetail::STATUS_ACTIVE)
            ->with('user');

        if ($filter === 'pending') {
            $query->where('hos_cycle_change_requested', true);
        }

        $drivers = $query->orderBy('hos_cycle_change_requested', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Count pending requests
        $pendingCount = UserDriverDetail::where('carrier_id', $carrier->id)
            ->where('status', UserDriverDetail::STATUS_ACTIVE)
            ->where('hos_cycle_change_requested', true)
            ->count();

        return view('carrier.drivers.hos-settings', [
            'drivers' => $drivers,
            'pendingCount' => $pendingCount,
            'currentFilter' => $filter,
        ]);
    }

    /**
     * Show a specific driver's HOS settings.
     */
    public function show(UserDriverDetail $driver)
    {
        $carrierDetails = Auth::user()->carrierDetails;
        $carrier = $carrierDetails ? $carrierDetails->carrier : null;
        
        if (!$carrier || $driver->carrier_id !== $carrier->id) {
            abort(403, 'You do not have access to this driver.');
        }

        return view('carrier.drivers.hos-settings-detail', [
            'driver' => $driver->load('user'),
        ]);
    }

    /**
     * Update a driver's HOS cycle type directly (carrier sets without request).
     */
    public function update(Request $request, UserDriverDetail $driver)
    {
        $carrierDetails = Auth::user()->carrierDetails;
        $carrier = $carrierDetails ? $carrierDetails->carrier : null;
        
        if (!$carrier || $driver->carrier_id !== $carrier->id) {
            abort(403, 'You do not have access to this driver.');
        }

        $request->validate([
            'hos_cycle_type' => 'required|in:60_7,70_8',
        ]);

        $driver->update([
            'hos_cycle_type' => $request->input('hos_cycle_type'),
            // Clear any pending request if carrier is setting directly
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
        $carrierDetails = Auth::user()->carrierDetails;
        $carrier = $carrierDetails ? $carrierDetails->carrier : null;
        
        if (!$carrier || $driver->carrier_id !== $carrier->id) {
            abort(403, 'You do not have access to this driver.');
        }

        if (!$driver->hasPendingCycleChangeRequest()) {
            return back()->with('error', 'This driver does not have a pending cycle change request.');
        }

        $newCycleType = $driver->hos_cycle_change_requested_to;
        $driver->approveCycleChange(Auth::id());

        // TODO: Send notification to driver

        return back()->with('success', "The cycle change request for {$driver->full_name} has been approved. New cycle: " . $this->getCycleTypeName($newCycleType) . ".");
    }

    /**
     * Reject a driver's cycle change request.
     */
    public function rejectRequest(Request $request, UserDriverDetail $driver)
    {
        $carrierDetails = Auth::user()->carrierDetails;
        $carrier = $carrierDetails ? $carrierDetails->carrier : null;
        
        if (!$carrier || $driver->carrier_id !== $carrier->id) {
            abort(403, 'You do not have access to this driver.');
        }

        if (!$driver->hasPendingCycleChangeRequest()) {
            return back()->with('error', 'This driver does not have a pending cycle change request.');
        }

        $driver->rejectCycleChange();

        // TODO: Send notification to driver

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
