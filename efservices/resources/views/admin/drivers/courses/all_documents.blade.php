@extends('../themes/' . $activeTheme)
@section('title', 'All Course Documents')
@php
    use Illuminate\Support\Facades\Storage;
    
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Courses', 'url' => route('admin.courses.index')],
        ['label' => 'All Documents', 'active' => true],
    ];
@endphp

@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <!-- Flash Messages -->
            @if (session('success'))
                <div class="alert alert-success flex items-center mb-5">
                    <x-base.lucide class="w-6 h-6 mr-2" icon="CheckCircle" />
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger flex items-center mb-5">
                    <x-base.lucide class="w-6 h-6 mr-2" icon="AlertCircle" />
                    {{ session('error') }}
                </div>
            @endif

            <!-- Professional Header -->
            <div class="box box--stacked p-4 sm:p-6 lg:p-8 mb-6 lg:mb-8">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 lg:gap-6">
                    <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-3 lg:gap-4">
                        <div class="p-2 sm:p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <x-base.lucide class="w-6 h-6 sm:w-8 sm:h-8 text-primary" icon="FileText" />
                        </div>
                        <div>
                            <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-slate-800 mb-1 sm:mb-2">All Course Documents</h1>
                            <p class="text-sm sm:text-base text-slate-600">View and manage all documents from driver courses</p>
                        </div>
                    </div>
                    <div class="mt-4 md:mt-0 flex flex-col sm:flex-row gap-2 w-full justify-end">
                        <x-base.button as="a" href="{{ route('admin.courses.index') }}"
                            variant="outline-primary">
                            <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowLeft" />
                            Back to Courses
                        </x-base.button>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
                <div class="box box--stacked p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-primary/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-primary" icon="Files" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-slate-800">{{ $documents->total() }}</div>
                            <div class="text-xs text-slate-500">Total Documents</div>
                        </div>
                    </div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-danger/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-danger" icon="FileType" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-danger">{{ $documents->where('mime_type', 'application/pdf')->count() }}</div>
                            <div class="text-xs text-slate-500">PDF Files</div>
                        </div>
                    </div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-success/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-success" icon="Image" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-success">{{ $documents->filter(fn($d) => str_starts_with($d->mime_type ?? '', 'image/'))->count() }}</div>
                            <div class="text-xs text-slate-500">Images</div>
                        </div>
                    </div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-info/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-info" icon="GraduationCap" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-info">{{ $courses->count() }}</div>
                            <div class="text-xs text-slate-500">Courses</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="box box--stacked">
                <div class="box-body p-5">
                    <form action="{{ route('admin.courses.all-documents') }}" method="GET" id="filter-form">
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                            <div>
                                <x-base.form-label>Course</x-base.form-label>
                                <select name="course" id="course-filter"
                                    class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                    <option value="">All Courses</option>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}"
                                            {{ request('course') == $course->id ? 'selected' : '' }}>
                                            {{ $course->organization_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-base.form-label>Driver</x-base.form-label>
                                <select name="driver" id="driver-filter"
                                    class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                    <option value="">All Drivers</option>
                                    @foreach ($drivers as $driver)
                                        <option value="{{ $driver->id }}"
                                            {{ request('driver') == $driver->id ? 'selected' : '' }}>
                                            {{ $driver->user->name ?? '' }} {{ $driver->middle_name ?? '' }} {{ $driver->last_name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-base.form-label>File Type</x-base.form-label>
                                <select name="file_type"
                                    class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                    <option value="">All Types</option>
                                    <option value="pdf" {{ request('file_type') == 'pdf' ? 'selected' : '' }}>PDF</option>
                                    <option value="image" {{ request('file_type') == 'image' ? 'selected' : '' }}>Images</option>
                                    <option value="doc" {{ request('file_type') == 'doc' ? 'selected' : '' }}>Documents</option>
                                </select>
                            </div>

                            <div>
                                <x-base.form-label>Date From</x-base.form-label>
                                <x-base.litepicker id="upload_from" name="upload_from" class="w-full" 
                                    value="{{ request('upload_from') }}" placeholder="Select date" />
                            </div>

                            <div>
                                <x-base.form-label>Date To</x-base.form-label>
                                <x-base.litepicker id="upload_to" name="upload_to" class="w-full" 
                                    value="{{ request('upload_to') }}" placeholder="Select date" />
                            </div>

                            <div class="flex items-end gap-2">
                                <x-base.button type="submit" variant="primary">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="Filter" />
                                    Filter
                                </x-base.button>
                                <x-base.button as="a" href="{{ route('admin.courses.all-documents') }}"
                                    variant="outline-secondary">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="RefreshCw" />
                                    Reset
                                </x-base.button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Documents Table -->
            <div class="box box--stacked mt-5">
                <div class="box-body p-5">
                    <div class="overflow-auto xl:overflow-visible">
                        <x-base.table class="border-b border-slate-200/60">
                            <x-base.table.thead>
                                <x-base.table.tr>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500 w-12">
                                        #
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Driver
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Course
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        File Name
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Type
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Size
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Upload Date
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 text-center font-medium text-slate-500 w-32">
                                        Actions
                                    </x-base.table.td>
                                </x-base.table.tr>
                            </x-base.table.thead>
                            <x-base.table.tbody>
                                @forelse($documents as $index => $document)
                                    <x-base.table.tr class="hover:bg-slate-50 transition-colors">
                                        <x-base.table.td class="border-dashed py-4">
                                            {{ $index + 1 + ($documents->currentPage() - 1) * $documents->perPage() }}
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-100 flex items-center justify-center flex-shrink-0">
                                                    @if($document->model && $document->model->driverDetail && $document->model->driverDetail->getFirstMediaUrl('profile_photo_driver'))
                                                        <img src="{{ $document->model->driverDetail->getFirstMediaUrl('profile_photo_driver') }}"
                                                            alt="Driver Photo" class="w-full h-full object-cover">
                                                    @else
                                                        <x-base.lucide class="w-5 h-5 text-slate-400" icon="User" />
                                                    @endif
                                                </div>
                                                <div>
                                                    @if($document->model && $document->model->driverDetail && $document->model->driverDetail->user)
                                                        <div class="font-medium text-slate-800">
                                                            {{ $document->model->driverDetail->user->name ?? '' }}
                                                            {{ $document->model->driverDetail->middle_name ?? '' }}
                                                            {{ $document->model->driverDetail->last_name ?? '' }}
                                                        </div>
                                                    @else
                                                        <span class="text-slate-400">Unknown</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            @if($document->model)
                                                <a href="{{ route('admin.courses.edit', $document->model->id) }}" 
                                                    class="text-primary hover:underline font-medium">
                                                    {{ Str::limit($document->model->organization_name, 25) }}
                                                </a>
                                            @else
                                                <span class="text-slate-400">Unknown</span>
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            <div class="flex items-center gap-2">
                                                @php
                                                    $extension = strtolower(pathinfo($document->file_name, PATHINFO_EXTENSION));
                                                    $iconName = 'File';
                                                    $iconColor = 'text-slate-500';
                                                    
                                                    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                                                        $iconName = 'Image';
                                                        $iconColor = 'text-success';
                                                    } elseif ($extension === 'pdf') {
                                                        $iconName = 'FileText';
                                                        $iconColor = 'text-danger';
                                                    } elseif (in_array($extension, ['doc', 'docx'])) {
                                                        $iconName = 'FileType';
                                                        $iconColor = 'text-primary';
                                                    }
                                                @endphp
                                                <x-base.lucide class="w-4 h-4 {{ $iconColor }}" icon="{{ $iconName }}" />
                                                <span class="text-sm">{{ Str::limit($document->file_name, 30) }}</span>
                                            </div>
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                @if($extension === 'pdf') bg-danger/10 text-danger
                                                @elseif(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) bg-success/10 text-success
                                                @else bg-primary/10 text-primary @endif">
                                                {{ strtoupper($extension) }}
                                            </span>
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4 text-slate-600">
                                            {{ number_format($document->size / 1024, 2) }} KB
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4 text-slate-600">
                                            {{ $document->created_at ? $document->created_at->format('M d, Y') : 'N/A' }}
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="{{ route('admin.courses.documents.preview', $document->id) }}" 
                                                    target="_blank"
                                                    class="inline-flex items-center justify-center w-8 h-8 text-primary hover:bg-primary/10 rounded-lg transition-colors"
                                                    title="View Document">
                                                    <x-base.lucide class="w-4 h-4" icon="Eye" />
                                                </a>
                                                <a href="{{ $document->getUrl() }}" 
                                                    download
                                                    class="inline-flex items-center justify-center w-8 h-8 text-success hover:bg-success/10 rounded-lg transition-colors"
                                                    title="Download">
                                                    <x-base.lucide class="w-4 h-4" icon="Download" />
                                                </a>
                                                <button type="button" 
                                                    onclick="confirmDelete({{ $document->id }}, '{{ addslashes($document->file_name) }}')"
                                                    class="inline-flex items-center justify-center w-8 h-8 text-danger hover:bg-danger/10 rounded-lg transition-colors"
                                                    title="Delete">
                                                    <x-base.lucide class="w-4 h-4" icon="Trash2" />
                                                </button>
                                            </div>
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @empty
                                    <x-base.table.tr>
                                        <x-base.table.td colspan="8" class="text-center py-12">
                                            <div class="flex flex-col items-center">
                                                <x-base.lucide class="w-16 h-16 text-slate-300 mb-4" icon="FileX" />
                                                <h3 class="text-lg font-medium text-slate-600 mb-2">No documents found</h3>
                                                <p class="text-slate-400 text-center max-w-sm">
                                                    No documents match your search criteria.
                                                </p>
                                            </div>
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @endforelse
                            </x-base.table.tbody>
                        </x-base.table>
                    </div>

                    <!-- Pagination -->
                    @if ($documents->hasPages())
                        <div class="border-t border-slate-200/60 pt-5 mt-5">
                            {{ $documents->appends(request()->except('page'))->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <x-base.dialog id="deleteModal">
        <x-base.dialog.panel>
            <div class="p-5 text-center">
                <x-base.lucide class="w-16 h-16 mx-auto mt-3 text-danger" icon="XCircle" />
                <div class="mt-5 text-3xl">Are you sure?</div>
                <div class="mt-2 text-slate-500" id="delete-message">
                    Do you really want to delete this document? <br>
                    This process cannot be undone.
                </div>
            </div>
            <div class="px-5 pb-8 text-center">
                <x-base.button class="w-24 mr-1" data-tw-dismiss="modal" type="button" variant="outline-secondary">
                    Cancel
                </x-base.button>
                <x-base.button class="w-24" type="button" variant="danger" id="confirmDeleteBtn">
                    Delete
                </x-base.button>
            </div>
        </x-base.dialog.panel>
    </x-base.dialog>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize TomSelect for filters
        if (typeof TomSelect !== 'undefined') {
            if (document.getElementById('course-filter')) {
                new TomSelect('#course-filter', {
                    placeholder: 'Select course',
                    allowEmptyOption: true
                });
            }
            if (document.getElementById('driver-filter')) {
                new TomSelect('#driver-filter', {
                    placeholder: 'Select driver',
                    allowEmptyOption: true
                });
            }
        }
    });

    let deleteDocumentId = null;

    function confirmDelete(id, fileName) {
        deleteDocumentId = id;
        document.getElementById('delete-message').innerHTML = 
            `Do you really want to delete "<strong>${fileName}</strong>"? <br>This process cannot be undone.`;
        const modal = tailwind.Modal.getOrCreateInstance(document.querySelector('#deleteModal'));
        modal.show();
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (deleteDocumentId) {
            fetch('{{ route("api.documents.delete.post") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ mediaId: deleteDocumentId })
            })
            .then(response => response.json())
            .then(data => {
                const modal = tailwind.Modal.getOrCreateInstance(document.querySelector('#deleteModal'));
                modal.hide();
                
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Error deleting document');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error processing request');
            });
        }
    });
</script>
@endpush
