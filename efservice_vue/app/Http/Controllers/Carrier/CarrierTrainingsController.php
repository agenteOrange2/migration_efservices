<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Admin\TrainingsController;
use App\Http\Controllers\Carrier\Concerns\ResolvesCarrierContext;
use App\Models\Admin\Driver\DriverTraining;
use App\Models\Admin\Driver\Training;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CarrierTrainingsController extends TrainingsController
{
    use ResolvesCarrierContext;

    public function index(Request $request): Response
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'status' => (string) $request->input('status', ''),
            'content_type' => (string) $request->input('content_type', ''),
            'sort' => (string) $request->input('sort', 'created_at'),
            'direction' => (string) $request->input('direction', 'desc'),
        ];

        $query = Training::query()
            ->withCount('driverAssignments');

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('title', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['content_type'] !== '') {
            $query->where('content_type', $filters['content_type']);
        }

        $allowedSorts = ['id', 'title', 'created_at', 'status', 'content_type'];
        $sortField = in_array($filters['sort'], $allowedSorts, true) ? $filters['sort'] : 'created_at';
        $direction = $filters['direction'] === 'asc' ? 'asc' : 'desc';

        $trainings = $query
            ->orderBy($sortField, $direction)
            ->paginate(15)
            ->withQueryString();

        $trainings->through(fn (Training $training) => [
            'id' => $training->id,
            'title' => $training->title,
            'description' => $training->description,
            'content_type' => $training->content_type,
            'status' => $training->status,
            'created_at' => $training->created_at?->format('n/j/Y'),
            'assignments_count' => (int) $training->driver_assignments_count,
            'documents_count' => $training->getMedia('training_files')->count(),
        ]);

        return Inertia::render('carrier/trainings/Index', [
            'trainings' => $trainings,
            'filters' => $filters,
            'stats' => [
                'total' => Training::count(),
                'active' => Training::query()->where('status', 'active')->count(),
                'inactive' => Training::query()->where('status', 'inactive')->count(),
                'assignments' => DriverTraining::query()
                    ->whereHas('driver', fn ($query) => $query->where('carrier_id', $this->resolveCarrierId()))
                    ->count(),
            ],
            'routeNames' => $this->routeNames(),
            'isCarrierContext' => true,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('carrier/trainings/Create', [
            'routeNames' => $this->routeNames(),
            'isCarrierContext' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        DB::transaction(function () use ($request, $validated) {
            $training = Training::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'content_type' => $validated['content_type'],
                'status' => $validated['status'],
                'video_url' => $validated['content_type'] === 'video' ? ($validated['video_url'] ?? null) : null,
                'url' => $validated['content_type'] === 'url' ? ($validated['url'] ?? null) : null,
                'created_by' => auth()->id(),
            ]);

            $this->syncMedia($training, $request);
        });

        return redirect()
            ->route('carrier.trainings.index')
            ->with('success', 'Training created successfully.');
    }

    public function show(Training $training): Response
    {
        $carrierId = (int) $this->resolveCarrierId();

        $training->load([
            'creator:id,name',
            'media',
            'driverAssignments' => fn ($query) => $query->whereHas('driver', fn ($driverQuery) => $driverQuery->where('carrier_id', $carrierId)),
            'driverAssignments.driver.user:id,name,email',
            'driverAssignments.driver.carrier:id,name',
        ]);

        return Inertia::render('carrier/trainings/Show', [
            'training' => [
                'id' => $training->id,
                'title' => $training->title,
                'description' => $training->description,
                'content_type' => $training->content_type,
                'status' => $training->status,
                'video_url' => $training->video_url,
                'url' => $training->url,
                'creator_name' => $training->creator?->name,
                'created_at' => $training->created_at?->format('n/j/Y g:i A'),
                'updated_at' => $training->updated_at?->format('n/j/Y g:i A'),
                'documents' => $training->getMedia('training_files')->map(fn (Media $media) => [
                    'id' => $media->id,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size_label' => $media->human_readable_size,
                    'preview_url' => $media->getUrl(),
                    'created_at_display' => $media->created_at?->format('n/j/Y g:i A'),
                ])->values(),
            ],
            'assignmentStats' => $this->carrierAssignmentStats($training, $carrierId),
            'recentAssignments' => $training->driverAssignments
                ->sortByDesc('created_at')
                ->take(8)
                ->map(fn ($assignment) => [
                    'id' => $assignment->id,
                    'driver_name' => trim((string) $assignment->driver?->full_name) ?: 'N/A',
                    'driver_email' => $assignment->driver?->user?->email,
                    'carrier_name' => $assignment->driver?->carrier?->name,
                    'status' => $this->displayAssignmentStatus($assignment),
                    'status_label' => $this->assignmentStatusLabel($this->displayAssignmentStatus($assignment)),
                    'assigned_date' => $assignment->assigned_date?->format('n/j/Y'),
                    'due_date' => $assignment->due_date?->format('n/j/Y'),
                ])
                ->values(),
            'assignmentFormOptions' => [
                'carriers' => [$this->carrierOption()],
                'drivers' => $this->carrierDriverOptions(),
                'selectedTraining' => [
                    'id' => $training->id,
                    'title' => $training->title,
                ],
            ],
            'routeNames' => $this->routeNames(),
            'assignmentRouteNames' => $this->assignmentRouteNames(),
            'carrier' => $this->carrierOption(),
            'isCarrierContext' => true,
        ]);
    }

    public function edit(Training $training): Response
    {
        $training->load('media');

        return Inertia::render('carrier/trainings/Edit', [
            'training' => [
                'id' => $training->id,
                'title' => $training->title,
                'description' => $training->description,
                'content_type' => $training->content_type,
                'status' => $training->status,
                'video_url' => $training->video_url,
                'url' => $training->url,
                'documents' => $training->getMedia('training_files')->map(fn (Media $media) => [
                    'id' => $media->id,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size_label' => $media->human_readable_size,
                    'preview_url' => $media->getUrl(),
                    'created_at_display' => $media->created_at?->format('n/j/Y g:i A'),
                ])->values(),
            ],
            'routeNames' => $this->routeNames(),
            'isCarrierContext' => true,
        ]);
    }

    public function update(Request $request, Training $training): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        DB::transaction(function () use ($request, $validated, $training) {
            $training->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'content_type' => $validated['content_type'],
                'status' => $validated['status'],
                'video_url' => $validated['content_type'] === 'video' ? ($validated['video_url'] ?? null) : null,
                'url' => $validated['content_type'] === 'url' ? ($validated['url'] ?? null) : null,
            ]);

            $this->syncMedia($training, $request);
        });

        return redirect()
            ->route('carrier.trainings.show', $training)
            ->with('success', 'Training updated successfully.');
    }

    public function destroy(Training $training): RedirectResponse
    {
        $assignmentsCount = $training->driverAssignments()->count();

        if ($assignmentsCount > 0) {
            return back()->with('error', "Cannot delete training. It has {$assignmentsCount} assignments.");
        }

        DB::transaction(function () use ($training) {
            $training->clearMediaCollection('training_files');
            $training->delete();
        });

        return redirect()
            ->route('carrier.trainings.index')
            ->with('success', 'Training deleted successfully.');
    }

    public function destroyMedia(Media $media): RedirectResponse
    {
        abort_unless(
            $media->model_type === Training::class
            && $media->collection_name === 'training_files',
            404
        );

        $media->delete();

        return back()->with('success', 'Training document deleted successfully.');
    }

    protected function carrierAssignmentStats(Training $training, int $carrierId): array
    {
        $baseQuery = $training->driverAssignments()
            ->whereHas('driver', fn ($query) => $query->where('carrier_id', $carrierId));

        return [
            'total' => (clone $baseQuery)->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'pending' => (clone $baseQuery)
                ->where('status', 'assigned')
                ->where(function ($query) {
                    $query
                        ->whereNull('due_date')
                        ->orWhere('due_date', '>=', now()->startOfDay());
                })
                ->count(),
            'overdue' => (clone $baseQuery)
                ->where(function ($query) {
                    $query
                        ->where('status', 'overdue')
                        ->orWhere(function ($innerQuery) {
                            $innerQuery
                                ->whereIn('status', ['assigned', 'in_progress'])
                                ->whereNotNull('due_date')
                                ->where('due_date', '<', now()->startOfDay());
                        });
                })
                ->count(),
        ];
    }

    protected function carrierDriverOptions()
    {
        return $this->driverOptions()
            ->where('carrier_id', $this->resolveCarrierId())
            ->values();
    }

    protected function routeNames(): array
    {
        return [
            'index' => 'carrier.trainings.index',
            'create' => 'carrier.trainings.create',
            'store' => 'carrier.trainings.store',
            'show' => 'carrier.trainings.show',
            'edit' => 'carrier.trainings.edit',
            'update' => 'carrier.trainings.update',
            'destroy' => 'carrier.trainings.destroy',
            'mediaDestroy' => 'carrier.trainings.media.destroy',
            'assignSelect' => 'carrier.trainings.assign.select',
            'assignForm' => 'carrier.trainings.assign.form',
            'assign' => 'carrier.trainings.assign',
        ];
    }

    protected function assignmentRouteNames(): array
    {
        return [
            'store' => 'carrier.trainings.assign',
            'index' => 'carrier.trainings.assign.select',
        ];
    }

    protected function carrierOption(): array
    {
        $carrier = $this->resolveCarrier();

        return [
            'id' => $carrier->id,
            'name' => $carrier->name,
        ];
    }
}
