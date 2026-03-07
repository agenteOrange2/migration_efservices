@extends('../themes/' . $activeTheme)
@section('title', 'New Repair')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'Repairs', 'url' => route('driver.emergency-repairs.index')],
        ['label' => 'New Repair', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    New Repair
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <x-base.button as="a" href="{{ route('driver.emergency-repairs.index') }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowLeft" />
                        Back to List
                    </x-base.button>
                </div>
            </div>

            <div class="box box--stacked mt-5">
                <div class="box-body p-5">
                    <!-- Vehicle Information -->
                    <div class="bg-slate-50 dark:bg-darkmode-800 p-4 rounded-lg mb-6 border border-slate-200/60 dark:border-darkmode-400">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-primary/10 rounded-lg">
                                <x-base.lucide class="w-6 h-6 text-primary" icon="Truck" />
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-800 dark:text-slate-200">
                                    @if($vehicle->company_unit_number)
                                        {{ $vehicle->company_unit_number }} - 
                                    @endif
                                    {{ $vehicle->make }} {{ $vehicle->model }} {{ $vehicle->year }}
                                </h3>
                                <p class="text-sm text-slate-500 mt-1">
                                    VIN: {{ $vehicle->vin ?? 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('driver.emergency-repairs.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <x-base.form-label for="repair_name">Repair Name *</x-base.form-label>
                                <x-base.form-input id="repair_name" name="repair_name" type="text"
                                    class="w-full @error('repair_name') border-danger @enderror" 
                                    placeholder="e.g., Brake Failure, Engine Problem" 
                                    value="{{ old('repair_name') }}" required />
                                @error('repair_name')
                                    <div class="text-danger text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <x-base.form-label for="repair_date">Repair Date *</x-base.form-label>
                                <x-base.litepicker id="repair_date" name="repair_date" 
                                    value="{{ old('repair_date', now()->format('m/d/Y')) }}"
                                    class="w-full @error('repair_date') border-danger @enderror" 
                                    placeholder="MM/DD/YYYY" required />
                                @error('repair_date')
                                    <div class="text-danger text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <x-base.form-label for="cost">Cost *</x-base.form-label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">$</span>
                                    <x-base.form-input id="cost" name="cost" type="number" step="0.01" min="0"
                                        class="w-full pl-8 @error('cost') border-danger @enderror" 
                                        placeholder="0.00" 
                                        value="{{ old('cost', '0.00') }}" required />
                                </div>
                                @error('cost')
                                    <div class="text-danger text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <x-base.form-label for="odometer">Odometer (miles)</x-base.form-label>
                                <x-base.form-input id="odometer" name="odometer" type="number" min="0"
                                    class="w-full @error('odometer') border-danger @enderror" 
                                    placeholder="e.g., 125000" 
                                    value="{{ old('odometer') }}" />
                                @error('odometer')
                                    <div class="text-danger text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <x-base.form-label for="status">Status *</x-base.form-label>
                                <select id="status" name="status"
                                    class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('status') border-danger @enderror" required>
                                    <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                                @error('status')
                                    <div class="text-danger text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-6 space-y-6">
                            <div>
                                <x-base.form-label for="description">Description</x-base.form-label>
                                <x-base.form-textarea id="description" name="description" rows="4"
                                    class="w-full @error('description') border-danger @enderror" 
                                    placeholder="Describe the emergency repair...">{{ old('description') }}</x-base.form-textarea>
                                @error('description')
                                    <div class="text-danger text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <x-base.form-label for="notes">Additional Notes</x-base.form-label>
                                <x-base.form-textarea id="notes" name="notes" rows="4"
                                    class="w-full @error('notes') border-danger @enderror" 
                                    placeholder="Any additional notes...">{{ old('notes') }}</x-base.form-textarea>
                                @error('notes')
                                    <div class="text-danger text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Upload Documents -->
                            <div>
                                <x-base.form-label for="repair_files" class="flex items-center gap-2 mb-2">
                                    <x-base.lucide class="w-4 h-4 text-primary" icon="Upload" />
                                    Upload Documents
                                    <span class="text-xs text-slate-400 font-normal">(Optional)</span>
                                </x-base.form-label>
                                <div class="border-2 border-dashed border-slate-200/60 dark:border-darkmode-400 rounded-lg p-6 transition-colors" id="file-upload-area">
                                    <div class="relative min-h-[120px]">
                                        <input type="file" id="repair_files" name="repair_files[]" multiple 
                                            accept="image/*,.pdf,.doc,.docx" 
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10 rounded-lg" />
                                        <div class="relative flex flex-col items-center justify-center py-4 text-center pointer-events-none">
                                            <div class="p-3 bg-primary/10 rounded-full mb-3">
                                                <x-base.lucide class="w-8 h-8 text-primary" icon="Upload" />
                                            </div>
                                            <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                                Drag files here or click to upload
                                            </p>
                                            <p class="text-xs text-slate-500 mt-1">
                                                PDF, JPG, PNG, DOC, DOCX (Max 10MB each)
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <!-- File list OUTSIDE the upload area to avoid z-index issues -->
                                <div id="file-list" class="mt-3 space-y-2"></div>
                                @error('repair_files')
                                    <div class="text-danger text-sm mt-1">{{ $message }}</div>
                                @enderror
                                @error('repair_files.*')
                                    <div class="text-danger text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row justify-end gap-3 mt-8 pt-6 border-t border-slate-200/60 dark:border-darkmode-400">
                            <x-base.button as="a" href="{{ route('driver.emergency-repairs.index') }}" 
                                variant="outline-secondary" class="w-full sm:w-auto">
                                Cancel
                            </x-base.button>
                            <x-base.button type="submit" variant="primary" class="w-full sm:w-auto">
                                <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="Plus" />
                                Create Emergency Repair
                            </x-base.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const fileInput = document.getElementById('repair_files');
            const fileList = document.getElementById('file-list');
            const uploadArea = document.getElementById('file-upload-area');

            fileInput.addEventListener('change', function () {
                displaySelectedFiles(this.files);
            });

            uploadArea.addEventListener('dragover', function (e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('border-primary', 'bg-primary/5');
            });

            uploadArea.addEventListener('dragleave', function (e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('border-primary', 'bg-primary/5');
            });

            uploadArea.addEventListener('drop', function (e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('border-primary', 'bg-primary/5');
                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    displaySelectedFiles(e.dataTransfer.files);
                }
            });

            function displaySelectedFiles(files) {
                fileList.innerHTML = '';
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const ext = (file.name.split('.').pop() || '').toLowerCase();
                    let icon = 'lucide lucide-file w-5 h-5 text-slate-500';
                    if (['jpg','jpeg','png','gif','webp'].includes(ext)) icon = 'lucide lucide-image w-5 h-5 text-blue-500';
                    else if (ext === 'pdf') icon = 'lucide lucide-file-text w-5 h-5 text-red-500';
                    const item = document.createElement('div');
                    item.className = 'flex items-center justify-between p-3 bg-slate-50 dark:bg-darkmode-800 rounded-lg border border-slate-200/60 dark:border-darkmode-400';
                    item.innerHTML = `
                        <div class="flex items-center gap-2 min-w-0">
                            <i class="${icon} flex-shrink-0"></i>
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-slate-800 dark:text-slate-200 truncate">${file.name}</div>
                                <div class="text-xs text-slate-500">${formatFileSize(file.size)}</div>
                            </div>
                        </div>
                        <button type="button" class="ml-2 p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors flex-shrink-0" data-index="${i}" title="Remove">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                        </button>
                    `;
                    fileList.appendChild(item);
                }
                fileList.querySelectorAll('button[data-index]').forEach(btn => {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        removeFile(parseInt(this.getAttribute('data-index'), 10));
                    });
                });
            }

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 B';
                const k = 1024;
                const sizes = ['B', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            function removeFile(index) {
                const dt = new DataTransfer();
                const files = Array.from(fileInput.files);
                files.forEach((f, i) => { if (i !== index) dt.items.add(f); });
                fileInput.files = dt.files;
                displaySelectedFiles(fileInput.files);
            }
        });
    </script>
    @endpush
@endsection

