<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverLicense;
use App\Models\Admin\Driver\LicenseEndorsement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DriverLicenseController extends Controller
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
     * Display a listing of the driver's licenses.
     */
    public function index()
    {
        $driver = $this->getDriverDetail();
        $licenses = $driver->licenses()
            ->with('endorsements')
            ->orderBy('is_primary', 'desc')
            ->orderBy('expiration_date', 'desc')
            ->get();

        return view('driver.licenses.index', compact('driver', 'licenses'));
    }

    /**
     * Show the form for creating a new license.
     */
    public function create()
    {
        $driver = $this->getDriverDetail();
        $endorsements = LicenseEndorsement::orderBy('name')->get();
        $states = $this->getUsStates();

        return view('driver.licenses.create', compact('driver', 'endorsements', 'states'));
    }

    /**
     * Store a newly created license.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'license_number' => 'required|string|max:50',
            'state_of_issue' => 'required|string|max:2',
            'license_class' => 'required|string|max:10',
            'expiration_date' => 'required|date',
            'is_cdl' => 'boolean',
            'restrictions' => 'nullable|string|max:255',
            'is_primary' => 'boolean',
            'endorsements' => 'nullable|array',
            'endorsements.*' => 'exists:license_endorsements,id',
            'license_front' => 'nullable|image|max:5120',
            'license_back' => 'nullable|image|max:5120',
        ]);

        $driver = $this->getDriverDetail();

        // If this is set as primary, unset other primary licenses
        if ($request->boolean('is_primary')) {
            $driver->licenses()->update(['is_primary' => false]);
        }

        $license = $driver->licenses()->create([
            'license_number' => $validated['license_number'],
            'state_of_issue' => $validated['state_of_issue'],
            'license_class' => $validated['license_class'],
            'expiration_date' => $validated['expiration_date'],
            'is_cdl' => $request->boolean('is_cdl'),
            'restrictions' => $validated['restrictions'] ?? null,
            'is_primary' => $request->boolean('is_primary'),
            'status' => 'active',
        ]);

        // Attach endorsements
        if (!empty($validated['endorsements'])) {
            $license->endorsements()->attach($validated['endorsements']);
        }

        // Handle file uploads
        if ($request->hasFile('license_front')) {
            $license->addMediaFromRequest('license_front')
                ->toMediaCollection('license_front');
        }

        if ($request->hasFile('license_back')) {
            $license->addMediaFromRequest('license_back')
                ->toMediaCollection('license_back');
        }

        // Handle additional documents
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $document) {
                $license->addMedia($document)
                    ->toMediaCollection('license_documents');
            }
        }

        return redirect()->route('driver.licenses.index')
            ->with('success', 'License added successfully.');
    }

    /**
     * Display the specified license.
     */
    public function show(DriverLicense $license)
    {
        $this->authorizeAccess($license);
        $license->load('endorsements');

        return view('driver.licenses.show', compact('license'));
    }

    /**
     * Show the form for editing the specified license.
     */
    public function edit(DriverLicense $license)
    {
        $this->authorizeAccess($license);
        $license->load('endorsements');
        $driver = $this->getDriverDetail();
        $endorsements = LicenseEndorsement::orderBy('name')->get();
        $states = $this->getUsStates();

        return view('driver.licenses.edit', compact('driver', 'license', 'endorsements', 'states'));
    }

    /**
     * Update the specified license.
     */
    public function update(Request $request, DriverLicense $license)
    {
        $this->authorizeAccess($license);

        $validated = $request->validate([
            'license_number' => 'required|string|max:50',
            'state_of_issue' => 'required|string|max:2',
            'license_class' => 'required|string|max:10',
            'expiration_date' => 'required|date',
            'is_cdl' => 'boolean',
            'restrictions' => 'nullable|string|max:255',
            'is_primary' => 'boolean',
            'endorsements' => 'nullable|array',
            'endorsements.*' => 'exists:license_endorsements,id',
            'license_front' => 'nullable|image|max:5120',
            'license_back' => 'nullable|image|max:5120',
        ]);

        $driver = $this->getDriverDetail();

        // If this is set as primary, unset other primary licenses
        if ($request->boolean('is_primary') && !$license->is_primary) {
            $driver->licenses()->where('id', '!=', $license->id)->update(['is_primary' => false]);
        }

        $license->update([
            'license_number' => $validated['license_number'],
            'state_of_issue' => $validated['state_of_issue'],
            'license_class' => $validated['license_class'],
            'expiration_date' => $validated['expiration_date'],
            'is_cdl' => $request->boolean('is_cdl'),
            'restrictions' => $validated['restrictions'] ?? null,
            'is_primary' => $request->boolean('is_primary'),
        ]);

        // Sync endorsements
        $license->endorsements()->sync($validated['endorsements'] ?? []);

        // Handle file uploads
        if ($request->hasFile('license_front')) {
            $license->addMediaFromRequest('license_front')
                ->toMediaCollection('license_front');
        }

        if ($request->hasFile('license_back')) {
            $license->addMediaFromRequest('license_back')
                ->toMediaCollection('license_back');
        }

        // Handle additional documents
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $document) {
                $license->addMedia($document)
                    ->toMediaCollection('license_documents');
            }
        }

        return redirect()->route('driver.licenses.show', $license)
            ->with('success', 'License updated successfully.');
    }

    /**
     * Delete a document from the license.
     */
    public function deleteDocument(DriverLicense $license, $mediaId)
    {
        $this->authorizeAccess($license);

        $media = $license->getMedia('license_documents')->where('id', $mediaId)->first();
        
        if ($media) {
            $media->delete();
            return redirect()->back()->with('success', 'Document deleted successfully.');
        }

        return redirect()->back()->with('error', 'Document not found.');
    }

    /**
     * Remove the specified license.
     */
    public function destroy(DriverLicense $license)
    {
        $this->authorizeAccess($license);

        $license->endorsements()->detach();
        $license->clearMediaCollection('license_front');
        $license->clearMediaCollection('license_back');
        $license->clearMediaCollection('license_documents');
        $license->delete();

        return redirect()->route('driver.licenses.index')
            ->with('success', 'License deleted successfully.');
    }

    /**
     * Authorize that the license belongs to the authenticated driver.
     */
    private function authorizeAccess(DriverLicense $license)
    {
        $driver = $this->getDriverDetail();
        
        // Use == for loose comparison to handle string/integer type differences
        if ((int) $license->user_driver_detail_id !== (int) $driver->id) {
            abort(403, 'Unauthorized access to this license.');
        }
    }

    /**
     * Get US states for dropdown.
     */
    private function getUsStates(): array
    {
        return [
            'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
            'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
            'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
            'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
            'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
            'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
            'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
            'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
            'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
            'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
            'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
            'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
            'WI' => 'Wisconsin', 'WY' => 'Wyoming', 'DC' => 'District of Columbia',
        ];
    }
}
