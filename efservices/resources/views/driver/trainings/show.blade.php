@extends('../themes/' . $activeTheme)
@section('title', $training->title ?? 'Training Details')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],       
        ['label' => 'Trainings', 'url' => route('driver.trainings.index')],
        ['label' => 'Training Details', 'active' => true], 
    ];
@endphp

@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            {{-- Page Header --}}
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <a href="{{ route('driver.trainings.index') }}" 
                            class="flex items-center justify-center w-10 h-10 rounded-lg border border-slate-300 hover:bg-slate-50 transition-colors">
                            <x-base.lucide class="w-5 h-5 text-slate-600" icon="ArrowLeft" />
                        </a>
                        <h2 class="text-2xl font-bold text-slate-800">
                            {{ $training->title ?? 'Training Details' }}
                        </h2>
                    </div>
                </div>
                
                {{-- Status Badge --}}
                <div>
                    @if($assignment->status === 'completed')
                        <x-base.badge variant="success" class="text-base px-4 py-2">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="CheckCircle2" />
                            Completed
                        </x-base.badge>
                    @elseif($assignment->status === 'in_progress')
                        <x-base.badge variant="info" class="text-base px-4 py-2">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="Clock" />
                            In Progress
                        </x-base.badge>
                    @elseif($assignment->status === 'overdue')
                        <x-base.badge variant="danger" class="text-base px-4 py-2">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="AlertCircle" />
                            Overdue
                        </x-base.badge>
                    @else
                        <x-base.badge variant="warning" class="text-base px-4 py-2">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="Circle" />
                            Pending
                        </x-base.badge>
                    @endif
                </div>
            </div>

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="box p-4 mb-6 bg-success/10 border border-success/20 text-success rounded-lg">
                    <div class="flex items-center gap-3">
                        <x-base.lucide class="w-5 h-5 flex-shrink-0" icon="CheckCircle2" />
                        <p class="font-medium">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="box p-4 mb-6 bg-danger/10 border border-danger/20 text-danger rounded-lg">
                    <div class="flex items-center gap-3">
                        <x-base.lucide class="w-5 h-5 flex-shrink-0" icon="AlertCircle" />
                        <p class="font-medium">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Main Content Area --}}
                <div class="lg:col-span-2 space-y-6">
                    {{-- Training Description --}}
                    @if($training->description)
                        <div class="box p-6">
                            <h3 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                                <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
                                Description
                            </h3>
                            <div class="prose prose-slate max-w-none text-slate-600">
                                {!! nl2br(e($training->description)) !!}
                            </div>
                        </div>
                    @endif

                    {{-- Training Content based on type --}}
                    <div class="box p-6">
                        <h3 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                            <x-base.lucide class="w-5 h-5 text-primary" icon="BookOpen" />
                            Training Content
                        </h3>

                        @if($training->content_type === 'video' && $training->video_url)
                            {{-- Video Content --}}
                            <div class="space-y-4">
                                <div class="bg-slate-900 rounded-lg overflow-hidden aspect-video">
                                    @if(str_contains($training->video_url, 'youtube.com') || str_contains($training->video_url, 'youtu.be'))
                                        @php
                                            preg_match('/[\\?\\&]v=([^\\?\\&]+)/', $training->video_url, $matches);
                                            $videoId = $matches[1] ?? (str_contains($training->video_url, 'youtu.be') ? basename(parse_url($training->video_url, PHP_URL_PATH)) : '');
                                        @endphp
                                        <iframe 
                                            class="w-full h-full" 
                                            src="https://www.youtube.com/embed/{{ $videoId }}" 
                                            frameborder="0" 
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                            allowfullscreen>
                                        </iframe>
                                    @elseif(str_contains($training->video_url, 'vimeo.com'))
                                        @php
                                            $videoId = basename(parse_url($training->video_url, PHP_URL_PATH));
                                        @endphp
                                        <iframe 
                                            class="w-full h-full" 
                                            src="https://player.vimeo.com/video/{{ $videoId }}" 
                                            frameborder="0" 
                                            allow="autoplay; fullscreen; picture-in-picture" 
                                            allowfullscreen>
                                        </iframe>
                                    @else
                                        <video controls class="w-full h-full">
                                            <source src="{{ $training->video_url }}" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                    @endif
                                </div>
                                <a href="{{ $training->video_url }}" 
                                    target="_blank"
                                    class="flex items-center gap-2 text-primary hover:text-primary/80 text-sm font-medium">
                                    <x-base.lucide class="w-4 h-4" icon="ExternalLink" />
                                    Open video in new tab
                                </a>
                            </div>

                        @elseif($training->content_type === 'url' && $training->url)
                            {{-- URL Content --}}
                            <div class="space-y-4">
                                <div class="bg-slate-50 border-2 border-dashed border-slate-300 rounded-lg p-6 text-center">
                                    <x-base.lucide class="w-12 h-12 text-slate-400 mx-auto mb-3" icon="ExternalLink" />
                                    <p class="text-slate-600 mb-4">This training is hosted externally</p>
                                    <a href="{{ $training->url }}" 
                                        target="_blank"
                                        class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors font-medium">
                                        <x-base.lucide class="w-5 h-5" icon="ExternalLink" />
                                        Open Training Website
                                    </a>
                                </div>
                                <div class="text-xs text-slate-500 bg-slate-50 rounded p-3">
                                    <strong>URL:</strong> {{ $training->url }}
                                </div>
                            </div>

                        @elseif($training->content_type === 'file' && $media->count() > 0)
                            {{-- File Content --}}
                            <div class="space-y-3">
                                @foreach($media as $file)
                                    <div class="flex items-center justify-between p-4 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 transition-colors">
                                        <div class="flex items-center gap-3 flex-1 min-w-0">
                                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary/10">
                                                @php
                                                    $extension = strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION));
                                                    $icon = match($extension) {
                                                        'pdf' => 'FileText',
                                                        'doc', 'docx' => 'FileText',
                                                        'xls', 'xlsx' => 'Table',
                                                        'ppt', 'pptx' => 'Presentation',
                                                        'jpg', 'jpeg', 'png', 'gif' => 'Image',
                                                        'mp4', 'avi', 'mov' => 'Video',
                                                        default => 'File'
                                                    };
                                                @endphp
                                                <x-base.lucide class="w-5 h-5 text-primary" :icon="$icon" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium text-slate-800 truncate">{{ $file->file_name }}</p>
                                                <p class="text-sm text-slate-500">{{ number_format($file->size / 1024, 2) }} KB</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 ml-4">
                                            <a href="{{ route('driver.trainings.documents.preview', $file->id) }}" 
                                                target="_blank"
                                                class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-sm font-medium">
                                                <x-base.lucide class="w-4 h-4" icon="Eye" />
                                                <span class="hidden sm:inline">View</span>
                                            </a>
                                            <a href="{{ route('driver.trainings.documents.preview', ['media' => $file->id, 'download' => true]) }}" 
                                                class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium">
                                                <x-base.lucide class="w-4 h-4" icon="Download" />
                                                <span class="hidden sm:inline">Download</span>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            {{-- No Content Available --}}
                            <div class="text-center py-12 bg-slate-50 rounded-lg">
                                <x-base.lucide class="w-16 h-16 text-slate-300 mx-auto mb-4" icon="FileQuestion" />
                                <p class="text-slate-600">No training content available</p>
                            </div>
                        @endif
                    </div>

                    {{-- Completion Notes (if completed) --}}
                    @if($assignment->status === 'completed' && $assignment->completion_notes)
                        <div class="box p-6 bg-success/5 border-success/20">
                            <h3 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                                <x-base.lucide class="w-5 h-5 text-success" icon="MessageSquare" />
                                Completion Notes
                            </h3>
                            <p class="text-slate-700">{{ $assignment->completion_notes }}</p>
                        </div>
                    @endif
                </div>

                {{-- Sidebar --}}
                <div class="lg:col-span-1 space-y-6">
                    {{-- Training Information Card --}}
                    <div class="box p-6">
                        <h3 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                            <x-base.lucide class="w-5 h-5 text-primary" icon="Info" />
                            Information
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-medium text-slate-500 uppercase mb-1 block">Assigned Date</label>
                                <p class="text-sm text-slate-800">{{ $assignment->assigned_date ? $assignment->assigned_date->format('M d, Y') : 'N/A' }}</p>
                            </div>
                            
                            @if($assignment->due_date)
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase mb-1 block">Due Date</label>
                                    <p class="text-sm font-medium {{ $assignment->isOverdue() && $assignment->status !== 'completed' ? 'text-danger' : 'text-slate-800' }}">
                                        {{ $assignment->due_date->format('M d, Y') }}
                                        @if($assignment->isOverdue() && $assignment->status !== 'completed')
                                            <span class="text-xs">(Overdue)</span>
                                        @endif
                                    </p>
                                </div>
                            @endif

                            @if($assignment->completed_date)
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase mb-1 block">Completed Date</label>
                                    <p class="text-sm text-success font-medium">{{ $assignment->completed_date->format('M d, Y H:i') }}</p>
                                </div>
                            @endif

                            <div>
                                <label class="text-xs font-medium text-slate-500 uppercase mb-1 block">Content Type</label>
                                <p class="text-sm text-slate-800 capitalize">
                                    @if($training->content_type === 'video')
                                        <span class="flex items-center gap-2">
                                            <x-base.lucide class="w-4 h-4 text-purple-600" icon="Video" />
                                            Video Training
                                        </span>
                                    @elseif($training->content_type === 'url')
                                        <span class="flex items-center gap-2">
                                            <x-base.lucide class="w-4 h-4 text-blue-600" icon="ExternalLink" />
                                            Online Training
                                        </span>
                                    @elseif($training->content_type === 'file')
                                        <span class="flex items-center gap-2">
                                            <x-base.lucide class="w-4 h-4 text-green-600" icon="FileText" />
                                            Document Training
                                        </span>
                                    @endif
                                </p>
                            </div>

                            @if($training->content_type === 'file')
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase mb-1 block">Files</label>
                                    <p class="text-sm text-slate-800">{{ $media->count() }} {{ $media->count() === 1 ? 'file' : 'files' }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="box p-6 space-y-3">
                        @if($assignment->status === 'assigned' || $assignment->status === 'overdue')
                            <form action="{{ route('driver.trainings.start-progress', $assignment->id) }}" method="POST">
                                @csrf
                                <button type="submit" 
                                    class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors font-medium min-h-[44px]">
                                    <x-base.lucide class="w-5 h-5" icon="Play" />
                                    Start Training
                                </button>
                            </form>
                        @elseif($assignment->status === 'in_progress')
                            <button 
                                onclick="openCompletionModal()"
                                class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-success text-white rounded-lg hover:bg-success/90 transition-colors font-medium min-h-[44px]">
                                <x-base.lucide class="w-5 h-5" icon="CheckCircle2" />
                                Mark as Complete
                            </button>
                        @elseif($assignment->status === 'completed')
                            <div class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-success/10 text-success rounded-lg font-medium min-h-[44px]">
                                <x-base.lucide class="w-5 h-5" icon="CheckCircle2" />
                                Training Completed
                            </div>
                        @endif

                        <a href="{{ route('driver.trainings.index') }}" 
                            class="w-full flex items-center justify-center gap-2 px-6 py-3 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors font-medium min-h-[44px]">
                            <x-base.lucide class="w-5 h-5" icon="ArrowLeft" />
                            Back to All Trainings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Completion Modal (only for in_progress status) --}}
    @if($assignment->status === 'in_progress')
        <x-base.dialog id="completion-modal" staticBackdrop>
            <x-base.dialog.panel class="max-w-lg">
                <form action="{{ route('driver.trainings.complete', $assignment->id) }}" method="POST">
                    @csrf
                    <x-base.dialog.title class="border-b border-slate-200 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-success/10">
                                <x-base.lucide class="w-5 h-5 text-success" icon="CheckCircle2" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-800">Complete Training</h3>
                                <p class="text-sm text-slate-500">Confirm your training completion</p>
                            </div>
                        </div>
                    </x-base.dialog.title>
                    
                    <x-base.dialog.description class="py-6 space-y-4">
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                            <h4 class="font-semibold text-slate-800 mb-1">{{ $training->title }}</h4>
                            @if($training->description)
                                <p class="text-sm text-slate-600 line-clamp-2">{{ $training->description }}</p>
                            @endif
                        </div>

                        <div class="space-y-3">
                            <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg border-2 border-slate-200 hover:border-slate-300 transition-colors">
                                <input type="checkbox" 
                                    name="confirmed" 
                                    required
                                    class="mt-1 rounded border-slate-300 text-success focus:ring-success focus:ring-offset-0 cursor-pointer w-5 h-5">
                                <div class="flex-1">
                                    <div class="font-medium text-slate-800">I confirm that I have completed this training</div>
                                    <div class="text-sm text-slate-600 mt-1">
                                        By checking this box, you acknowledge that you have reviewed all training materials and completed the required content.
                                    </div>
                                </div>
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">
                                Notes (Optional)
                            </label>
                            <textarea 
                                name="notes" 
                                rows="3" 
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm resize-none"
                                placeholder="Add any notes or comments about your training completion..."
                                maxlength="500"></textarea>
                        </div>
                    </x-base.dialog.description>
                    
                    <x-base.dialog.footer class="border-t border-slate-200 pt-4">
                        <div class="flex gap-3 justify-end w-full">
                            <button 
                                type="button" 
                                data-tw-dismiss="modal"
                                class="px-6 py-2.5 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors font-medium min-h-[44px]">
                                Cancel
                            </button>
                            <button 
                                type="submit" 
                                class="px-6 py-2.5 bg-success text-white rounded-lg hover:bg-success/90 transition-colors font-medium flex items-center gap-2 min-h-[44px]">
                                <x-base.lucide class="w-4 h-4" icon="CheckCircle2" />
                                Complete Training
                            </button>
                        </div>
                    </x-base.dialog.footer>
                </form>
            </x-base.dialog.panel>
        </x-base.dialog>
    @endif
@endsection

@push('scripts')
    <script>
        function openCompletionModal() {
            const modal = tailwind.Modal.getOrCreateInstance(document.querySelector("#completion-modal"));
            modal.show();
        }
    </script>
@endpush

