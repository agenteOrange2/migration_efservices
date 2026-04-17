<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\DriverDocumentStatus;
use App\Models\UserDriverDetail;
use App\Services\Driver\DriverNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class DriverDocumentController extends Controller
{
    private const UPLOAD_CATEGORIES = [
        'driving_records' => 'Driving Records',
        'medical_records' => 'Medical Records',
        'criminal_records' => 'Criminal Records',
        'clearing_house' => 'Clearing House',
        'other' => 'Other',
    ];

    public function index(): Response
    {
        return $this->renderIndexPage(false);
    }

    public function pending(): Response
    {
        return $this->renderIndexPage(true);
    }

    public function create(Request $request): Response
    {
        $driver = $this->resolveDriver();
        $selectedCategory = (string) $request->input('category', '');

        if (! array_key_exists($selectedCategory, self::UPLOAD_CATEGORIES)) {
            $selectedCategory = '';
        }

        return Inertia::render('driver/documents/Create', [
            'driver' => [
                'id' => $driver->id,
                'full_name' => $driver->full_name,
                'carrier_name' => $driver->carrier?->name,
            ],
            'selectedCategory' => $selectedCategory,
            'categories' => collect(self::UPLOAD_CATEGORIES)->map(
                fn (string $label, string $value) => ['value' => $value, 'label' => $label]
            )->values(),
            'requirements' => $this->documentRequirements($driver),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'category' => ['required', 'string', 'in:' . implode(',', array_keys(self::UPLOAD_CATEGORIES))],
        ]);

        $driver = $this->resolveDriver();

        $media = $driver->addMediaFromRequest('document')
            ->toMediaCollection($validated['category']);

        if (Schema::hasTable('driver_document_status')) {
            DriverDocumentStatus::query()->create([
                'driver_id' => $driver->id,
                'media_id' => $media->id,
                'category' => $validated['category'],
                'status' => 'pending',
            ]);
        }

        DriverNotificationService::notifyDocumentUploaded(
            $driver,
            $validated['category'],
            $request->file('document')?->getClientOriginalName()
        );

        return redirect()
            ->route('driver.documents.index')
            ->with('success', 'Document uploaded successfully.');
    }

    public function downloadAll(): RedirectResponse|BinaryFileResponse
    {
        $user = auth()->user();
        $key = 'driver-documents-download:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->with('error', 'Too many download attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.');
        }

        RateLimiter::hit($key, 3600);

        $driver = $this->loadDriverWithRelationships();
        $zipFileName = 'documents_' . $driver->id . '_' . now()->format('Y-m-d_His') . '.zip';
        $zipDirectory = storage_path('app/temp');
        $zipPath = $zipDirectory . DIRECTORY_SEPARATOR . $zipFileName;

        if (! is_dir($zipDirectory)) {
            mkdir($zipDirectory, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Could not create ZIP file.');
        }

        $filesAdded = 0;

        // Licenses
        foreach ($driver->licenses as $index => $license) {
            $folder = 'Licenses/License_' . ($index + 1);
            foreach (['license_front', 'license_back', 'license_documents'] as $collection) {
                foreach ($license->getMedia($collection) as $media) {
                    if (file_exists($media->getPath())) {
                        $zip->addFile($media->getPath(), "{$folder}/{$collection}/" . $this->sanitizeFileName($media->file_name));
                        $filesAdded++;
                    }
                }
            }
        }

        // Medical
        if ($driver->medicalQualification) {
            foreach (['medical_certificate', 'test_results', 'additional_documents', 'medical_documents', 'medical_card', 'social_security_card'] as $collection) {
                foreach ($driver->medicalQualification->getMedia($collection) as $media) {
                    if (file_exists($media->getPath())) {
                        $zip->addFile($media->getPath(), "Medical/{$collection}/" . $this->sanitizeFileName($media->file_name));
                        $filesAdded++;
                    }
                }
            }
        }

        // Training
        foreach ($driver->trainingSchools as $school) {
            foreach ($school->getMedia('school_certificates') as $media) {
                if (file_exists($media->getPath())) {
                    $slug = $school->school_name ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $school->school_name) : ('School_' . $school->id);
                    $zip->addFile($media->getPath(), "Training/{$slug}/" . $this->sanitizeFileName($media->file_name));
                    $filesAdded++;
                }
            }
        }
        foreach ($driver->courses as $course) {
            foreach ($course->getMedia('course_certificates') as $media) {
                if (file_exists($media->getPath())) {
                    $slug = $course->organization_name ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $course->organization_name) : ('Course_' . $course->id);
                    $zip->addFile($media->getPath(), "Training/{$slug}/" . $this->sanitizeFileName($media->file_name));
                    $filesAdded++;
                }
            }
        }

        // Testing
        foreach ($driver->testings as $index => $testing) {
            $folder = 'Testing/Test_' . ($index + 1);
            foreach (['drug_test_pdf', 'test_results', 'test_certificates', 'document_attachments'] as $collection) {
                foreach ($testing->getMedia($collection) as $media) {
                    if (file_exists($media->getPath())) {
                        $zip->addFile($media->getPath(), "{$folder}/{$collection}/" . $this->sanitizeFileName($media->file_name));
                        $filesAdded++;
                    }
                }
            }
        }

        // Inspections
        foreach ($driver->inspections as $index => $inspection) {
            foreach ($inspection->getMedia('inspection_documents') as $media) {
                if (file_exists($media->getPath())) {
                    $zip->addFile($media->getPath(), 'Inspections/Inspection_' . ($index + 1) . '/' . $this->sanitizeFileName($media->file_name));
                    $filesAdded++;
                }
            }
        }

        // Accidents
        foreach ($driver->accidents as $index => $accident) {
            foreach ($accident->getMedia('accident-images') as $media) {
                if (file_exists($media->getPath())) {
                    $zip->addFile($media->getPath(), 'Accidents/Accident_' . ($index + 1) . '/' . $this->sanitizeFileName($media->file_name));
                    $filesAdded++;
                }
            }
        }

        // Traffic convictions
        foreach ($driver->trafficConvictions as $index => $conviction) {
            foreach ($conviction->getMedia('traffic_images') as $media) {
                if (file_exists($media->getPath())) {
                    $zip->addFile($media->getPath(), 'Traffic/Conviction_' . ($index + 1) . '/' . $this->sanitizeFileName($media->file_name));
                    $filesAdded++;
                }
            }
        }

        // Records (direct uploads on driver)
        foreach (['driving_records', 'criminal_records', 'medical_records', 'clearing_house', 'dot_policy_documents'] as $collection) {
            foreach ($driver->getMedia($collection) as $media) {
                if (file_exists($media->getPath())) {
                    $zip->addFile($media->getPath(), "Records/{$collection}/" . $this->sanitizeFileName($media->file_name));
                    $filesAdded++;
                }
            }
        }

        // Application
        if ($driver->application) {
            foreach (['application_pdf', 'signed_application'] as $collection) {
                foreach ($driver->application->getMedia($collection) as $media) {
                    if (file_exists($media->getPath())) {
                        $zip->addFile($media->getPath(), "Application/{$collection}/" . $this->sanitizeFileName($media->file_name));
                        $filesAdded++;
                    }
                }
            }
        }
        foreach (['signed_application', 'application_pdf', 'lease_agreement', 'contract_documents', 'application_forms', 'individual_forms'] as $collection) {
            foreach ($driver->getMedia($collection) as $media) {
                if (file_exists($media->getPath())) {
                    $zip->addFile($media->getPath(), "Application/{$collection}/" . $this->sanitizeFileName($media->file_name));
                    $filesAdded++;
                }
            }
        }

        // Employment
        foreach ($driver->employmentCompanies as $index => $company) {
            foreach ($company->getMedia('employment_verification_documents') as $media) {
                if (file_exists($media->getPath())) {
                    $slug = $company->company_name ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $company->company_name) : ('Company_' . ($index + 1));
                    $zip->addFile($media->getPath(), "Employment/{$slug}/" . $this->sanitizeFileName($media->file_name));
                    $filesAdded++;
                }
            }
        }

        // Other direct uploads
        foreach (['other', 'miscellaneous', 'w9_documents'] as $collection) {
            foreach ($driver->getMedia($collection) as $media) {
                if (file_exists($media->getPath())) {
                    $zip->addFile($media->getPath(), "Other/{$collection}/" . $this->sanitizeFileName($media->file_name));
                    $filesAdded++;
                }
            }
        }

        $zip->close();

        if ($filesAdded === 0) {
            @unlink($zipPath);

            return back()->with('error', 'No documents available to download.');
        }

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    private function sanitizeFileName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_.\-]/', '_', $name);
    }

    public function destroy(Media $media): RedirectResponse
    {
        $driver = $this->resolveDriver();

        abort_unless(
            $media->model_type === UserDriverDetail::class
            && (int) $media->model_id === (int) $driver->id
            && array_key_exists($media->collection_name, self::UPLOAD_CATEGORIES),
            403,
            'You do not have permission to delete this document.'
        );

        if (Schema::hasTable('driver_document_status')) {
            DriverDocumentStatus::query()
                ->where('driver_id', $driver->id)
                ->where('media_id', $media->id)
                ->delete();
        }

        $media->delete();

        return back()->with('success', 'Document deleted successfully.');
    }

    private function renderIndexPage(bool $pendingMode): Response
    {
        $driver = $this->loadDriverWithRelationships();
        $documentStatuses = Schema::hasTable('driver_document_status')
            ? DriverDocumentStatus::query()->where('driver_id', $driver->id)->get()->keyBy('media_id')
            : collect();

        $categories = $this->buildDocumentCategories($driver, $documentStatuses);
        $totalDocuments = $categories->sum('count');
        $requirements = $this->documentRequirements($driver);

        return Inertia::render('driver/documents/Index', [
            'driver' => [
                'id' => $driver->id,
                'full_name' => $driver->full_name,
                'carrier_name' => $driver->carrier?->name,
            ],
            'pendingMode' => $pendingMode,
            'stats' => [
                'total_documents' => $totalDocuments,
                'categories_count' => $categories->count(),
                'direct_uploads' => collect($requirements)->sum('uploaded_count'),
                'deletable_documents' => $categories->sum(fn (array $category) => collect($category['documents'])->where('can_delete', true)->count()),
            ],
            'requirements' => $requirements,
            'categories' => $categories->values(),
        ]);
    }

    private function resolveDriver(): UserDriverDetail
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        $driver = $user?->driverDetails ?? $user?->driverDetail;

        abort_unless($driver, 403, 'No driver profile associated with this account.');

        $driver->loadMissing(['user:id,name,email', 'carrier:id,name']);

        return $driver;
    }

    private function loadDriverWithRelationships(): UserDriverDetail
    {
        $driver = $this->resolveDriver();

        return $driver->load([
            'licenses' => fn ($query) => $query->latest('expiration_date'),
            'medicalQualification',
            'trainingSchools' => fn ($query) => $query->latest('date_end'),
            'courses' => fn ($query) => $query->latest('certification_date'),
            'testings' => fn ($query) => $query->latest('test_date'),
            'inspections' => fn ($query) => $query->latest('inspection_date'),
            'accidents',
            'trafficConvictions',
            'employmentCompanies',
            'application',
        ]);
    }

    private function documentRequirements(UserDriverDetail $driver): array
    {
        return collect(self::UPLOAD_CATEGORIES)->map(function (string $label, string $collection) use ($driver) {
            $count = $driver->getMedia($collection)->count();

            return [
                'collection' => $collection,
                'label' => $label,
                'uploaded_count' => $count,
                'is_complete' => $count > 0,
                'upload_url' => route('driver.documents.create', ['category' => $collection]),
            ];
        })->values()->all();
    }

    private function buildDocumentCategories(UserDriverDetail $driver, $documentStatuses)
    {
        $categories = collect();

        $licenseDocuments = collect();
        foreach ($driver->licenses as $license) {
            $licenseDocuments = $licenseDocuments
                ->merge($license->getMedia('license_front'))
                ->merge($license->getMedia('license_back'))
                ->merge($license->getMedia('license_documents'));
        }
        $this->pushCategory($categories, 'Licenses', $licenseDocuments, $documentStatuses);

        if ($driver->medicalQualification) {
            $medicalDocuments = collect();
            foreach (['medical_certificate', 'test_results', 'additional_documents', 'medical_documents', 'medical_card', 'social_security_card'] as $collection) {
                $medicalDocuments = $medicalDocuments->merge($driver->medicalQualification->getMedia($collection));
            }
            $this->pushCategory($categories, 'Medical', $medicalDocuments, $documentStatuses);
        }

        $trainingDocuments = collect();
        foreach ($driver->trainingSchools as $school) {
            $trainingDocuments = $trainingDocuments->merge($school->getMedia('school_certificates'));
        }
        foreach ($driver->courses as $course) {
            $trainingDocuments = $trainingDocuments->merge($course->getMedia('course_certificates'));
        }
        $this->pushCategory($categories, 'Training', $trainingDocuments, $documentStatuses);

        $testingDocuments = collect();
        foreach ($driver->testings as $testing) {
            foreach (['drug_test_pdf', 'test_results', 'test_certificates', 'document_attachments'] as $collection) {
                $testingDocuments = $testingDocuments->merge($testing->getMedia($collection));
            }
        }
        $this->pushCategory($categories, 'Testing', $testingDocuments, $documentStatuses);

        $inspectionDocuments = collect();
        foreach ($driver->inspections as $inspection) {
            $inspectionDocuments = $inspectionDocuments->merge($inspection->getMedia('inspection_documents'));
        }
        $this->pushCategory($categories, 'Inspections', $inspectionDocuments, $documentStatuses);

        $accidentDocuments = collect();
        foreach ($driver->accidents as $accident) {
            $accidentDocuments = $accidentDocuments->merge($accident->getMedia('accident-images'));
        }
        $this->pushCategory($categories, 'Accidents', $accidentDocuments, $documentStatuses);

        $trafficDocuments = collect();
        foreach ($driver->trafficConvictions as $conviction) {
            $trafficDocuments = $trafficDocuments->merge($conviction->getMedia('traffic_images'));
        }
        $this->pushCategory($categories, 'Traffic', $trafficDocuments, $documentStatuses);

        $recordsDocuments = collect();
        foreach (['driving_records', 'criminal_records', 'medical_records', 'clearing_house', 'dot_policy_documents'] as $collection) {
            $recordsDocuments = $recordsDocuments->merge($driver->getMedia($collection));
        }
        $this->pushCategory($categories, 'Records', $recordsDocuments, $documentStatuses);

        $applicationDocuments = collect();
        if ($driver->application) {
            $applicationDocuments = $applicationDocuments
                ->merge($driver->application->getMedia('application_pdf'))
                ->merge($driver->application->getMedia('signed_application'));
        }
        foreach (['signed_application', 'application_pdf', 'lease_agreement', 'contract_documents', 'application_forms', 'individual_forms'] as $collection) {
            $applicationDocuments = $applicationDocuments->merge($driver->getMedia($collection));
        }
        $this->pushCategory($categories, 'Application', $applicationDocuments, $documentStatuses);

        $employmentDocuments = collect();
        foreach ($driver->employmentCompanies as $company) {
            $employmentDocuments = $employmentDocuments->merge($company->getMedia('employment_verification_documents'));
        }
        $this->pushCategory($categories, 'Employment', $employmentDocuments, $documentStatuses);

        $otherDocuments = collect();
        foreach (['other', 'miscellaneous', 'w9_documents'] as $collection) {
            $otherDocuments = $otherDocuments->merge($driver->getMedia($collection));
        }
        $this->pushCategory($categories, 'Other', $otherDocuments, $documentStatuses);

        return $categories;
    }

    private function pushCategory($categories, string $label, $media, $documentStatuses): void
    {
        if ($media->isEmpty()) {
            return;
        }

        $categories->push([
            'label' => $label,
            'count' => $media->count(),
            'documents' => $media->map(fn (Media $item) => $this->mapMedia($item, $documentStatuses))->values()->all(),
        ]);
    }

    private function mapMedia(Media $media, $documentStatuses): array
    {
        $status = $documentStatuses->get($media->id);
        $canDelete = $media->model_type === UserDriverDetail::class
            && array_key_exists($media->collection_name, self::UPLOAD_CATEGORIES);

        return [
            'id' => $media->id,
            'name' => $media->file_name,
            'url' => $media->getUrl(),
            'size' => $media->human_readable_size,
            'mime_type' => $media->mime_type,
            'created_at' => optional($media->created_at)->format('M d, Y'),
            'collection_name' => $media->collection_name,
            'status' => $status?->status,
            'expiry_date' => $status?->expiry_date?->format('n/j/Y'),
            'notes' => $status?->notes,
            'can_delete' => $canDelete,
            'delete_url' => $canDelete ? route('driver.documents.destroy', $media->id) : null,
        ];
    }
}
