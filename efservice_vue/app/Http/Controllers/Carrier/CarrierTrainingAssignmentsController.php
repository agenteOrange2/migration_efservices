<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Admin\TrainingAssignmentsController;
use App\Http\Controllers\Carrier\Concerns\ResolvesCarrierContext;
use App\Models\Admin\Driver\DriverTraining;
use App\Models\Admin\Driver\Training;
use App\Models\UserDriverDetail;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CarrierTrainingAssignmentsController extends TrainingAssignmentsController
{
    use ResolvesCarrierContext;

    public function assignSelect(): Response
    {
        $trainings = Training::query()
            ->where('status', 'active')
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

        $trainings->through(fn (Training $training) => [
            'id' => $training->id,
            'title' => $training->title,
            'description' => $training->description,
            'content_type' => $training->content_type,
            'status' => $training->status,
            'created_at' => $training->created_at?->format('n/j/Y'),
            'assignments_count' => (int) $training->driverAssignments()
                ->whereHas('driver', fn ($query) => $query->where('carrier_id', $this->resolveCarrierId()))
                ->count(),
            'documents_count' => $training->getMedia('training_files')->count(),
        ]);

        return Inertia::render('carrier/trainings/AssignSelect', [
            'trainings' => $trainings,
            'routeNames' => $this->trainingRouteNames(),
            'isCarrierContext' => true,
        ]);
    }

    public function createForTraining(Training $training): Response
    {
        return Inertia::render('carrier/training-assignments/Create', [
            'trainings' => $this->trainingOptions(),
            'carriers' => [$this->carrierOption()],
            'drivers' => $this->carrierDriverOptions(),
            'selectedTraining' => [
                'id' => $training->id,
                'title' => $training->title,
            ],
            'carrier' => $this->carrierOption(),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
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
        ]);

        $carrierId = (int) $this->resolveCarrierId();
        $allowedDriverIds = UserDriverDetail::query()
            ->where('carrier_id', $carrierId)
            ->whereIn('id', $validated['driver_ids'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        abort_unless(count($allowedDriverIds) === count($validated['driver_ids']), 403);

        $training = Training::query()->findOrFail($validated['training_id']);
        $dueDate = ! empty($validated['due_date'])
            ? Carbon::createFromFormat('n/j/Y', $validated['due_date'])->format('Y-m-d')
            : null;

        $assignedCount = 0;
        $alreadyAssignedCount = 0;

        DB::transaction(function () use ($validated, $dueDate, $allowedDriverIds, &$assignedCount, &$alreadyAssignedCount) {
            foreach ($allowedDriverIds as $driverId) {
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

        $message = "{$assignedCount} driver(s) assigned successfully.";

        if ($alreadyAssignedCount > 0) {
            $message .= " {$alreadyAssignedCount} assignment(s) were skipped because they already existed.";
        }

        return redirect()
            ->route('carrier.trainings.show', $training)
            ->with('success', $message);
    }

    public function storeForTraining(Request $request, Training $training): RedirectResponse
    {
        $request->merge([
            'training_id' => $training->id,
        ]);

        return $this->store($request);
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
            'store' => 'carrier.trainings.assign',
            'index' => 'carrier.trainings.assign.select',
        ];
    }

    protected function trainingRouteNames(): array
    {
        return [
            'index' => 'carrier.trainings.index',
            'create' => 'carrier.trainings.create',
            'show' => 'carrier.trainings.show',
            'edit' => 'carrier.trainings.edit',
            'destroy' => 'carrier.trainings.destroy',
            'assignSelect' => 'carrier.trainings.assign.select',
            'assignForm' => 'carrier.trainings.assign.form',
            'assign' => 'carrier.trainings.assign',
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
