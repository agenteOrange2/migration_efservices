@extends('../themes/' . $activeTheme)
@section('title', 'Safety Data System - ' . $carrier->name)

@php
$breadcrumbLinks = [
    ['label' => 'App', 'url' => route('admin.dashboard')],
    ['label' => 'Carriers', 'url' => route('admin.carrier.index')],
    ['label' => $carrier->name, 'url' => route('admin.carrier.show', $carrier->slug)],
    ['label' => 'Safety Data System', 'active' => true],
];
@endphp

@section('subcontent')

<!-- Breadcrumbs -->
<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<!-- Header -->
<div class="box box--stacked p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                <x-base.lucide class="w-8 h-8 text-primary" icon="Shield" />
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">Safety Data System</h1>
                <p class="text-slate-600">Manage safety data system image for {{ $carrier->name }}</p>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <x-base.button as="a" href="{{ route('admin.carrier.show', $carrier->slug) }}" variant="secondary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                Back to Details
            </x-base.button>
        </div>
    </div>
</div>

<div class="grid grid-cols-12 gap-6">
    <!-- Información del Carrier -->
    <div class="col-span-12 lg:col-span-4">
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Info" />
                <h2 class="text-lg font-semibold text-slate-800">Carrier Information</h2>
            </div>
            
            <div class="space-y-4">
                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Name</label>
                    <p class="text-sm font-semibold text-slate-800">{{ $carrier->name }}</p>
                </div>
                
                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">DOT Number</label>
                    <p class="text-sm font-semibold text-slate-800">{{ $carrier->dot_number ?? 'N/A' }}</p>
                </div>
                
                <!-- URL Personalizada -->
                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Manage URL</label>
                    <form action="{{ route('admin.carrier.safety-data-system.update', $carrier) }}" method="POST" class="space-y-3">
                        @csrf
                        @method('PUT')
                        
                        <div>
                            <label for="custom_safety_url" class="block text-xs font-medium text-slate-600 mb-1">
                                Custom URL (optional)
                            </label>
                            <input 
                                type="url" 
                                id="custom_safety_url" 
                                name="custom_safety_url" 
                                value="{{ old('custom_safety_url', $carrier->custom_safety_url) }}"
                                placeholder="https://ejemplo.com/safety-data"
                                class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                            >
                            <p class="mt-1 text-xs text-slate-500">
                                Leave blank to use the automatically generated URL with the DOT Number
                            </p>
                        </div>
                        
                        <x-base.button type="submit" variant="primary" size="sm" class="w-full gap-2">
                            <x-base.lucide class="w-4 h-4" icon="Save" />
                            Save URL
                        </x-base.button>
                    </form>
                </div>
                
                @if($carrier->dot_number)
                <div class="bg-blue-50/50 rounded-lg p-4 border border-blue-100">
                    <label class="text-xs font-medium text-blue-600 uppercase tracking-wide mb-2 block">
                        {{ $carrier->hasCustomSafetyUrl() ? 'Active URL (Custom)' : 'Active URL (Automatic)' }}
                    </label>
                    @if($carrier->hasCustomSafetyUrl())
                        <div class="mb-2 px-2 py-1 bg-warning/10 border border-warning/20 rounded text-xs text-warning-700 flex items-center gap-1">
                            <x-base.lucide class="w-3 h-3" icon="AlertCircle" />
                            Using custom URL
                        </div>
                    @endif
                    <div class="break-all mb-2">
                        <a href="{{ $carrier->safety_data_system_url }}" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 underline">
                            {{ $carrier->safety_data_system_url }}
                        </a>
                    </div>
                    
                    @if($carrier->hasCustomSafetyUrl() && $carrier->auto_generated_safety_url)
                        <div class="mt-3 pt-3 border-t border-blue-200">
                            <label class="text-xs font-medium text-slate-500 mb-1 block">Automatic URL (not in use)</label>
                            <p class="text-xs text-slate-600 break-all">{{ $carrier->auto_generated_safety_url }}</p>
                        </div>
                    @endif
                    
                    <div class="mt-3">
                        <x-base.button as="a" href="{{ $carrier->safety_data_system_url }}" target="_blank" variant="primary" size="sm" class="w-full gap-2">
                            <x-base.lucide class="w-4 h-4" icon="ExternalLink" />
                            View in FMCSA
                        </x-base.button>
                    </div>
                </div>
                @else
                <div class="bg-yellow-50/50 rounded-lg p-4 border border-yellow-100">
                    <div class="flex items-start gap-2">
                        <x-base.lucide class="w-5 h-5 text-yellow-600 mt-0.5" icon="AlertTriangle" />
                        <div>
                            <p class="text-sm font-medium text-yellow-800">DOT Number Required</p>
                            <p class="text-xs text-yellow-700 mt-1">This carrier needs a DOT Number to generate the Safety Data System URL.</p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Gestión de Imagen -->
    <div class="col-span-12 lg:col-span-8">
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Image" />
                <h2 class="text-lg font-semibold text-slate-800">Safety Data System Image</h2>
            </div>

            <!-- Imagen Actual -->
            @if($carrier->hasSafetyDataSystemImage())
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 mb-3">Imagen Actual</label>
                <div class="relative inline-block">
                    <img src="{{ $carrier->getSafetyDataSystemImageUrl() }}" 
                         alt="Safety Data System" 
                         class="w-full max-w-md rounded-lg border-2 border-slate-200 shadow-sm">
                    
                    <!-- Botón para eliminar -->
                    <form action="{{ route('admin.carrier.safety-data-system.delete', $carrier) }}" method="POST" class="mt-4">
                        @csrf
                        @method('DELETE')
                        <x-base.button type="submit" variant="danger" size="sm" class="gap-2" 
                                       onclick="return confirm('Are you sure you want to delete this image?')">
                            <x-base.lucide class="w-4 h-4" icon="Trash2" />
                            Delete Image
                        </x-base.button>
                    </form>
                </div>
            </div>
            @else
            <div class="mb-6 bg-slate-50 rounded-lg p-8 border-2 border-dashed border-slate-200">
                <div class="text-center">
                    <x-base.lucide class="w-16 h-16 text-slate-400 mx-auto mb-3" icon="ImageOff" />
                    <p class="text-sm text-slate-600">No safety data system image</p>
                    <p class="text-xs text-slate-500 mt-1">Upload an image to appear in the dashboard and carrier profile</p>
                </div>
            </div>
            @endif

            <!-- Formulario de Subida -->
            <div class="border-t pt-6">
                <form action="{{ route('admin.carrier.safety-data-system.upload', $carrier) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-6">
                        <label for="safety_image" class="block text-sm font-medium text-slate-700 mb-2">
                            {{ $carrier->hasSafetyDataSystemImage() ? 'Change Image' : 'Upload Image' }}
                        </label>
                        <input type="file" 
                               name="safety_image" 
                               id="safety_image" 
                               accept="image/*" 
                               required
                               class="block w-full text-sm text-slate-500
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-lg file:border-0
                                      file:text-sm file:font-semibold
                                      file:bg-primary file:text-white
                                      hover:file:bg-primary/90
                                      cursor-pointer border border-slate-300 rounded-lg">
                        
                        @error('safety_image')
                            <p class="mt-2 text-sm text-danger">{{ $message }}</p>
                        @enderror
                        
                        <p class="mt-2 text-xs text-slate-500">
                            Allowed formats: JPG, PNG, GIF. Maximum size: 2MB
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <x-base.button type="submit" variant="primary" class="gap-2">
                            <x-base.lucide class="w-4 h-4" icon="Upload" />
                            {{ $carrier->hasSafetyDataSystemImage() ? 'Update Image' : 'Upload Image' }}
                        </x-base.button>
                    </div>
                </form>
            </div>

            <!-- Preview de cómo se verá -->
            @if($carrier->hasSafetyDataSystemImage() || $carrier->dot_number)
            <div class="border-t pt-6 mt-6">
                <label class="block text-sm font-medium text-slate-700 mb-3">Preview - How it will look for the Carrier</label>
                
                <div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-lg p-6 border border-slate-200">
                    <!-- Card Preview -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden max-w-sm">
                        <div class="relative h-48 bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center">
                            <img src="{{ $carrier->getSafetyDataSystemImageUrl() }}" 
                                 alt="Safety Data System" 
                                 class="w-full h-full object-cover">
                        </div>
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-slate-800 mb-1">{{ $carrier->name }}</h3>
                            <p class="text-sm text-slate-500 mb-4">Safety Data System</p>
                            <x-base.button as="a" href="#" variant="primary" class="w-full gap-2" disabled>
                                <x-base.lucide class="w-4 h-4" icon="Shield" />
                                Consulting Safety
                            </x-base.button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Preview de imagen antes de subir
    document.getElementById('safety_image')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                // Aquí podrías agregar un preview dinámico si lo deseas
                console.log('Imagen seleccionada:', file.name);
            };
            reader.readAsDataURL(file);
        }
    });
</script>
@endpush

