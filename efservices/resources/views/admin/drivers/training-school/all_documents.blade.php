@extends('../themes/' . $activeTheme)
@section('title', 'All Training School Documents')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Training Schools', 'url' => route('admin.training-schools.index')],
        ['label' => 'All Documents', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <!-- Mensajes Flash -->
            @if (session()->has('success'))
                <div class="alert alert-success flex items-center mb-5">
                    <x-base.lucide class="w-6 h-6 mr-2" icon="CheckCircle" />
                    {{ session('success') }}
                </div>
            @endif

            @if (session()->has('error'))
                <div class="alert alert-danger flex items-center mb-5">
                    <x-base.lucide class="w-6 h-6 mr-2" icon="AlertCircle" />
                    {{ session('error') }}
                </div>
            @endif
            <!-- Professional Header -->
            <div class="box box--stacked p-8 mb-8">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <x-base.lucide class="w-8 h-8 text-primary" icon="PlusCircle" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-2">All Training School Documents</h1>
                            <p class="text-slate-600">View and manage all training school documents</p>
                        </div>
                    </div>
                    <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                        <x-base.button as="a" href="{{ route('admin.training-schools.index') }}" variant="primary">
                            <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowLeft" />
                            Back to Training Schools
                        </x-base.button>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="box box--stacked mt-5">
                <div class="box-body p-5">
                    <form action="{{ route('admin.training-schools.docs.all') }}" method="GET" id="filter-form">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <x-base.form-label>Search</x-base.form-label>
                                <div class="relative">
                                    <x-base.lucide
                                        class="absolute inset-y-0 left-0 z-10 my-auto ml-3 h-4 w-4 stroke-[1.3] text-slate-500"
                                        icon="Search" />
                                    <x-base.form-input class="rounded-[0.5rem] pl-9" name="search_term"
                                        value="{{ request('search_term') }}" type="text"
                                        placeholder="Search documents..." />
                                </div>
                            </div>

                            <div>
                                <x-base.form-label>Filter by Carrier</x-base.form-label>
                                <select name="carrier_filter" id="carrier-filter"
                                    class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                    <option value="">All Carriers</option>
                                    @foreach ($carriers as $carrier)
                                        <option value="{{ $carrier->id }}"
                                            {{ request('carrier_filter') == $carrier->id ? 'selected' : '' }}>
                                            {{ $carrier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-base.form-label>Filter by School</x-base.form-label>
                                <select name="school_filter" id="school-filter"
                                    class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                    <option value="">All Schools</option>
                                    @foreach ($schools as $schoolItem)
                                        <option value="{{ $schoolItem->id }}"
                                            {{ request('school_filter') == $schoolItem->id ? 'selected' : '' }}>
                                            {{ $schoolItem->school_name }}
                                            @if ($schoolItem->userDriverDetail && $schoolItem->userDriverDetail->carrier)
                                                ({{ $schoolItem->userDriverDetail->carrier->name }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-base.form-label>From Date</x-base.form-label>
                                <div class="relative">
                                    <x-base.litepicker name="date_from" value="{{ request('date_from') }}"
                                        class="w-full pl-10" />
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <x-base.lucide class="w-5 h-5 text-slate-500" icon="Calendar" />
                                    </div>
                                </div>
                            </div>

                            <div>
                                <x-base.form-label>To Date</x-base.form-label>
                                <div class="relative">
                                    <x-base.litepicker name="date_to" value="{{ request('date_to') }}"
                                        class="w-full pl-10" />
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <x-base.lucide class="w-5 h-5 text-slate-500" icon="Calendar" />
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-end gap-3">
                                <x-base.button type="submit" variant="primary">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="Filter" />
                                    Filter
                                </x-base.button>
                                <x-base.button as="a" href="{{ route('admin.training-schools.docs.all') }}"
                                    variant="outline-secondary">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="RefreshCw" />
                                    Reset
                                </x-base.button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de documentos -->
            <div class="box box--stacked mt-6">
                <div class="box-body p-5">
                    <div class="overflow-auto xl:overflow-visible">
                        <x-base.table class="border-b border-slate-200/60">
                            <x-base.table.thead>
                                <x-base.table.tr>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500 w-12">
                                        #
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Document
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Type
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Size
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        School
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Carrier
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Driver
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Date
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 text-center font-medium text-slate-500 w-32">
                                        Actions
                                    </x-base.table.td>
                                </x-base.table.tr>
                            </x-base.table.thead>
                            <x-base.table.tbody>
                                @forelse ($documents as $document)
                                    @php
                                        $trainingSchool = \App\Models\Admin\Driver\DriverTrainingSchool::with(
                                            'userDriverDetail.carrier',
                                            'userDriverDetail.user',
                                        )->find($document->model_id);
                                        $extension = pathinfo($document->file_name, PATHINFO_EXTENSION);
                                        $iconClass = 'FileText';

                                        if (
                                            in_array(strtolower($extension), [
                                                'jpg',
                                                'jpeg',
                                                'png',
                                                'gif',
                                                'webp',
                                                'svg',
                                            ])
                                        ) {
                                            $iconClass = 'Image';
                                        } elseif (in_array(strtolower($extension), ['pdf'])) {
                                            $iconClass = 'FileText';
                                        } elseif (in_array(strtolower($extension), ['doc', 'docx'])) {
                                            $iconClass = 'File';
                                        } elseif (in_array(strtolower($extension), ['xls', 'xlsx', 'csv'])) {
                                            $iconClass = 'FileSpreadsheet';
                                        }
                                    @endphp
                                    <x-base.table.tr class="hover:bg-slate-50 transition-colors">
                                        <x-base.table.td class="border-dashed py-4">
                                            {{ $loop->iteration + ($documents->currentPage() - 1) * $documents->perPage() }}
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            <div class="flex items-center gap-2">
                                                <x-base.lucide class="w-5 h-5 text-primary flex-shrink-0"
                                                    icon="{{ $iconClass }}" />
                                                <span class="text-sm text-slate-700 dark:text-slate-300 truncate max-w-xs">
                                                    {{ $document->file_name }}
                                                </span>
                                            </div>
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            <span
                                                class="px-2 py-1 text-xs font-medium rounded-full bg-slate-100 text-slate-600">
                                                {{ strtoupper($extension) }}
                                            </span>
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            <span class="text-sm text-slate-600 dark:text-slate-400">
                                                {{ $document->human_readable_size }}
                                            </span>
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            @if ($trainingSchool)
                                                <a href="{{ route('admin.training-schools.show', $trainingSchool->id) }}"
                                                    class="text-primary hover:underline font-medium">
                                                    {{ $trainingSchool->school_name }}
                                                </a>
                                            @else
                                                <span class="text-slate-400">N/A</span>
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            @if ($trainingSchool && $trainingSchool->userDriverDetail && $trainingSchool->userDriverDetail->carrier)
                                                <span
                                                    class="px-2 py-1 text-xs font-medium rounded-full bg-primary/10 text-primary">
                                                    {{ $trainingSchool->userDriverDetail->carrier->name }}
                                                </span>
                                            @else
                                                <span class="text-slate-400">N/A</span>
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            @if ($trainingSchool && $trainingSchool->userDriverDetail && $trainingSchool->userDriverDetail->user)
                                                <span class="text-sm text-slate-700 dark:text-slate-300">
                                                    {{ $trainingSchool->userDriverDetail->user->name }}
                                                    {{ $trainingSchool->userDriverDetail->user->last_name ?? '' }}
                                                </span>
                                            @else
                                                <span class="text-slate-400">N/A</span>
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            <span class="text-sm text-slate-600 dark:text-slate-400">
                                                {{ $document->created_at->format('m/d/Y H:i') }}
                                            </span>
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="{{ route('admin.training-schools.docs.preview', $document->id) }}"
                                                    class="inline-flex items-center justify-center w-8 h-8 text-primary hover:text-primary/80 hover:bg-primary/10 rounded-lg transition-colors"
                                                    target="_blank" title="View Document">
                                                    <x-base.lucide class="w-4 h-4" icon="Eye" />
                                                </a>
                                                @if ($trainingSchool)
                                                    <a href="{{ route('admin.training-schools.edit', $trainingSchool->id) }}"
                                                        class="inline-flex items-center justify-center w-8 h-8 text-warning hover:text-warning/80 hover:bg-warning/10 rounded-lg transition-colors"
                                                        title="Edit School">
                                                        <x-base.lucide class="w-4 h-4" icon="Pencil" />
                                                    </a>
                                                @endif
                                                <button type="button" onclick="confirmDelete({{ $document->id }})"
                                                    class="inline-flex items-center justify-center w-8 h-8 text-danger hover:text-danger/80 hover:bg-danger/10 rounded-lg transition-colors"
                                                    title="Delete Document">
                                                    <x-base.lucide class="w-4 h-4" icon="Trash2" />
                                                </button>
                                            </div>
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @empty
                                    <x-base.table.tr>
                                        <x-base.table.td colspan="9" class="text-center py-12">
                                            <div class="flex flex-col items-center">
                                                <x-base.lucide class="w-16 h-16 text-slate-300 mb-4" icon="FileText" />
                                                <h3 class="text-lg font-medium text-slate-600 mb-2">
                                                    No documents found
                                                </h3>
                                                <p class="text-slate-400 text-center max-w-sm">
                                                    No documents match your current search criteria.
                                                </p>
                                            </div>
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @endforelse
                            </x-base.table.tbody>
                        </x-base.table>
                    </div>

                    <!-- Paginación -->
                    @if ($documents->hasPages())
                        <div class="border-t border-slate-200/60 dark:border-darkmode-400 pt-5 mt-5">
                            {{ $documents->appends(request()->except('page'))->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación de eliminación -->
    <x-base.dialog id="delete-confirmation-modal">
        <x-base.dialog.panel>
            <div class="p-5 text-center">
                <x-base.lucide class="w-16 h-16 mx-auto mt-3 text-danger" icon="XCircle" />
                <div class="mt-5 text-3xl">Are you sure?</div>
                <div class="mt-2 text-slate-500">
                    Do you really want to delete this document? <br>
                    This process cannot be undone.
                </div>
            </div>
            <div class="px-5 pb-8 text-center">
                <x-base.button class="w-24 mr-1" data-tw-dismiss="modal" type="button" variant="outline-secondary">
                    Cancel
                </x-base.button>
                <form id="delete-form" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <x-base.button class="w-24" type="submit" variant="danger">
                        Delete
                    </x-base.button>
                </form>
            </div>
        </x-base.dialog.panel>
    </x-base.dialog>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar TomSelect para los selectores
            if (typeof TomSelect !== 'undefined') {
                if (document.getElementById('carrier-filter')) {
                    new TomSelect('#carrier-filter', {
                        placeholder: 'Select a carrier',
                        allowEmptyOption: true
                    });
                }

                if (document.getElementById('school-filter')) {
                    new TomSelect('#school-filter', {
                        placeholder: 'Select a school',
                        allowEmptyOption: true
                    });
                }
            }
        });

        function confirmDelete(documentId) {
            const modal = tailwind.Modal.getOrCreateInstance(document.querySelector("#delete-confirmation-modal"));
            const form = document.getElementById('delete-form');
            form.action = "{{ route('admin.training-schools.docs.delete', '') }}/" + documentId;
            modal.show();
        }
    </script>
@endpush
