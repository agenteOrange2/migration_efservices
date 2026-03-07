@extends('../themes/' . $activeTheme)
@section('title', 'Training Details')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Trainings', 'url' => route('admin.trainings.index')],
        ['label' => 'Training Details', 'active' => true],
    ];
@endphp

@section('subcontent')
    <div>
        <!-- Flash Messages -->
        @if (session('success'))
            <x-base.alert variant="success" dismissible class="mb-5">
                {{ session('success') }}
            </x-base.alert>
        @endif

        @if (session('error'))
            <x-base.alert variant="danger" dismissible class="mb-5">
                {{ session('error') }}
            </x-base.alert>
        @endif

        <!-- Professional Header -->
        <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <x-base.lucide class="w-8 h-8 text-primary" icon="BookOpen" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">{{ $training->title }}</h1>
                        <div class="flex flex-wrap items-center gap-3">
                            @if ($training->status === 'active')
                                <span class="inline-flex items-center rounded-full bg-success/10 px-3 py-1 text-sm font-medium text-success">
                                    <x-base.lucide class="w-4 h-4 mr-1" icon="CheckCircle" />
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-danger/10 px-3 py-1 text-sm font-medium text-danger">
                                    <x-base.lucide class="w-4 h-4 mr-1" icon="XCircle" />
                                    Inactive
                                </span>
                            @endif
                            @if ($training->content_type === 'file')
                                <span class="inline-flex items-center rounded-full bg-info/10 px-3 py-1 text-sm font-medium text-info">
                                    <x-base.lucide class="w-4 h-4 mr-1" icon="FileText" />
                                    File
                                </span>
                            @elseif($training->content_type === 'video')
                                <span class="inline-flex items-center rounded-full bg-purple-100 px-3 py-1 text-sm font-medium text-purple-800">
                                    <x-base.lucide class="w-4 h-4 mr-1" icon="Video" />
                                    Video
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-warning/10 px-3 py-1 text-sm font-medium text-warning">
                                    <x-base.lucide class="w-4 h-4 mr-1" icon="Link" />
                                    URL
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                    <x-base.button as="a" href="{{ route('admin.trainings.index') }}" class="w-full sm:w-auto"
                        variant="outline-secondary">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="ArrowLeft" />
                        Back to Trainings
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('admin.trainings.edit', $training->id) }}"
                        class="w-full sm:w-auto" variant="primary">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="Pencil" />
                        Edit Training
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('admin.trainings.assign.form', $training->id) }}"
                        class="w-full sm:w-auto" variant="success">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="UserPlus" />
                        Assign to Drivers
                    </x-base.button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6">
            <!-- Main Content -->
            <div class="col-span-12 xl:col-span-8">
                <!-- Training Information -->
                <div class="box box--stacked p-5 mb-6">
                    <div class="flex items-center border-b border-dashed border-slate-300/70 pb-5 mb-5">
                        <x-base.lucide class="mr-2 h-6 w-6 text-primary" icon="Info" />
                        <h3 class="text-base font-medium">Training Information</h3>
                    </div>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="col-span-2">
                            <div class="text-xs uppercase tracking-widest text-slate-500 mb-1">Description</div>
                            <div class="text-base text-slate-700 leading-relaxed">
                                {!! nl2br(e($training->description)) !!}
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <div class="text-xs uppercase tracking-widest text-slate-500">Created By</div>
                            <div class="mt-1 text-base font-medium">
                                {{ $training->creator ? $training->creator->name : 'N/A' }}
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <div class="text-xs uppercase tracking-widest text-slate-500">Creation Date</div>
                            <div class="mt-1 text-base font-medium">
                                {{ $training->created_at->format('M d, Y H:i') }}
                            </div>
                        </div>
                        @if($training->updated_at && $training->updated_at != $training->created_at)
                        <div class="flex flex-col">
                            <div class="text-xs uppercase tracking-widest text-slate-500">Last Updated</div>
                            <div class="mt-1 text-base font-medium">
                                {{ $training->updated_at->format('M d, Y H:i') }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Video Content -->
                @if ($training->content_type === 'video' && $training->video_url)
                <div class="box box--stacked p-5 mb-6">
                    <div class="flex items-center border-b border-dashed border-slate-300/70 pb-5 mb-5">
                        <x-base.lucide class="mr-2 h-6 w-6 text-primary" icon="Video" />
                        <h3 class="text-base font-medium">Training Video</h3>
                    </div>
                    @php
                        $videoId = null;
                        $videoUrl = trim($training->video_url);
                        
                        if (!empty($videoUrl)) {
                            if (strpos($videoUrl, 'youtube.com') !== false) {
                                $parsedUrl = parse_url($videoUrl);
                                if (isset($parsedUrl['query'])) {
                                    parse_str($parsedUrl['query'], $params);
                                    $videoId = $params['v'] ?? null;
                                }
                            } elseif (strpos($videoUrl, 'youtu.be') !== false) {
                                $parsedUrl = parse_url($videoUrl);
                                if (isset($parsedUrl['path'])) {
                                    $videoId = trim(substr($parsedUrl['path'], 1));
                                    $videoId = explode('?', $videoId)[0];
                                }
                            }
                        }
                        
                        if ($videoId && !preg_match('/^[a-zA-Z0-9_-]+$/', $videoId)) {
                            $videoId = null;
                        }
                    @endphp

                    @if ($videoId)
                        <div class="relative w-full rounded-lg overflow-hidden" style="padding-bottom: 56.25%;">
                            <iframe 
                                src="https://www.youtube-nocookie.com/embed/{{ $videoId }}?rel=0&modestbranding=1"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                                class="absolute top-0 left-0 w-full h-full rounded-lg"
                                loading="lazy" 
                                title="Training Video">
                            </iframe>
                        </div>
                    @else
                        <div class="w-full bg-slate-50 border-2 border-dashed border-slate-300 rounded-lg p-8 text-center">
                            <x-base.lucide class="w-12 h-12 text-slate-400 mx-auto mb-4" icon="PlayCircle" />
                            <h3 class="text-lg font-medium text-slate-900 mb-2">External Video</h3>
                            <p class="text-sm text-slate-600 mb-4">This video is hosted on an external platform</p>
                            <x-base.button as="a" href="{{ $training->video_url }}" target="_blank" variant="primary">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="ExternalLink" />
                                Watch Video
                            </x-base.button>
                        </div>
                    @endif
                </div>
                @endif

                <!-- URL Content -->
                @if ($training->content_type === 'url' && isset($training->url))
                <div class="box box--stacked p-5 mb-6">
                    <div class="flex items-center border-b border-dashed border-slate-300/70 pb-5 mb-5">
                        <x-base.lucide class="mr-2 h-6 w-6 text-primary" icon="Link" />
                        <h3 class="text-base font-medium">Training URL</h3>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <x-base.lucide class="w-5 h-5 text-primary" icon="Globe" />
                            <span class="text-sm text-slate-700 truncate max-w-md">{{ $training->url }}</span>
                        </div>
                        <x-base.button as="a" href="{{ $training->url }}" target="_blank" variant="primary" size="sm">
                            <x-base.lucide class="w-4 h-4 mr-1" icon="ExternalLink" />
                            Open Link
                        </x-base.button>
                    </div>
                </div>
                @endif

                <!-- Attached Files -->
                @if ($training->content_type === 'file')
                <div class="box box--stacked p-5">
                    <div class="flex items-center justify-between border-b border-dashed border-slate-300/70 pb-5 mb-5">
                        <div class="flex items-center">
                            <x-base.lucide class="mr-2 h-6 w-6 text-primary" icon="Paperclip" />
                            <h3 class="text-base font-medium">Attached Files</h3>
                        </div>
                        <x-base.button as="a" href="{{ route('admin.trainings.edit', $training->id) }}" variant="outline-primary" size="sm">
                            <x-base.lucide class="w-4 h-4 mr-1" icon="Upload" />
                            Add Files
                        </x-base.button>
                    </div>
                    
                    @if ($training->media->count() > 0)
                        <div class="space-y-3">
                            @foreach ($training->media as $file)
                                @php
                                    $extension = pathinfo($file->file_name, PATHINFO_EXTENSION);
                                    $iconClass = match (strtolower($extension)) {
                                        'pdf' => 'text-red-600',
                                        'doc', 'docx' => 'text-blue-600',
                                        'xls', 'xlsx' => 'text-green-600',
                                        'ppt', 'pptx' => 'text-orange-600',
                                        'jpg', 'jpeg', 'png', 'gif' => 'text-purple-600',
                                        default => 'text-slate-600',
                                    };
                                    $iconName = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif']) ? 'Image' : 'FileText';
                                @endphp
                                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <x-base.lucide class="w-5 h-5 {{ $iconClass }}" icon="{{ $iconName }}" />
                                        <div>
                                            <p class="text-sm font-medium text-slate-800">{{ $file->file_name }}</p>
                                            <p class="text-xs text-slate-500">
                                                {{ number_format($file->size / 1024, 2) }} KB · {{ strtoupper($extension) }} · {{ $file->created_at->format('M d, Y') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.trainings.preview-document', $file->id) }}" target="_blank"
                                           class="inline-flex items-center justify-center w-8 h-8 text-primary hover:bg-primary/10 rounded-lg transition-colors"
                                           title="View">
                                            <x-base.lucide class="w-4 h-4" icon="Eye" />
                                        </a>
                                        <a href="{{ route('admin.trainings.preview-document', ['document' => $file->id, 'download' => true]) }}"
                                           class="inline-flex items-center justify-center w-8 h-8 text-success hover:bg-success/10 rounded-lg transition-colors"
                                           title="Download">
                                            <x-base.lucide class="w-4 h-4" icon="Download" />
                                        </a>
                                        <button type="button" onclick="confirmDeleteFile({{ $file->id }})"
                                                class="inline-flex items-center justify-center w-8 h-8 text-danger hover:bg-danger/10 rounded-lg transition-colors"
                                                title="Delete">
                                            <x-base.lucide class="w-4 h-4" icon="Trash2" />
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex items-center justify-center py-12">
                            <div class="text-center">
                                <x-base.lucide class="mx-auto h-12 w-12 text-slate-400" icon="FolderOpen" />
                                <h3 class="mt-4 text-lg font-medium text-slate-900">No files attached</h3>
                                <p class="mt-2 text-sm text-slate-500 mb-4">Add files by editing this training.</p>
                                <x-base.button as="a" href="{{ route('admin.trainings.edit', $training->id) }}" variant="primary">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="Pencil" />
                                    Edit Training
                                </x-base.button>
                            </div>
                        </div>
                    @endif
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-span-12 xl:col-span-4">
                <!-- Statistics -->
                <div class="box box--stacked p-5 mb-6">
                    <div class="flex items-center border-b border-dashed border-slate-300/70 pb-5 mb-5">
                        <x-base.lucide class="mr-2 h-6 w-6 text-primary" icon="BarChart3" />
                        <h3 class="text-base font-medium">Statistics</h3>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-primary/10 rounded-lg">
                                    <x-base.lucide class="w-5 h-5 text-primary" icon="Users" />
                                </div>
                                <span class="text-sm text-slate-600">Total Assignments</span>
                            </div>
                            <span class="text-2xl font-bold text-slate-800">{{ $assignmentStats['total'] ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-success/5 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-success/10 rounded-lg">
                                    <x-base.lucide class="w-5 h-5 text-success" icon="CheckCircle" />
                                </div>
                                <span class="text-sm text-slate-600">Completed</span>
                            </div>
                            <span class="text-2xl font-bold text-success">{{ $assignmentStats['completed'] ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-warning/5 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-warning/10 rounded-lg">
                                    <x-base.lucide class="w-5 h-5 text-warning" icon="Clock" />
                                </div>
                                <span class="text-sm text-slate-600">In Progress</span>
                            </div>
                            <span class="text-2xl font-bold text-warning">{{ $assignmentStats['in_progress'] ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-slate-200 rounded-lg">
                                    <x-base.lucide class="w-5 h-5 text-slate-600" icon="Circle" />
                                </div>
                                <span class="text-sm text-slate-600">Pending</span>
                            </div>
                            <span class="text-2xl font-bold text-slate-600">{{ $assignmentStats['pending'] ?? 0 }}</span>
                        </div>
                    </div>
                    
                    @if(($assignmentStats['total'] ?? 0) > 0)
                    <div class="mt-5 pt-5 border-t border-dashed border-slate-300/70">
                        <div class="text-xs uppercase tracking-widest text-slate-500 mb-3">Completion Rate</div>
                        @php
                            $completionRate = $assignmentStats['total'] > 0 
                                ? round(($assignmentStats['completed'] / $assignmentStats['total']) * 100) 
                                : 0;
                        @endphp
                        <div class="flex items-center gap-3">
                            <div class="flex-1 h-3 bg-slate-200 rounded-full overflow-hidden">
                                <div class="h-full bg-success rounded-full transition-all duration-500" style="width: {{ $completionRate }}%"></div>
                            </div>
                            <span class="text-sm font-bold text-slate-700">{{ $completionRate }}%</span>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Quick Actions -->
                <div class="box box--stacked p-5">
                    <div class="flex items-center border-b border-dashed border-slate-300/70 pb-5 mb-5">
                        <x-base.lucide class="mr-2 h-6 w-6 text-primary" icon="Zap" />
                        <h3 class="text-base font-medium">Quick Actions</h3>
                    </div>
                    <div class="space-y-3">
                        <x-base.button as="a" href="{{ route('admin.trainings.assign.form', $training->id) }}" 
                            variant="primary" class="w-full justify-center">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="UserPlus" />
                            Assign to Drivers
                        </x-base.button>
                        <x-base.button as="a" href="{{ route('admin.training-assignments.index', ['training_id' => $training->id]) }}" 
                            variant="outline-primary" class="w-full justify-center">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="List" />
                            View Assignments
                        </x-base.button>
                        <x-base.button as="a" href="{{ route('admin.trainings.edit', $training->id) }}" 
                            variant="outline-secondary" class="w-full justify-center">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="Settings" />
                            Edit Settings
                        </x-base.button>
                        <form action="{{ route('admin.trainings.destroy', $training->id) }}" method="POST" 
                              onsubmit="return confirm('Are you sure you want to delete this training?')">
                            @csrf
                            @method('DELETE')
                            <x-base.button type="submit" variant="outline-danger" class="w-full justify-center">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="Trash2" />
                                Delete Training
                            </x-base.button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete File Modal -->
    <x-base.dialog id="deleteFileModal">
        <x-base.dialog.panel>
            <div class="p-5 text-center">
                <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-danger" icon="XCircle" />
                <div class="mt-5 text-3xl">Are you sure?</div>
                <div class="mt-2 text-slate-500">
                    Do you really want to delete this file? <br>
                    This process cannot be undone.
                </div>
            </div>
            <div class="px-5 pb-8 text-center">
                <x-base.button class="mr-1 w-24" data-tw-dismiss="modal" type="button" variant="outline-secondary">
                    Cancel
                </x-base.button>
                <x-base.button class="w-24" type="button" variant="danger" onclick="deleteFile()">
                    Delete
                </x-base.button>
            </div>
        </x-base.dialog.panel>
    </x-base.dialog>
@endsection

@push('scripts')
<script>
    let fileIdToDelete = null;
    
    function confirmDeleteFile(fileId) {
        fileIdToDelete = fileId;
        const modal = tailwind.Modal.getOrCreateInstance(document.querySelector('#deleteFileModal'));
        modal.show();
    }
    
    function deleteFile() {
        if (!fileIdToDelete) return;
        
        fetch('{{ route("api.documents.delete.post") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ 
                mediaId: fileIdToDelete,
                _token: '{{ csrf_token() }}'
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                window.location.reload();
            } else {
                alert('Error deleting file: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Error: ' + error);
        });
    }
</script>
@endpush
