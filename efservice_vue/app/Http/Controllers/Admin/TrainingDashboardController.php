<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverTraining;
use App\Models\Admin\Driver\Training;
use App\Models\Carrier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

class TrainingDashboardController extends Controller
{
    public function index(): Response
    {
        $totalTrainings = Training::query()->count();
        $activeTrainings = Training::query()->where('status', 'active')->count();
        $inactiveTrainings = Training::query()->where('status', 'inactive')->count();

        $totalAssignments = DriverTraining::query()->count();
        $completedAssignments = DriverTraining::query()->where('status', 'completed')->count();
        $inProgressAssignments = DriverTraining::query()->where('status', 'in_progress')->count();
        $pendingAssignments = DriverTraining::query()
            ->where('status', 'assigned')
            ->where(function ($query) {
                $query
                    ->whereNull('due_date')
                    ->orWhere('due_date', '>=', now()->startOfDay());
            })
            ->count();
        $overdueAssignments = DriverTraining::query()
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
            ->count();

        $completionRate = $totalAssignments > 0
            ? round(($completedAssignments / $totalAssignments) * 100, 1)
            : 0;

        $recentCompletions = DriverTraining::query()
            ->with(['driver.user:id,name,email', 'driver.carrier:id,name', 'training:id,title'])
            ->where('status', 'completed')
            ->orderByDesc('completed_date')
            ->limit(10)
            ->get()
            ->map(fn (DriverTraining $assignment) => [
                'id' => $assignment->id,
                'driver_name' => $assignment->driver?->full_name ?: 'N/A',
                'driver_email' => $assignment->driver?->user?->email,
                'carrier_name' => $assignment->driver?->carrier?->name,
                'training_title' => $assignment->training?->title ?? 'N/A',
                'completed_date' => $assignment->completed_date?->format('n/j/Y'),
                'completed_relative' => $assignment->completed_date?->diffForHumans(),
                'assignment_url' => route('admin.training-assignments.show', $assignment),
            ])
            ->values();

        $trainingStats = Training::query()
            ->withCount([
                'driverAssignments as total_assignments',
                'driverAssignments as completed_assignments' => function ($query) {
                    $query->where('status', 'completed');
                },
            ])
            ->having('total_assignments', '>', 0)
            ->orderByDesc('total_assignments')
            ->limit(5)
            ->get()
            ->map(fn (Training $training) => [
                'id' => $training->id,
                'name' => $training->title,
                'total' => (int) $training->total_assignments,
                'completed' => (int) $training->completed_assignments,
                'rate' => $training->total_assignments > 0
                    ? round(($training->completed_assignments / $training->total_assignments) * 100, 1)
                    : 0,
                'show_url' => route('admin.trainings.show', $training),
            ])
            ->values();

        $carrierStats = Carrier::query()
            ->select('carriers.id', 'carriers.name')
            ->join('user_driver_details', 'carriers.id', '=', 'user_driver_details.carrier_id')
            ->join('driver_trainings', 'user_driver_details.id', '=', 'driver_trainings.user_driver_detail_id')
            ->selectRaw('COUNT(driver_trainings.id) as total_assignments')
            ->selectRaw('SUM(CASE WHEN driver_trainings.status = "completed" THEN 1 ELSE 0 END) as completed_assignments')
            ->groupBy('carriers.id', 'carriers.name')
            ->having('total_assignments', '>', 0)
            ->orderByDesc('total_assignments')
            ->limit(5)
            ->get()
            ->map(fn ($carrier) => [
                'id' => $carrier->id,
                'name' => $carrier->name,
                'total' => (int) $carrier->total_assignments,
                'completed' => (int) $carrier->completed_assignments,
                'rate' => $carrier->total_assignments > 0
                    ? round(($carrier->completed_assignments / $carrier->total_assignments) * 100, 1)
                    : 0,
            ])
            ->values();

        $trendData = $this->buildTrendData();

        $upcomingDue = DriverTraining::query()
            ->with(['driver.user:id,name,email', 'driver.carrier:id,name', 'training:id,title'])
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
            ->orderBy('due_date')
            ->limit(10)
            ->get()
            ->map(fn (DriverTraining $assignment) => [
                'id' => $assignment->id,
                'driver_name' => $assignment->driver?->full_name ?: 'N/A',
                'carrier_name' => $assignment->driver?->carrier?->name,
                'training_title' => $assignment->training?->title ?? 'N/A',
                'due_date' => $assignment->due_date?->format('n/j/Y'),
                'due_relative' => $assignment->due_date?->diffForHumans(),
                'assignment_url' => route('admin.training-assignments.show', $assignment),
            ])
            ->values();

        return Inertia::render('admin/training-dashboard/Index', [
            'overview' => [
                'total_trainings' => $totalTrainings,
                'active_trainings' => $activeTrainings,
                'inactive_trainings' => $inactiveTrainings,
                'total_assignments' => $totalAssignments,
                'completed_assignments' => $completedAssignments,
                'in_progress_assignments' => $inProgressAssignments,
                'pending_assignments' => $pendingAssignments,
                'overdue_assignments' => $overdueAssignments,
                'completion_rate' => $completionRate,
            ],
            'trainingStats' => $trainingStats,
            'carrierStats' => $carrierStats,
            'recentCompletions' => $recentCompletions,
            'upcomingDue' => $upcomingDue,
            'trend' => $trendData,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $type = (string) $request->input('type', 'assignments');

        return match ($type) {
            'trainings' => $this->exportTrainings(),
            'analytics' => $this->exportAnalytics(),
            default => $this->exportAssignments(),
        };
    }

    protected function exportAssignments(): StreamedResponse
    {
        $assignments = DriverTraining::query()
            ->with(['driver.user:id,name,email', 'driver.carrier:id,name', 'training:id,title'])
            ->latest('created_at')
            ->get();

        return response()->streamDownload(function () use ($assignments) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Assignment ID',
                'Training Title',
                'Driver Name',
                'Driver Email',
                'Carrier',
                'Status',
                'Assigned Date',
                'Due Date',
                'Completed Date',
                'Notes',
            ]);

            foreach ($assignments as $assignment) {
                fputcsv($handle, [
                    $assignment->id,
                    $assignment->training?->title ?? 'N/A',
                    $assignment->driver?->full_name ?? 'N/A',
                    $assignment->driver?->user?->email ?? 'N/A',
                    $assignment->driver?->carrier?->name ?? 'N/A',
                    $assignment->status,
                    $assignment->assigned_date?->format('Y-m-d'),
                    $assignment->due_date?->format('Y-m-d'),
                    $assignment->completed_date?->format('Y-m-d'),
                    $assignment->completion_notes,
                ]);
            }

            fclose($handle);
        }, 'training-assignments-' . now()->format('Y-m-d') . '.csv');
    }

    protected function exportTrainings(): StreamedResponse
    {
        $trainings = Training::query()
            ->withCount([
                'driverAssignments as total_assignments',
                'driverAssignments as completed_assignments' => function ($query) {
                    $query->where('status', 'completed');
                },
                'driverAssignments as in_progress_assignments' => function ($query) {
                    $query->where('status', 'in_progress');
                },
                'driverAssignments as assigned_assignments' => function ($query) {
                    $query->where('status', 'assigned');
                },
            ])
            ->orderBy('title')
            ->get();

        return response()->streamDownload(function () use ($trainings) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Training ID',
                'Title',
                'Content Type',
                'Status',
                'Total Assignments',
                'Completed',
                'In Progress',
                'Assigned',
                'Completion Rate',
            ]);

            foreach ($trainings as $training) {
                $rate = $training->total_assignments > 0
                    ? round(($training->completed_assignments / $training->total_assignments) * 100, 1)
                    : 0;

                fputcsv($handle, [
                    $training->id,
                    $training->title,
                    $training->content_type,
                    $training->status,
                    $training->total_assignments,
                    $training->completed_assignments,
                    $training->in_progress_assignments,
                    $training->assigned_assignments,
                    $rate,
                ]);
            }

            fclose($handle);
        }, 'trainings-report-' . now()->format('Y-m-d') . '.csv');
    }

    protected function exportAnalytics(): StreamedResponse
    {
        $carriers = Carrier::query()
            ->select('carriers.name')
            ->join('user_driver_details', 'carriers.id', '=', 'user_driver_details.carrier_id')
            ->join('driver_trainings', 'user_driver_details.id', '=', 'driver_trainings.user_driver_detail_id')
            ->selectRaw('COUNT(DISTINCT user_driver_details.id) as total_drivers')
            ->selectRaw('COUNT(driver_trainings.id) as total_assignments')
            ->selectRaw('SUM(CASE WHEN driver_trainings.status = "completed" THEN 1 ELSE 0 END) as completed')
            ->selectRaw('SUM(CASE WHEN driver_trainings.due_date IS NOT NULL AND driver_trainings.status != "completed" AND driver_trainings.due_date < CURDATE() THEN 1 ELSE 0 END) as overdue')
            ->groupBy('carriers.id', 'carriers.name')
            ->orderBy('carriers.name')
            ->get();

        return response()->streamDownload(function () use ($carriers) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Carrier',
                'Drivers',
                'Assignments',
                'Completed',
                'Overdue',
                'Completion Rate',
            ]);

            foreach ($carriers as $carrier) {
                $rate = $carrier->total_assignments > 0
                    ? round(($carrier->completed / $carrier->total_assignments) * 100, 1)
                    : 0;

                fputcsv($handle, [
                    $carrier->name,
                    $carrier->total_drivers,
                    $carrier->total_assignments,
                    $carrier->completed,
                    $carrier->overdue,
                    $rate,
                ]);
            }

            fclose($handle);
        }, 'training-analytics-' . now()->format('Y-m-d') . '.csv');
    }

    protected function buildTrendData(): array
    {
        $start = Carbon::now()->subDays(29)->startOfDay();
        $dailyCompletions = DriverTraining::query()
            ->where('status', 'completed')
            ->where('completed_date', '>=', $start)
            ->selectRaw('DATE(completed_date) as completion_date, COUNT(*) as total')
            ->groupBy('completion_date')
            ->orderBy('completion_date')
            ->get()
            ->pluck('total', 'completion_date');

        $data = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $key = $date->format('Y-m-d');

            $data[] = [
                'date' => $key,
                'label' => $date->format('M j'),
                'count' => (int) ($dailyCompletions[$key] ?? 0),
            ];
        }

        return $data;
    }
}
