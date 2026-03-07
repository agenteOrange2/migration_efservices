@extends('../themes/' . $activeTheme)
@section('title', 'Edit Maintenance Record')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'Maintenance', 'url' => route('driver.maintenance.index')],
        ['label' => 'Edit Maintenance Record', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Edit Maintenance Record
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <x-base.button as="a" href="{{ route('driver.maintenance.show', $maintenance->id) }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowLeft" />
                        Back to Details
                    </x-base.button>
                </div>
            </div>

            <div class="intro-y box p-5 mt-5">
                <div class="flex items-center border-b border-slate-200/60 dark:border-darkmode-400 pb-5 mb-5">
                    <div class="font-medium text-base truncate">Maintenance Information</div>                    
                </div>

                <!-- Vehicle Information -->
                <div class="bg-slate-50 dark:bg-darkmode-800 p-4 rounded-lg mb-5">
                    <div class="flex items-center gap-3">
                        <x-base.lucide class="w-8 h-8 text-primary" icon="Truck" />
                        <div>
                            <h3 class="font-semibold text-slate-800 dark:text-slate-200">
                                @if($vehicle->company_unit_number)
                                    {{ $vehicle->company_unit_number }} - 
                                @endif
                                {{ $vehicle->make }} {{ $vehicle->model }} {{ $vehicle->year }}
                            </h3>
                            <p class="text-sm text-slate-500">
                                VIN: {{ $vehicle->vin ?? 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('driver.maintenance.update', $maintenance->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mt-3">
                        <x-base.form-label for="service_tasks">Maintenance Type *</x-base.form-label>
                        <select id="service_tasks" name="service_tasks"
                            class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('service_tasks') border-danger @enderror" required>
                            <option value="">Select Maintenance Type</option>
                            @foreach ($maintenanceTypes as $type)
                                <option value="{{ $type }}" {{ old('service_tasks', $maintenance->service_tasks) == $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                        @error('service_tasks')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-base.form-label for="unit">Unit *</x-base.form-label>
                            <x-base.form-input id="unit" name="unit" type="text"
                                class="w-full @error('unit') border-danger @enderror" 
                                placeholder="Unit number or identifier" 
                                value="{{ old('unit', $maintenance->unit) }}" required />
                            @error('unit')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div>
                            <x-base.form-label for="vendor_mechanic">Vendor/Mechanic *</x-base.form-label>
                            <x-base.form-input id="vendor_mechanic" name="vendor_mechanic" type="text"
                                class="w-full @error('vendor_mechanic') border-danger @enderror" 
                                placeholder="e.g., ABC Auto Shop" 
                                value="{{ old('vendor_mechanic', $maintenance->vendor_mechanic) }}" required />
                            @error('vendor_mechanic')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-base.form-label for="service_date">Service Date *</x-base.form-label>
                            <x-base.litepicker id="service_date" name="service_date" 
                                value="{{ old('service_date', $maintenance->service_date ? \Carbon\Carbon::parse($maintenance->service_date)->format('m/d/Y') : '') }}"
                                class="w-full @error('service_date') border-danger @enderror" required />
                            @error('service_date')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <x-base.form-label for="next_service_date">Next Service Date *</x-base.form-label>
                            <x-base.litepicker id="next_service_date" name="next_service_date" 
                                value="{{ old('next_service_date', $maintenance->next_service_date ? \Carbon\Carbon::parse($maintenance->next_service_date)->format('m/d/Y') : '') }}"
                                class="w-full @error('next_service_date') border-danger @enderror" required />
                            @error('next_service_date')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-base.form-label for="cost">Cost ($) *</x-base.form-label>
                            <x-base.form-input id="cost" name="cost" type="number" step="0.01" min="0"
                                class="w-full @error('cost') border-danger @enderror" 
                                placeholder="0.00" 
                                value="{{ old('cost', $maintenance->cost) }}" required />
                            @error('cost')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <x-base.form-label for="odometer">Odometer (miles) *</x-base.form-label>
                            <x-base.form-input id="odometer" name="odometer" type="number" min="0"
                                class="w-full @error('odometer') border-danger @enderror" 
                                placeholder="Current mileage" 
                                value="{{ old('odometer', $maintenance->odometer) }}" required />
                            @error('odometer')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <x-base.form-label for="description">Description/Notes</x-base.form-label>
                        <x-base.form-textarea id="description" name="description" rows="4"
                            class="w-full @error('description') border-danger @enderror" 
                            placeholder="Additional notes about this maintenance">{{ old('description', $maintenance->description) }}</x-base.form-textarea>
                        @error('description')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Existing Documents -->
                    @if($maintenance->getMedia('maintenance_files')->count() > 0)
                    <div class="mt-3">
                        <x-base.form-label>Existing Documents</x-base.form-label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-2">
                            @foreach($maintenance->getMedia('maintenance_files') as $media)
                            <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-darkmode-800 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
                                    <div>
                                        <a href="{{ $media->getUrl() }}" target="_blank" class="text-sm font-medium text-primary hover:underline">
                                            {{ $media->file_name }}
                                        </a>
                                        <p class="text-xs text-slate-500">
                                            {{ number_format($media->size / 1024, 2) }} KB
                                        </p>
                                    </div>
                                </div>
                                @if($media->getCustomProperty('uploaded_by_driver') && $media->getCustomProperty('driver_id') == $driver->id)
                                <form action="{{ route('driver.maintenance.delete-document', [$maintenance->id, $media->id]) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-danger hover:text-danger/80" onclick="return confirm('Are you sure you want to delete this document?')">
                                        <x-base.lucide class="w-4 h-4" icon="Trash2" />
                                    </button>
                                </form>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="mt-5 p-4 bg-slate-50 dark:bg-darkmode-800 rounded-lg border border-slate-200/60">
                        <x-base.form-label for="documents" class="flex items-center gap-2 mb-3">
                            <x-base.lucide class="w-4 h-4 text-primary" icon="Upload" />
                            Upload Additional Documents (Optional)
                        </x-base.form-label>
                        <div class="relative">
                            <input id="documents" name="documents[]" type="file" multiple
                                accept=".pdf,.jpg,.jpeg,.png"
                                class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary/90 cursor-pointer border border-slate-200 rounded-lg @error('documents.*') border-danger @enderror" />
                        </div>
                        <div class="flex items-center gap-2 mt-2 text-xs text-slate-500">
                            <x-base.lucide class="w-3 h-3" icon="Info" />
                            <span>Accepted: PDF, JPG, PNG (Max 10MB each)</span>
                        </div>
                        @error('documents.*')
                            <div class="text-danger mt-2 text-sm flex items-center gap-1">
                                <x-base.lucide class="w-4 h-4" icon="AlertCircle" />
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mt-5 p-4 bg-slate-50 dark:bg-darkmode-800 rounded-lg border border-slate-200/60">
                        <div class="mt-3">
                            <div class="flex items-center">
                                <input id="status" type="checkbox" name="status" value="1"
                                {{ old('status', $maintenance->status) ? 'checked' : '' }} 
                                    class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded mr-2"/>
                                <label for="status" class="ml-2 form-label">Mark as Completed</label>
                            </div>
                            @error('status')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        <p class="text-xs text-slate-500 mt-2 ml-6">Check this if the maintenance has already been completed</p>
                    </div>

                    <div class="mt-5 flex justify-end gap-2">
                        <x-base.button as="a" href="{{ route('driver.maintenance.show', $maintenance->id) }}"
                            variant="outline-secondary">
                            Cancel
                        </x-base.button>
                        <x-base.button type="submit" variant="primary">
                            <x-base.lucide class="mr-2 h-4 w-4" icon="Save" />
                            Update Maintenance Record
                        </x-base.button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

