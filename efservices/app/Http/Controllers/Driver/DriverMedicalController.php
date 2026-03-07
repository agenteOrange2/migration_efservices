<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverMedicalQualification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DriverMedicalController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::user() || !Auth::user()->driverDetail) {
                abort(403, 'Access denied. Driver profile not found.');
            }
            return $next($request);
        });
    }

    /**
     * Get the authenticated driver's detail.
     */
    private function getDriverDetail()
    {
        return Auth::user()->driverDetail;
    }

    /**
     * Display the driver's medical qualification.
     */
    public function index()
    {
        $driver = $this->getDriverDetail();
        $medical = $driver->medicalQualification;

        return view('driver.medical.index', compact('driver', 'medical'));
    }

    /**
     * Show the form for editing the medical qualification.
     */
    public function edit()
    {
        $driver = $this->getDriverDetail();
        $medical = $driver->medicalQualification;

        // Create medical record if doesn't exist
        if (!$medical) {
            $medical = $driver->medicalQualification()->create([
                'user_driver_detail_id' => $driver->id,
            ]);
        }

        return view('driver.medical.edit', compact('driver', 'medical'));
    }

    /**
     * Update the medical qualification.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'social_security_number' => 'nullable|string|max:20',
            'hire_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'medical_examiner_name' => 'nullable|string|max:255',
            'medical_examiner_registry_number' => 'nullable|string|max:100',
            'medical_card_expiration_date' => 'nullable|date',
            'medical_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'medical_card' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'social_security_card' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $driver = $this->getDriverDetail();
        $medical = $driver->medicalQualification;

        // Create if doesn't exist
        if (!$medical) {
            $medical = $driver->medicalQualification()->create([
                'user_driver_detail_id' => $driver->id,
            ]);
        }

        $medical->update([
            'social_security_number' => $validated['social_security_number'] ?? $medical->social_security_number,
            'hire_date' => $validated['hire_date'] ?? $medical->hire_date,
            'location' => $validated['location'] ?? $medical->location,
            'medical_examiner_name' => $validated['medical_examiner_name'] ?? $medical->medical_examiner_name,
            'medical_examiner_registry_number' => $validated['medical_examiner_registry_number'] ?? $medical->medical_examiner_registry_number,
            'medical_card_expiration_date' => $validated['medical_card_expiration_date'] ?? $medical->medical_card_expiration_date,
        ]);

        // Handle file uploads
        if ($request->hasFile('medical_certificate')) {
            $medical->addMediaFromRequest('medical_certificate')
                ->toMediaCollection('medical_certificate');
        }

        if ($request->hasFile('medical_card')) {
            $medical->addMediaFromRequest('medical_card')
                ->toMediaCollection('medical_card');
        }

        if ($request->hasFile('social_security_card')) {
            $medical->clearMediaCollection('social_security_card');
            $medical->addMediaFromRequest('social_security_card')
                ->toMediaCollection('social_security_card');
        }

        return redirect()->route('driver.medical.index')
            ->with('success', 'Medical information updated successfully.');
    }

    /**
     * Upload additional medical documents.
     */
    public function uploadDocument(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'collection' => 'nullable|string|in:medical_certificate,test_results,additional_documents,medical_documents',
        ]);

        $driver = $this->getDriverDetail();
        $medical = $driver->medicalQualification;

        if (!$medical) {
            return back()->with('error', 'No medical record found. Please create one first.');
        }

        $collection = $request->input('collection', 'medical_documents');

        $medical->addMediaFromRequest('document')
            ->toMediaCollection($collection);

        return back()->with('success', 'Document uploaded successfully.');
    }

    /**
     * Delete a medical document.
     */
    public function deleteDocument($mediaId)
    {
        $driver = $this->getDriverDetail();
        $medical = $driver->medicalQualification;

        if (!$medical) {
            return back()->with('error', 'No medical record found.');
        }

        $media = $medical->media()->find($mediaId);

        if (!$media) {
            return back()->with('error', 'Document not found.');
        }

        $media->delete();

        return back()->with('success', 'Document deleted successfully.');
    }
}
