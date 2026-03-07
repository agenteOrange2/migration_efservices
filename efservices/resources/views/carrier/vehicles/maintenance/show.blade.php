@extends('../themes/' . $activeTheme)
@section('title', 'Maintenance Details')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'Vehicles', 'url' => route('carrier.vehicles.index')],
        ['label' => $vehicle->make . ' ' . $vehicle->model, 'url' => route('carrier.vehicles.show', $vehicle->id)],
        ['label' => 'Maintenance', 'url' => route('carrier.vehicles.maintenance.index', $vehicle->id)],
        ['label' => 'Details', 'active' => true],
    ];

    // Determine status class for visual indicators (Requirements 9.1, 9.2, 9.3)
    $statusClass = '';
    $statusText = '';
    if ($maintenance->status) {
        $statusClass = 'completed';
        $statusText = 'Completed';
    } elseif ($maintenance->next_service_date && \Carbon\Carbon::parse($maintenance->next_service_date)->isPast()) {
        $statusClass = 'overdue';
        $statusText = 'Overdue';
    } else {
        $statusClass = 'pending';
        $statusText = 'Pending';
    }
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
            <!-- Header with action buttons -->
            <div class="flex flex-col md:flex-row items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="text-xl font-medium">
                        <span class="text-slate-600">Maintenance Record #{{ $maintenance->id }}</span>
                        <span class="badge badge-{{ $statusClass === 'completed' ? 'success' : ($statusClass === 'overdue' ? 'danger' : 'warning') }} ml-2">
                            {{ $statusText }}
                        </span>
                    </div>
                </div>
                
                <div class="flex flex-wrap gap-2 mt-4 md:mt-0">
                    <form action="{{ route('carrier.vehicles.maintenance.generate-report', [$vehicle->id, $maintenance->id]) }}" method="POST" class="inline">
                        @csrf
                        <x-base.button type="submit" variant="outline-success" class="w-full sm:w-auto">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="FileText" />
                            Generate Report
                        </x-base.button>
                    </form>
                    <x-base.button as="a" href="{{ route('carrier.vehicles.maintenance.edit', [$vehicle->id, $maintenance->id]) }}" 
                        variant="primary" class="w-full sm:w-auto">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="Edit" />
                        Edit
                    </x-base.button>
                    <form action="{{ route('carrier.vehicles.maintenance.toggle-status', [$vehicle->id, $maintenance->id]) }}"
                        method="POST" class="inline">
                        @csrf
                        <x-base.button type="submit"
                            variant="{{ $maintenance->status ? 'outline-secondary' : 'success' }}"
                            class="w-full sm:w-auto">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="{{ $maintenance->status ? 'Clock' : 'CheckCircle' }}" />
                            {{ $maintenance->status ? 'Mark as Pending' : 'Mark as Completed' }}
                        </x-base.button>
                    </form>
                    <x-base.button as="a" href="{{ route('carrier.vehicles.maintenance.index', $vehicle->id) }}" 
                        variant="outline-secondary" class="w-full sm:w-auto">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="ArrowLeft" />
                        Back to List
                    </x-base.button>
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
                            <div class="inline-flex items-center rounded-md bg-{{ $statusClass === 'completed' ? 'success' : ($statusClass === 'overdue' ? 'danger' : 'warning') }}/10 px-2 py-1 text-xs font-medium text-{{ $statusClass === 'completed' ? 'success' : ($statusClass === 'overdue' ? 'danger' : 'warning') }}">
                                <x-base.lucide class="w-4 h-4 mr-1" icon="{{ $maintenance->status ? 'CheckCircle' : ($statusClass === 'overdue' ? 'AlertOctagon' : 'Clock') }}" />
                                {{ $statusText }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 xl:grid-cols-1 gap-6">
                        <!-- Vehicle Information (Requirement 4.2) -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-slate-900 border-b pb-2">Vehicle Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-slate-50/50 p-3 rounded-lg">
                                    <div class="text-sm text-slate-500">Vehicle</div>
                                    <div class="font-medium">{{ $vehicle->make }} {{ $vehicle->model }}</div>
                                    <div class="text-sm">{{ $vehicle->year }}</div>
                                </div>
                                
                                <div class="bg-slate-50/50 p-3 rounded-lg">
                                    <div class="text-sm text-slate-500">Unit</div>
                                    <div class="font-medium">{{ $maintenance->unit }}</div>
                                </div>
                                
                                <div class="bg-slate-50/50 p-3 rounded-lg col-span-full">
                                    <div class="text-sm text-slate-500">VIN</div>
                                    <div class="font-medium">{{ $vehicle->vin }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Service Details (Requirements 4.1, 4.5) -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-slate-900 border-b pb-2">Service Details</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-slate-50/50 p-3 rounded-lg">
                                    <div class="text-sm text-slate-500">Service Date</div>
                                    <div class="font-medium">{{ \Carbon\Carbon::parse($maintenance->service_date)->format('m/d/Y') }}</div>
                                </div>
                                
                                <div class="bg-slate-50/50 p-3 rounded-lg {{ $statusClass === 'overdue' ? 'bg-danger/10' : ($statusClass === 'pending' ? 'bg-warning/10' : '') }}">
                                    <div class="text-sm text-slate-500">Next Service Date</div>
                                    <div class="font-medium">
                                        @if ($maintenance->next_service_date)
                                            {{ \Carbon\Carbon::parse($maintenance->next_service_date)->format('m/d/Y') }}
                                        @else
                                            <span class="text-slate-400">Not set</span>
                                        @endif
                                    </div>
                                    @if(!$maintenance->status && $maintenance->next_service_date)
                                        @if($statusClass === 'overdue')
                                            <div class="text-xs text-danger mt-1">
                                                Overdue by {{ \Carbon\Carbon::parse($maintenance->next_service_date)->diffInDays(now()) }} days
                                            </div>
                                        @elseif($statusClass === 'pending')
                                            <div class="text-xs text-warning mt-1">
                                                Due in {{ now()->diffInDays(\Carbon\Carbon::parse($maintenance->next_service_date)) }} days
                                            </div>
                                        @endif
                                    @endif
                                </div>
                                
                                <div class="bg-slate-50/50 p-3 rounded-lg col-span-full">
                                    <div class="text-sm text-slate-500">Service Tasks</div>
                                    <div class="font-medium">{{ $maintenance->service_tasks }}</div>
                                </div>
                                
                                <div class="bg-slate-50/50 p-3 rounded-lg">
                                    <div class="text-sm text-slate-500">Vendor/Mechanic</div>
                                    <div class="font-medium">{{ $maintenance->vendor_mechanic }}</div>
                                </div>
                                
                                <div class="bg-slate-50/50 p-3 rounded-lg">
                                    <div class="text-sm text-slate-500">Cost</div>
                                    <div class="font-medium">${{ number_format($maintenance->cost, 2) }}</div>
                                </div>
                                
                                <div class="bg-slate-50/50 p-3 rounded-lg">
                                    <div class="text-sm text-slate-500">Odometer Reading</div>
                                    <div class="font-medium">{{ number_format($maintenance->odometer) }} mi</div>
                                </div>
                                
                                <div class="bg-slate-50/50 p-3 rounded-lg">
                                    <div class="text-sm text-slate-500">Status</div>
                                    <div class="flex items-center">
                                        @if ($statusClass === 'completed')
                                            <div class="w-2 h-2 rounded-full mr-2 bg-success"></div>
                                            <span class="text-success font-medium">Completed</span>
                                        @elseif ($statusClass === 'overdue')
                                            <div class="w-2 h-2 rounded-full mr-2 bg-danger"></div>
                                            <span class="text-danger font-medium">Overdue</span>
                                        @else
                                            <div class="w-2 h-2 rounded-full mr-2 bg-warning"></div>
                                            <span class="text-warning font-medium">Pending</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Description and notes (Requirement 4.1) -->
                    @if($maintenance->description)
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

                        @if($documents->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($documents as $media)
                                    <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden" id="doc-card-{{ $media->id }}">
                                        <div class="p-4">
                                            <div class="flex items-center mb-3">
                                                @if(str_contains($media->mime_type, 'image'))
                                                    <div class="w-10 h-10 flex-shrink-0 mr-3 bg-primary/10 rounded-lg flex items-center justify-center">
                                                        <x-base.lucide class="w-5 h-5 text-primary" icon="Image" />
                                                    </div>
                                                @elseif(str_contains($media->mime_type, 'pdf'))
                                                    <div class="w-10 h-10 flex-shrink-0 mr-3 bg-danger/10 rounded-lg flex items-center justify-center">
                                                        <x-base.lucide class="w-5 h-5 text-danger" icon="FileText" />
                                                    </div>
                                                @else
                                                    <div class="w-10 h-10 flex-shrink-0 mr-3 bg-warning/10 rounded-lg flex items-center justify-center">
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
                                                    class="btn btn-sm btn-outline-primary flex-1 flex items-center justify-center">
                                                    <x-base.lucide class="w-4 h-4 mr-1" icon="Eye" /> View
                                                </a>
                                                <a href="{{ $media->getUrl() }}" download
                                                    class="btn btn-sm btn-outline-secondary flex-1 flex items-center justify-center">
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
                            <form action="{{ route('carrier.vehicles.maintenance.store-documents', [$vehicle->id, $maintenance->id]) }}" method="POST" enctype="multipart/form-data">
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
                            <form action="{{ route('carrier.vehicles.maintenance.generate-report', [$vehicle->id, $maintenance->id]) }}" method="POST" class="inline">
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
                                                        <form action="{{ route('carrier.vehicles.maintenance.delete-report', [$vehicle->id, $maintenance->id, $report->id]) }}" method="POST" class="inline"
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

    @push('scripts')
    <script>
        window.deleteDocument = function(documentId) {
            if (!confirm('Are you sure you want to delete this document?')) {
                return;
            }

            fetch(`/carrier/vehicles/{{ $vehicle->id }}/maintenance/documents/${documentId}`, {
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
                    if (card) card.remove();
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
    </script>
    @endpush
@endsection
