<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Models\Hos\HosConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HosConfigurationController extends Controller
{
    public function edit()
    {
        $carrier = $this->getCarrier();
        $config = HosConfiguration::firstOrCreate(
            ['carrier_id' => $carrier->id],
            [
                'fmcsa_texas_mode' => false,
                'cycle_type' => '70_8',
                'allow_24_hour_reset' => false,
                'require_30_min_break' => true,
                'ghost_log_detection_enabled' => true,
                'ghost_log_threshold_minutes' => 30,
                'gps_tracking_interval_minutes' => 5,
            ]
        );

        return view('carrier.hos.configuration', compact('config'));
    }

    public function update(Request $request)
    {
        $carrier = $this->getCarrier();
        
        $validated = $request->validate([
            'fmcsa_texas_mode' => 'nullable|boolean',
            'cycle_type' => 'required|in:60_7,70_8',
            'allow_24_hour_reset' => 'nullable|boolean',
            'require_30_min_break' => 'nullable|boolean',
            'ghost_log_detection_enabled' => 'nullable|boolean',
            'ghost_log_threshold_minutes' => 'required|integer|min:10|max:120',
            'gps_tracking_interval_minutes' => 'required|integer|min:1|max:30',
        ]);

        $config = HosConfiguration::updateOrCreate(
            ['carrier_id' => $carrier->id],
            [
                'fmcsa_texas_mode' => $request->boolean('fmcsa_texas_mode'),
                'cycle_type' => $validated['cycle_type'],
                'allow_24_hour_reset' => $request->boolean('allow_24_hour_reset'),
                'require_30_min_break' => $request->boolean('require_30_min_break'),
                'ghost_log_detection_enabled' => $request->boolean('ghost_log_detection_enabled'),
                'ghost_log_threshold_minutes' => $validated['ghost_log_threshold_minutes'],
                'gps_tracking_interval_minutes' => $validated['gps_tracking_interval_minutes'],
            ]
        );

        return back()->with('success', 'HOS Configuration updated successfully.');
    }

    /**
     * Get the current carrier.
     */
    protected function getCarrier()
    {
        $user = Auth::user();
        
        if ($user->carrierDetails) {
            return $user->carrierDetails->carrier;
        }

        $carrier = $user->carriers()->first();
        if ($carrier) {
            return $carrier;
        }

        abort(403, 'Carrier not found.');
    }
}
