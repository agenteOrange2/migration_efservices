<?php

namespace App\Http\Controllers\Admin;

use App\Models\Carrier;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SafetyDataSystemController extends Controller
{
    public function edit(Carrier $carrier): Response
    {
        $carrierData = $carrier->only([
            'id', 'name', 'slug', 'dot_number', 'mc_number',
            'custom_safety_url', 'status',
        ]);
        $carrierData['safety_data_system_url'] = $carrier->safety_data_system_url;
        $carrierData['auto_generated_safety_url'] = $carrier->auto_generated_safety_url;
        $carrierData['has_custom_url'] = $carrier->hasCustomSafetyUrl();
        $carrierData['safety_image_url'] = $carrier->getSafetyDataSystemImageUrl();
        $carrierData['has_safety_image'] = $carrier->hasSafetyDataSystemImage();

        return Inertia::render('admin/carriers/SafetyDataSystem', [
            'carrier' => $carrierData,
        ]);
    }

    public function update(Request $request, Carrier $carrier): RedirectResponse
    {
        $request->validate([
            'custom_safety_url' => 'nullable|url|max:500',
        ]);

        try {
            $carrier->update([
                'custom_safety_url' => $request->custom_safety_url,
            ]);

            Log::info('Safety Data System URL updated', [
                'carrier_id' => $carrier->id,
                'custom_url' => $request->custom_safety_url,
                'admin_user_id' => auth()->id(),
            ]);

            return back()->with('success', 'Safety Data System URL updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating Safety Data System URL', [
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error updating URL: ' . $e->getMessage());
        }
    }

    public function uploadImage(Request $request, Carrier $carrier): RedirectResponse
    {
        $request->validate([
            'safety_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        try {
            $carrier->clearMediaCollection('safety_data_system');

            $carrier->addMediaFromRequest('safety_image')
                ->toMediaCollection('safety_data_system');

            Log::info('Safety Data System image uploaded', [
                'carrier_id' => $carrier->id,
                'admin_user_id' => auth()->id(),
            ]);

            return back()->with('success', 'Safety Data System image uploaded successfully.');
        } catch (\Exception $e) {
            Log::error('Error uploading Safety Data System image', [
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error uploading image: ' . $e->getMessage());
        }
    }

    public function deleteImage(Carrier $carrier): RedirectResponse
    {
        try {
            if ($carrier->hasMedia('safety_data_system')) {
                $carrier->clearMediaCollection('safety_data_system');

                Log::info('Safety Data System image deleted', [
                    'carrier_id' => $carrier->id,
                    'admin_user_id' => auth()->id(),
                ]);

                return back()->with('success', 'Safety Data System image deleted successfully.');
            }

            return back()->with('info', 'No image to delete.');
        } catch (\Exception $e) {
            Log::error('Error deleting Safety Data System image', [
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error deleting image: ' . $e->getMessage());
        }
    }
}
