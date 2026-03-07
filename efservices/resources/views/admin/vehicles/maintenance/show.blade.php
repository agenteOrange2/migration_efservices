@extends('../themes/' . $activeTheme)
@section('title', 'Maintenance Details')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Maintenance', 'url' => route('admin.maintenance.index')],
        ['label' => 'Details #' . $maintenance->id, 'active' => true],
    ];

    // Define status classes for visual indicators
    $statusClass = $maintenance->status
        ? 'success'
        : ($maintenance->isOverdue()
            ? 'danger'
            : ($maintenance->isUpcoming()
                ? 'warning'
                : 'primary'));
    $statusText = $maintenance->status
        ? 'Completed'
        : ($maintenance->isOverdue()
            ? 'Overdue'
            : ($maintenance->isUpcoming()
                ? 'Upcoming'
                : 'Scheduled'));
@endphp

@section('subcontent')
    @if(session('maintenance_success'))
        <div class="alert alert-success-soft show flex items-center mb-5" role="alert">
            <x-base.lucide class="w-6 h-6 mr-2" icon="CheckCircle" />
            {{ session('maintenance_success') }}
        </div>
    @endif
    @if(session('maintenance_error'))
        <div class="alert alert-danger-soft show flex items-center mb-5" role="alert">
            <x-base.lucide class="w-6 h-6 mr-2" icon="AlertOctagon" />
            {{ session('maintenance_error') }}
        </div>
    @endif
    <div class="grid grid-cols-12 gap-y-10">
        <div class="col-span-12">
            <!-- Professional Header -->
            <div class="box box--stacked p-8 mb-8">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <x-base.lucide class="w-8 h-8 text-primary" icon="Calendar" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-2">Maintenance Details</h1>
                            <p class="text-slate-600">Maintenance details: #{{ $maintenance->id }} -
                                {{ $maintenance->vehicle->make }} {{ $maintenance->vehicle->model }}</p>
                        </div>
                    </div>
                    <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                        @if (!$maintenance->status)
                            <x-base.button type="button" id="open-reschedule-modal" variant="outline-warning"
                                class="w-full sm:w-auto">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="Calendar" />
                                Reschedule
                            </x-base.button>
                        @endif
                        <form action="{{ route('admin.maintenance-system.toggle-status', $maintenance->id) }}"
                            method="POST" class="inline">
                            @csrf
                            @method('PUT')
                            <x-base.button type="submit"
                                variant="{{ $maintenance->status ? 'outline-secondary' : 'outline-success' }}"
                                class="w-full sm:w-auto">
                                <x-base.lucide class="w-4 h-4 mr-2"
                                    icon="{{ $maintenance->status ? 'XCircle' : 'CheckCircle' }}" />
                                {{ $maintenance->status ? 'Mark as Pending' : 'Mark as Completed' }}
                            </x-base.button>
                        </form>
                        <form action="{{ route('admin.maintenance-system.generate-report', $maintenance->id) }}" method="POST" class="inline w-full sm:w-auto">
                            @csrf
                            <x-base.button type="submit" variant="outline-success" class="w-full sm:w-auto">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="FileText" />
                                Generate Report
                            </x-base.button>
                        </form>
                        <x-base.button as="a" href="{{ route('admin.maintenance.edit', $maintenance->id) }}"
                            variant="primary" class="w-full sm:w-auto">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="Edit" />
                            Edit Maintenance
                        </x-base.button>
                        <x-base.button as="a" href="{{ route('admin.maintenance.index') }}"
                            variant="outline-secondary" class="w-full sm:w-auto">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="ArrowLeft" />
                            Back
                        </x-base.button>
                    </div>
                </div>
            </div>


            <!-- Main container -->
            <div class="box box--stacked mt-5">
                <div class="box-header">
                    <div class="box-title p-5 border-b border-slate-200/60 bg-slate-50">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-base font-medium">Maintenance Information</span>
                            </div>
                            <!-- Status indicator -->
                            <div
                                class="inline-flex items-center rounded-md bg-{{ $statusClass }}/10 px-2 py-1 text-xs font-medium text-{{ $statusClass }}">
                                <x-base.lucide class="w-4 h-4 mr-1"
                                    icon="{{ $maintenance->status ? 'CheckCircle' : ($maintenance->isOverdue() ? 'AlertOctagon' : 'Clock') }}" />
                                {{ $statusText }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 xl:grid-cols-1 gap-6">
                        <!-- Vehicle Information -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-slate-900 border-b pb-2">Vehicle Information</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-slate-50/50 p-3 rounded-lg">
                                    <div class="text-sm text-slate-500">Vehicle</div>
                                    <div class="font-medium">{{ $maintenance->vehicle->make }}
                                        {{ $maintenance->vehicle->model }}</div>
                                    <div class="text-sm">{{ $maintenance->vehicle->year }}</div>
                                </div>

                                <div class="bg-slate-50/50 p-3 rounded-lg">
                                    <div class="text-sm text-slate-500">Unit</div>
                                    <div class="font-medium">{{ $maintenance->unit }}</div>
                                </div>

                                @if ($maintenance->vehicle->vin)
                                    <div class="bg-slate-50/50 p-3 rounded-lg col-span-full">
                                        <div class="text-sm text-slate-500">VIN</div>
                                        <div class="font-medium">{{ $maintenance->vehicle->vin }}</div>
                                    </div>
                                @endif

                                <div class="bg-slate-50/50 p-3 rounded-lg">
                                    <div class="text-sm text-slate-500">Odometer Reading</div>
                                    <div class="font-medium">{{ number_format($maintenance->odometer) }} miles</div>
                                </div>

                                <div class="bg-slate-50/50 p-3 rounded-lg">
                                    <div class="text-sm text-slate-500">Cost</div>
                                    <div class="font-medium">${{ number_format($maintenance->cost, 2) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Service Information -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-slate-900 border-b pb-2">Service Details</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-slate-50/50 p-3 rounded-lg col-span-full">
                                    <div class="text-sm text-slate-500">Service Tasks</div>
                                    <div class="font-medium">{{ $maintenance->service_tasks }}</div>
                                </div>

                                <div class="bg-slate-50/50 p-3 rounded-lg">
                                    <div class="text-sm text-slate-500">Vendor/Mechanic</div>
                                    <div class="font-medium">{{ $maintenance->vendor_mechanic }}</div>
                                </div>

                                <div class="bg-slate-50/50 p-3 rounded-lg">
                                    <div class="text-sm text-slate-500">Service Date</div>
                                    <div class="font-medium">
                                        {{ $maintenance->service_date ? $maintenance->service_date->format('m/d/Y') : 'Not established' }}
                                    </div>
                                </div>

                                @if ($maintenance->next_service_date)
                                    <div
                                        class="bg-slate-50/50 p-3 rounded-lg col-span-full {{ $maintenance->isOverdue() ? 'bg-danger/10' : ($maintenance->isUpcoming() ? 'bg-warning/10' : '') }}">
                                        <div class="text-sm text-slate-500">Next Service Date</div>
                                        <div class="font-medium">{{ $maintenance->next_service_date->format('m/d/Y') }}
                                        </div>
                                        @if (!$maintenance->status)
                                            @if ($maintenance->isOverdue())
                                                <div class="text-xs text-danger mt-1">
                                                    <x-base.lucide class="w-3 h-3 inline mr-1" icon="AlertTriangle" />
                                                    Overdue by
                                                    {{ floor($maintenance->next_service_date->diffInDays(now())) }} days
                                                </div>
                                            @elseif($maintenance->isUpcoming())
                                                <div class="text-xs text-warning mt-1">
                                                    <x-base.lucide class="w-3 h-3 inline mr-1" icon="Clock" />
                                                    Due in {{ floor(now()->diffInDays($maintenance->next_service_date)) }}
                                                    days
                                                </div>
                                            @else
                                                <div class="text-xs text-success mt-1">
                                                    <x-base.lucide class="w-3 h-3 inline mr-1" icon="CheckCircle" />
                                                    Up to date
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Description and notes -->
                    @if ($maintenance->description)
                        <div class="mt-8">
                            <h3 class="text-lg font-medium text-slate-900 border-b pb-2 mb-4">Description and Notes</h3>
                            <div class="bg-slate-50/60 p-4 rounded-lg">
                                {!! nl2br(e($maintenance->description)) !!}
                            </div>
                        </div>
                    @endif

                    <!-- Attached documents -->
                    @php
                        $documents = $maintenance->getMedia('maintenance_files');
                    @endphp
                    <div class="mt-8">
                        <div class="flex items-center justify-between border-b pb-2 mb-4">
                            <h3 class="text-lg font-medium text-slate-900">Attached Documents</h3>
                        </div>

                        @if ($documents->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach ($documents as $media)
                                    <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden" id="doc-card-{{ $media->id }}">
                                        <div class="p-4">
                                            <div class="flex items-center mb-3">
                                                @if (str_contains($media->mime_type, 'image'))
                                                    <div
                                                        class="w-10 h-10 flex-shrink-0 mr-3 bg-primary/10 rounded-lg flex items-center justify-center">
                                                        <x-base.lucide class="w-5 h-5 text-primary" icon="Image" />
                                                    </div>
                                                @elseif(str_contains($media->mime_type, 'pdf'))
                                                    <div
                                                        class="w-10 h-10 flex-shrink-0 mr-3 bg-danger/10 rounded-lg flex items-center justify-center">
                                                        <x-base.lucide class="w-5 h-5 text-danger" icon="FileText" />
                                                    </div>
                                                @else
                                                    <div
                                                        class="w-10 h-10 flex-shrink-0 mr-3 bg-warning/10 rounded-lg flex items-center justify-center">
                                                        <x-base.lucide class="w-5 h-5 text-warning" icon="File" />
                                                    </div>
                                                @endif
                                                <div class="flex-grow overflow-hidden">
                                                    <p class="font-medium text-sm truncate">{{ $media->file_name }}</p>
                                                    <p class="text-xs text-slate-500">{{ $media->human_readable_size }}</p>
                                                </div>
                                            </div>
                                            <div class="flex space-x-2">
                                                <a href="{{ $media->getUrl() }}" target="_blank"
                                                    class="btn btn-sm btn-outline-secondary flex-1 flex items-center justify-center">
                                                    <x-base.lucide class="w-4 h-4 mr-1" icon="Eye" /> View
                                                </a>
                                                <a href="{{ $media->getUrl() }}" download
                                                    class="btn btn-sm btn-outline-primary flex-1 flex items-center justify-center">
                                                    <x-base.lucide class="w-4 h-4 mr-1" icon="Download" /> Download
                                                </a>
                                                <button type="button" onclick="deleteDocument({{ $media->id }})"
                                                    class="btn btn-sm btn-outline-danger flex-1 flex items-center justify-center">
                                                    <x-base.lucide class="w-4 h-4 mr-1" icon="Trash" /> Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-6">
                                <x-base.lucide class="h-10 w-10 mx-auto text-slate-300" icon="FileX" />
                                <div class="mt-2 text-slate-500 text-sm">No documents attached yet</div>
                            </div>
                        @endif

                        <!-- Upload Documents Form -->
                        <div class="mt-5 p-4 bg-slate-50/60 rounded-lg border border-dashed border-slate-300">
                            <form action="{{ route('admin.maintenance-system.store-documents', $maintenance->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="flex flex-col sm:flex-row items-center gap-3">
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <x-base.lucide class="w-5 h-5 text-slate-400" icon="Upload" />
                                        <span class="text-sm font-medium text-slate-600">Upload Documents:</span>
                                    </div>
                                    <input type="file" name="documents[]" multiple
                                        class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20"
                                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" />
                                    <x-base.button type="submit" variant="primary" class="flex-shrink-0">
                                        <x-base.lucide class="w-4 h-4 mr-1" icon="Upload" />
                                        Upload
                                    </x-base.button>
                                </div>
                                <p class="text-xs text-slate-400 mt-2">Accepted: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX</p>
                            </form>
                        </div>
                    </div>

                    <!-- Generated Reports Section -->
                    @php
                        $generatedReports = $vehicle->documents()
                            ->where('document_type', 'maintenance_record')
                            ->where('notes', 'like', '%Service: ' . $maintenance->service_tasks . '%')
                            ->orderBy('created_at', 'desc')
                            ->get();
                    @endphp
                    <div class="mt-8">
                        <div class="flex items-center justify-between border-b pb-2 mb-4">
                            <h3 class="text-lg font-medium text-slate-900">
                                <x-base.lucide class="w-5 h-5 inline mr-1 text-amber-600" icon="FileText" />
                                Generated Reports
                            </h3>
                            <form action="{{ route('admin.maintenance-system.generate-report', $maintenance->id) }}" method="POST" class="inline">
                                @csrf
                                <x-base.button type="submit" variant="primary" size="sm">
                                    <x-base.lucide class="w-4 h-4 mr-1" icon="FileText" />
                                    Generate Report
                                </x-base.button>
                            </form>
                        </div>

                        @if ($generatedReports->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-4 py-3">Report</th>
                                            <th scope="col" class="px-4 py-3">Document #</th>
                                            <th scope="col" class="px-4 py-3">Generated Date</th>
                                            <th scope="col" class="px-4 py-3">Status</th>
                                            <th scope="col" class="px-4 py-3 text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($generatedReports as $report)
                                            <tr class="bg-white border-b hover:bg-gray-50">
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-2">
                                                        <div class="p-1.5 bg-amber-500/10 rounded">
                                                            <x-base.lucide class="h-4 w-4 text-amber-600" icon="FileText" />
                                                        </div>
                                                        <span class="font-medium">Maintenance Record</span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">{{ $report->document_number ?? 'N/A' }}</td>
                                                <td class="px-4 py-3">
                                                    {{ $report->created_at ? $report->created_at->format('m/d/Y h:i A') : 'N/A' }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center whitespace-nowrap">
                                                        <div class="w-2 h-2 rounded-full mr-2 bg-success"></div>
                                                        Active
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <div class="flex justify-center gap-2">
                                                        @php $reportMedia = $report->getFirstMedia('document_files'); @endphp
                                                        @if ($reportMedia)
                                                            <a href="{{ $reportMedia->getUrl() }}" target="_blank"
                                                                class="btn btn-primary btn-sm" title="Preview">
                                                                <x-base.lucide class="h-4 w-4" icon="Eye" />
                                                            </a>
                                                            <a href="{{ $reportMedia->getUrl() }}" download
                                                                class="btn btn-success btn-sm" title="Download">
                                                                <x-base.lucide class="h-4 w-4" icon="Download" />
                                                            </a>
                                                        @endif
                                                        <form action="{{ route('admin.maintenance-system.delete-report', [$maintenance->id, $report->id]) }}" method="POST" class="inline"
                                                            onsubmit="return confirm('Are you sure you want to delete this report?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete Report">
                                                                <x-base.lucide class="h-4 w-4" icon="Trash2" />
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-6">
                                <x-base.lucide class="h-10 w-10 mx-auto text-slate-300" icon="FileText" />
                                <div class="mt-2 text-slate-500 text-sm">No reports generated yet</div>
                                <div class="mt-1 text-xs text-slate-400">Click "Generate Report" to create a PDF report for this maintenance record.</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reschedule Modal -->
    <x-base.dialog id="reschedule-modal" size="md">
        <x-base.dialog.panel>
            <form id="reschedule-form" action="{{ route('admin.maintenance-system.reschedule', $maintenance->id) }}"
                method="POST">
                @csrf
                <div class="p-5">
                    <div class="text-center">
                        <x-base.lucide class="mx-auto h-16 w-16 text-warning" icon="Calendar" />
                        <div class="mt-2 text-xl font-medium">Reschedule Maintenance #{{ $maintenance->id }}</div>
                        <div class="mt-1 text-slate-500">
                            Select a new date for the maintenance and indicate the reason for the change.
                        </div>
                    </div>

                    <div class="mt-5 grid grid-cols-12 gap-4">
                        <div class="col-span-12">
                            <label for="next_service_date" class="form-label">New Service Date *</label>
                            <x-base.litepicker id="next_service_date" name="next_service_date" class="w-full"
                                placeholder="MM/DD/YYYY" required />
                            <p class="text-xs text-slate-500 mt-1">Must be a future date</p>
                        </div>
                        <div class="col-span-12">
                            <label for="reschedule_reason" class="form-label">Reason for Rescheduling *</label>
                            <textarea id="reschedule_reason" name="reschedule_reason"
                                class="py-2 px-3 block w-full border-gray-200 rounded-md text-sm" rows="4"
                                placeholder="Explain why this maintenance is being rescheduled..." minlength="3" required></textarea>
                            <p class="text-xs text-slate-500 mt-1">Minimum 3 characters</p>
                        </div>
                    </div>
                </div>
                <div class="px-5 pb-8 text-center">
                    <x-base.button class="mr-1 w-24" data-tw-dismiss="modal" type="button" variant="outline-secondary">
                        Cancel
                    </x-base.button>
                    <x-base.button class="w-24" type="submit" variant="primary">
                        Reschedule
                    </x-base.button>
                </div>
            </form>
        </x-base.dialog.panel>
    </x-base.dialog>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // References to elements
            const openModalBtn = document.getElementById('open-reschedule-modal');
            const modal = document.getElementById('reschedule-modal');

            // Initialize components that require initialization
            if (typeof tailwind !== 'undefined') {
                tailwind.Modal.getInstance(document.querySelector('#reschedule-modal'));
            }

            // Handle modal opening
            if (openModalBtn) {
                openModalBtn.addEventListener('click', function() {
                    const modalInstance = tailwind.Modal.getInstance(document.querySelector(
                        '#reschedule-modal'));
                    if (modalInstance) {
                        modalInstance.show();
                    }
                });
            }

            // Form validation
            const form = document.getElementById('reschedule-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const dateInput = document.getElementById('next_service_date');
                    const reasonInput = document.getElementById('reschedule_reason');

                    let isValid = true;

                    // Validate date
                    if (!dateInput.value) {
                        isValid = false;
                        dateInput.classList.add('border-danger');
                    } else {
                        dateInput.classList.remove('border-danger');
                    }

                    // Validate reason (minimum 3 characters)
                    if (!reasonInput.value || reasonInput.value.trim().length < 3) {
                        isValid = false;
                        reasonInput.classList.add('border-danger');
                    } else {
                        reasonInput.classList.remove('border-danger');
                    }

                    if (!isValid) {
                        e.preventDefault();
                        alert('Please fill in all required fields correctly.');
                    }
                });
            }

            // Delete document via AJAX
            window.deleteDocument = function(documentId) {
                if (!confirm('Are you sure you want to delete this document?')) {
                    return;
                }

                fetch(`/admin/maintenance-system/documents/${documentId}/ajax-delete`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const card = document.getElementById('doc-card-' + documentId);
                        if (card) {
                            card.remove();
                        }
                        alert('Document deleted successfully.');
                    } else {
                        alert('Error: ' + (data.message || 'Could not delete document.'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the document.');
                });
            };
        });
    </script>
@endpush
