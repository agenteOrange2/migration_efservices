<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverMedicalQualification;
use App\Models\UserDriverDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SocialSecurityCardController extends Controller
{
    /**
     * Display a listing of the social security cards.
     */
    public function index(Request $request)
    {
        try {
            $query = DriverMedicalQualification::with(['userDriverDetail.user', 'userDriverDetail.carrier'])
                ->whereHas('media', function($q) {
                    $q->where('collection_name', 'social_security_card');
                });
            
            // Handle tab-based filtering
            $currentTab = $request->get('tab', 'all');
            
            if ($currentTab === 'with_ssn') {
                $query->whereNotNull('social_security_number')
                      ->where('social_security_number', '!=', '');
            } elseif ($currentTab === 'without_ssn') {
                $query->where(function($q) {
                    $q->whereNull('social_security_number')
                      ->orWhere('social_security_number', '');
                });
            }
            
            // Apply filters
            if ($request->filled('search_term')) {
                $searchTerm = '%' . $request->search_term . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->where('social_security_number', 'like', $searchTerm)
                      ->orWhereHas('userDriverDetail.user', function($subQ) use ($searchTerm) {
                          $subQ->where('name', 'like', $searchTerm)
                               ->orWhere('email', 'like', $searchTerm);
                      });
                });
            }
            
            if ($request->filled('driver_filter')) {
                $query->where('user_driver_detail_id', $request->driver_filter);
            }
            
            if ($request->filled('carrier_filter')) {
                $query->whereHas('userDriverDetail', function($q) use ($request) {
                    $q->where('carrier_id', $request->carrier_filter);
                });
            }
            
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
            
            // Handle sorting
            $sortField = $request->get('sort', 'created_at');
            $sortDirection = $request->get('direction', 'desc');
            
            if (in_array($sortField, ['created_at', 'social_security_number'])) {
                $query->orderBy($sortField, $sortDirection);
            } else {
                $query->orderBy('created_at', 'desc');
            }
            
            $records = $query->paginate(15);
            
            // Get data for filters
            $drivers = UserDriverDetail::with('user')->get();
            $carriers = \App\Models\Carrier::where('status', 1)->orderBy('name')->get();
            
            // Get statistics
            $totalCount = DriverMedicalQualification::whereHas('media', function($q) {
                $q->where('collection_name', 'social_security_card');
            })->count();
            
            $withSsnCount = DriverMedicalQualification::whereHas('media', function($q) {
                $q->where('collection_name', 'social_security_card');
            })->whereNotNull('social_security_number')
              ->where('social_security_number', '!=', '')
              ->count();
            
            $withoutSsnCount = $totalCount - $withSsnCount;
            
            return view('admin.drivers.social-security-cards.index', compact(
                'records',
                'drivers',
                'carriers',
                'totalCount',
                'withSsnCount',
                'withoutSsnCount',
                'currentTab'
            ));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading social security cards', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Error loading social security cards: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new social security card record.
     */
    public function create()
    {
        $carriers = \App\Models\Carrier::where('status', 1)->orderBy('name')->get();
        $drivers = collect();

        return view('admin.drivers.social-security-cards.create', compact('carriers', 'drivers'));
    }

    /**
     * Store a newly created social security card in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'social_security_number' => 'required|string|max:255',
            'social_security_card' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'hire_date' => 'nullable|date',
            'location' => 'nullable|string|max:255'
        ]);

        // Check if driver already has a social security card uploaded
        $existingRecord = DriverMedicalQualification::where('user_driver_detail_id', $validated['user_driver_detail_id'])
            ->whereHas('media', function($q) {
                $q->where('collection_name', 'social_security_card');
            })
            ->first();
        
        if ($existingRecord) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This driver already has a Social Security Card on file. Please edit the existing record instead of creating a new one.');
        }

        // Check if driver already has a medical qualification record
        $medicalRecord = DriverMedicalQualification::where('user_driver_detail_id', $validated['user_driver_detail_id'])->first();
        
        if (!$medicalRecord) {
            $medicalRecord = DriverMedicalQualification::create([
                'user_driver_detail_id' => $validated['user_driver_detail_id'],
                'social_security_number' => $validated['social_security_number'],
                'hire_date' => $request->hire_date ? \Carbon\Carbon::parse($request->hire_date) : null,
                'location' => $request->location,
            ]);
        } else {
            $medicalRecord->update([
                'social_security_number' => $validated['social_security_number'],
                'hire_date' => $request->hire_date ? \Carbon\Carbon::parse($request->hire_date) : null,
                'location' => $request->location,
            ]);
        }

        // Handle social security card upload
        if ($request->hasFile('social_security_card')) {
            $medicalRecord->addMediaFromRequest('social_security_card')
                ->toMediaCollection('social_security_card');
        }

        return redirect()->route('admin.social-security-cards.index')
            ->with('success', 'Social Security Card uploaded successfully.');
    }

    /**
     * Display the specified social security card.
     */
    public function show(DriverMedicalQualification $social_security_card)
    {
        $social_security_card->load(['userDriverDetail.user', 'userDriverDetail.carrier', 'media']);
        
        return view('admin.drivers.social-security-cards.show', [
            'record' => $social_security_card
        ]);
    }

    /**
     * Show the form for editing the specified social security card.
     */
    public function edit(DriverMedicalQualification $social_security_card)
    {
        $carriers = \App\Models\Carrier::where('status', 1)->orderBy('name')->get();
        
        $social_security_card->load(['userDriverDetail.user', 'userDriverDetail.carrier']);
        
        $currentCarrierId = $social_security_card->userDriverDetail->carrier_id ?? null;
        
        $drivers = $currentCarrierId 
            ? UserDriverDetail::with('user')->where('carrier_id', $currentCarrierId)->get()
            : collect();

        return view('admin.drivers.social-security-cards.edit', [
            'record' => $social_security_card,
            'carriers' => $carriers,
            'drivers' => $drivers
        ]);
    }

    /**
     * Update the specified social security card in storage.
     */
    public function update(Request $request, DriverMedicalQualification $social_security_card)
    {
        $validated = $request->validate([
            'social_security_number' => 'required|string|max:255',
            'social_security_card' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'hire_date' => 'nullable|date',
            'location' => 'nullable|string|max:255'
        ]);

        $social_security_card->update([
            'social_security_number' => $validated['social_security_number'],
            'hire_date' => $request->hire_date ? \Carbon\Carbon::parse($request->hire_date) : null,
            'location' => $request->location,
        ]);

        // Handle social security card upload
        if ($request->hasFile('social_security_card')) {
            $social_security_card->clearMediaCollection('social_security_card');
            
            $social_security_card->addMediaFromRequest('social_security_card')
                ->toMediaCollection('social_security_card');
        }

        return redirect()->route('admin.social-security-cards.index')
            ->with('success', 'Social Security Card updated successfully.');
    }

    /**
     * Remove the specified social security card from storage.
     */
    public function destroy(DriverMedicalQualification $social_security_card)
    {
        // Only clear the social security card media, don't delete the medical record
        $social_security_card->clearMediaCollection('social_security_card');

        return redirect()->route('admin.social-security-cards.index')
            ->with('success', 'Social Security Card deleted successfully.');
    }

    /**
     * Contact driver about social security card.
     */
    public function contact(DriverMedicalQualification $social_security_card)
    {
        $social_security_card->load(['userDriverDetail.user', 'userDriverDetail.carrier']);
        
        return view('admin.drivers.social-security-cards.contact', [
            'record' => $social_security_card
        ]);
    }

    /**
     * Send contact message to driver.
     */
    public function sendContact(Request $request, DriverMedicalQualification $social_security_card)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $social_security_card->load(['userDriverDetail.user']);
        
        $user = $social_security_card->userDriverDetail->user ?? null;
        
        if ($user && $user->email) {
            \Illuminate\Support\Facades\Mail::raw($validated['message'], function($mail) use ($user, $validated) {
                $mail->to($user->email)
                     ->subject($validated['subject']);
            });
            
            return redirect()->route('admin.social-security-cards.index')
                ->with('success', 'Message sent successfully to ' . $user->email);
        }

        return redirect()->route('admin.social-security-cards.index')
            ->with('error', 'Could not send message. Driver email not found.');
    }
}
