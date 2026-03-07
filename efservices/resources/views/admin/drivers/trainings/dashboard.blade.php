@extends('../themes/' . $activeTheme)
@section('title', 'Training Dashboard')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Training Dashboard', 'active' => true],
    ];
@endphp

@section('subcontent')

    <div class="box box--stacked p-4 sm:p-6 lg:p-8 mb-6 lg:mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 lg:gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-3 lg:gap-4">
                <div class="p-2 sm:p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-6 h-6 sm:w-8 sm:h-8 text-primary" icon="BarChart3" />
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-slate-800 mb-1 sm:mb-2">Training Management
                        Dashboard</h1>
                    <p class="text-sm sm:text-base text-slate-600">Overview and analytics of all training activities</p>
                </div>
            </div>
            <div class="mt-4 md:mt-0 flex flex-col sm:flex-row gap-2 w-full justify-end">
                <x-base.menu>
                    <x-base.menu.button as="x-base.button" variant="primary">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="Download" />
                        Export Data
                        <x-base.lucide class="w-4 h-4 ml-2" icon="ChevronDown" />
                    </x-base.menu.button>
                    <x-base.menu.items class="w-48">
                        <x-base.menu.item href="{{ route('admin.training-dashboard.export', ['type' => 'assignments']) }}">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="FileText" />
                            Assignments Report
                        </x-base.menu.item>
                        <x-base.menu.item href="{{ route('admin.training-dashboard.export', ['type' => 'trainings']) }}">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="BookOpen" />
                            Trainings Report
                        </x-base.menu.item>
                        <x-base.menu.item href="{{ route('admin.training-dashboard.export', ['type' => 'analytics']) }}">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="TrendingUp" />
                            Analytics Report
                        </x-base.menu.item>
                    </x-base.menu.items>
                </x-base.menu>

                <x-base.button as="a" href="{{ route('admin.trainings.index') }}" variant="outline-secondary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="List" />
                    View All Trainings
                </x-base.button>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Total Trainings --}}
        <div class="box box--stacked p-5 bg-gradient-to-br from-primary/5 to-primary/10 border-primary/20">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-primary/20">
                    <x-base.lucide class="w-6 h-6 text-primary" icon="BookOpen" />
                </div>
                <span class="text-xs text-slate-500 bg-white px-2 py-1 rounded">Trainings</span>
            </div>
            <div class="text-3xl font-bold text-slate-800">{{ $totalTrainings }}</div>
            <div class="text-sm text-slate-600 mt-1">
                <span class="text-success">{{ $activeTrainings }} Active</span> •
                <span class="text-slate-500">{{ $inactiveTrainings }} Inactive</span>
            </div>
        </div>

        {{-- Total Assignments --}}
        <div class="box box--stacked p-5 bg-gradient-to-br from-info/5 to-info/10 border-info/20">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-info/20">
                    <x-base.lucide class="w-6 h-6 text-info" icon="Users" />
                </div>
                <span class="text-xs text-slate-500 bg-white px-2 py-1 rounded">Assignments</span>
            </div>
            <div class="text-3xl font-bold text-slate-800">{{ $totalAssignments }}</div>
            <div class="text-sm text-slate-600 mt-1">Total driver assignments</div>
        </div>

        {{-- Completion Rate --}}
        <div class="box box--stacked p-5 bg-gradient-to-br from-success/5 to-success/10 border-success/20">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-success/20">
                    <x-base.lucide class="w-6 h-6 text-success" icon="CheckCircle2" />
                </div>
                <span class="text-xs text-slate-500 bg-white px-2 py-1 rounded">Completion</span>
            </div>
            <div class="text-3xl font-bold text-slate-800">{{ $completionRate }}%</div>
            <div class="text-sm text-slate-600 mt-1">
                {{ $completedAssignments }} of {{ $totalAssignments }} completed
            </div>
        </div>

        {{-- Overdue --}}
        <div class="box box--stacked p-5 bg-gradient-to-br from-danger/5 to-danger/10 border-danger/20">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-danger/20">
                    <x-base.lucide class="w-6 h-6 text-danger" icon="AlertCircle" />
                </div>
                <span class="text-xs text-slate-500 bg-white px-2 py-1 rounded">Attention</span>
            </div>
            <div class="text-3xl font-bold text-slate-800">{{ $overdueAssignments }}</div>
            <div class="text-sm text-slate-600 mt-1">Overdue assignments</div>
        </div>
    </div>

    {{-- Status Breakdown --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="box p-4">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-success/10">
                    <x-base.lucide class="w-5 h-5 text-success" icon="CheckCircle2" />
                </div>
                <div class="flex-1">
                    <div class="text-sm text-slate-600">Completed</div>
                    <div class="text-xl font-bold text-slate-800">{{ $completedAssignments }}</div>
                </div>
            </div>
        </div>

        <div class="box p-4">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-info/10">
                    <x-base.lucide class="w-5 h-5 text-info" icon="Clock" />
                </div>
                <div class="flex-1">
                    <div class="text-sm text-slate-600">In Progress</div>
                    <div class="text-xl font-bold text-slate-800">{{ $inProgressAssignments }}</div>
                </div>
            </div>
        </div>

        <div class="box p-4">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-warning/10">
                    <x-base.lucide class="w-5 h-5 text-warning" icon="Circle" />
                </div>
                <div class="flex-1">
                    <div class="text-sm text-slate-600">Pending</div>
                    <div class="text-xl font-bold text-slate-800">{{ $pendingAssignments }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Completion Rate by Training --}}
        <div class="box box--stacked p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-primary" icon="BookOpen" />
                Top Trainings by Assignments
            </h3>
            @if ($trainingStats->count() > 0)
                <div class="space-y-4">
                    @foreach ($trainingStats as $stat)
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span
                                    class="text-sm font-medium text-slate-700 truncate">{{ Str::limit($stat['name'], 30) }}</span>
                                <span class="text-sm font-semibold text-slate-800">{{ $stat['rate'] }}%</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-slate-200 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-primary to-primary/70 h-2 rounded-full transition-all"
                                        style="width: {{ $stat['rate'] }}%"></div>
                                </div>
                                <span
                                    class="text-xs text-slate-500 whitespace-nowrap">{{ $stat['completed'] }}/{{ $stat['total'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-slate-500">
                    <x-base.lucide class="w-12 h-12 mx-auto mb-2 text-slate-300" icon="BarChart" />
                    <p>No training data available yet</p>
                </div>
            @endif
        </div>

        {{-- Completion Rate by Carrier --}}
        <div class="box box--stacked p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-info" icon="Building2" />
                Top Carriers by Performance
            </h3>
            @if ($carrierStats->count() > 0)
                <div class="space-y-4">
                    @foreach ($carrierStats as $stat)
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span
                                    class="text-sm font-medium text-slate-700 truncate">{{ Str::limit($stat['name'], 30) }}</span>
                                <span class="text-sm font-semibold text-slate-800">{{ $stat['rate'] }}%</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-slate-200 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-info to-info/70 h-2 rounded-full transition-all"
                                        style="width: {{ $stat['rate'] }}%"></div>
                                </div>
                                <span
                                    class="text-xs text-slate-500 whitespace-nowrap">{{ $stat['completed'] }}/{{ $stat['total'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-slate-500">
                    <x-base.lucide class="w-12 h-12 mx-auto mb-2 text-slate-300" icon="Building" />
                    <p>No carrier data available yet</p>
                </div>
            @endif
        </div>
    </div>

    {{-- 30 Day Trend --}}
    <div class="box p-6 mb-6">
        <h3 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
            <x-base.lucide class="w-5 h-5 text-success" icon="TrendingUp" />
            Completions Trend (Last 30 Days)
        </h3>
        <div class="h-64" id="completions-chart"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Completions --}}
        <div class="box box--stacked p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-success" icon="CheckCircle2" />
                Recent Completions
            </h3>
            @if ($recentCompletions->count() > 0)
                <div class="space-y-3">
                    @foreach ($recentCompletions as $completion)
                        <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-success/10">
                                <x-base.lucide class="w-5 h-5 text-success" icon="User" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-800 truncate">
                                    {{ $completion->driver->user->name ?? 'Unknown' }} {{ $completion->driver->middle_name ?? '' }} {{ $completion->driver->last_name ?? '' }}</p>
                                <p class="text-xs text-slate-500 truncate">
                                    {{ $completion->training->title ?? 'Unknown Training' }}</p>
                                    <p class="text-xs text-slate-500 truncate">
                                        Carrier: {{ $completion->driver->carrier->name ?? 'Unknown Carrier' }}
                                    </p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-slate-600">
                                    <a href="{{ route('admin.drivers.show', $completion->driver->id) }}" class="text-blue-500 hover:text-blue-600">View Driver</a>                                    
                                </p>
                                <p class="text-xs text-slate-600">{{ $completion->completed_date->format('M d') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-slate-500">
                    <x-base.lucide class="w-12 h-12 mx-auto mb-2 text-slate-300" icon="Inbox" />
                    <p>No recent completions</p>
                </div>
            @endif
        </div>

        {{-- Upcoming Due Dates --}}
        <div class="box box--stacked p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-warning" icon="Clock" />
                Due Soon (Next 7 Days)
            </h3>
            @if ($upcomingDue->count() > 0)
                <div class="space-y-3">
                    @foreach ($upcomingDue as $assignment)
                        <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-warning/10">
                                <x-base.lucide class="w-5 h-5 text-warning" icon="AlertCircle" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-800 truncate">
                                    {{ $assignment->driver->user->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-slate-500 truncate">
                                    {{ $assignment->training->title ?? 'Unknown Training' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-medium text-warning">{{ $assignment->due_date->format('M d') }}</p>
                                <p class="text-xs text-slate-500">{{ $assignment->due_date->diffForHumans() }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-slate-500">
                    <x-base.lucide class="w-12 h-12 mx-auto mb-2 text-slate-300" icon="Calendar" />
                    <p>No upcoming due dates</p>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Trend Chart
            const trendData = @json(array_values($trendData));
            const trendLabels = @json(array_keys($trendData));

            const chartOptions = {
                series: [{
                    name: 'Completions',
                    data: trendData
                }],
                chart: {
                    type: 'area',
                    height: 250,
                    toolbar: {
                        show: false
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.1,
                    }
                },
                xaxis: {
                    categories: trendLabels.map(date => {
                        const d = new Date(date);
                        return d.toLocaleDateString('en-US', {
                            month: 'short',
                            day: 'numeric'
                        });
                    }),
                    labels: {
                        rotate: -45,
                        rotateAlways: false
                    }
                },
                yaxis: {
                    title: {
                        text: 'Completions'
                    }
                },
                colors: ['#10b981'],
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val + ' completions';
                        }
                    }
                }
            };

            const chart = new ApexCharts(document.querySelector("#completions-chart"), chartOptions);
            chart.render();
        });
    </script>
@endpush
