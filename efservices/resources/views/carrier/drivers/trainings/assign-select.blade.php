@extends('../themes/' . $activeTheme)
@section('title', 'Assign Training')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('carrier.dashboard')],
        ['label' => 'Driver Trainings Management', 'url' => route('carrier.trainings.index')],
        ['label' => 'Assign Training', 'active' => true],
    ];
@endphp

@section('subcontent')
    <div class="py-5">
        <!-- Toast Notifications -->
        <x-toast-notifications />

        <!-- Professional Header -->
        <div class="box box--stacked p-4 sm:p-6 lg:p-8 mb-6 lg:mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 lg:gap-6">
                <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-3 lg:gap-4">
                    <div class="p-2 sm:p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <x-base.lucide class="w-6 h-6 sm:w-8 sm:h-8 text-primary" icon="UserPlus" />
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-slate-800 mb-1 sm:mb-2">Assign Training to Drivers</h1>
                        <p class="text-sm sm:text-base text-slate-600">Select a training to assign to your drivers</p>
                    </div>
                </div>
                <div class="flex flex-col justify-center md:flex-row md:justify-end gap-2 lg:gap-3 w-full lg:w-auto">
                    <x-base.button as="a" href="{{ route('carrier.trainings.index') }}" class="w-full sm:w-auto"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="ArrowLeft" />
                        Back to Trainings
                    </x-base.button>
                </div>
            </div>
        </div>

        <!-- Trainings Grid -->
        <div class="box box--stacked">
            <div class="box-body p-4 sm:p-5">
                @if ($trainings->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                        @foreach ($trainings as $training)
                            <div class="box border border-slate-200 hover:border-primary/50 transition-all duration-200 hover:shadow-lg">
                                <div class="box-body p-4 sm:p-6">
                                    <!-- Content Type Badge -->
                                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                                        @if ($training->content_type == 'file')
                                            <span class="inline-flex items-center gap-1 px-2 sm:px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <x-base.lucide class="w-3 h-3" icon="file-text" />
                                                File
                                            </span>
                                        @elseif ($training->content_type == 'video')
                                            <span class="inline-flex items-center gap-1 px-2 sm:px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                <x-base.lucide class="w-3 h-3" icon="video" />
                                                Video
                                            </span>
                                        @elseif ($training->content_type == 'url')
                                            <span class="inline-flex items-center gap-1 px-2 sm:px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <x-base.lucide class="w-3 h-3" icon="link" />
                                                URL
                                            </span>
                                        @endif

                                        <span class="inline-flex items-center gap-1 px-2 sm:px-3 py-1 rounded-full text-xs font-medium bg-success/10 text-success">
                                            <x-base.lucide class="w-3 h-3" icon="check-circle" />
                                            Active
                                        </span>
                                    </div>

                                    <!-- Training Title -->
                                    <h3 class="text-base sm:text-lg font-semibold text-slate-800 mb-2 sm:mb-3 line-clamp-2 min-h-[2.5rem] sm:min-h-[3.5rem]">
                                        {{ $training->title }}
                                    </h3>

                                    <!-- Training Description -->
                                    <p class="text-xs sm:text-sm text-slate-600 mb-3 sm:mb-4 line-clamp-3 min-h-[3rem] sm:min-h-[4.5rem]">
                                        {{ $training->description }}
                                    </p>

                                    <!-- Training Meta Info -->
                                    <div class="flex items-center justify-between text-xs text-slate-500 mb-3 sm:mb-4 pb-3 sm:pb-4 border-b border-slate-200">
                                        <div class="flex items-center gap-1">
                                            <x-base.lucide class="w-3 h-3" icon="calendar" />
                                            <span>{{ $training->created_at->format('M d, Y') }}</span>
                                        </div>
                                        @if ($training->creator)
                                            <div class="flex items-center gap-1 min-w-0">
                                                <x-base.lucide class="w-3 h-3 flex-shrink-0" icon="user" />
                                                <span class="truncate max-w-[80px] sm:max-w-[120px]" title="{{ $training->creator->name }}">
                                                    {{ $training->creator->name }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Assign Button -->
                                    <x-base.button 
                                        as="a" 
                                        href="{{ route('carrier.trainings.assign.form', $training->id) }}" 
                                        variant="primary" 
                                        class="w-full justify-center text-sm sm:text-base">
                                        <x-base.lucide class="w-4 h-4 mr-2" icon="UserPlus" />
                                        <span class="hidden sm:inline">Assign to Drivers</span>
                                        <span class="sm:hidden">Assign</span>
                                    </x-base.button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <!-- Empty State -->
                    <div class="flex flex-col items-center justify-center py-16">
                        <div class="p-4 bg-slate-100 rounded-full mb-4">
                            <x-base.lucide class="h-16 w-16 text-slate-400" icon="book-open" />
                        </div>
                        <h3 class="text-xl font-semibold text-slate-700 mb-2">No Active Trainings Available</h3>
                        <p class="text-slate-500 mb-6 text-center max-w-md">
                            There are currently no active trainings available for assignment. Create a new training or activate an existing one to get started.
                        </p>
                        <x-base.button as="a" href="{{ route('carrier.trainings.create') }}" variant="primary">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="plus-circle" />
                            Create New Training
                        </x-base.button>
                    </div>
                @endif
            </div>
        </div>
    </div>

@pushOnce('scripts')
    @vite('resources/js/carrier-trainings-notifications.js')
@endPushOnce

@endsection
