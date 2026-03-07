<div>
    <!-- Notificación de documentos incompletos -->
    @if(!$carrier->documents_completed)
        <div class="mb-6 rounded-lg border border-warning bg-warning/10 p-4 shadow-sm">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <x-base.lucide class="h-5 w-5 text-warning" icon="AlertTriangle" />
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-semibold text-warning">
                        Incomplete Documents
                    </h3>
                    <div class="mt-2 text-sm text-warning">
                        <p>You have pending documents to complete. Please upload all required documents to access all platform features and ensure compliance.</p>
                    </div>
                    <div class="mt-4">
                        <div class="-mx-2 -my-1.5 flex">
                            <a href="{{ route('carrier.documents.index', $carrier->slug) }}" 
                               class="rounded-md bg-warning px-3 py-2 text-sm font-medium text-white hover:bg-warning/90 focus:outline-none focus:ring-2 focus:ring-warning focus:ring-offset-2 transition-colors">
                                Complete Documents
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif 

    <!-- Alertas Inteligentes -->
    @if(!empty($alertsData))
        <div class="mb-6 flex flex-col gap-4">
            @foreach($alertsData as $alert)
                @php
                    // Determinar clases de color según el tipo de alerta
                    $alertClasses = match($alert['type']) {
                        'warning' => 'border-warning/20 bg-warning/10 text-warning-800',
                        'info' => 'border-info/20 bg-info/10 text-info-800',
                        'danger' => 'border-danger/20 bg-danger/10 text-danger-800',
                        'success' => 'border-success/20 bg-success/10 text-success-800',
                        default => 'border-slate-200 bg-slate-50 text-slate-800'
                    };
                    
                    $iconColor = match($alert['type']) {
                        'warning' => 'text-warning',
                        'info' => 'text-info',
                        'danger' => 'text-danger',
                        'success' => 'text-success',
                        default => 'text-slate-600'
                    };
                    
                    $buttonClasses = match($alert['type']) {
                        'warning' => 'bg-warning/20 text-warning-800 hover:bg-warning/30 focus:ring-warning',
                        'info' => 'bg-info/20 text-info-800 hover:bg-info/30 focus:ring-info',
                        'danger' => 'bg-danger/20 text-danger-800 hover:bg-danger/30 focus:ring-danger',
                        'success' => 'bg-success/20 text-success-800 hover:bg-success/30 focus:ring-success',
                        default => 'bg-slate-200 text-slate-800 hover:bg-slate-300 focus:ring-slate-400'
                    };
                @endphp
                
                <div class="rounded-lg border {{ $alertClasses }} p-4 shadow-sm transition-all hover:shadow-md">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <x-base.lucide class="h-5 w-5 {{ $iconColor }}" icon="{{ $alert['icon'] }}" />
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-sm font-semibold">
                                {{ $alert['title'] }}
                            </h3>
                            <div class="mt-2 text-sm opacity-90">
                                <p>{{ $alert['message'] }}</p>
                            </div>
                            <div class="mt-4">
                                <div class="-mx-2 -my-1.5 flex">
                                    <a href="{{ $alert['url'] }}" 
                                       class="rounded-md {{ $buttonClasses }} px-3 py-2 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2">
                                        {{ $alert['action'] }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Botón de actualización -->
    <div class="mb-4 flex justify-end">
        <button 
            wire:click="refreshData" 
            class="btn btn-outline-secondary flex items-center gap-2 px-4 py-2 text-sm transition-all hover:bg-slate-100"
            wire:loading.attr="disabled"
            wire:target="refreshData"
        >
            <x-base.lucide class="h-4 w-4" icon="RefreshCw" wire:loading.class="animate-spin" wire:target="refreshData" />
            <span wire:loading.remove wire:target="refreshData">Refresh Data</span>
            <span wire:loading wire:target="refreshData">Refreshing...</span>
        </button>
    </div>

    <div class="grid grid-cols-12 gap-x-4 gap-y-6 md:gap-x-6 md:gap-y-10">
        <!-- Estadísticas de cabecera -->
        <div class="col-span-12">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <!-- Card 1: Drivers - Con gradiente destacado -->
                <div class="box relative overflow-hidden rounded-[0.6rem] border-0 bg-gradient-to-b from-theme-2/90 to-theme-1/[0.85] p-5 shadow-sm before:absolute before:right-0 before:top-0 before:-mr-[62%] before:h-[130%] before:w-full before:rotate-45 before:bg-gradient-to-b before:from-black/[0.15] before:to-transparent before:content-['']">
                    <div class="relative z-10">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full border border-white/10 bg-white/10">
                            <x-base.lucide class="h-6 w-6 fill-white/10 text-white" icon="Users" />
                        </div>
                        <div class="mt-12 flex items-center">
                            <div class="text-2xl font-medium leading-none text-white">{{ $driversCount }}</div>
                            @if($this->membershipLimits['maxDrivers'] > 0)
                                <div class="ml-3.5 flex items-center rounded-full border border-white/30 bg-white/20 py-[2px] pl-[7px] pr-1 text-xs font-medium text-white">
                                    {{ $this->membershipLimits['driversPercentage'] }}%
                                    <x-base.lucide class="ml-px h-4 w-4 stroke-[1.5]" icon="TrendingUp" />
                                </div>
                            @endif
                        </div>
                        <div class="mt-1 text-base text-white/70">
                            Drivers
                        </div>
                        @if($this->membershipLimits['maxDrivers'] > 0)
                            <div class="mt-2 text-xs text-white/60">
                                {{ $driversCount }} / {{ $this->membershipLimits['maxDrivers'] }} used
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Card 2: Vehicles -->
                <div class="box relative overflow-hidden rounded-[0.6rem] border border-slate-200/60 bg-white p-5 shadow-sm transition-all hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full border border-primary/10 bg-primary/10">
                        <x-base.lucide class="h-6 w-6 fill-primary/10 text-primary" icon="Truck" />
                    </div>
                    <div class="mt-12 flex items-center">
                        <div class="text-2xl font-medium leading-none text-slate-800">{{ $vehiclesCount }}</div>
                        @if($this->membershipLimits['maxVehicles'] > 0)
                            <div class="ml-3.5 flex items-center rounded-full border border-primary/30 bg-primary/10 py-[2px] pl-[7px] pr-1 text-xs font-medium text-primary">
                                {{ $this->membershipLimits['vehiclesPercentage'] }}%
                                <x-base.lucide class="ml-px h-4 w-4 stroke-[1.5]" icon="TrendingUp" />
                            </div>
                        @endif
                    </div>
                    <div class="mt-1 text-base text-slate-500">
                        Vehicles
                    </div>
                    @if($this->membershipLimits['maxVehicles'] > 0)
                        <div class="mt-2 text-xs text-slate-400">
                            {{ $vehiclesCount }} / {{ $this->membershipLimits['maxVehicles'] }} used
                        </div>
                    @endif
                </div>
                
                <!-- Card 3: Documents Approved -->
                <div class="box relative overflow-hidden rounded-[0.6rem] border border-slate-200/60 bg-white p-5 shadow-sm transition-all hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full border border-success/10 bg-success/10">
                        <x-base.lucide class="h-6 w-6 fill-success/10 text-success" icon="FileCheck" />
                    </div>
                    <div class="mt-12 flex items-center">
                        <div class="text-2xl font-medium leading-none text-slate-800">{{ $documentStats['approved'] }}</div>
                        @if($documentStats['total'] > 0)
                            <div class="ml-3.5 flex items-center rounded-full border border-success/30 bg-success/10 py-[2px] pl-[7px] pr-1 text-xs font-medium text-success">
                                {{ round(($documentStats['approved'] / $documentStats['total']) * 100) }}%
                                <x-base.lucide class="ml-px h-4 w-4 stroke-[1.5]" icon="CheckCircle" />
                            </div>
                        @endif
                    </div>
                    <div class="mt-1 text-base text-slate-500">
                        Documents Approved
                    </div>
                    @if($documentStats['total'] > 0)
                        <div class="mt-2 text-xs text-slate-400">
                            {{ $documentStats['approved'] }} / {{ $documentStats['total'] }} total
                        </div>
                    @endif
                </div>
                
                <!-- Card 4: Pending Documents -->
                <div class="box relative overflow-hidden rounded-[0.6rem] border border-slate-200/60 bg-white p-5 shadow-sm transition-all hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full border border-warning/10 bg-warning/10">
                        <x-base.lucide class="h-6 w-6 fill-warning/10 text-warning" icon="FileClock" />
                    </div>
                    <div class="mt-12 flex items-center">
                        <div class="text-2xl font-medium leading-none text-slate-800">{{ $documentStats['pending'] }}</div>
                        @if($documentStats['total'] > 0)
                            <div class="ml-3.5 flex items-center rounded-full border border-warning/30 bg-warning/10 py-[2px] pl-[7px] pr-1 text-xs font-medium text-warning">
                                {{ round(($documentStats['pending'] / $documentStats['total']) * 100) }}%
                                <x-base.lucide class="ml-px h-4 w-4 stroke-[1.5]" icon="Clock" />
                            </div>
                        @endif
                    </div>
                    <div class="mt-1 text-base text-slate-500">
                        Pending Documents
                    </div>
                    @if($documentStats['total'] > 0)
                        <div class="mt-2 text-xs text-slate-400">
                            {{ $documentStats['pending'] }} / {{ $documentStats['total'] }} total
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Medical Records Statistics Section -->
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium">Medical Records Overview</div>
                <a href="{{ route('carrier.medical-records.index') }}" class="md:ml-auto text-sm font-medium text-primary hover:text-primary/80 transition-colors">View All Medical Records</a>
            </div>
            <div class="mt-3.5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <!-- Total Medical Records -->
                <div class="box relative overflow-hidden rounded-[0.6rem] border border-slate-200/60 bg-white p-5 shadow-sm transition-all hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full border border-primary/10 bg-primary/10">
                        <x-base.lucide class="h-6 w-6 fill-primary/10 text-primary" icon="FileText" />
                    </div>
                    <div class="mt-12 flex items-center">
                        <div class="text-2xl font-medium leading-none text-slate-800">{{ $medicalRecordsStats['total'] }}</div>
                    </div>
                    <div class="mt-1 text-base text-slate-500">
                        Total Medical Records
                    </div>
                    <div class="mt-2 text-xs text-slate-400">
                        All driver medical records
                    </div>
                </div>
                
                <!-- Active Medical Records -->
                <div class="box relative overflow-hidden rounded-[0.6rem] border border-slate-200/60 bg-white p-5 shadow-sm transition-all hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full border border-success/10 bg-success/10">
                        <x-base.lucide class="h-6 w-6 fill-success/10 text-success" icon="CheckCircle" />
                    </div>
                    <div class="mt-12 flex items-center">
                        <div class="text-2xl font-medium leading-none text-slate-800">{{ $medicalRecordsStats['active'] }}</div>
                        @if($medicalRecordsStats['total'] > 0)
                            <div class="ml-3.5 flex items-center rounded-full border border-success/30 bg-success/10 py-[2px] pl-[7px] pr-1 text-xs font-medium text-success">
                                {{ round(($medicalRecordsStats['active'] / $medicalRecordsStats['total']) * 100) }}%
                                <x-base.lucide class="ml-px h-4 w-4 stroke-[1.5]" icon="TrendingUp" />
                            </div>
                        @endif
                    </div>
                    <div class="mt-1 text-base text-slate-500">
                        Active Records
                    </div>
                    <div class="mt-2 text-xs text-slate-400">
                        Valid for 30+ days
                    </div>
                </div>
                
                <!-- Expiring Soon -->
                <div class="box relative overflow-hidden rounded-[0.6rem] border border-slate-200/60 bg-white p-5 shadow-sm transition-all hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full border border-warning/10 bg-warning/10">
                        <x-base.lucide class="h-6 w-6 fill-warning/10 text-warning" icon="Clock" />
                    </div>
                    <div class="mt-12 flex items-center">
                        <div class="text-2xl font-medium leading-none text-slate-800">{{ $medicalRecordsStats['expiring_soon'] }}</div>
                        @if($medicalRecordsStats['total'] > 0)
                            <div class="ml-3.5 flex items-center rounded-full border border-warning/30 bg-warning/10 py-[2px] pl-[7px] pr-1 text-xs font-medium text-warning">
                                {{ round(($medicalRecordsStats['expiring_soon'] / $medicalRecordsStats['total']) * 100) }}%
                                <x-base.lucide class="ml-px h-4 w-4 stroke-[1.5]" icon="AlertTriangle" />
                            </div>
                        @endif
                    </div>
                    <div class="mt-1 text-base text-slate-500">
                        Expiring Soon
                    </div>
                    <div class="mt-2 text-xs text-slate-400">
                        Within 30 days
                    </div>
                </div>
                
                <!-- Expired Medical Records -->
                <div class="box relative overflow-hidden rounded-[0.6rem] border border-slate-200/60 bg-white p-5 shadow-sm transition-all hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full border border-danger/10 bg-danger/10">
                        <x-base.lucide class="h-6 w-6 fill-danger/10 text-danger" icon="AlertCircle" />
                    </div>
                    <div class="mt-12 flex items-center">
                        <div class="text-2xl font-medium leading-none text-slate-800">{{ $medicalRecordsStats['expired'] }}</div>
                        @if($medicalRecordsStats['total'] > 0)
                            <div class="ml-3.5 flex items-center rounded-full border border-danger/30 bg-danger/10 py-[2px] pl-[7px] pr-1 text-xs font-medium text-danger">
                                {{ round(($medicalRecordsStats['expired'] / $medicalRecordsStats['total']) * 100) }}%
                                <x-base.lucide class="ml-px h-4 w-4 stroke-[1.5]" icon="AlertCircle" />
                            </div>
                        @endif
                    </div>
                    <div class="mt-1 text-base text-slate-500">
                        Expired Records
                    </div>
                    <div class="mt-2 text-xs text-slate-400">
                        Require renewal
                    </div>
                </div>
            </div>
        </div>
        
        <!-- License Statistics Section -->
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium">Driver Licenses Overview</div>
                <a href="{{ route('carrier.licenses.index') }}" class="md:ml-auto text-sm font-medium text-primary hover:text-primary/80 transition-colors">View All Licenses</a>
            </div>
            <div class="mt-3.5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <!-- Total Licenses -->
                <div class="box relative overflow-hidden rounded-[0.6rem] border border-slate-200/60 bg-white p-5 shadow-sm transition-all hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full border border-primary/10 bg-primary/10">
                        <x-base.lucide class="h-6 w-6 fill-primary/10 text-primary" icon="CreditCard" />
                    </div>
                    <div class="mt-12 flex items-center">
                        <div class="text-2xl font-medium leading-none text-slate-800">{{ $licenseStats['total'] }}</div>
                    </div>
                    <div class="mt-1 text-base text-slate-500">
                        Total Licenses
                    </div>
                    <div class="mt-2 text-xs text-slate-400">
                        All driver licenses
                    </div>
                </div>
                
                <!-- Valid Licenses -->
                <div class="box relative overflow-hidden rounded-[0.6rem] border border-slate-200/60 bg-white p-5 shadow-sm transition-all hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full border border-success/10 bg-success/10">
                        <x-base.lucide class="h-6 w-6 fill-success/10 text-success" icon="CheckCircle" />
                    </div>
                    <div class="mt-12 flex items-center">
                        <div class="text-2xl font-medium leading-none text-slate-800">{{ $licenseStats['valid'] }}</div>
                        @if($licenseStats['total'] > 0)
                            <div class="ml-3.5 flex items-center rounded-full border border-success/30 bg-success/10 py-[2px] pl-[7px] pr-1 text-xs font-medium text-success">
                                {{ round(($licenseStats['valid'] / $licenseStats['total']) * 100) }}%
                                <x-base.lucide class="ml-px h-4 w-4 stroke-[1.5]" icon="TrendingUp" />
                            </div>
                        @endif
                    </div>
                    <div class="mt-1 text-base text-slate-500">
                        Valid Licenses
                    </div>
                    <div class="mt-2 text-xs text-slate-400">
                        Active and compliant
                    </div>
                </div>
                
                <!-- Expiring Soon -->
                <div class="box relative overflow-hidden rounded-[0.6rem] border border-slate-200/60 bg-white p-5 shadow-sm transition-all hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full border border-warning/10 bg-warning/10">
                        <x-base.lucide class="h-6 w-6 fill-warning/10 text-warning" icon="Clock" />
                    </div>
                    <div class="mt-12 flex items-center">
                        <div class="text-2xl font-medium leading-none text-slate-800">{{ $licenseStats['expiring_soon'] }}</div>
                        @if($licenseStats['total'] > 0)
                            <div class="ml-3.5 flex items-center rounded-full border border-warning/30 bg-warning/10 py-[2px] pl-[7px] pr-1 text-xs font-medium text-warning">
                                {{ round(($licenseStats['expiring_soon'] / $licenseStats['total']) * 100) }}%
                                <x-base.lucide class="ml-px h-4 w-4 stroke-[1.5]" icon="AlertTriangle" />
                            </div>
                        @endif
                    </div>
                    <div class="mt-1 text-base text-slate-500">
                        Expiring Soon
                    </div>
                    <div class="mt-2 text-xs text-slate-400">
                        Within 30 days
                    </div>
                </div>
                
                <!-- Expired Licenses -->
                <div class="box relative overflow-hidden rounded-[0.6rem] border border-slate-200/60 bg-white p-5 shadow-sm transition-all hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full border border-danger/10 bg-danger/10">
                        <x-base.lucide class="h-6 w-6 fill-danger/10 text-danger" icon="XCircle" />
                    </div>
                    <div class="mt-12 flex items-center">
                        <div class="text-2xl font-medium leading-none text-slate-800">{{ $licenseStats['expired'] }}</div>
                        @if($licenseStats['total'] > 0)
                            <div class="ml-3.5 flex items-center rounded-full border border-danger/30 bg-danger/10 py-[2px] pl-[7px] pr-1 text-xs font-medium text-danger">
                                {{ round(($licenseStats['expired'] / $licenseStats['total']) * 100) }}%
                                <x-base.lucide class="ml-px h-4 w-4 stroke-[1.5]" icon="AlertCircle" />
                            </div>
                        @endif
                    </div>
                    <div class="mt-1 text-base text-slate-500">
                        Expired Licenses
                    </div>
                    <div class="mt-2 text-xs text-slate-400">
                        Require renewal
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Columnas de contenido principal -->
        <div class="col-span-12 flex flex-col gap-y-10 lg:col-span-8 xl:col-span-8">
            <!-- Límites de Membresía -->
            <div>
                <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                    <div class="text-base font-medium">Membership Plan Limits</div>
                </div>
                <div class="box box--stacked mt-3.5 p-5">
                    @if($this->membershipLimits['maxDrivers'] > 0 || $this->membershipLimits['maxVehicles'] > 0)
                        <div class="flex flex-col gap-y-6">
                            <!-- Drivers Progress Bar -->
                            @if($this->membershipLimits['maxDrivers'] > 0)
                                <div>
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                                        <div class="flex items-center">
                                            <x-base.lucide class="h-4 w-4 mr-2 text-success" icon="Users" />
                                            <span class="text-slate-600 font-medium">Drivers</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-base sm:text-lg font-semibold text-slate-800">{{ $driversCount }} / {{ $this->membershipLimits['maxDrivers'] }}</span>
                                            <span class="text-xs font-medium text-success bg-success/10 px-2 py-1 rounded-full">
                                                {{ $this->membershipLimits['driversPercentage'] }}%
                                            </span>
                                        </div>
                                    </div>
                                    <div class="relative h-3 w-full rounded-full bg-slate-200 overflow-hidden">
                                        <div
                                            class="h-full rounded-full bg-success transition-all duration-500 ease-out"
                                            style="width: {{ min($this->membershipLimits['driversPercentage'], 100) }}%"
                                        ></div>
                                    </div>
                                    @if($this->membershipLimits['driversPercentage'] >= 90)
                                        <div class="mt-2 flex items-center text-xs text-warning">
                                            <x-base.lucide class="h-3 w-3 mr-1" icon="AlertTriangle" />
                                            <span>Cerca del límite de tu plan</span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                            
                            <!-- Vehicles Progress Bar -->
                            @if($this->membershipLimits['maxVehicles'] > 0)
                                <div>
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                                        <div class="flex items-center">
                                            <x-base.lucide class="h-4 w-4 mr-2 text-primary" icon="Truck" />
                                            <span class="text-slate-600 font-medium">Vehicles</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-base sm:text-lg font-semibold text-slate-800">{{ $vehiclesCount }} / {{ $this->membershipLimits['maxVehicles'] }}</span>
                                            <span class="text-xs font-medium text-primary bg-primary/10 px-2 py-1 rounded-full">
                                                {{ $this->membershipLimits['vehiclesPercentage'] }}%
                                            </span>
                                        </div>
                                    </div>
                                    <div class="relative h-3 w-full rounded-full bg-slate-200 overflow-hidden">
                                        <div
                                            class="h-full rounded-full bg-primary transition-all duration-500 ease-out"
                                            style="width: {{ min($this->membershipLimits['vehiclesPercentage'], 100) }}%"
                                        ></div>
                                    </div>
                                    @if($this->membershipLimits['vehiclesPercentage'] >= 90)
                                        <div class="mt-2 flex items-center text-xs text-warning">
                                            <x-base.lucide class="h-3 w-3 mr-1" icon="AlertTriangle" />
                                            <span>Cerca del límite de tu plan</span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @else
                        <!-- Mensaje cuando no hay plan de membresía -->
                        <div class="flex flex-col items-center justify-center text-slate-500 py-8">
                            <x-base.lucide class="h-12 w-12 mb-3 text-slate-400" icon="Package" />
                            <p class="font-medium text-base">No Membership Plan</p>
                            <p class="text-xs mt-1 text-center">You don't have an active membership plan with limits</p>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Conductores Recientes -->
            <div>
                <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                    <div class="text-base font-medium">Recent Drivers</div>
                    {{-- <a href="{{ route('carrier.user_drivers.index', $carrier) }}" class="md:ml-auto text-sm font-medium text-primary">Ver Todos</a> --}}
                </div>
                <div class="box box--stacked mt-3.5 p-5">
                    <div class="overflow-x-auto">
                        <table class="table w-full text-left">
                            <thead>
                                <tr>
                                    <th class="border-b-2 border-slate-200/60 pb-3 font-medium text-slate-600 whitespace-nowrap">Driver</th>
                                    <th class="border-b-2 border-slate-200/60 pb-3 font-medium text-slate-600 whitespace-nowrap">Status</th>
                                    <th class="border-b-2 border-slate-200/60 pb-3 font-medium text-slate-600 whitespace-nowrap text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentDrivers as $driver)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="border-b border-slate-200/60 py-4">
                                            <div class="flex items-center">
                                                <div class="image-fit zoom-in h-10 w-10 flex-shrink-0">
                                                    <img class="rounded-full shadow-sm" src="{{ $driver->getFirstMediaUrl('profile_photo_driver') ?: asset('build/default_profile.png') }}" alt="{{ $driver->user->name }}">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="font-medium text-slate-800">{{ trim($driver->user->name . ' ' . ($driver->last_name ?? '')) }}</div>
                                                    <div class="text-slate-500 text-xs mt-0.5">{{ $driver->user->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="border-b border-slate-200/60 py-4">
                                            @if($driver->status === \App\Models\UserDriverDetail::STATUS_ACTIVE)
                                                <div class="flex items-center text-success font-medium">
                                                    <x-base.lucide class="h-4 w-4 mr-1.5" icon="CheckCircle" />
                                                    <span>Active</span>
                                                </div>
                                            @elseif($driver->status === \App\Models\UserDriverDetail::STATUS_PENDING)
                                                <div class="flex items-center text-warning font-medium">
                                                    <x-base.lucide class="h-4 w-4 mr-1.5" icon="Clock" />
                                                    <span>Pendiente</span>
                                                </div>
                                            @else
                                                <div class="flex items-center text-danger font-medium">
                                                    <x-base.lucide class="h-4 w-4 mr-1.5" icon="XCircle" />
                                                    <span>Inactivo</span>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="border-b border-slate-200/60 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                {{-- <a href="{{ route('carrier.user_drivers.show', [$carrier, $driver]) }}" class="flex items-center justify-center h-8 w-8 rounded-md text-slate-500 hover:bg-slate-100 hover:text-primary transition-colors" title="Ver detalles">
                                                    <x-base.lucide class="h-4 w-4" icon="Eye" />
                                                </a>
                                                <a href="{{ route('carrier.user_drivers.edit', [$carrier, $driver]) }}" class="flex items-center justify-center h-8 w-8 rounded-md text-slate-500 hover:bg-slate-100 hover:text-primary transition-colors" title="Editar">
                                                    <x-base.lucide class="h-4 w-4" icon="Edit" />
                                                </a> --}}
                                                <span class="text-slate-400 text-xs">-</span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center border-b border-slate-200/60 py-8">
                                            <div class="flex flex-col items-center justify-center text-slate-500">
                                                <x-base.lucide class="h-8 w-8 mb-2 text-slate-400" icon="Users" />
                                                <p class="font-medium">No se encontraron conductores</p>
                                                <p class="text-xs mt-1">Los conductores recientes aparecerán aquí</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Documentos Recientes -->
            <div>
                <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                    <div class="text-base font-medium">Recent Documents</div>
                    <a href="{{ route('carrier.documents.index', $carrier) }}" class="md:ml-auto text-sm font-medium text-primary hover:text-primary/80 transition-colors">View All</a>
                </div>
                <div class="box box--stacked mt-3.5 p-5">
                    <div class="overflow-x-auto">
                        <table class="table w-full text-left">
                            <thead>
                                <tr>
                                    <th class="border-b-2 border-slate-200/60 pb-3 font-medium text-slate-600 whitespace-nowrap">Documents</th>
                                    <th class="border-b-2 border-slate-200/60 pb-3 font-medium text-slate-600 whitespace-nowrap">Type</th>
                                    <th class="border-b-2 border-slate-200/60 pb-3 font-medium text-slate-600 whitespace-nowrap">Date</th>
                                    <th class="border-b-2 border-slate-200/60 pb-3 font-medium text-slate-600 whitespace-nowrap">Status</th>
                                    <th class="border-b-2 border-slate-200/60 pb-3 font-medium text-slate-600 whitespace-nowrap text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentDocuments as $document)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="border-b border-slate-200/60 py-4">
                                            <div class="font-medium text-slate-800">{{ $document->filename ?: 'Documento #' . $document->id }}</div>
                                        </td>
                                        <td class="border-b border-slate-200/60 py-4">
                                            <span class="text-slate-600">{{ $document->documentType->name }}</span>
                                        </td>
                                        <td class="border-b border-slate-200/60 py-4">
                                            <span class="text-slate-600 text-sm">{{ $document->date ? $document->date->format('d/m/Y') : $document->created_at->format('d/m/Y') }}</span>
                                        </td>
                                        <td class="border-b border-slate-200/60 py-4">
                                            @if($document->status === \App\Models\CarrierDocument::STATUS_APPROVED)
                                                <div class="flex items-center text-success font-medium">
                                                    <x-base.lucide class="h-4 w-4 mr-1.5" icon="CheckCircle" />
                                                    <span>Aprobado</span>
                                                </div>
                                            @elseif($document->status === \App\Models\CarrierDocument::STATUS_PENDING)
                                                <div class="flex items-center text-warning font-medium">
                                                    <x-base.lucide class="h-4 w-4 mr-1.5" icon="Clock" />
                                                    <span>Pendiente</span>
                                                </div>
                                            @elseif($document->status === \App\Models\CarrierDocument::STATUS_REJECTED)
                                                <div class="flex items-center text-danger font-medium">
                                                    <x-base.lucide class="h-4 w-4 mr-1.5" icon="XCircle" />
                                                    <span>Rechazado</span>
                                                </div>
                                            @else
                                                <div class="flex items-center text-info font-medium">
                                                    <x-base.lucide class="h-4 w-4 mr-1.5" icon="AlertCircle" />
                                                    <span>En Proceso</span>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="border-b border-slate-200/60 py-4 text-right">
                                            @php
                                                $documentUrl = $document->getFirstMediaUrl('carrier_documents');
                                            @endphp
                                            @if($documentUrl)
                                                <a href="{{ $documentUrl }}" target="_blank" class="flex items-center justify-center h-8 w-8 rounded-md text-slate-500 hover:bg-slate-100 hover:text-primary transition-colors ml-auto" title="Ver documento">
                                                    <x-base.lucide class="h-4 w-4" icon="ExternalLink" />
                                                </a>
                                            @else
                                                <span class="flex items-center justify-center h-8 w-8 rounded-md text-slate-300 ml-auto" title="No file available">
                                                    <x-base.lucide class="h-4 w-4" icon="FileX" />
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center border-b border-slate-200/60 py-8">
                                            <div class="flex flex-col items-center justify-center text-slate-500">
                                                <x-base.lucide class="h-8 w-8 mb-2 text-slate-400" icon="FileText" />
                                                <p class="font-medium">No se encontraron documentos</p>
                                                <p class="text-xs mt-1">Los documentos recientes aparecerán aquí</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Estado de los Documentos -->
            <div>
                <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                    <div class="text-base font-medium">Document Status</div>
                </div>
                <div class="box box--stacked mt-3.5 p-5">
                    @if($documentStats['total'] > 0)
                        <!-- Estados agrupados con barras de progreso -->
                        <div class="flex flex-col gap-5">
                            @foreach($documentStatusCounts as $status => $count)
                                @php
                                    $percentage = $documentStats['total'] > 0 ? round(($count / $documentStats['total']) * 100, 1) : 0;
                                    
                                    // Determinar color e icono según el estado
                                    if ($status === 'Aprobado') {
                                        $colorClass = 'success';
                                        $icon = 'CheckCircle';
                                    } elseif ($status === 'Pendiente') {
                                        $colorClass = 'warning';
                                        $icon = 'Clock';
                                    } elseif ($status === 'Rechazado') {
                                        $colorClass = 'danger';
                                        $icon = 'XCircle';
                                    } else {
                                        $colorClass = 'info';
                                        $icon = 'AlertCircle';
                                    }
                                @endphp
                                
                                <div>
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                                        <div class="flex items-center">
                                            <x-base.lucide class="h-4 w-4 mr-2 text-{{ $colorClass }}" icon="{{ $icon }}" />
                                            <span class="text-slate-600 font-medium">{{ $status }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-base sm:text-lg font-semibold text-slate-800">{{ $count }}</span>
                                            <span class="text-xs font-medium text-{{ $colorClass }} bg-{{ $colorClass }}/10 px-2 py-1 rounded-full">
                                                {{ $percentage }}%
                                            </span>
                                        </div>
                                    </div>
                                    <div class="relative h-3 w-full rounded-full bg-slate-200 overflow-hidden">
                                        <div
                                            class="h-full rounded-full bg-{{ $colorClass }} transition-all duration-500 ease-out"
                                            style="width: {{ min($percentage, 100) }}%"
                                        ></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <!-- Subsección con conteo de documentos por tipo -->
                        <div class="mt-6 pt-6 border-t border-dashed border-slate-300/70">
                            <div class="mb-4 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Documents by Type
                            </div>
                            <div class="flex flex-col gap-3">
                                @forelse($documentTypeCounts as $type => $count)
                                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2.5 transition-colors hover:bg-slate-100">
                                        <div class="flex items-center text-slate-600">
                                            <div class="h-2 w-2 rounded-full bg-primary mr-2.5"></div>
                                            <span class="text-sm">{{ $type }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-slate-800">{{ $count }}</span>
                                            <span class="text-xs text-slate-500">
                                                ({{ $documentStats['total'] > 0 ? round(($count / $documentStats['total']) * 100, 1) : 0 }}%)
                                            </span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center text-slate-500 py-4">
                                        <x-base.lucide class="h-6 w-6 mx-auto mb-2 text-slate-400" icon="FileText" />
                                        <p class="text-sm">No document types found</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @else
                        <!-- Mensaje cuando no hay documentos -->
                        <div class="flex flex-col items-center justify-center text-slate-500 py-8">
                            <x-base.lucide class="h-12 w-12 mb-3 text-slate-400" icon="FileText" />
                            <p class="font-medium text-base">No se encontraron documentos</p>
                            <p class="text-xs mt-1 text-center">Los documentos y sus estados aparecerán aquí una vez que comiences a subirlos</p>
                            <a 
                                href="{{ route('carrier.documents.index', $carrier) }}" 
                                class="mt-4 btn btn-primary btn-sm flex items-center gap-2"
                            >
                                <x-base.lucide class="h-4 w-4" icon="Upload" />
                                <span>Subir Documento</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>            
        </div>
        
        <!-- Contenido de la barra lateral -->
        <div class="col-span-12 flex flex-col gap-y-10 lg:col-span-4 xl:col-span-4">
            <!-- Detalles del Carrier -->
            <div>
                <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                    <div class="text-base font-medium">Carrier Details</div>
                </div>
                <div class="box box--stacked mt-3.5 p-5">
                    <!-- Header con logo, nombre y estado -->
                    <div class="mb-5 flex flex-col items-center gap-y-3 border-b border-dashed border-slate-300/70 pb-5">
                        <!-- Logo del carrier -->
                        <div class="image-fit h-20 w-20 overflow-hidden rounded-full border-4 border-slate-200/70 shadow-sm">
                            <img 
                                src="{{ $carrier->getFirstMediaUrl('logo_carrier') ?: asset('build/default_company.png') }}" 
                                alt="{{ $carrier->name }}"
                                class="h-full w-full object-cover"
                            >
                        </div>
                        
                        <!-- Nombre y dirección -->
                        <div class="text-center w-full">
                            <div class="text-base sm:text-lg font-semibold text-slate-800 break-words px-2">
                                {{ $carrier->name }}
                            </div>
                            <div class="mt-1 text-xs sm:text-sm text-slate-500 flex items-center justify-center flex-wrap gap-1 px-2">
                                <x-base.lucide class="h-3.5 w-3.5 flex-shrink-0" icon="MapPin" />
                                <span class="break-words">
                                    @php
                                        $addressParts = array_filter([
                                            $carrier->address,
                                            $carrier->state,
                                            $carrier->zipcode
                                        ]);
                                    @endphp
                                    {{ !empty($addressParts) ? implode(', ', $addressParts) : 'N/A' }}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Badge de estado -->
                        @if($carrier->status === \App\Models\Carrier::STATUS_ACTIVE)
                            <div class="flex items-center rounded-full border border-success/20 bg-success/10 px-4 py-1.5 font-medium text-success">
                                <x-base.lucide class="h-4 w-4 mr-2" icon="CheckCircle" />
                                <span>{{ $carrier->statusName }}</span>
                            </div>
                        @elseif($carrier->status === \App\Models\Carrier::STATUS_PENDING || $carrier->status === \App\Models\Carrier::STATUS_PENDING_VALIDATION)
                            <div class="flex items-center rounded-full border border-warning/20 bg-warning/10 px-4 py-1.5 font-medium text-warning">
                                <x-base.lucide class="h-4 w-4 mr-2" icon="Clock" />
                                <span>{{ $carrier->statusName }}</span>
                            </div>
                        @elseif($carrier->status === \App\Models\Carrier::STATUS_REJECTED)
                            <div class="flex items-center rounded-full border border-danger/20 bg-danger/10 px-4 py-1.5 font-medium text-danger">
                                <x-base.lucide class="h-4 w-4 mr-2" icon="XCircle" />
                                <span>{{ $carrier->statusName }}</span>
                            </div>
                        @else
                            <div class="flex items-center rounded-full border border-slate-300/20 bg-slate-100 px-4 py-1.5 font-medium text-slate-600">
                                <x-base.lucide class="h-4 w-4 mr-2" icon="AlertCircle" />
                                <span>{{ $carrier->statusName }}</span>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Números regulatorios -->
                    <div class="mb-5">
                        <div class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Regulatory Information
                        </div>
                        <div class="flex flex-col gap-3">
                            <!-- DOT Number -->
                            <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2.5 transition-colors hover:bg-slate-100">
                                <div class="flex items-center text-slate-600">
                                    <x-base.lucide class="h-4 w-4 mr-2 text-slate-400" icon="FileText" />
                                    <span class="text-sm">DOT Number</span>
                                </div>
                                <div class="font-semibold text-slate-800">{{ $carrier->dot_number ?: 'N/A' }}</div>
                            </div>
                            
                            <!-- MC Number -->
                            <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2.5 transition-colors hover:bg-slate-100">
                                <div class="flex items-center text-slate-600">
                                    <x-base.lucide class="h-4 w-4 mr-2 text-slate-400" icon="FileText" />
                                    <span class="text-sm">MC Number</span>
                                </div>
                                <div class="font-semibold text-slate-800">{{ $carrier->mc_number ?: 'N/A' }}</div>
                            </div>
                            
                            <!-- EIN Number -->
                            <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2.5 transition-colors hover:bg-slate-100">
                                <div class="flex items-center text-slate-600">
                                    <x-base.lucide class="h-4 w-4 mr-2 text-slate-400" icon="Hash" />
                                    <span class="text-sm">EIN Number</span>
                                </div>
                                <div class="font-semibold text-slate-800">{{ $carrier->ein_number ?: 'N/A' }}</div>
                            </div>
                            
                            <!-- State DOT Number -->
                            <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2.5 transition-colors hover:bg-slate-100">
                                <div class="flex items-center text-slate-600">
                                    <x-base.lucide class="h-4 w-4 mr-2 text-slate-400" icon="MapPin" />
                                    <span class="text-sm">State DOT</span>
                                </div>
                           <div class="font-semibold text-slate-800">{{ $carrier->state_dot ?: 'N/A' }}</div>
                            </div>
                            
                            <!-- IFTA Account Number -->
                            <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2.5 transition-colors hover:bg-slate-100">
                                <div class="flex items-center text-slate-600">
                                    <x-base.lucide class="h-4 w-4 mr-2 text-slate-400" icon="CreditCard" />
                                    <span class="text-sm">IFTA Account</span>
                                </div>
                                <div class="font-semibold text-slate-800">{{ $carrier->ifta_account ?: 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botón de editar perfil -->
                    <div class="flex justify-center border-t border-dashed border-slate-300/70 pt-5">
                        <a 
                            href="{{ route('carrier.profile.edit') }}" 
                            class="btn btn-primary w-full sm:w-auto px-6 py-2.5 flex items-center justify-center gap-2 shadow-sm hover:shadow transition-all"
                        >
                            <x-base.lucide class="h-4 w-4" icon="Edit" />
                            <span>Edit Profile</span>
                        </a>
                    </div>
                </div>
            </div>
            
                <!-- Safety Data System Card -->
    @if($carrier->dot_number)
    <div class="mb-6">
        <div class="box box--stacked overflow-hidden rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-300">
            <div class="relative">
                <!-- Imagen de fondo -->
                <div class="relative h-56 bg-gradient-to-br from-primary via-primary/90 to-primary/80 flex items-center justify-center overflow-hidden">
                    @if($carrier->hasSafetyDataSystemImage())
                        <img src="{{ $carrier->getSafetyDataSystemImageUrl() }}" 
                             alt="Safety Data System" 
                             class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                    @else
                        <!-- Patrón de fondo por defecto -->
                        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.05\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-30"></div>
                        <x-base.lucide class="w-24 h-24 text-white/30 relative z-10" icon="Shield" />
                    @endif
                </div>
                
                <!-- Contenido de la card -->
                <div class="p-6 bg-white">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-slate-800 mb-1">{{ $carrier->name }}</h3>
                            <p class="text-sm text-slate-500 flex items-center gap-2">
                                <x-base.lucide class="w-4 h-4" icon="Shield" />
                                Safety Data System
                            </p>
                        </div>
                        @if($carrier->dot_number)
                        <div class="flex items-center gap-2 px-3 py-1.5 bg-primary/10 rounded-full">
                            <x-base.lucide class="w-4 h-4 text-primary" icon="FileText" />
                            <span class="text-sm font-semibold text-primary">DOT: {{ $carrier->dot_number }}</span>
                        </div>
                        @endif
                    </div>
                    
                    <!-- Botón de acción -->
                    <x-base.button 
                        as="a" 
                        href="{{ $carrier->safety_data_system_url }}" 
                        target="_blank"
                        variant="primary" 
                        class="w-full gap-2 justify-center py-3"
                    >
                        <x-base.lucide class="w-5 h-5" icon="ExternalLink" />
                        Consulting Safety
                    </x-base.button>
                    
                    <!-- Información adicional -->
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <p class="text-xs text-slate-500 text-center">
                            <x-base.lucide class="w-3 h-3 inline mr-1" icon="Info" />
                            Sistema de datos de seguridad de FMCSA
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

        </div>
    </div>
</div>