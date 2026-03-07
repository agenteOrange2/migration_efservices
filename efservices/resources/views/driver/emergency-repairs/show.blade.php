@extends('../themes/' . $activeTheme)
@section('title', 'Emergency Repair Details')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'Emergency Repairs', 'url' => route('driver.emergency-repairs.index')],
        ['label' => 'Repair Details', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Repair Details: {{ $emergencyRepair->repair_name }}
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <x-base.button as="a" href="{{ route('driver.emergency-repairs.index') }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowLeft" />
                        Back to List
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('driver.emergency-repairs.edit', $emergencyRepair->id) }}"
                        variant="primary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="Edit" />
                        Edit Repair
                    </x-base.button>
                </div>
            </div>

            <!-- Status Badge -->
            <div class="box box--stacked p-5 mt-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">{{ $emergencyRepair->repair_name }}</h2>
                        <p class="text-slate-500 mt-1">Repair Date: {{ \Carbon\Carbon::parse($emergencyRepair->repair_date)->format('m/d/Y') }}</p>
                    </div>
                    <div>
                        @if($emergencyRepair->status == 'completed')
                            <span class="px-4 py-2 text-sm font-medium rounded-full bg-success/10 text-success">
                                <x-base.lucide class="w-4 h-4 inline mr-1" icon="CheckCircle" />
                                Completed
                            </span>
                        @elseif($emergencyRepair->status == 'in_progress')
                            <span class="px-4 py-2 text-sm font-medium rounded-full bg-primary/10 text-primary">
                                <x-base.lucide class="w-4 h-4 inline mr-1" icon="Settings" />
                                In Progress
                            </span>
                        @else
                            <span class="px-4 py-2 text-sm font-medium rounded-full bg-warning/10 text-warning">
                                <x-base.lucide class="w-4 h-4 inline mr-1" icon="Clock" />
                                Pending
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Vehicle Information -->
            <div class="box box--stacked p-5 mt-5">
                <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Truck" />
                    Vehicle Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-slate-500">Vehicle</p>
                        <p class="font-medium text-slate-800">
                            @if($vehicle->company_unit_number)
                                {{ $vehicle->company_unit_number }} - 
                            @endif
                            {{ $vehicle->make }} {{ $vehicle->model }} {{ $vehicle->year }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">VIN</p>
                        <p class="font-medium text-slate-800">{{ $vehicle->vin ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Repair Details -->
            <div class="box box--stacked p-5 mt-5">
                <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Wrench" />
                    Repair Details
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-slate-500">Repair Name</p>
                        <p class="font-medium text-slate-800">{{ $emergencyRepair->repair_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Repair Date</p>
                        <p class="font-medium text-slate-800">{{ \Carbon\Carbon::parse($emergencyRepair->repair_date)->format('m/d/Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Cost</p>
                        <p class="font-medium text-slate-800 text-lg">${{ number_format($emergencyRepair->cost, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Odometer</p>
                        <p class="font-medium text-slate-800">
                            @if($emergencyRepair->odometer)
                                {{ number_format($emergencyRepair->odometer) }} miles
                            @else
                                <span class="text-slate-400">Not recorded</span>
                            @endif
                        </p>
                    </div>
                    <div>
                            <p class="text-sm text-slate-500">Status</p>
                        <p class="font-medium text-slate-800">
                            @if($emergencyRepair->status == 'completed')
                                Completed
                            @elseif($emergencyRepair->status == 'in_progress')
                                In Progress
                            @else
                                Pending
                            @endif
                        </p>
                    </div>
                </div>

                @if($emergencyRepair->description)
                <div class="mt-4">
                    <p class="text-sm text-slate-500 mb-2">Description</p>
                    <p class="text-slate-700">{{ $emergencyRepair->description }}</p>
                </div>
                @endif

                @if($emergencyRepair->notes)
                <div class="mt-4">
                    <p class="text-sm text-slate-500 mb-2">Additional Notes</p>
                    <p class="text-slate-700">{{ $emergencyRepair->notes }}</p>
                </div>
                @endif
            </div>

            <!-- Documents -->
            <div class="box box--stacked mt-5">
                <div class="flex items-center gap-3 p-5 border-b border-slate-200/60 dark:border-darkmode-400">
                    <div class="p-2 bg-primary/10 rounded-lg">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-slate-800 text-lg">Documents</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Manage the documents related to this repair</p>
                    </div>
                </div>

                <div class="p-5">
                    @php
                        $documents = $emergencyRepair->getMedia('emergency_repair_files');
                    @endphp
                    
                    @if($documents->count() > 0)
                    <div class="space-y-3 mb-6">
                        @foreach($documents as $media)
                        <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-darkmode-800 rounded-lg border border-slate-200/60 dark:border-darkmode-400 hover:bg-slate-100 dark:hover:bg-darkmode-700 transition-colors">
                            <div class="flex items-center gap-4 flex-1 min-w-0">
                                @if(str_starts_with($media->mime_type, 'image/'))
                                    <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg flex-shrink-0">
                                        <x-base.lucide class="w-6 h-6 text-blue-500" icon="Image" />
                                    </div>
                                @elseif($media->mime_type === 'application/pdf')
                                    <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg flex-shrink-0">
                                        <x-base.lucide class="w-6 h-6 text-red-500" icon="FileText" />
                                    </div>
                                @else
                                    <div class="p-3 bg-slate-100 dark:bg-slate-700 rounded-lg flex-shrink-0">
                                        <x-base.lucide class="w-6 h-6 text-slate-500" icon="File" />
                                    </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <a href="{{ $media->getUrl() }}" target="_blank" 
                                       class="text-sm font-semibold text-slate-800 dark:text-slate-200 hover:text-primary block truncate">
                                        {{ $media->file_name }}
                                    </a>
                                    <div class="flex items-center gap-3 mt-1">
                                        <span class="text-xs text-slate-500">
                                            {{ $media->human_readable_size }}
                                        </span>
                                        @if($media->getCustomProperty('uploaded_by_driver'))
                                        <span class="text-xs text-primary font-medium flex items-center gap-1">
                                            <x-base.lucide class="w-3 h-3" icon="User" />                                            
                                            Subido por ti
                                            {{ $media->getCustomProperty('driver_name') }}
                                            {{ $media->getCustomProperty('driver_id') }}
                                            {{ $driver->id }}
                                        </span>
                                        @endif
                                        @if($media->getCustomProperty('description'))
                                        <span class="text-xs text-slate-400 truncate">
                                            • {{ $media->getCustomProperty('description') }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0 ml-4">
                                <a href="{{ $media->getUrl() }}" target="_blank" 
                                   class="p-2 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors" 
                                   title="Ver documento">
                                    <x-base.lucide class="w-4 h-4" icon="Eye" />
                                </a>
                                <a href="{{ $media->getUrl() }}" download
                                   class="p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors" 
                                   title="Descargar">
                                    <x-base.lucide class="w-4 h-4" icon="Download" />
                                </a>
                                @if($media->getCustomProperty('uploaded_by_driver') && $media->getCustomProperty('driver_id') == $driver->id)
                                <form action="{{ route('driver.emergency-repairs.delete-document', [$emergencyRepair->id, $media->id]) }}" 
                                      method="POST" 
                                      class="inline"
                                      onsubmit="return confirm('¿Estás seguro de que deseas eliminar este documento?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" 
                                            title="Eliminar">
                                        <x-base.lucide class="w-4 h-4" icon="Trash2" />
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-12 bg-slate-50 dark:bg-darkmode-800 rounded-lg border border-slate-200/60 dark:border-darkmode-400 mb-6">
                        <x-base.lucide class="w-16 h-16 mx-auto text-slate-400 mb-4" icon="FileText" />
                        <p class="text-slate-500 font-medium mb-1">No documents uploaded</p>
                        <p class="text-xs text-slate-400">Upload documents related to this emergency repair</p>
                    </div>
                    @endif

                    <!-- Upload Document Form -->
                    <div class="bg-slate-50 dark:bg-darkmode-800 rounded-lg p-5 border border-slate-200/60 dark:border-darkmode-400">
                        <div class="flex items-center gap-2 mb-4">
                            <x-base.lucide class="w-4 h-4 text-primary" icon="Upload" />
                            <h4 class="font-semibold text-slate-800 dark:text-slate-200">Upload New Document</h4>
                        </div>
                        <form action="{{ route('driver.emergency-repairs.upload-document', $emergencyRepair->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            
                            <div>
                                <x-base.form-label for="document" class="flex items-center gap-2 mb-2">
                                    <x-base.lucide class="w-4 h-4 text-slate-600" icon="Paperclip" />
                                    Select File *
                                </x-base.form-label>
                                <input 
                                    id="document" 
                                    name="document" 
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                    class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary/90 cursor-pointer border border-slate-200 rounded-lg @error('document') border-danger @enderror" 
                                    required />
                                <div class="flex items-center gap-2 mt-2 text-xs text-slate-500">
                                    <x-base.lucide class="w-3 h-3" icon="Info" />
                                    <span>Accepted formats: PDF, JPG, PNG, DOC, DOCX (Max. 10MB)</span>
                                </div>
                                @error('document')
                                    <div class="text-danger mt-2 text-sm flex items-center gap-1">
                                        <x-base.lucide class="w-4 h-4" icon="AlertCircle" />
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div>
                                <x-base.form-label for="document_description" class="flex items-center gap-2 mb-2">
                                    <x-base.lucide class="w-4 h-4 text-slate-600" icon="Tag" />
                                    Description
                                    <span class="text-xs text-slate-400 font-normal">(Opcional)</span>
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

                            <div class="flex justify-end gap-3 pt-3 border-t border-slate-200/60 dark:border-darkmode-400">
                                <x-base.button type="submit" variant="primary" class="w-full sm:w-auto">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="Upload" />
                                    Upload Document
                                </x-base.button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

