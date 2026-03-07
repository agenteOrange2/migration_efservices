@extends('../themes/' . $activeTheme)
@section('title', 'All Training School Documents')
@php
    use Illuminate\Support\Facades\Storage;

    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Training Schools', 'url' => route('admin.training-schools.index')],
        ['label' => 'All Documents', 'active' => true],
    ];
@endphp

@section('subcontent')

<!-- Professional Breadcrumbs -->
<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<!-- Flash Messages -->
@if (session()->has('success'))
    <div class="alert alert-success flex items-center mb-5">
        <x-base.lucide class="w-6 h-6 mr-2" icon="check-circle" />
        {{ session('success') }}
    </div>
@endif

@if (session()->has('error'))
    <div class="alert alert-danger flex items-center mb-5">
        <x-base.lucide class="w-6 h-6 mr-2" icon="alert-circle" />
        {{ session('error') }}
    </div>
@endif

<!-- Professional Header -->
<div class="box box--stacked p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                <x-base.lucide class="w-8 h-8 text-primary" icon="FileText" />
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">All Training School Documents</h1>
                <p class="text-slate-600">View and manage all training school documents</p>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <x-base.button as="a" href="{{ route('admin.training-schools.index') }}" variant="secondary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                Back to Training Schools
            </x-base.button>
        </div>
    </div>
</div>

    <!-- Filtros -->
    <div class="box box--stacked mt-5">
        <div class="box-body p-5">
            <form action="{{ route('admin.training-schools.docs.all') }}" method="GET"
                class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <x-base.form-label for="school_filter">School</x-base.form-label>
                    <select id="school_filter" name="school" class="form-select">
                        <option value="">All Schools</option>
                        @foreach ($schools as $school)
                            <option value="{{ $school->id }}"
                                {{ request()->query('school') == $school->id ? 'selected' : '' }}>
                                {{ $school->school_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-base.form-label for="driver_filter">Driver</x-base.form-label>
                    <select id="driver_filter" name="driver" class="form-select">
                        <option value="">All Drivers</option>
                        @foreach ($drivers as $driver)
                            <option value="{{ $driver->id }}"
                                {{ request()->query('driver') == $driver->id ? 'selected' : '' }}>
                                {{ $driver->user->name }} {{ $driver->user->last_name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-base.form-label for="file_type">File Type</x-base.form-label>
                    <select id="file_type" name="file_type" class="form-select">
                        <option value="">All Types</option>
                        <option value="pdf" {{ request()->query('file_type') == 'pdf' ? 'selected' : '' }}>PDF
                        </option>
                        <option value="image" {{ request()->query('file_type') == 'image' ? 'selected' : '' }}>Images
                        </option>
                        <option value="doc" {{ request()->query('file_type') == 'doc' ? 'selected' : '' }}>Documents
                        </option>
                    </select>
                </div>

                <div>
                    <x-base.form-label for="upload_date_from">Upload Date (From)</x-base.form-label>
                    <x-base.litepicker id="upload_date_from" name="upload_from"
                        value="{{ request()->query('upload_from') }}" placeholder="MM/DD/YYYY" />
                </div>

                <div>
                    <x-base.form-label for="upload_date_to">Upload Date (To)</x-base.form-label>
                    <x-base.litepicker id="upload_date_to" name="upload_to" value="{{ request()->query('upload_to') }}"
                        placeholder="MM/DD/YYYY" />
                </div>

                <div class="flex items-end">
                    <x-base.button type="submit" variant="primary" class="mr-2">
                        <x-base.lucide class="w-4 h-4 mr-1" icon="search" />
                        Filter
                    </x-base.button>
                    <a href="{{ route('admin.training-schools.docs.all') }}" class="btn btn-outline-secondary">
                        <x-base.lucide class="w-4 h-4 mr-1" icon="refresh-cw" />
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

<!-- Documents Table -->
<div class="box box--stacked">
    <div class="p-6 border-b border-slate-200/60">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
                <h2 class="text-lg font-semibold text-slate-800">Documents</h2>
                @if ($documents->count() > 0)
                    <x-base.badge variant="primary" class="px-3 py-1.5">
                        {{ $documents->total() }} Total
                    </x-base.badge>
                @endif
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        @if ($documents->count() > 0)
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200/60">
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">#</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Document</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Size</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">School</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Driver</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Uploaded</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60">
                    @foreach ($documents as $index => $document)
                        @php
                            $trainingSchool = \App\Models\Admin\Driver\DriverTrainingSchool::find($document->model_id);
                            $extension = pathinfo($document->file_name, PATHINFO_EXTENSION);
                            $iconClass = 'file-text';
                            
                            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                                $iconClass = 'image';
                            } elseif (in_array($extension, ['pdf'])) {
                                $iconClass = 'file-text';
                            } elseif (in_array($extension, ['doc', 'docx'])) {
                                $iconClass = 'file';
                            } elseif (in_array($extension, ['xls', 'xlsx', 'csv'])) {
                                $iconClass = 'file-spreadsheet';
                            }
                        @endphp
                        <tr id="document-row-{{ $document->id }}" class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="text-sm text-slate-700">{{ $loop->iteration + ($documents->currentPage() - 1) * $documents->perPage() }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <x-base.lucide class="w-5 h-5 text-primary" icon="{{ $iconClass }}" />
                                    <span class="text-sm font-medium text-slate-800">{{ $document->file_name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-slate-700">{{ strtoupper($extension) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-slate-700">{{ $document->human_readable_size }}</span>
                            </td>
                            <td class="px-6 py-4">
                                @if ($trainingSchool)
                                    <a href="{{ route('admin.training-schools.show', $trainingSchool->id) }}"
                                        class="text-sm font-medium text-primary hover:text-primary/80 transition-colors">
                                        {{ $trainingSchool->school_name }}
                                    </a>
                                @else
                                    <span class="text-sm text-slate-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($trainingSchool && $trainingSchool->userDriverDetail)
                                    <span class="text-sm text-slate-700">
                                        {{ implode(' ', array_filter([$trainingSchool->userDriverDetail->user->name, $trainingSchool->userDriverDetail->middle_name, $trainingSchool->userDriverDetail->last_name])) ?: 'N/A' }}
                                    </span>
                                @else
                                    <span class="text-sm text-slate-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-slate-700">{{ $document->created_at->format('M d, Y H:i') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <x-base.button as="a" 
                                        href="{{ route('admin.training-schools.docs.preview', $document->id) }}" 
                                        target="_blank"
                                        variant="primary" 
                                        size="sm"
                                        class="gap-1.5"
                                        title="View">
                                        <x-base.lucide class="w-4 h-4" icon="Eye" />
                                    </x-base.button>
                                    @if ($trainingSchool)
                                        <x-base.button as="a" 
                                            href="{{ route('admin.training-schools.edit', $trainingSchool->id) }}"
                                            variant="warning" 
                                            size="sm"
                                            class="gap-1.5"
                                            title="Edit School">
                                            <x-base.lucide class="w-4 h-4" icon="Edit" />
                                        </x-base.button>
                                    @endif
                                    <form action="{{ route('admin.training-schools.docs.delete', $document->id) }}" 
                                        method="POST" 
                                        class="inline"
                                        onsubmit="return confirm('Are you sure you want to delete this document?');">
                                        @csrf
                                        @method('DELETE')
                                        <x-base.button type="submit" variant="danger" size="sm" class="gap-1.5" title="Delete">
                                            <x-base.lucide class="w-4 h-4" icon="Trash2" />
                                        </x-base.button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="p-12 text-center">
                <div class="flex flex-col items-center justify-center py-8">
                    <div class="bg-slate-100 rounded-full p-4 mb-4 w-16 h-16 flex items-center justify-center">
                        <x-base.lucide class="w-8 h-8 text-slate-400" icon="FileText" />
                    </div>
                    <p class="text-slate-600 font-medium mb-1">No documents found</p>
                    <p class="text-sm text-slate-500 mb-5">No documents found matching your criteria.</p>
                    <x-base.button as="a" href="{{ route('admin.training-schools.index') }}" variant="primary" class="gap-2">
                        <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                        Back to Training Schools
                    </x-base.button>
                </div>
            </div>
        @endif
    </div>

    @if ($documents->hasPages())
        <div class="p-6 border-t border-slate-200/60">
            {{ $documents->appends(request()->query())->links('custom.pagination') }}
        </div>
    @endif
</div>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Inicializar tom-select para selectores
                if (document.querySelector('#school_filter')) {
                    new TomSelect('#school_filter', {
                        plugins: {
                            'dropdown_input': {}
                        }
                    });
                }

                if (document.querySelector('#driver_filter')) {
                    new TomSelect('#driver_filter', {
                        plugins: {
                            'dropdown_input': {}
                        }
                    });
                }

                if (document.querySelector('#file_type')) {
                    new TomSelect('#file_type');
                }
            });
        </script>
    @endpush

@endsection
