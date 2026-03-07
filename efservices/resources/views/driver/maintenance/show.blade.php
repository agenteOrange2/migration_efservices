@extends('../themes/' . $activeTheme)
@section('title', 'Maintenance Details - EF Services')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'Maintenance', 'url' => route('driver.maintenance.index')],
        ['label' => 'Details', 'active' => true],
    ];
@endphp

@section('subcontent')

<!-- Header -->
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Maintenance Details</h1>
            <p class="text-slate-500 mt-1">{{ $maintenance->service_tasks }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('driver.maintenance.index') }}" class="btn btn-secondary">
                <x-base.lucide class="w-4 h-4 mr-2" icon="ArrowLeft" />
                Back to List
            </a>
            <a href="{{ route('driver.maintenance.edit', $maintenance->id) }}" class="btn btn-primary">
                <x-base.lucide class="w-4 h-4 mr-2" icon="Pencil" />
                Edit
            </a>
            <button type="button" class="btn btn-danger" id="delete-maintenance-btn">
                <x-base.lucide class="w-4 h-4 mr-2" icon="Trash2" />
                Delete
            </button>
        </div>
    </div>
</div>

<!-- Status Banner -->
<div class="mb-6">
    @if($maintenance->status)
    <div class="box p-4 bg-success/10 border-l-4 border-success">
        <div class="flex items-center gap-3">
            <x-base.lucide class="w-6 h-6 text-success" icon="CheckCircle" />
            <div>
                <p class="font-semibold text-success">Maintenance Completed</p>
                <p class="text-sm text-slate-600">This maintenance task has been marked as completed.</p>
            </div>
        </div>
    </div>
    @elseif($maintenance->next_service_date && $maintenance->next_service_date->isPast())
    <div class="box p-4 bg-danger/10 border-l-4 border-danger">
        <div class="flex items-center gap-3">
            <x-base.lucide class="w-6 h-6 text-danger" icon="AlertCircle" />
            <div>
                <p class="font-semibold text-danger">Maintenance Overdue</p>
                <p class="text-sm text-slate-600">This maintenance was due on {{ $maintenance->next_service_date->format('F d, Y') }}. Please complete it as soon as possible.</p>
            </div>
        </div>
    </div>
    @elseif($maintenance->next_service_date && $maintenance->next_service_date->diffInDays(now()) <= 30)
    <div class="box p-4 bg-warning/10 border-l-4 border-warning">
        <div class="flex items-center gap-3">
            <x-base.lucide class="w-6 h-6 text-warning" icon="Clock" />
            <div>
                <p class="font-semibold text-warning">Maintenance Due Soon</p>
                <p class="text-sm text-slate-600">This maintenance is due on {{ $maintenance->next_service_date->format('F d, Y') }} (in {{ $maintenance->next_service_date->diffForHumans() }}).</p>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        
        <!-- Maintenance Information -->
        <div class="box box--stacked">
            <div class="p-5 border-b border-slate-200/80">
                <h3 class="text-lg font-semibold text-slate-800">Maintenance Information</h3>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-slate-500">Service Type</label>
                        <p class="text-slate-800 font-medium">{{ $maintenance->service_tasks }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-500">Status</label>
                        <div class="mt-1">
                            @if($maintenance->status)
                                <x-base.badge variant="success">Completed</x-base.badge>
                            @else
                                <x-base.badge variant="warning">Pending</x-base.badge>
                            @endif
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-500">Service Date</label>
                        <p class="text-slate-800">{{ $maintenance->service_date ? $maintenance->service_date->format('F d, Y') : 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-500">Next Service Date</label>
                        <p class="text-slate-800">{{ $maintenance->next_service_date ? $maintenance->next_service_date->format('F d, Y') : 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-500">Vendor/Mechanic</label>
                        <p class="text-slate-800">{{ $maintenance->vendor_mechanic ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-500">Cost</label>
                        <p class="text-slate-800">${{ number_format($maintenance->cost ?? 0, 2) }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-500">Odometer Reading</label>
                        <p class="text-slate-800">{{ $maintenance->odometer ? number_format($maintenance->odometer) . ' miles' : 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-500">Unit Number</label>
                        <p class="text-slate-800">{{ $maintenance->unit ?? 'N/A' }}</p>
                    </div>
                </div>
                
                @if($maintenance->description)
                <div class="mt-4 pt-4 border-t border-slate-200/80">
                    <label class="text-sm font-medium text-slate-500">Description/Notes</label>
                    <p class="text-slate-800 mt-1 whitespace-pre-wrap">{{ $maintenance->description }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Complete Maintenance Form -->
        @if(!$maintenance->status)
        <div class="box box--stacked">
            <div class="flex items-center gap-3 p-5 border-b border-slate-200/60 dark:border-darkmode-400">
                <div class="p-2 bg-success/10 rounded-lg">
                    <x-base.lucide class="w-5 h-5 text-success" icon="CheckCircle" />
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800 text-lg">Mark as Completed</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Complete this maintenance task and add final notes</p>
                </div>
            </div>
            <div class="p-5">
                <form action="{{ route('driver.maintenance.complete', $maintenance->id) }}" method="POST" class="space-y-5">
                    @csrf
                    
                    <div class="bg-slate-50 dark:bg-darkmode-800 rounded-lg p-4 border border-slate-200/60">
                        <x-base.form-label for="completion_notes" class="flex items-center gap-2 mb-3">
                            <x-base.lucide class="w-4 h-4 text-slate-600" icon="FileText" />
                            Completion Notes
                            <span class="text-xs text-slate-400 font-normal">(Optional)</span>
                        </x-base.form-label>
                        <x-base.form-textarea 
                            id="completion_notes" 
                            name="completion_notes" 
                            rows="4"
                            class="w-full @error('completion_notes') border-danger @enderror" 
                            placeholder="Add any notes about the completed maintenance (e.g., parts replaced, observations, recommendations)...">{{ old('completion_notes') }}</x-base.form-textarea>
                        @error('completion_notes')
                            <div class="text-danger mt-2 text-sm flex items-center gap-1">
                                <x-base.lucide class="w-4 h-4" icon="AlertCircle" />
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="bg-slate-50 dark:bg-darkmode-800 rounded-lg p-4 border border-slate-200/60">
                        <x-base.form-label for="completion_odometer" class="flex items-center gap-2 mb-3">
                            <x-base.lucide class="w-4 h-4 text-slate-600" icon="Gauge" />
                            Current Odometer Reading
                            <span class="text-xs text-slate-400 font-normal">(Optional)</span>
                        </x-base.form-label>
                        <x-base.form-input 
                            id="completion_odometer" 
                            name="completion_odometer" 
                            type="number" 
                            min="0"
                            class="w-full @error('completion_odometer') border-danger @enderror" 
                            placeholder="Enter current mileage (e.g., 45000)" 
                            value="{{ old('completion_odometer') }}" />
                        @error('completion_odometer')
                            <div class="text-danger mt-2 text-sm flex items-center gap-1">
                                <x-base.lucide class="w-4 h-4" icon="AlertCircle" />
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t border-slate-200/60">
                        <x-base.button type="submit" variant="success" class="w-full sm:w-auto">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="CheckCircle" />
                            Complete Maintenance
                        </x-base.button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- Upload Document Form -->
        <div class="box box--stacked">
            <div class="flex items-center gap-3 p-5 border-b border-slate-200/60 dark:border-darkmode-400">
                <div class="p-2 bg-primary/10 rounded-lg">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Upload" />
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800 text-lg">Upload Documentation</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Add receipts, photos, or work orders</p>
                </div>
            </div>
            <div class="p-5">
                <form action="{{ route('driver.maintenance.upload-document', $maintenance->id) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    
                    <div class="bg-slate-50 dark:bg-darkmode-800 rounded-lg p-4 border border-slate-200/60">
                        <x-base.form-label for="document" class="flex items-center gap-2 mb-3">
                            <x-base.lucide class="w-4 h-4 text-primary" icon="Paperclip" />
                            Select Document *
                        </x-base.form-label>
                        <input 
                            id="document" 
                            name="document" 
                            type="file" 
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary/90 cursor-pointer border border-slate-200 rounded-lg @error('document') border-danger @enderror" 
                            required />
                        <div class="flex items-center gap-2 mt-2 text-xs text-slate-500">
                            <x-base.lucide class="w-3 h-3" icon="Info" />
                            <span>Accepted: PDF, JPG, PNG (Max 10MB)</span>
                        </div>
                        @error('document')
                            <div class="text-danger mt-2 text-sm flex items-center gap-1">
                                <x-base.lucide class="w-4 h-4" icon="AlertCircle" />
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="bg-slate-50 dark:bg-darkmode-800 rounded-lg p-4 border border-slate-200/60">
                        <x-base.form-label for="document_description" class="flex items-center gap-2 mb-3">
                            <x-base.lucide class="w-4 h-4 text-slate-600" icon="Tag" />
                            Description
                            <span class="text-xs text-slate-400 font-normal">(Optional)</span>
                        </x-base.form-label>
                        <x-base.form-input 
                            id="document_description" 
                            name="document_description" 
                            type="text"
                            class="w-full @error('document_description') border-danger @enderror" 
                            placeholder="e.g., Receipt, Work Order, Before/After Photos" 
                            value="{{ old('document_description') }}" />
                        @error('document_description')
                            <div class="text-danger mt-2 text-sm flex items-center gap-1">
                                <x-base.lucide class="w-4 h-4" icon="AlertCircle" />
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t border-slate-200/60">
                        <x-base.button type="submit" variant="primary" class="w-full sm:w-auto">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="Upload" />
                            Upload Document
                        </x-base.button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        
        <!-- Vehicle Info -->
        <div class="box box--stacked p-5">
            <h3 class="font-semibold text-slate-800 mb-4">Vehicle Information</h3>
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-primary/10 rounded-lg">
                    <x-base.lucide class="w-6 h-6 text-primary" icon="Truck" />
                </div>
                <div>
                    <p class="font-semibold text-slate-800">{{ $vehicle->make }} {{ $vehicle->model }}</p>
                    <p class="text-sm text-slate-500">{{ $vehicle->year }}</p>
                </div>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Unit Number:</span>
                    <span class="text-slate-800 font-medium">{{ $vehicle->company_unit_number ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">VIN:</span>
                    <span class="text-slate-800 font-medium">{{ $vehicle->vin ?? 'N/A' }}</span>
                </div>
            </div>
            <a href="{{ route('driver.vehicles.show', $vehicle->id) }}" class="mt-4 block text-center text-sm text-primary hover:underline">
                View Vehicle Details →
            </a>
        </div>

        <!-- Documents -->
        <div class="box box--stacked">
            <div class="p-5 border-b border-slate-200/80">
                <h3 class="font-semibold text-slate-800">Documents</h3>
            </div>
            <div class="p-5">
                @php
                    $documents = $maintenance->getMedia('maintenance_files');
                @endphp
                
                @if($documents->count() > 0)
                <div class="space-y-2">
                    @foreach($documents as $document)
                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <x-base.lucide class="w-4 h-4 text-slate-400 flex-shrink-0" icon="FileText" />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-slate-800 truncate">{{ $document->file_name }}</p>
                                @if($document->getCustomProperty('description'))
                                <p class="text-xs text-slate-500 truncate">{{ $document->getCustomProperty('description') }}</p>
                                @endif
                                @if($document->getCustomProperty('uploaded_by_driver'))
                                <p class="text-xs text-info">Uploaded by you</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <a href="{{ $document->getUrl() }}" target="_blank" class="p-1 text-primary hover:bg-primary/10 rounded">
                                <x-base.lucide class="w-4 h-4" icon="Eye" />
                            </a>
                            @if($document->getCustomProperty('uploaded_by_driver') && $document->getCustomProperty('driver_id') == $driver->id)
                            <form action="{{ route('driver.maintenance.delete-document', [$maintenance->id, $document->id]) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this document?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1 text-danger hover:bg-danger/10 rounded">
                                    <x-base.lucide class="w-4 h-4" icon="Trash2" />
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-4">
                    <x-base.lucide class="w-8 h-8 text-slate-300 mx-auto mb-2" icon="FileX" />
                    <p class="text-sm text-slate-500">No documents uploaded yet</p>
                </div>
                @endif
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const deleteBtn = document.getElementById('delete-maintenance-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (confirm('¿Estás seguro de que deseas eliminar este mantenimiento?')) {
                    const deleteForm = document.createElement('form');
                    deleteForm.method = 'POST';
                    deleteForm.action = '{{ route("driver.maintenance.destroy", $maintenance->id) }}';
                    deleteForm.style.display = 'none';

                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = '{{ csrf_token() }}';
                    deleteForm.appendChild(csrfInput);

                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'DELETE';
                    deleteForm.appendChild(methodInput);

                    document.body.appendChild(deleteForm);
                    deleteForm.submit();
                }
            });
        }
    });
</script>
@endpush

@endsection

