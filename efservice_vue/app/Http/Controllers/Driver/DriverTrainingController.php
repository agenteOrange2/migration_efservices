<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverTraining;
use App\Models\Admin\Driver\Training;
use App\Models\UserDriverDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DriverTrainingController extends Controller
{
    public function index(Request $request): Response
    {
        $driver = $this->resolveDriver();
        $this->syncOverdueAssignments($driver);

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'status' => (string) $request->input('status', ''),
        ];

        $query = DriverTraining::query()
            ->where('user_driver_detail_id', $driver->id)
            ->with(['training:id,title,description,content_type,video_url,url,status', 'training.media']);

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';

            $query->whereHas('training', function ($trainingQuery) use ($search) {
                $trainingQuery
                    ->where('title', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        }

        if ($filters['status'] !== '') {
            if ($filters['status'] === 'pending') {
                $query->where('status', 'assigned');
            } else {
                $query->where('status', $filters['status']);
            }
        }

        $assignments = $query
            ->orderByRaw("CASE
                WHEN status = 'overdue' THEN 1
                WHEN status = 'in_progress' THEN 2
                WHEN status = 'assigned' THEN 3
                WHEN status = 'completed' THEN 4
                ELSE 5 END")
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        $assignments->through(fn (DriverTraining $assignment) => $this->transformAssignmentSummary($assignment));

        return Inertia::render('driver/trainings/Index', [
            'driver' => [
                'id' => $driver->id,
                'full_name' => $driver->full_name,
                'carrier_name' => $driver->carrier?->name,
            ],
            'filters' => $filters,
            'stats' => $this->trainingStats($driver),
            'trainings' => $assignments,
        ]);
    }

    public function show(DriverTraining $assignment): Response
    {
        $driver = $this->resolveDriver();
        $this->authorizeAssignment($driver, $assignment);
        $this->refreshSingleAssignmentStatus($assignment);

        $assignment->load([
            'training.creator:id,name',
            'training.media',
        ]);

        return Inertia::render('driver/trainings/Show', [
            'driver' => [
                'id' => $driver->id,
                'full_name' => $driver->full_name,
                'carrier_name' => $driver->carrier?->name,
            ],
            'assignment' => $this->transformAssignmentDetail($assignment),
        ]);
    }

    public function startProgress(DriverTraining $assignment): RedirectResponse
    {
        $driver = $this->resolveDriver();
        $this->authorizeAssignment($driver, $assignment);
        $this->refreshSingleAssignmentStatus($assignment);

        if ($assignment->status === 'completed') {
            return redirect()
                ->route('driver.trainings.show', $assignment)
                ->with('info', 'This training has already been completed.');
        }

        if (in_array($assignment->status, ['assigned', 'overdue'], true)) {
            $assignment->update(['status' => 'in_progress']);
        }

        return redirect()
            ->route('driver.trainings.show', $assignment)
            ->with('success', 'Training marked as in progress.');
    }

    public function complete(Request $request, DriverTraining $assignment): RedirectResponse
    {
        $driver = $this->resolveDriver();
        $this->authorizeAssignment($driver, $assignment);
        $this->refreshSingleAssignmentStatus($assignment);

        $validated = $request->validate([
            'confirmed' => ['required', 'accepted'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($assignment->status === 'completed') {
            return redirect()
                ->route('driver.trainings.show', $assignment)
                ->with('info', 'This training has already been completed.');
        }

        $assignment->update([
            'status' => 'completed',
            'completed_date' => now(),
            'completion_notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('driver.trainings.show', $assignment)
            ->with('success', 'Training marked as completed.');
    }

    public function previewDocument(Request $request, Media $media): BinaryFileResponse
    {
        $driver = $this->resolveDriver();

        abort_unless($media->model_type === Training::class, 404);

        $hasAccess = DriverTraining::query()
            ->where('user_driver_detail_id', $driver->id)
            ->where('training_id', $media->model_id)
            ->exists();

        abort_unless($hasAccess, 403, 'You do not have access to this document.');
        abort_unless(file_exists($media->getPath()), 404, 'Document file not found.');

        if ($request->boolean('download')) {
            return response()->download(
                $media->getPath(),
                $media->file_name,
                ['Content-Type' => $media->mime_type]
            );
        }

        return response()->file($media->getPath(), [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
        ]);
    }

    protected function resolveDriver(): UserDriverDetail
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        $driver = $user?->driverDetails ?? $user?->driverDetail;

        abort_unless($driver, 403, 'No driver profile associated with this account.');

        $driver->loadMissing('carrier:id,name');

        return $driver;
    }

    protected function authorizeAssignment(UserDriverDetail $driver, DriverTraining $assignment): void
    {
        abort_unless((int) $assignment->user_driver_detail_id === (int) $driver->id, 403, 'Unauthorized access to this training assignment.');
    }

    protected function syncOverdueAssignments(UserDriverDetail $driver): void
    {
        DriverTraining::query()
            ->where('user_driver_detail_id', $driver->id)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->update(['status' => 'overdue']);
    }

    protected function refreshSingleAssignmentStatus(DriverTraining $assignment): void
    {
        if (
            in_array($assignment->status, ['assigned', 'in_progress'], true)
            && $assignment->due_date
            && $assignment->due_date->lt(now())
        ) {
            $assignment->update(['status' => 'overdue']);
            $assignment->refresh();
        }
    }

    protected function trainingStats(UserDriverDetail $driver): array
    {
        $query = DriverTraining::query()->where('user_driver_detail_id', $driver->id);

        return [
            'total' => (clone $query)->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'pending' => (clone $query)->where('status', 'assigned')->count(),
            'overdue' => (clone $query)->where('status', 'overdue')->count(),
            'completion_percentage' => $query->count() > 0
                ? (int) round(((clone $query)->where('status', 'completed')->count() / (clone $query)->count()) * 100)
                : 0,
        ];
    }

    protected function transformAssignmentSummary(DriverTraining $assignment): array
    {
        $assignment->loadMissing('training.media');

        $status = $this->resolvedStatus($assignment);

        return [
            'id' => $assignment->id,
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'assigned_date' => $assignment->assigned_date?->format('n/j/Y'),
            'due_date' => $assignment->due_date?->format('n/j/Y'),
            'completed_date' => $assignment->completed_date?->format('n/j/Y'),
            'is_overdue' => $status === 'overdue',
            'can_start' => in_array($status, ['assigned', 'overdue'], true),
            'can_complete' => $status === 'in_progress',
            'training' => [
                'id' => $assignment->training?->id,
                'title' => $assignment->training?->title ?? 'Training',
                'description' => $assignment->training?->description,
                'content_type' => $assignment->training?->content_type,
                'status' => $assignment->training?->status,
                'documents_count' => $assignment->training?->getMedia('training_files')->count() ?? 0,
            ],
        ];
    }

    protected function transformAssignmentDetail(DriverTraining $assignment): array
    {
        $assignment->loadMissing('training.creator', 'training.media');

        $status = $this->resolvedStatus($assignment);
        $training = $assignment->training;

        return [
            'id' => $assignment->id,
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'assigned_date' => $assignment->assigned_date?->format('n/j/Y'),
            'due_date' => $assignment->due_date?->format('n/j/Y'),
            'completed_date' => $assignment->completed_date?->format('n/j/Y g:i A'),
            'completion_notes' => $assignment->completion_notes,
            'can_start' => in_array($status, ['assigned', 'overdue'], true),
            'can_complete' => $status === 'in_progress',
            'training' => [
                'id' => $training?->id,
                'title' => $training?->title ?? 'Training',
                'description' => $training?->description,
                'content_type' => $training?->content_type,
                'status' => $training?->status,
                'video_url' => $training?->video_url,
                'url' => $training?->url,
                'creator_name' => $training?->creator?->name,
                'documents' => $training
                    ? $training->getMedia('training_files')->map(fn (Media $media) => [
                        'id' => $media->id,
                        'file_name' => $media->file_name,
                        'mime_type' => $media->mime_type,
                        'size_label' => $media->human_readable_size,
                        'file_type' => $this->resolveFileType($media),
                        'created_at' => $media->created_at?->format('n/j/Y g:i A'),
                        'preview_url' => route('driver.trainings.documents.preview', $media),
                        'download_url' => route('driver.trainings.documents.preview', ['media' => $media->id, 'download' => 1]),
                    ])->values()
                    : [],
            ],
        ];
    }

    protected function resolvedStatus(DriverTraining $assignment): string
    {
        if (
            $assignment->status !== 'completed'
            && $assignment->due_date
            && $assignment->due_date->lt(now())
        ) {
            return 'overdue';
        }

        return (string) $assignment->status;
    }

    protected function statusLabel(string $status): string
    {
        return match ($status) {
            'assigned' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'overdue' => 'Overdue',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    protected function resolveFileType(Media $media): string
    {
        $mimeType = strtolower((string) $media->mime_type);

        return match (true) {
            str_contains($mimeType, 'pdf') => 'pdf',
            str_contains($mimeType, 'image') => 'image',
            str_contains($mimeType, 'video') => 'video',
            str_contains($mimeType, 'word'),
            str_contains($mimeType, 'officedocument') => 'document',
            default => pathinfo($media->file_name, PATHINFO_EXTENSION) ?: 'file',
        };
    }
}
