@extends('../themes/' . $activeTheme)
@section('title', 'Bulk Data Import')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Bulk Import', 'active' => true],
    ];
@endphp

@section('subcontent')

<!-- Breadcrumbs -->
<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<!-- Flash Messages -->
@if(session('success'))
    <div class="alert alert-success flex items-center mb-5">
        <x-base.lucide class="w-6 h-6 mr-2" icon="CheckCircle" />
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger flex items-center mb-5">
        <x-base.lucide class="w-6 h-6 mr-2" icon="AlertCircle" />
        {{ session('error') }}
    </div>
@endif

<!-- Header -->
<div class="box box--stacked p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 rounded-xl bg-primary/10 border border-primary/20">
                <x-base.lucide class="w-8 h-8 text-primary" icon="Upload" />
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-800 mb-1">Bulk Data Import</h1>
                <p class="text-slate-500">Import vehicles, maintenance records, repairs, HOS entries, and applications from CSV files</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Import Form -->
    <div class="lg:col-span-2 space-y-6">
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200/60">
                <x-base.lucide class="w-5 h-5 text-primary" icon="FileUp" />
                <h2 class="text-lg font-semibold text-slate-800">Upload CSV File</h2>
            </div>

            <form action="{{ route('admin.imports.preview') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- Carrier Selection -->
                <div class="mb-6" id="carrier_selection">
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Select Carrier <span class="text-danger" id="carrier_required">*</span>
                    </label>
                    <select
                        name="carrier_id"
                        id="carrier_id"
                        class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                        required
                    >
                        <option value="">-- Select a Carrier --</option>
                        @foreach($carriers as $carrier)
                            <option value="{{ $carrier->id }}" {{ old('carrier_id') == $carrier->id ? 'selected' : '' }}>
                                {{ $carrier->name }} ({{ $carrier->dot_number ?? 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                    @error('carrier_id')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Import Type Selection -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Import Type <span class="text-danger">*</span>
                    </label>
                    <select
                        name="import_type"
                        id="import_type"
                        class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                        required
                    >
                        <option value="">-- Select Import Type --</option>
                        @foreach($importTypes as $key => $type)
                            <option value="{{ $key }}" {{ old('import_type') == $key ? 'selected' : '' }}>
                                {{ $type['name'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('import_type')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <!-- File Upload -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        CSV File <span class="text-danger">*</span>
                    </label>
                    <div class="border-2 border-dashed border-slate-200 rounded-lg p-6 text-center hover:border-primary/50 transition-colors">
                        <input
                            type="file"
                            name="csv_file"
                            id="csv_file"
                            accept=".csv,.xlsx,.xls"
                            class="hidden"
                            required
                        >
                        <label for="csv_file" class="cursor-pointer">
                            <x-base.lucide class="w-12 h-12 text-slate-400 mx-auto mb-3" icon="Upload" />
                            <p class="text-sm text-slate-600 mb-1">
                                <span class="text-primary font-medium">Click to upload</span> or drag and drop
                            </p>
                            <p class="text-xs text-slate-400">CSV or Excel files up to 10MB</p>
                        </label>
                        <p id="file_name" class="mt-3 text-sm text-primary font-medium hidden"></p>
                    </div>
                    @error('csv_file')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div class="flex items-center gap-4">
                    <x-base.button type="submit" variant="primary" class="gap-2">
                        <x-base.lucide class="w-4 h-4" icon="Eye" />
                        Preview Import
                    </x-base.button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sidebar - Import Types Info -->
    <div class="space-y-6">
        <!-- Download Templates -->
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200/60">
                <x-base.lucide class="w-5 h-5 text-info" icon="Download" />
                <h2 class="text-lg font-semibold text-slate-800">Download Templates</h2>
            </div>

            <div class="space-y-3">
                @foreach($importTypes as $key => $type)
                    <a href="{{ route('admin.imports.template', $key) }}" class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="{{ $type['icon'] }}" />
                        <div class="flex-1">
                            <p class="text-sm font-medium text-slate-800">{{ $type['name'] }}</p>
                            <p class="text-xs text-slate-500">{{ $type['template'] }}</p>
                        </div>
                        <x-base.lucide class="w-4 h-4 text-slate-400" icon="Download" />
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Import Guidelines -->
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200/60">
                <x-base.lucide class="w-5 h-5 text-warning" icon="Info" />
                <h2 class="text-lg font-semibold text-slate-800">Guidelines</h2>
            </div>

            <div class="space-y-4 text-sm text-slate-600">
                <div class="flex items-start gap-2">
                    <x-base.lucide class="w-4 h-4 text-success mt-0.5" icon="Check" />
                    <p>Download the template for your import type first</p>
                </div>
                <div class="flex items-start gap-2">
                    <x-base.lucide class="w-4 h-4 text-success mt-0.5" icon="Check" />
                    <p>Keep the header row intact - do not modify column names</p>
                </div>
                <div class="flex items-start gap-2">
                    <x-base.lucide class="w-4 h-4 text-success mt-0.5" icon="Check" />
                    <p>Use consistent date formats (YYYY-MM-DD recommended)</p>
                </div>
                <div class="flex items-start gap-2">
                    <x-base.lucide class="w-4 h-4 text-success mt-0.5" icon="Check" />
                    <p>Duplicate records will be automatically skipped</p>
                </div>
                <div class="flex items-start gap-2">
                    <x-base.lucide class="w-4 h-4 text-warning mt-0.5" icon="AlertTriangle" />
                    <p>Preview your data before importing to catch errors</p>
                </div>
            </div>
        </div>

        <!-- Import Type Descriptions -->
        <div class="box box--stacked p-6" id="type_description" style="display: none;">
            <div class="flex items-center gap-3 mb-4">
                <x-base.lucide class="w-5 h-5 text-primary" id="type_icon" icon="FileText" />
                <h3 class="font-semibold text-slate-800" id="type_name">Import Type</h3>
            </div>
            <p class="text-sm text-slate-600" id="type_desc"></p>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // File upload display
    document.getElementById('csv_file').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name;
        const fileNameDisplay = document.getElementById('file_name');
        if (fileName) {
            fileNameDisplay.textContent = fileName;
            fileNameDisplay.classList.remove('hidden');
        } else {
            fileNameDisplay.classList.add('hidden');
        }
    });

    // Import type descriptions
    const typeDescriptions = @json($importTypes);

    document.getElementById('import_type').addEventListener('change', function(e) {
        const type = e.target.value;
        const descBox = document.getElementById('type_description');
        const carrierSelection = document.getElementById('carrier_selection');
        const carrierSelect = document.getElementById('carrier_id');
        const carrierRequired = document.getElementById('carrier_required');

        // Handle carrier selection visibility based on import type
        if (type === 'carriers') {
            // Hide carrier selection for 'carriers' import type
            carrierSelection.style.display = 'none';
            carrierSelect.removeAttribute('required');
            carrierSelect.value = ''; // Clear selection
        } else {
            // Show carrier selection for all other import types
            carrierSelection.style.display = 'block';
            carrierSelect.setAttribute('required', 'required');
        }

        // Show type description
        if (type && typeDescriptions[type]) {
            document.getElementById('type_name').textContent = typeDescriptions[type].name;
            document.getElementById('type_desc').textContent = typeDescriptions[type].description;
            descBox.style.display = 'block';
        } else {
            descBox.style.display = 'none';
        }
    });
</script>
@endpush
