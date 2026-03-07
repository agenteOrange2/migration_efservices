<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Services\Driver\DriverNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DriverDocumentController extends Controller
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

    private function getDriverDetail()
    {
        return Auth::user()->driverDetail;
    }

    /**
     * Display all driver documents organized by category.
     */
    public function index()
    {
        $driver = $this->getDriverDetail()->load([
            'licenses',
            'medicalQualification',
            'trainingSchools',
            'courses',
            'testings',
            'inspections',
            'accidents',
            'trafficConvictions',
            'application'
        ]);

        $documentCategories = $this->organizeDocuments($driver);
        $totalDocuments = collect($documentCategories)->sum(fn($docs) => $docs->count());

        return view('driver.documents.index', compact('driver', 'documentCategories', 'totalDocuments'));
    }

    /**
     * Show upload form.
     */
    public function create()
    {
        $driver = $this->getDriverDetail();
        return view('driver.documents.upload', compact('driver'));
    }

    /**
     * Upload a new document.
     */
    public function store(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'category' => 'required|string|in:driving_records,medical_records,criminal_records,clearing_house,other',
        ]);

        $driver = $this->getDriverDetail();

        $driver->addMediaFromRequest('document')
            ->toMediaCollection($request->input('category'));

        // Notificar a carrier y admins sobre el nuevo documento
        DriverNotificationService::notifyDocumentUploaded(
            $driver,
            $request->input('category'),
            $request->file('document')->getClientOriginalName()
        );

        return redirect()->route('driver.documents.index')
            ->with('success', 'Document uploaded successfully.');
    }

    /**
     * Download all documents as ZIP.
     */
    public function downloadAll()
    {
        // Redirect to the existing download method in DriverDashboardController
        return redirect()->route('driver.profile.download-documents');
    }

    /**
     * Delete a document.
     */
    public function destroy($mediaId)
    {
        $driver = $this->getDriverDetail();
        
        // Find media in driver's collections
        $media = $driver->media()->find($mediaId);
        
        if (!$media) {
            return back()->with('error', 'Document not found.');
        }

        $media->delete();

        return back()->with('success', 'Document deleted successfully.');
    }

    /**
     * Organize all driver documents by category.
     */
    private function organizeDocuments($driver): array
    {
        $categories = [];

        // License Documents
        $licenseMedia = collect();
        foreach ($driver->licenses as $license) {
            $licenseMedia = $licenseMedia->merge($license->getMedia('license_front'));
            $licenseMedia = $licenseMedia->merge($license->getMedia('license_back'));
            $licenseMedia = $licenseMedia->merge($license->getMedia('license_documents'));
        }
        if ($licenseMedia->count() > 0) {
            $categories['Licenses'] = $licenseMedia;
        }

        // Medical Documents
        if ($driver->medicalQualification) {
            $medicalMedia = collect();
            foreach (['medical_certificate', 'test_results', 'additional_documents', 'medical_documents', 'medical_card'] as $collection) {
                $medicalMedia = $medicalMedia->merge($driver->medicalQualification->getMedia($collection));
            }
            if ($medicalMedia->count() > 0) {
                $categories['Medical'] = $medicalMedia;
            }
        }

        // Training Documents
        $trainingMedia = collect();
        foreach ($driver->trainingSchools as $school) {
            $trainingMedia = $trainingMedia->merge($school->getMedia('school_certificates'));
        }
        foreach ($driver->courses as $course) {
            $trainingMedia = $trainingMedia->merge($course->getMedia('course_certificates'));
        }
        if ($trainingMedia->count() > 0) {
            $categories['Training'] = $trainingMedia;
        }

        // Testing Documents
        $testingMedia = collect();
        if ($driver->testings) {
            foreach ($driver->testings as $testing) {
                $testingMedia = $testingMedia->merge($testing->getMedia('drug_test_pdf'));
                $testingMedia = $testingMedia->merge($testing->getMedia('test_results'));
                $testingMedia = $testingMedia->merge($testing->getMedia('test_certificates'));
            }
        }
        if ($testingMedia->count() > 0) {
            $categories['Testing'] = $testingMedia;
        }

        // Inspection Documents
        $inspectionMedia = collect();
        if ($driver->inspections) {
            foreach ($driver->inspections as $inspection) {
                $inspectionMedia = $inspectionMedia->merge($inspection->getMedia('inspection_documents'));
            }
        }
        if ($inspectionMedia->count() > 0) {
            $categories['Inspections'] = $inspectionMedia;
        }

        // Records
        $recordsMedia = collect();
        foreach (['driving_records', 'criminal_records', 'medical_records', 'clearing_house'] as $collection) {
            $recordsMedia = $recordsMedia->merge($driver->getMedia($collection));
        }
        if ($recordsMedia->count() > 0) {
            $categories['Records'] = $recordsMedia;
        }

        // Application Documents
        $applicationMedia = collect();
        if ($driver->application) {
            $applicationMedia = $applicationMedia->merge($driver->application->getMedia('application_pdf'));
            $applicationMedia = $applicationMedia->merge($driver->application->getMedia('signed_application'));
        }
        foreach (['signed_application', 'application_pdf', 'lease_agreement', 'contract_documents'] as $collection) {
            $applicationMedia = $applicationMedia->merge($driver->getMedia($collection));
        }
        if ($applicationMedia->count() > 0) {
            $categories['Application'] = $applicationMedia;
        }

        // W-9 Documents
        $w9Media = $driver->getMedia('w9_documents');
        if ($w9Media->count() > 0) {
            $categories['W-9'] = $w9Media;
        }

        // Other Documents
        $otherMedia = collect();
        foreach (['other', 'miscellaneous'] as $collection) {
            $otherMedia = $otherMedia->merge($driver->getMedia($collection));
        }
        if ($otherMedia->count() > 0) {
            $categories['Other'] = $otherMedia;
        }

        return $categories;
    }
}
