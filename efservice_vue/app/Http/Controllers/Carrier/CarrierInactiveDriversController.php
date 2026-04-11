<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Admin\Driver\ArchivedDriversController;
use App\Http\Controllers\Carrier\Concerns\ResolvesCarrierContext;
use App\Models\DriverArchive;
use App\Services\Driver\ArchiveDownloadService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CarrierInactiveDriversController extends ArchivedDriversController
{
    use ResolvesCarrierContext;

    public function __construct(protected ArchiveDownloadService $downloadService)
    {
    }

    public function index(Request $request): Response
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'carrier_id' => '',
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'archive_reason' => (string) $request->input('archive_reason', ''),
            'sort_field' => (string) $request->input('sort_field', 'archived_at'),
            'sort_direction' => (string) $request->input('sort_direction', 'desc'),
        ];

        $accessibleQuery = DriverArchive::query()
            ->archived()
            ->forCarrier($this->resolveCarrierId() ?? 0)
            ->with(['carrier:id,name', 'migrationRecord.targetCarrier:id,name']);

        $query = clone $accessibleQuery;

        if ($filters['search'] !== '') {
            $query->searchByName($filters['search']);
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('archived_at', '>=', $this->toDbDate($filters['date_from']));
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('archived_at', '<=', $this->toDbDate($filters['date_to']));
        }

        if ($filters['archive_reason'] !== '') {
            $query->byReason($filters['archive_reason']);
        }

        $archives = $query
            ->orderBy($filters['sort_field'] === 'created_at' ? 'created_at' : 'archived_at', $filters['sort_direction'] === 'asc' ? 'asc' : 'desc')
            ->paginate(15)
            ->withQueryString();

        $archives->through(fn (DriverArchive $archive) => $this->transformArchiveRow($archive));

        return Inertia::render('carrier/drivers/inactive/Index', [
            'archives' => $archives,
            'filters' => $filters,
            'carriers' => [],
            'archiveReasons' => [
                ['value' => DriverArchive::REASON_MIGRATION, 'label' => 'Migration'],
                ['value' => DriverArchive::REASON_TERMINATION, 'label' => 'Termination'],
                ['value' => DriverArchive::REASON_MANUAL, 'label' => 'Manual Archive'],
            ],
            'stats' => [
                'total' => (clone $accessibleQuery)->count(),
                'migration' => (clone $accessibleQuery)->where('archive_reason', DriverArchive::REASON_MIGRATION)->count(),
                'termination' => (clone $accessibleQuery)->where('archive_reason', DriverArchive::REASON_TERMINATION)->count(),
                'restored' => DriverArchive::query()->forCarrier($this->resolveCarrierId() ?? 0)->restored()->count(),
            ],
            'isSuperadmin' => false,
            'routeNames' => [
                'index' => 'carrier.drivers.inactive.index',
                'show' => 'carrier.drivers.inactive.show',
                'back' => 'carrier.drivers.index',
            ],
        ]);
    }

    public function show(DriverArchive $archive): Response
    {
        abort_unless($archive->canBeAccessedByCarrier($this->resolveCarrierId() ?? 0), 403);

        $archive->load([
            'carrier:id,name,dot_number,mc_number',
            'user:id,name,email',
            'originalDriverDetail.medicalQualification',
            'migrationRecord.targetCarrier:id,name',
            'migrationRecord.sourceCarrier:id,name',
            'migrationRecord.migratedByUser:id,name',
        ]);

        $documentsByCategory = collect($archive->getDocumentsByCategory())
            ->values()
            ->map(function (array $category) {
                return [
                    'category' => $category['category'],
                    'count' => (int) ($category['count'] ?? 0),
                    'documents' => collect($category['documents'] ?? [])->map(function (array $document) {
                        return [
                            'name' => $document['name'] ?? 'Document',
                            'url' => $document['url'] ?? null,
                            'size' => (int) ($document['size'] ?? 0),
                            'mime_type' => $document['mime_type'] ?? null,
                            'created_at' => $document['created_at'] ?? null,
                        ];
                    })->values()->all(),
                ];
            })
            ->all();

        $medicalDocuments = collect($documentsByCategory)->firstWhere('category', 'Medical');

        return Inertia::render('carrier/drivers/inactive/Detail', [
            'archive' => [
                'id' => $archive->id,
                'full_name' => $archive->full_name,
                'email' => $archive->email,
                'phone' => $archive->phone,
                'carrier_name' => $archive->carrier?->name,
                'carrier_dot' => $archive->carrier?->dot_number,
                'carrier_mc' => $archive->carrier?->mc_number,
                'archived_at' => $archive->archived_at?->toIso8601String(),
                'archive_reason' => $archive->archive_reason,
                'status' => $archive->status,
                'document_count' => $archive->getDocumentCount(),
                'profile_photo_url' => $archive->getFirstMediaUrl('archived_profile_photo') ?: ($archive->driver_data_snapshot['profile_photo_url'] ?? null),
            ],
            'sections' => [
                'personal' => $this->personalSnapshot($archive),
                'licenses' => $this->licensesSnapshot($archive),
                'medical' => $this->medicalSnapshot($archive),
                'medical_documents' => $medicalDocuments['documents'] ?? [],
                'employment' => $this->employmentSnapshot($archive),
                'training' => $this->trainingSnapshot($archive),
                'testing' => $this->testingSnapshot($archive),
                'safety' => [
                    'accidents' => $this->accidentsSnapshot($archive),
                    'convictions' => $this->convictionsSnapshot($archive),
                    'inspections' => $this->inspectionsSnapshot($archive),
                ],
                'hos' => $this->hosSnapshot($archive),
                'vehicles' => $this->vehiclesSnapshot($archive),
                'documents' => $documentsByCategory,
                'migration' => $this->migrationSnapshot($archive),
            ],
            'stats' => [
                'licenses' => count($this->licensesSnapshot($archive)),
                'medical' => count($this->medicalSnapshot($archive)),
                'employment' => count($this->employmentSnapshot($archive)),
                'training' => count($this->trainingSnapshot($archive)),
                'testing' => count($this->testingSnapshot($archive)),
                'safety' => count($this->accidentsSnapshot($archive)) + count($this->convictionsSnapshot($archive)) + count($this->inspectionsSnapshot($archive)),
            ],
            'routeNames' => [
                'index' => 'carrier.drivers.inactive.index',
                'download' => 'carrier.drivers.inactive.download',
            ],
        ]);
    }

    public function download(DriverArchive $archive): StreamedResponse
    {
        abort_unless($archive->canBeAccessedByCarrier($this->resolveCarrierId() ?? 0), 403);

        return $this->downloadService->streamArchiveZip($archive);
    }
}
