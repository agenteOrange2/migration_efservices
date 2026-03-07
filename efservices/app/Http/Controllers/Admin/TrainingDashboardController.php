<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\Training;
use App\Models\Admin\Driver\DriverTraining;
use App\Models\Carrier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TrainingDashboardController extends Controller
{
    /**
     * Display training management dashboard
     */
    public function index()
    {
        // Overall Statistics
        $totalTrainings = Training::count();
        $activeTrainings = Training::where('status', 'active')->count();
        $inactiveTrainings = Training::where('status', 'inactive')->count();

        // Assignment Statistics
        $totalAssignments = DriverTraining::count();
        $completedAssignments = DriverTraining::where('status', 'completed')->count();
        $inProgressAssignments = DriverTraining::where('status', 'in_progress')->count();
        $pendingAssignments = DriverTraining::where('status', 'assigned')->count();
        $overdueAssignments = DriverTraining::where('status', 'overdue')->count();

        // Completion Rate
        $completionRate = $totalAssignments > 0 
            ? round(($completedAssignments / $totalAssignments) * 100, 1) 
            : 0;

        // Recent Activity (Last 10 completed trainings)
        $recentCompletions = DriverTraining::with(['driver.user', 'training'])
            ->where('status', 'completed')
            ->orderBy('completed_date', 'desc')
            ->limit(10)
            ->get();

        // Completion Rate by Training (Top 5)
        $trainingStats = Training::withCount([
            'driverAssignments as total_assignments',
            'driverAssignments as completed_assignments' => function ($query) {
                $query->where('status', 'completed');
            }
        ])
        ->having('total_assignments', '>', 0)
        ->orderBy('total_assignments', 'desc')
        ->limit(5)
        ->get()
        ->map(function ($training) {
            return [
                'name' => $training->title,
                'total' => $training->total_assignments,
                'completed' => $training->completed_assignments,
                'rate' => $training->total_assignments > 0 
                    ? round(($training->completed_assignments / $training->total_assignments) * 100, 1)
                    : 0
            ];
        });

        // Completion Rate by Carrier (Top 5)
        $carrierStats = Carrier::select('carriers.id', 'carriers.name')
            ->join('user_driver_details', 'carriers.id', '=', 'user_driver_details.carrier_id')
            ->join('driver_trainings', 'user_driver_details.id', '=', 'driver_trainings.user_driver_detail_id')
            ->selectRaw('COUNT(driver_trainings.id) as total_assignments')
            ->selectRaw('SUM(CASE WHEN driver_trainings.status = "completed" THEN 1 ELSE 0 END) as completed_assignments')
            ->groupBy('carriers.id', 'carriers.name')
            ->having('total_assignments', '>', 0)
            ->orderBy('total_assignments', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($carrier) {
                return [
                    'name' => $carrier->name,
                    'total' => $carrier->total_assignments,
                    'completed' => $carrier->completed_assignments,
                    'rate' => $carrier->total_assignments > 0 
                        ? round(($carrier->completed_assignments / $carrier->total_assignments) * 100, 1)
                        : 0
                ];
            });

        // Last 30 days trend
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $dailyCompletions = DriverTraining::where('status', 'completed')
            ->where('completed_date', '>=', $thirtyDaysAgo)
            ->selectRaw('DATE(completed_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->pluck('count', 'date');

        // Fill missing days with 0
        $trendData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $trendData[$date] = $dailyCompletions->get($date, 0);
        }

        // Upcoming Due Dates (Next 7 days)
        $upcomingDue = DriverTraining::with(['driver.user', 'training'])
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [Carbon::now(), Carbon::now()->addDays(7)])
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get();

        return view('admin.drivers.trainings.dashboard', compact(
            'totalTrainings',
            'activeTrainings',
            'inactiveTrainings',
            'totalAssignments',
            'completedAssignments',
            'inProgressAssignments',
            'pendingAssignments',
            'overdueAssignments',
            'completionRate',
            'recentCompletions',
            'trainingStats',
            'carrierStats',
            'trendData',
            'upcomingDue'
        ));
    }

    /**
     * Export training data to CSV
     */
    public function export(Request $request)
    {
        $type = $request->input('type', 'assignments');

        if ($type === 'assignments') {
            return $this->exportAssignments($request);
        } elseif ($type === 'trainings') {
            return $this->exportTrainings($request);
        } elseif ($type === 'analytics') {
            return $this->exportAnalytics($request);
        }

        return back()->with('error', 'Invalid export type');
    }

    /**
     * Export assignments data
     */
    private function exportAssignments(Request $request)
    {
        $assignments = DriverTraining::with(['driver.user', 'driver.carrier', 'training'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'training-assignments-' . Carbon::now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($assignments) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'Assignment ID',
                'Training Title',
                'Driver Name',
                'Driver Email',
                'Carrier',
                'Status',
                'Assigned Date',
                'Due Date',
                'Completed Date',
                'Days to Complete',
                'Notes'
            ]);

            // Data
            foreach ($assignments as $assignment) {
                $daysToComplete = null;
                if ($assignment->completed_date && $assignment->assigned_date) {
                    $daysToComplete = $assignment->assigned_date->diffInDays($assignment->completed_date);
                }

                fputcsv($file, [
                    $assignment->id,
                    $assignment->training->title ?? 'N/A',
                    $assignment->driver->user->name ?? 'N/A',
                    $assignment->driver->user->email ?? 'N/A',
                    $assignment->driver->carrier->name ?? 'N/A',
                    ucfirst($assignment->status),
                    $assignment->assigned_date ? $assignment->assigned_date->format('Y-m-d') : 'N/A',
                    $assignment->due_date ? $assignment->due_date->format('Y-m-d') : 'N/A',
                    $assignment->completed_date ? $assignment->completed_date->format('Y-m-d') : 'N/A',
                    $daysToComplete ?? 'N/A',
                    $assignment->completion_notes ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export trainings data
     */
    private function exportTrainings(Request $request)
    {
        $trainings = Training::withCount([
            'driverAssignments as total_assignments',
            'driverAssignments as completed_assignments' => function ($query) {
                $query->where('status', 'completed');
            },
            'driverAssignments as pending_assignments' => function ($query) {
                $query->where('status', 'assigned');
            },
            'driverAssignments as in_progress_assignments' => function ($query) {
                $query->where('status', 'in_progress');
            },
            'driverAssignments as overdue_assignments' => function ($query) {
                $query->where('status', 'overdue');
            }
        ])->get();

        $filename = 'trainings-report-' . Carbon::now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($trainings) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'Training ID',
                'Title',
                'Content Type',
                'Status',
                'Total Assignments',
                'Completed',
                'In Progress',
                'Pending',
                'Overdue',
                'Completion Rate (%)',
                'Created Date'
            ]);

            // Data
            foreach ($trainings as $training) {
                $completionRate = $training->total_assignments > 0
                    ? round(($training->completed_assignments / $training->total_assignments) * 100, 1)
                    : 0;

                fputcsv($file, [
                    $training->id,
                    $training->title,
                    ucfirst($training->content_type),
                    ucfirst($training->status),
                    $training->total_assignments,
                    $training->completed_assignments,
                    $training->in_progress_assignments,
                    $training->pending_assignments,
                    $training->overdue_assignments,
                    $completionRate,
                    $training->created_at->format('Y-m-d')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export analytics data
     */
    private function exportAnalytics(Request $request)
    {
        // Carrier performance data
        $carrierData = Carrier::select('carriers.id', 'carriers.name')
            ->join('user_driver_details', 'carriers.id', '=', 'user_driver_details.carrier_id')
            ->join('driver_trainings', 'user_driver_details.id', '=', 'driver_trainings.user_driver_detail_id')
            ->selectRaw('COUNT(DISTINCT user_driver_details.id) as total_drivers')
            ->selectRaw('COUNT(driver_trainings.id) as total_assignments')
            ->selectRaw('SUM(CASE WHEN driver_trainings.status = "completed" THEN 1 ELSE 0 END) as completed')
            ->selectRaw('SUM(CASE WHEN driver_trainings.status = "overdue" THEN 1 ELSE 0 END) as overdue')
            ->selectRaw('AVG(CASE WHEN driver_trainings.status = "completed" AND driver_trainings.assigned_date IS NOT NULL AND driver_trainings.completed_date IS NOT NULL THEN DATEDIFF(driver_trainings.completed_date, driver_trainings.assigned_date) ELSE NULL END) as avg_completion_days')
            ->groupBy('carriers.id', 'carriers.name')
            ->get();

        $filename = 'training-analytics-' . Carbon::now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($carrierData) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'Carrier',
                'Total Drivers',
                'Total Assignments',
                'Completed',
                'Overdue',
                'Completion Rate (%)',
                'Avg Days to Complete'
            ]);

            // Data
            foreach ($carrierData as $carrier) {
                $completionRate = $carrier->total_assignments > 0
                    ? round(($carrier->completed / $carrier->total_assignments) * 100, 1)
                    : 0;

                fputcsv($file, [
                    $carrier->name,
                    $carrier->total_drivers,
                    $carrier->total_assignments,
                    $carrier->completed,
                    $carrier->overdue,
                    $completionRate,
                    $carrier->avg_completion_days ? round($carrier->avg_completion_days, 1) : 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

