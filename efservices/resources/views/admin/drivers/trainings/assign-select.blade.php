@extends('../themes/' . $activeTheme)

@section('title', 'Select Training to Assign')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Trainings', 'url' => route('admin.trainings.index')],
        ['label' => 'Select to Assign', 'active' => true],
    ];
@endphp

@section('subcontent')

    <!-- Professional Header -->
    <div class="box box--stacked p-4 sm:p-3 lg:p-4 mb-3 lg:mb-4">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 lg:gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-3 lg:gap-4">
                <div class="p-2 sm:p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-6 h-6 sm:w-8 sm:h-8 text-primary" icon="BookPlus" />
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-slate-800 mb-1 sm:mb-2">Select Training to
                        Assign
                    </h1>
                    <p class="text-sm sm:text-base text-slate-600">Available Trainings</p>
                </div>
            </div>
            <div class="mt-4 md:mt-0 flex flex-col sm:flex-row gap-2 w-full justify-end">
                <x-base.button as="a" href="{{ route('admin.trainings.index') }}" variant="primary">
                    <x-base.lucide class="w-5 h-5 mr-2" icon="arrow-left" />
                    Back To Trainings
                </x-base.button>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-2 sm:px-2 lg:px-2 py-8">
        <div class="box box--stacked mt-5 p-3">
            <div class="box-content">
                @if ($trainings->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($trainings as $training)
                            <div class="border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                                <div class="p-4 border-b bg-gray-50">
                                    <h3 class="text-lg font-medium text-gray-900 truncate">{{ $training->title }}</h3>
                                </div>
                                <div class="p-4">
                                    <p class="text-sm text-gray-600 mb-4 line-clamp-3">
                                        {{ Str::limit($training->description, 150) }}
                                    </p>

                                    <div class="flex items-center text-sm text-gray-500 mb-4">
                                        <x-base.lucide class="w-4 h-4 mr-1" icon="calendar" />
                                        <span>{{ $training->created_at->format('m/d/Y') }}</span>

                                        <span class="mx-2">•</span>

                                        @php
                                            $filesCount = \Spatie\MediaLibrary\MediaCollections\Models\Media::where(
                                                'model_type',
                                                \App\Models\Admin\Driver\Training::class,
                                            )
                                                ->where('model_id', $training->id)
                                                ->where('collection_name', 'training_files')
                                                ->count();
                                        @endphp

                                        <x-base.lucide class="w-4 h-4 mr-1" icon="file" />
                                        <span>{{ $filesCount }} {{ $filesCount === 1 ? 'file' : 'files' }}</span>
                                    </div>

                                    <div class="flex justify-end">
                                        <x-base.button as="a"
                                            href="{{ route('admin.trainings.assign.form', $training->id) }}"
                                            variant="primary">
                                            <x-base.lucide class="w-5 h-5 mr-2" icon="users" />
                                            Assign
                                        </x-base.button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <x-base.lucide class="w-16 h-16 mx-auto text-gray-400" icon="file-question" />
                        <h3 class="mt-4 text-lg font-medium text-gray-900">No trainings available</h3>
                        <p class="mt-1 text-sm text-gray-500">Create a training first to be able to assign it to drivers.
                        </p>
                        <div class="mt-6">
                            <x-base.button as="a" href="{{ route('admin.trainings.create') }}">
                                <x-base.lucide class="w-5 h-5 mr-2" icon="plus" />
                                Create Training
                            </x-base.button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
