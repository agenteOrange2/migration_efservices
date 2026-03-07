@extends('../themes/' . $activeTheme)
@section('title', 'Training Details')
@php
$breadcrumbLinks = [
    ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
    ['label' => 'Trainings', 'url' => route('carrier.trainings.index')],
    ['label' => 'Details', 'active' => true],
];
@endphp

@section('subcontent')
    <!-- Flash Messages -->
    @if(session('success'))
        <div class="alert alert-success-soft show flex items-center mb-5" role="alert">
            <x-base.lucide class="w-6 h-6 mr-2" icon="CheckCircle" />
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger-soft show flex items-center mb-5" role="alert">
            <x-base.lucide class="w-6 h-6 mr-2" icon="AlertOctagon" />
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-12 gap-y-10">
        <div class="col-span-12">
            <!-- Professional Header -->
            <div class="box box--stacked p-5 mb-5">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <x-base.lucide class="w-8 h-8 text-primary" icon="BookOpen" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800 mb-1">{{ $training->title }}</h1>
                            <div class="flex items-center gap-3 text-slate-500 text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($training->status === 'active') bg-success/10 text-success
                                    @else bg-slate-100 text-slate-600
                                    @endif">
                                    <x-base.lucide class="w-3 h-3 mr-1" icon="{{ $training->status === 'active' ? 'CheckCircle' : 'XCircle' }}" />
                                    {{ ucfirst($training->status) }}
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($training->content_type === 'file') bg-primary/10 text-primary
                                    @elseif($training->content_type === 'video') bg-purple-100 text-blue-700
                                    @else bg-success/10 text-success
                                    @endif">
                                    @if($training->content_type === 'file')
                                        <x-base.lucide class="w-3 h-3 mr-1" icon="FileText" />
                                    @elseif($training->content_type === 'video')
                                        <x-base.lucide class="w-3 h-3 mr-1" icon="Video" />
                                    @else
                                        <x-base.lucide class="w-3 h-3 mr-1" icon="Link" />
                                    @endif
                                    {{ ucfirst($training->content_type) }}
                                </span>
                                <span>Created {{ $training->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-base.button as="a" href="{{ route('carrier.trainings.index') }}" variant="outline-secondary" class="w-full sm:w-auto">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="ArrowLeft" />
                            Back
                        </x-base.button>
                        <x-base.button as="a" href="{{ route('carrier.trainings.edit', $training->id) }}" variant="primary" class="w-full sm:w-auto">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="Edit" />
                            Edit
                        </x-base.button>
                        <form action="{{ route('carrier.trainings.destroy', $training->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this training?');">
                            @csrf
                            @method('DELETE')
                            <x-base.button type="submit" variant="danger" class="w-full sm:w-auto">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="Trash2" />
                                Delete
                            </x-base.button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-12 gap-5">
                <!-- Left Column -->
                <div class="col-span-12 lg:col-span-8 space-y-5">
                    <!-- Basic Information -->
                    <div class="box box--stacked">
                        <div class="box-header">
                            <div class="box-title p-5 border-b border-slate-200/60 bg-slate-50">
                                <div class="flex items-center">
                                    <x-base.lucide class="w-5 h-5 mr-2 text-primary" icon="Info" />
                                    <span class="text-base font-medium">Basic Information</span>
                                </div>
                            </div>
                        </div>
                        <div class="p-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2 bg-slate-50/50 p-3 rounded-lg">
                                    <div class="text-sm text-slate-500">Description</div>
                                    <div class="font-medium mt-1 whitespace-pre-wrap">{{ $training->description }}</div>
                                </div>
                                <div class="bg-slate-50/50 p-3 rounded-lg">
                                    <div class="text-sm text-slate-500">Created By</div>
                                    <div class="font-medium mt-1">{{ $training->creator->name ?? 'N/A' }}</div>
                                </div>
                                <div class="bg-slate-50/50 p-3 rounded-lg">
                                    <div class="text-sm text-slate-500">Last Updated</div>
                                    <div class="font-medium mt-1">{{ $training->updated_at->format('M d, Y h:i A') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Details -->
                    <div class="box box--stacked">
                        <div class="box-header">
                            <div class="box-title p-5 border-b border-slate-200/60 bg-slate-50">
                                <div class="flex items-center">
                                    <x-base.lucide class="w-5 h-5 mr-2 text-primary" icon="FileText" />
                                    <span class="text-base font-medium">Content Details</span>
                                </div>
                            </div>
                        </div>
                        <div class="p-5">
                            @if($training->content_type === 'video' && $training->video_url)
                                <div class="bg-slate-50/50 p-4 rounded-lg">
                                    <div class="text-sm text-slate-500 mb-2">Video URL</div>
                                    <a href="{{ $training->video_url }}" target="_blank" class="text-primary hover:text-primary/80 hover:underline flex items-center gap-1 font-medium">
                                        {{ $training->video_url }}
                                        <x-base.lucide class="w-4 h-4" icon="ExternalLink" />
                                    </a>
                                </div>
                            @elseif($training->content_type === 'url' && $training->url)
                                <div class="bg-slate-50/50 p-4 rounded-lg">
                                    <div class="text-sm text-slate-500 mb-2">External URL</div>
                                    <a href="{{ $training->url }}" target="_blank" class="text-primary hover:text-primary/80 hover:underline flex items-center gap-1 font-medium">
                                        {{ $training->url }}
                                        <x-base.lucide class="w-4 h-4" icon="ExternalLink" />
                                    </a>
                                </div>
                            @elseif($training->content_type === 'file')
                                @if($trainingFiles->count() > 0)
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        @foreach($trainingFiles as $file)
                                        <div class="border border-slate-200/60 rounded-lg p-4 bg-slate-50/30 hover:bg-slate-50 transition-colors document-item" data-document-id="{{ $file->id }}">
                                            <div class="flex items-center gap-3 mb-3">
                                                <div class="p-2 bg-primary/10 rounded-lg">
                                                    <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-slate-800 truncate" title="{{ $file->file_name }}">{{ $file->file_name }}</p>
                                                    <p class="text-xs text-slate-500">{{ number_format($file->size / 1024, 2) }} KB</p>
                                                </div>
                                            </div>
                                            <div class="flex gap-2">
                                                <a href="{{ route('carrier.trainings.documents.preview', $file->id) }}" target="_blank"
                                                   class="flex-1 inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium text-primary bg-primary/10 rounded-md hover:bg-primary/20 transition-colors">
                                                    <x-base.lucide class="w-3 h-3 mr-1" icon="Eye" />
                                                    Preview
                                                </a>
                                                <a href="{{ route('carrier.trainings.documents.preview', $file->id) }}" download
                                                   class="flex-1 inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium text-success bg-success/10 rounded-md hover:bg-success/20 transition-colors">
                                                    <x-base.lucide class="w-3 h-3 mr-1" icon="Download" />
                                                    Download
                                                </a>
                                                <button type="button" 
                                                        class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium text-danger bg-danger/10 rounded-md hover:bg-danger/20 transition-colors delete-document-btn"
                                                        data-document-id="{{ $file->id }}"
                                                        data-document-name="{{ $file->file_name }}">
                                                    <x-base.lucide class="w-3 h-3" icon="Trash2" />
                                                </button>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-10 text-slate-400">
                                        <x-base.lucide class="w-12 h-12 mx-auto mb-3" icon="FileX" />
                                        <p class="text-sm">No files uploaded yet</p>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>

                    <!-- Driver Assignments -->
                    <div class="box box--stacked">
                        <div class="box-header">
                            <div class="box-title p-5 border-b border-slate-200/60 bg-slate-50">
                                <div class="flex items-center">
                                    <x-base.lucide class="w-5 h-5 mr-2 text-primary" icon="Users" />
                                    <span class="text-base font-medium">Driver Assignments</span>
                                </div>
                            </div>
                        </div>
                        <div class="p-5">
                            @if($training->driverAssignments->count() > 0)
                                <div class="overflow-x-auto">
                                    <x-base.table class="border-b border-dashed border-slate-200/80">
                                        <x-base.table.thead>
                                            <x-base.table.tr>
                                                <x-base.table.td class="border-b-0 whitespace-nowrap bg-slate-50 py-4 font-medium text-slate-500">Driver</x-base.table.td>
                                                <x-base.table.td class="border-b-0 whitespace-nowrap bg-slate-50 py-4 font-medium text-slate-500">Assigned</x-base.table.td>
                                                <x-base.table.td class="border-b-0 whitespace-nowrap bg-slate-50 py-4 font-medium text-slate-500">Due Date</x-base.table.td>
                                                <x-base.table.td class="border-b-0 whitespace-nowrap bg-slate-50 py-4 font-medium text-slate-500">Status</x-base.table.td>
                                                <x-base.table.td class="border-b-0 whitespace-nowrap bg-slate-50 py-4 font-medium text-slate-500">Completed</x-base.table.td>
                                            </x-base.table.tr>
                                        </x-base.table.thead>
                                        <x-base.table.tbody>
                                            @foreach($training->driverAssignments as $assignment)
                                            <x-base.table.tr class="hover:bg-slate-50/50">
                                                <x-base.table.td class="border-dashed py-4">
                                                    <div class="font-medium text-slate-800">{{ $assignment->driver->user->name ?? 'N/A' }}</div>
                                                </x-base.table.td>
                                                <x-base.table.td class="border-dashed py-4 text-slate-600">
                                                    {{ \Carbon\Carbon::parse($assignment->assigned_date)->format('M d, Y') }}
                                                </x-base.table.td>
                                                <x-base.table.td class="border-dashed py-4 text-slate-600">
                                                    {{ \Carbon\Carbon::parse($assignment->due_date)->format('M d, Y') }}
                                                </x-base.table.td>
                                                <x-base.table.td class="border-dashed py-4">
                                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                                        @if($assignment->status === 'completed') bg-success/10 text-success
                                                        @elseif($assignment->status === 'in_progress') bg-primary/10 text-primary
                                                        @elseif($assignment->status === 'overdue') bg-danger/10 text-danger
                                                        @else bg-slate-100 text-slate-600
                                                        @endif">
                                                        {{ ucfirst(str_replace('_', ' ', $assignment->status)) }}
                                                    </span>
                                                </x-base.table.td>
                                                <x-base.table.td class="border-dashed py-4 text-slate-600">
                                                    {{ $assignment->completed_date ? \Carbon\Carbon::parse($assignment->completed_date)->format('M d, Y') : '-' }}
                                                </x-base.table.td>
                                            </x-base.table.tr>
                                            @endforeach
                                        </x-base.table.tbody>
                                    </x-base.table>
                                </div>
                            @else
                                <div class="text-center py-10 text-slate-400">
                                    <x-base.lucide class="w-12 h-12 mx-auto mb-3" icon="Users" />
                                    <p class="text-sm">No driver assignments yet</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Right Column: Statistics -->
                <div class="col-span-12 lg:col-span-4">
                    <div class="box box--stacked">
                        <div class="box-header">
                            <div class="box-title p-5 border-b border-slate-200/60 bg-slate-50">
                                <div class="flex items-center">
                                    <x-base.lucide class="w-5 h-5 mr-2 text-primary" icon="BarChart3" />
                                    <span class="text-base font-medium">Assignment Statistics</span>
                                </div>
                            </div>
                        </div>
                        <div class="p-5 space-y-3">
                            <div class="flex items-center justify-between p-3 bg-primary/5 rounded-lg border border-primary/10">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-primary/10 rounded-lg">
                                        <x-base.lucide class="w-5 h-5 text-primary" icon="Users" />
                                    </div>
                                    <span class="text-sm text-slate-600">Total</span>
                                </div>
                                <span class="text-xl font-bold text-slate-800">{{ $assignmentStats['total'] }}</span>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-success/5 rounded-lg border border-success/10">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-success/10 rounded-lg">
                                        <x-base.lucide class="w-5 h-5 text-success" icon="CheckCircle" />
                                    </div>
                                    <span class="text-sm text-slate-600">Completed</span>
                                </div>
                                <span class="text-xl font-bold text-slate-800">{{ $assignmentStats['completed'] }}</span>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-warning/5 rounded-lg border border-warning/10">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-warning/10 rounded-lg">
                                        <x-base.lucide class="w-5 h-5 text-warning" icon="Clock" />
                                    </div>
                                    <span class="text-sm text-slate-600">In Progress</span>
                                </div>
                                <span class="text-xl font-bold text-slate-800">{{ $assignmentStats['in_progress'] }}</span>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200/60">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-slate-100 rounded-lg">
                                        <x-base.lucide class="w-5 h-5 text-slate-500" icon="Circle" />
                                    </div>
                                    <span class="text-sm text-slate-600">Pending</span>
                                </div>
                                <span class="text-xl font-bold text-slate-800">{{ $assignmentStats['pending'] }}</span>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-danger/5 rounded-lg border border-danger/10">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-danger/10 rounded-lg">
                                        <x-base.lucide class="w-5 h-5 text-danger" icon="AlertCircle" />
                                    </div>
                                    <span class="text-sm text-slate-600">Overdue</span>
                                </div>
                                <span class="text-xl font-bold text-slate-800">{{ $assignmentStats['overdue'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
