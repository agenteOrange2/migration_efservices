@extends('../themes/' . $activeTheme)
@section('title', 'Create Emergency Repair')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Vehicles', 'url' => route('admin.vehicles.index')],
        ['label' => $vehicle->make . ' ' . $vehicle->model, 'url' => route('admin.vehicles.show', $vehicle->id)],
        ['label' => 'Maintenances', 'url' => route('admin.vehicles.maintenances.index', $vehicle->id)],
        ['label' => 'New Emergency Repair', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium">
                    New Emergency Repair: {{ $vehicle->make }} {{ $vehicle->model }} ({{ $vehicle->year }})
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <x-base.button as="a" href="{{ route('admin.vehicles.maintenances.index', $vehicle->id) }}"
                        class="w-full sm:w-auto" variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowLeft" />
                        Back to Maintenances
                    </x-base.button>
                </div>
            </div>

            <div class="box box--stacked mt-5">
                <div class="box-header">
                    <div class="box-title p-5 border-b border-slate-200/60 bg-slate-50">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-danger/10 rounded-lg">
                                <x-base.lucide class="h-5 w-5 text-danger" icon="AlertTriangle" />
                            </div>
                            Emergency Repair Data
                        </div>
                    </div>
                </div>
                <div class="box-body p-5">
                    @if ($errors->any())
                        <div class="alert alert-danger mb-4">
                            <ul class="ml-4 list-disc">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.admin-vehicles.vehicle-emergency-repairs.store', $vehicle->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                            <!-- Vehicle Info (Read-only) -->
                            <div>
                                <x-base.form-label for="vehicle_info">Vehicle</x-base.form-label>
                                <x-base.form-input id="vehicle_info" 
                                    value="{{ $vehicle->company_unit_number }} - {{ $vehicle->make }} {{ $vehicle->model }}" 
                                    readonly class="bg-slate-100"/>
                            </div>

                            <!-- Repair Name -->
                            <div>
                                <x-base.form-label for="repair_name">Repair Name <span class="text-danger">*</span></x-base.form-label>
                                <x-base.form-input id="repair_name" name="repair_name" type="text" 
                                    placeholder="Ex: Engine failure, Brake replacement..." 
                                    value="{{ old('repair_name') }}" required />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                            <!-- Repair Date -->
                            <div>
                                <x-base.form-label for="repair_date">Repair Date <span class="text-danger">*</span></x-base.form-label>
                                <x-base.litepicker id="repair_date" name="repair_date" 
                                    value="{{ old('repair_date', date('m/d/Y')) }}" 
                                    placeholder="MM/DD/YYYY" data-single-mode="true" data-format="MM/DD/YYYY" class="w-full" required />
                            </div>

                            <!-- Cost -->
                            <div>
                                <x-base.form-label for="cost">Cost ($) <span class="text-danger">*</span></x-base.form-label>
                                <x-base.form-input id="cost" name="cost" type="number" step="0.01" min="0"
                                    placeholder="0.00" value="{{ old('cost', '0.00') }}" required />
                            </div>

                            <!-- Odometer -->
                            <div>
                                <x-base.form-label for="odometer">Odometer (miles)</x-base.form-label>
                                <x-base.form-input id="odometer" name="odometer" type="number" min="0"
                                    placeholder="Odometer reading" value="{{ old('odometer') }}" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-1 gap-6 mb-4">
                            <!-- Status -->
                            <div>
                                <x-base.form-label for="status">Status <span class="text-danger">*</span></x-base.form-label>
                                <select id="status" name="status" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8" required>
                                    <option value="pending" {{ old('status', 'pending') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <x-base.form-label for="description">Description</x-base.form-label>
                            <x-base.form-textarea id="description" name="description" rows="3"
                                placeholder="Describe the emergency repair...">{{ old('description') }}</x-base.form-textarea>
                        </div>

                        <div class="mb-4">
                            <x-base.form-label for="notes">Notes</x-base.form-label>
                            <x-base.form-textarea id="notes" name="notes" rows="2"
                                placeholder="Additional notes...">{{ old('notes') }}</x-base.form-textarea>
                        </div>

                        <!-- File Upload -->
                        <div class="mb-4">
                            <x-base.form-label>Repair Documents/Photos</x-base.form-label>
                            <div 
                                x-data="{
                                    files: [],
                                    isDragging: false,
                                    handleFileSelect(e) {
                                        if (e.target.files.length) {
                                            this.addFiles(e.target.files);
                                        }
                                    },
                                    addFiles(fileList) {
                                        for (let i = 0; i < fileList.length; i++) {
                                            if (this.validateFile(fileList[i])) {
                                                this.files.push({
                                                    file: fileList[i],
                                                    name: fileList[i].name,
                                                    size: this.formatFileSize(fileList[i].size),
                                                    type: fileList[i].type,
                                                    preview: this.createPreview(fileList[i])
                                                });
                                            }
                                        }
                                    },
                                    validateFile(file) {
                                        const validTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
                                        if (!validTypes.includes(file.type)) {
                                            alert('File type not allowed. Only PDF and images are allowed.');
                                            return false;
                                        }
                                        if (file.size > 10 * 1024 * 1024) {
                                            alert('File is too large. Maximum size: 10MB');
                                            return false;
                                        }
                                        return true;
                                    },
                                    formatFileSize(size) {
                                        if (size < 1024) return size + ' bytes';
                                        else if (size < 1024 * 1024) return (size / 1024).toFixed(2) + ' KB';
                                        else return (size / (1024 * 1024)).toFixed(2) + ' MB';
                                    },
                                    createPreview(file) {
                                        if (file.type.startsWith('image/')) {
                                            return URL.createObjectURL(file);
                                        }
                                        return null;
                                    },
                                    removeFile(index) {
                                        this.files.splice(index, 1);
                                    },
                                    handleDrop(e) {
                                        e.preventDefault();
                                        this.isDragging = false;
                                        if (e.dataTransfer.files.length) {
                                            this.addFiles(e.dataTransfer.files);
                                        }
                                    }
                                }"
                                class="border-2 border-dashed rounded-lg p-6"
                                :class="{'border-primary bg-primary/10': isDragging, 'border-slate-300': !isDragging}"
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop="handleDrop"
                            >
                                <div x-show="files.length === 0" class="text-center">
                                    <x-base.lucide class="mx-auto h-12 w-12 text-slate-400" icon="Upload" />
                                    <p class="mt-2 text-sm text-slate-500">
                                        Drag and drop files here or
                                        <label class="relative cursor-pointer text-primary hover:text-primary-dark">
                                            <span class="underline">select files</span>
                                            <input type="file" class="sr-only" multiple @change="handleFileSelect" name="repair_files[]">
                                        </label>
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        PDF, JPG, PNG (Max. 10MB per file)
                                    </p>
                                </div>
                                
                                <div x-show="files.length > 0" x-cloak>
                                    <div class="space-y-3 mb-4">
                                        <template x-for="(file, index) in files" :key="index">
                                            <div class="flex items-center justify-between p-3 border border-slate-200 rounded-lg bg-white shadow-sm">
                                                <div class="flex items-center min-w-0 flex-1">
                                                    <div class="flex-shrink-0 mr-3">
                                                        <template x-if="file.type === 'application/pdf'">
                                                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                                                <x-base.lucide class="h-6 w-6 text-red-600" icon="FileText" />
                                                            </div>
                                                        </template>
                                                        <template x-if="file.type.startsWith('image/')">
                                                            <div class="w-10 h-10 rounded-lg overflow-hidden bg-slate-100">
                                                                <img :src="file.preview" class="w-full h-full object-cover" alt="Preview" />
                                                            </div>
                                                        </template>
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <p class="text-sm font-medium text-slate-900 truncate" x-text="file.name"></p>
                                                        <p class="text-xs text-slate-500" x-text="file.size"></p>
                                                    </div>
                                                </div>
                                                <button type="button" @click="removeFile(index)" 
                                                    class="flex-shrink-0 ml-3 p-1 text-slate-400 hover:text-red-500 rounded-full hover:bg-red-50 transition-colors">
                                                    <x-base.lucide class="h-5 w-5" icon="X" />
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                    
                                    <div class="flex flex-wrap gap-2 pt-3 border-t border-slate-200">
                                        <label class="inline-flex items-center px-3 py-2 text-sm font-medium text-primary bg-white border border-primary rounded-lg hover:bg-primary hover:text-white transition-colors cursor-pointer">
                                            <x-base.lucide class="mr-2 h-4 w-4" icon="Plus" />
                                            Add more files
                                            <input type="file" class="sr-only" multiple @change="handleFileSelect" name="repair_files[]">
                                        </label>
                                        <button type="button" @click="files = []" 
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                                            <x-base.lucide class="mr-2 h-4 w-4" icon="Trash" />
                                            Clear all
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center mt-5 pt-5 border-t border-slate-200/60">
                            <div class="ml-auto">
                                <x-base.button as="a" href="{{ route('admin.vehicles.maintenances.index', $vehicle->id) }}" 
                                    variant="outline-secondary" class="mr-1 w-24">
                                    Cancel
                                </x-base.button>
                                <x-base.button type="submit" variant="danger" class="w-32">
                                    <x-base.lucide class="mr-2 h-4 w-4" icon="Save" />
                                    Save Repair
                                </x-base.button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
