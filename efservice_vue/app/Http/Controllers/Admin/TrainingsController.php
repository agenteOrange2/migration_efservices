<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverApplication;
use App\Models\Admin\Driver\DriverTraining;
use App\Models\Admin\Driver\Training;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TrainingsController extends Controller
{
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

        return Inertia::render('admin/trainings/Index', [
            'trainings' => $trainings,
            'filters' => $filters,
            'stats' => [
                'total' => Training::count(),
                'active' => Training::query()->where('status', 'active')->count(),
                'inactive' => Training::query()->where('status', 'inactive')->count(),
                'assignments' => DB::table('driver_trainings')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/trainings/Create');
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
            ->route('admin.trainings.index')
            ->with('success', 'Training created successfully.');
    }

    public function show(Training $training): Response
    {
        $training->load([
            'creator:id,name',
            'media',
            'driverAssignments.driver.user:id,name,email',
            'driverAssignments.driver.carrier:id,name',
        ]);

        return Inertia::render('admin/trainings/Show', [
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
            'assignmentStats' => $this->trainingAssignmentStats($training),
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
                'carriers' => $this->carrierOptions(),
                'drivers' => $this->driverOptions(),
                'selectedTraining' => [
                    'id' => $training->id,
                    'title' => $training->title,
                ],
            ],
        ]);
    }

    public function edit(Training $training): Response
    {
        $training->load('media');

        return Inertia::render('admin/trainings/Edit', [
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
            ->route('admin.trainings.index')
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
            ->route('admin.trainings.index')
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

    protected function validatePayload(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'content_type' => ['required', 'in:file,video,url'],
            'status' => ['required', 'in:active,inactive'],
            'video_url' => ['nullable', 'url', 'required_if:content_type,video'],
            'url' => ['nullable', 'url', 'required_if:content_type,url'],
            'training_files' => ['nullable', 'array'],
            'training_files.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx,ppt,pptx,mp4,mov', 'max:20480'],
        ]);
    }

    protected function syncMedia(Training $training, Request $request): void
    {
        foreach ($request->file('training_files', []) as $file) {
            $training->addMedia($file)
                ->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                ->toMediaCollection('training_files');
        }
    }

    protected function trainingAssignmentStats(Training $training): array
    {
        $baseQuery = $training->driverAssignments();

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

    protected function displayAssignmentStatus(DriverTraining $assignment): string
    {
        if (
            $assignment->due_date
            && ! in_array($assignment->status, ['completed', 'overdue'], true)
            && now()->gt($assignment->due_date)
        ) {
            return 'overdue';
        }

        return (string) $assignment->status;
    }

    protected function assignmentStatusLabel(string $status): string
    {
        return match ($status) {
            'assigned' => 'Assigned',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'overdue' => 'Overdue',
            default => ucfirst($status),
        };
    }

    protected function carrierOptions()
    {
        return Carrier::query()
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Carrier $carrier) => [
                'id' => $carrier->id,
                'name' => $carrier->name,
            ]);
    }

    protected function driverOptions()
    {
        return UserDriverDetail::query()
            ->with(['user:id,name,email', 'carrier:id,name'])
            ->where('status', UserDriverDetail::STATUS_ACTIVE)
            ->whereHas('application', function ($query) {
                $query->where('status', DriverApplication::STATUS_APPROVED);
            })
            ->orderBy('carrier_id')
            ->orderBy('last_name')
            ->get()
            ->map(fn (UserDriverDetail $driver) => [
                'id' => $driver->id,
                'carrier_id' => $driver->carrier_id,
                'carrier_name' => $driver->carrier?->name,
                'name' => $driver->full_name ?: 'N/A',
                'email' => $driver->user?->email,
            ]);
    }
}
