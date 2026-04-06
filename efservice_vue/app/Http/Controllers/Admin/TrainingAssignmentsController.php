<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverApplication;
use App\Models\Admin\Driver\DriverTraining;
use App\Models\Admin\Driver\Training;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TrainingAssignmentsController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'status' => (string) $request->input('status', ''),
            'carrier_id' => (string) $request->input('carrier_id', ''),
            'training_id' => (string) $request->input('training_id', ''),
        ];

        $query = DriverTraining::query()
            ->with([
                'driver.user:id,name,email',
                'driver.carrier:id,name',
                'training:id,title,content_type,status,description,video_url,url,created_by',
                'training.creator:id,name',
            ]);

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($builder) use ($search) {
                $builder
                    ->whereHas('training', fn ($trainingQuery) => $trainingQuery->where('title', 'like', $search))
                    ->orWhereHas('driver.user', function ($userQuery) use ($search) {
                        $userQuery
                            ->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search);
                    })
                    ->orWhereHas('driver', function ($driverQuery) use ($search) {
                        $driverQuery
                            ->where('middle_name', 'like', $search)
                            ->orWhere('last_name', 'like', $search)
                            ->orWhere('phone', 'like', $search);
                    });
            });
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['carrier_id'] !== '') {
            $query->whereHas('driver', fn ($driverQuery) => $driverQuery->where('carrier_id', $filters['carrier_id']));
        }

        if ($filters['training_id'] !== '') {
            $query->where('training_id', $filters['training_id']);
        }

        $assignments = $query
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        $assignments->through(fn (DriverTraining $assignment) => $this->transformAssignment($assignment));

        return Inertia::render('admin/training-assignments/Index', [
            'assignments' => $assignments,
            'filters' => $filters,
            'carriers' => $this->carrierOptions(),
            'trainings' => $this->trainingOptions(),
            'stats' => $this->assignmentStats(),
        ]);
    }

    public function create(Request $request): Response
    {
        $selectedTraining = null;

        if ($request->filled('training_id')) {
            $training = Training::query()->find($request->integer('training_id'));

            if ($training) {
                $selectedTraining = [
                    'id' => $training->id,
                    'title' => $training->title,
                ];
            }
        }

        return Inertia::render('admin/training-assignments/Create', [
            'trainings' => $this->trainingOptions(),
            'carriers' => $this->carrierOptions(),
            'drivers' => $this->driverOptions(),
            'selectedTraining' => $selectedTraining,
        ]);
    }

    public function createForTraining(Training $training): Response
    {
        return Inertia::render('admin/training-assignments/Create', [
            'trainings' => $this->trainingOptions(),
            'carriers' => $this->carrierOptions(),
            'drivers' => $this->driverOptions(),
            'selectedTraining' => [
                'id' => $training->id,
                'title' => $training->title,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'training_id' => ['required', 'exists:trainings,id'],
            'driver_ids' => ['required', 'array', 'min:1'],
            'driver_ids.*' => ['integer', 'exists:user_driver_details,id'],
            'due_date' => ['nullable', 'date_format:n/j/Y'],
            'status' => ['required', 'in:assigned,in_progress,completed'],
            'notes' => ['nullable', 'string'],
            'redirect_to' => ['nullable', 'in:assignments,training'],
        ]);

        $training = Training::query()->findOrFail($validated['training_id']);
        $dueDate = ! empty($validated['due_date'])
            ? Carbon::createFromFormat('n/j/Y', $validated['due_date'])->format('Y-m-d')
            : null;

        $assignedCount = 0;
        $alreadyAssignedCount = 0;

        DB::transaction(function () use ($validated, $dueDate, &$assignedCount, &$alreadyAssignedCount) {
            foreach ($validated['driver_ids'] as $driverId) {
                $existing = DriverTraining::query()->where([
                    'user_driver_detail_id' => $driverId,
                    'training_id' => $validated['training_id'],
                ])->first();

                if ($existing) {
                    $alreadyAssignedCount++;
                    continue;
                }

                $status = $validated['status'];
                $completedAt = $status === 'completed' ? now() : null;

                DriverTraining::query()->create([
                    'user_driver_detail_id' => $driverId,
                    'training_id' => $validated['training_id'],
                    'assigned_date' => now(),
                    'due_date' => $dueDate,
                    'status' => $status,
                    'completed_date' => $completedAt,
                    'assigned_by' => auth()->id(),
                    'completion_notes' => $validated['notes'] ?? null,
                ]);
                $assignedCount++;
            }
        });

        $redirect = $request->input('redirect_to') === 'training'
            ? route('admin.trainings.show', $training)
            : route('admin.training-assignments.index');

        $message = "{$assignedCount} driver(s) assigned successfully.";

        if ($alreadyAssignedCount > 0) {
            $message .= " {$alreadyAssignedCount} assignment(s) were skipped because they already existed.";
        }

        return redirect($redirect)->with('success', $message);
    }

    public function storeForTraining(Request $request, Training $training): RedirectResponse
    {
        $request->merge([
            'training_id' => $training->id,
            'redirect_to' => 'training',
        ]);

        return $this->store($request);
    }

    public function show(DriverTraining $training_assignment): Response
    {
        $training_assignment->load(['driver.user:id,name,email', 'driver.carrier:id,name', 'training.creator:id,name', 'training.media']);

        return Inertia::render('admin/training-assignments/Show', [
            'assignment' => $this->transformAssignment($training_assignment, true),
        ]);
    }

    public function markComplete(Request $request, DriverTraining $assignment): RedirectResponse
    {
        $validated = $request->validate([
            'completion_notes' => ['nullable', 'string'],
            'revert' => ['nullable', 'boolean'],
        ]);

        $revert = $request->boolean('revert');

        $assignment->update([
            'status' => $revert ? 'assigned' : 'completed',
            'completed_date' => $revert ? null : now(),
            'completion_notes' => $revert ? null : ($validated['completion_notes'] ?? $assignment->completion_notes),
        ]);

        return back()->with('success', $revert ? 'Assignment reverted successfully.' : 'Assignment marked as completed.');
    }

    public function destroy(DriverTraining $training_assignment): RedirectResponse
    {
        $training_assignment->delete();

        return redirect()
            ->route('admin.training-assignments.index')
            ->with('success', 'Training assignment deleted successfully.');
    }

    protected function transformAssignment(DriverTraining $assignment, bool $includeTrainingFiles = false): array
    {
        $dueDate = $assignment->due_date;
        $isOverdue = $dueDate && ! in_array($assignment->status, ['completed', 'overdue'], true) && now()->gt($dueDate);
        $status = $isOverdue ? 'overdue' : $assignment->status;

        return [
            'id' => $assignment->id,
            'status' => $status,
            'status_label' => match ($status) {
                'assigned' => 'Assigned',
                'in_progress' => 'In Progress',
                'completed' => 'Completed',
                'overdue' => 'Overdue',
                default => ucfirst((string) $status),
            },
            'assigned_date' => $assignment->assigned_date?->format('n/j/Y g:i A'),
            'due_date' => $assignment->due_date?->format('n/j/Y'),
            'completed_date' => $assignment->completed_date?->format('n/j/Y g:i A'),
            'completion_notes' => $assignment->completion_notes,
            'driver' => $assignment->driver ? [
                'id' => $assignment->driver->id,
                'name' => $this->driverFullName($assignment->driver),
                'email' => $assignment->driver->user?->email,
                'carrier_name' => $assignment->driver->carrier?->name,
            ] : null,
            'training' => $assignment->training ? [
                'id' => $assignment->training->id,
                'title' => $assignment->training->title,
                'content_type' => $assignment->training->content_type,
                'status' => $assignment->training->status,
                'description' => $assignment->training->description,
                'video_url' => $assignment->training->video_url,
                'url' => $assignment->training->url,
                'creator_name' => $assignment->training->creator?->name,
                'documents' => $includeTrainingFiles
                    ? $assignment->training->getMedia('training_files')->map(fn ($media) => [
                        'id' => $media->id,
                        'file_name' => $media->file_name,
                        'size_label' => $media->human_readable_size,
                        'preview_url' => $media->getUrl(),
                    ])->values()
                    : [],
            ] : null,
        ];
    }

    protected function assignmentStats(): array
    {
        $baseQuery = DriverTraining::query();

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

    protected function trainingOptions()
    {
        return Training::query()
            ->where('status', 'active')
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn (Training $training) => [
                'id' => $training->id,
                'title' => $training->title,
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
                'name' => $this->driverFullName($driver),
                'email' => $driver->user?->email,
            ]);
    }

    protected function driverFullName(?UserDriverDetail $driver): string
    {
        if (! $driver) {
            return 'N/A';
        }

        return trim(implode(' ', array_filter([
            $driver->user?->name,
            $driver->middle_name,
            $driver->last_name,
        ]))) ?: 'N/A';
    }
}
