@extends('../themes/' . $activeTheme)
@section('title', 'Driver Details')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('carrier.dashboard')],
        ['label' => 'Driver Management', 'url' => route('carrier.drivers.index')],
        ['label' => 'Driver Details', 'active' => true],
    ];
@endphp

@section('subcontent')
    <div class="container-fluid">
        <!-- Mensajes Flash -->
        @if (session()->has('success'))
            <div class="alert alert-success-soft show flex items-center mb-5" role="alert">
                <x-base.lucide class="w-6 h-6 mr-2" icon="check-circle" />
                <div class="ml-1">{{ session('success') }}</div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger-soft show flex items-center mb-5" role="alert">
                <x-base.lucide class="w-6 h-6 mr-2" icon="alert-circle" />
                <div class="ml-1">{{ session('error') }}</div>
            </div>
        @endif

        <!-- Cabecera -->
        <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center mt-8">
            <div class="text-base font-medium group-[.mode--light]:text-white">
                Driver Details
            </div>
            <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                <a href="{{ route('carrier.drivers.edit', $driver->id) }}" class="btn btn-primary w-full sm:w-auto">
                    <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="pencil" />
                    Edit Driver
                </a>
                <a href="{{ route('carrier.drivers.index') }}" class="btn btn-outline-secondary w-full sm:w-auto">
                    <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="arrow-left" />
                    Back to List
                </a>
            </div>
        </div>

        <!-- Profile Header Card -->
        <div class="box box--stacked mt-5">
            <div class="p-6">
                <div class="flex flex-col items-center sm:flex-row sm:items-start gap-6">
                    <!-- Profile Photo -->
                    <div class="flex-shrink-0">
                        <div class="image-fit zoom-in h-32 w-32 overflow-hidden rounded-full border-4 border-slate-200 shadow-lg">
                            <img 
                                class="h-full w-full object-cover cursor-pointer" 
                                src="{{ $driver->getFirstMediaUrl('profile_photo_driver') ?: asset('default_profile.png') }}"
                                alt="{{ $driver->user->name }}"
                                onerror="this.src='{{ asset('default_profile.png') }}'"
                                onclick="openImageModal(this.src)"
                            >
                        </div>
                    </div>
                    
                    <!-- Driver Info -->
                    <div class="flex-1">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-200">
                                    {{ $driver->user->name }} {{ $driver->middle_name }} {{ $driver->last_name }}
                                </h2>
                                <p class="mt-1 text-slate-600 dark:text-slate-400">
                                    <x-base.lucide class="inline h-4 w-4 mr-1" icon="mail" />
                                    {{ $driver->user->email }}
                                </p>
                                @if($driver->phone)
                                    <p class="mt-1 text-slate-600 dark:text-slate-400">
                                        <x-base.lucide class="inline h-4 w-4 mr-1" icon="phone" />
                                        {{ $driver->formatted_phone }}
                                    </p>
                                @endif
                            </div>
                            
                            <!-- Status Badge -->
                            <div>
                                @if($driver->status === 1)
                                    <div class="inline-flex items-center rounded-full bg-success/10 px-4 py-2 text-success">
                                        <div class="mr-2 h-2.5 w-2.5 rounded-full bg-success"></div>
                                        <span class="text-sm font-semibold">Active</span>
                                    </div>
                                @elseif($driver->status === 2)
                                    <div class="inline-flex items-center rounded-full bg-warning/10 px-4 py-2 text-warning">
                                        <div class="mr-2 h-2.5 w-2.5 rounded-full bg-warning"></div>
                                        <span class="text-sm font-semibold">Pending</span>
                                    </div>
                                @else
                                    <div class="inline-flex items-center rounded-full bg-danger/10 px-4 py-2 text-danger">
                                        <div class="mr-2 h-2.5 w-2.5 rounded-full bg-danger"></div>
                                        <span class="text-sm font-semibold">Inactive</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 mt-5 lg:grid-cols-2">
            <!-- Personal Information Card -->
            <div class="box box--stacked">
                <div class="flex items-center border-b border-slate-200/60 px-5 py-4 dark:border-darkmode-300">
                    <x-base.lucide class="mr-2 h-5 w-5 text-primary" icon="user" />
                    <h3 class="text-lg font-semibold text-slate-700 dark:text-slate-300">Personal Information</h3>
                </div>
                <div class="p-5">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-xs font-medium text-slate-500 uppercase">First Name</label>
                                <p class="mt-1 text-slate-700 dark:text-slate-300">{{ $driver->user->name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-slate-500 uppercase">Middle Name</label>
                                <p class="mt-1 text-slate-700 dark:text-slate-300">{{ $driver->middle_name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-xs font-medium text-slate-500 uppercase">Last Name</label>
                                <p class="mt-1 text-slate-700 dark:text-slate-300">{{ $driver->last_name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-slate-500 uppercase">Date of Birth</label>
                                <p class="mt-1 text-slate-700 dark:text-slate-300">
                                    {{ $driver->date_of_birth ? $driver->date_of_birth->format('M d, Y') : 'N/A' }}
                                </p>
                            </div>
                        </div>
                        
                        @if($driver->hire_date)
                            <div>
                                <label class="text-xs font-medium text-slate-500 uppercase">Hire Date</label>
                                <p class="mt-1 text-slate-700 dark:text-slate-300">
                                    {{ $driver->hire_date->format('M d, Y') }}
                                </p>
                            </div>
                        @endif
                        
                        @if($driver->notes)
                            <div>
                                <label class="text-xs font-medium text-slate-500 uppercase">Notes</label>
                                <p class="mt-1 text-slate-700 dark:text-slate-300">{{ $driver->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Emergency Contact Card -->
            @if($driver->emergency_contact_name || $driver->emergency_contact_phone)
                <div class="box box--stacked">
                    <div class="flex items-center border-b border-slate-200/60 px-5 py-4 dark:border-darkmode-300">
                        <x-base.lucide class="mr-2 h-5 w-5 text-primary" icon="phone-call" />
                        <h3 class="text-lg font-semibold text-slate-700 dark:text-slate-300">Emergency Contact</h3>
                    </div>
                    <div class="p-5">
                        <div class="space-y-4">
                            @if($driver->emergency_contact_name)
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Contact Name</label>
                                    <p class="mt-1 text-slate-700 dark:text-slate-300">{{ $driver->emergency_contact_name }}</p>
                                </div>
                            @endif
                            
                            @if($driver->emergency_contact_phone)
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Contact Phone</label>
                                    <p class="mt-1 text-slate-700 dark:text-slate-300">{{ $driver->emergency_contact_phone }}</p>
                                </div>
                            @endif
                            
                            @if($driver->emergency_contact_relationship)
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Relationship</label>
                                    <p class="mt-1 text-slate-700 dark:text-slate-300">{{ $driver->emergency_contact_relationship }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- License Information Card -->
            <div class="box box--stacked">
                <div class="flex items-center border-b border-slate-200/60 px-5 py-4 dark:border-darkmode-300">
                    <x-base.lucide class="mr-2 h-5 w-5 text-primary" icon="credit-card" />
                    <h3 class="text-lg font-semibold text-slate-700 dark:text-slate-300">License Information</h3>
                </div>
                <div class="p-5">
                    @if($driver->licenses && $driver->licenses->count() > 0)
                        <div class="space-y-6">
                            @foreach($driver->licenses as $license)
                                <div class="rounded-lg border border-slate-200 p-4 dark:border-darkmode-300">
                                    <div class="mb-3 flex items-center justify-between">
                                        <span class="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                                            @if($license->is_primary)
                                                <x-base.lucide class="mr-1 h-3 w-3" icon="star" />
                                                Primary License
                                            @else
                                                Additional License
                                            @endif
                                        </span>
                                        @if($license->is_cdl)
                                            <span class="inline-flex items-center rounded-full bg-success/10 px-3 py-1 text-xs font-semibold text-success">
                                                CDL
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="text-xs font-medium text-slate-500 uppercase">License Number</label>
                                            <p class="mt-1 font-medium text-slate-700 dark:text-slate-300">{{ $license->license_number ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <label class="text-xs font-medium text-slate-500 uppercase">State</label>
                                            <p class="mt-1 text-slate-700 dark:text-slate-300">{{ $license->state_of_issue ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <label class="text-xs font-medium text-slate-500 uppercase">Class</label>
                                            <p class="mt-1 text-slate-700 dark:text-slate-300">{{ $license->license_class ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <label class="text-xs font-medium text-slate-500 uppercase">Expiration Date</label>
                                            <p class="mt-1 text-slate-700 dark:text-slate-300">
                                                {{ $license->expiration_date ? $license->expiration_date->format('M d, Y') : 'N/A' }}
                                            </p>
                                        </div>
                                    </div>
                                    
                                    @if($license->restrictions)
                                        <div class="mt-4">
                                            <label class="text-xs font-medium text-slate-500 uppercase">Restrictions</label>
                                            <p class="mt-1 text-slate-700 dark:text-slate-300">{{ $license->restrictions }}</p>
                                        </div>
                                    @endif
                                    
                                    @if($license->endorsements && $license->endorsements->count() > 0)
                                        <div class="mt-4">
                                            <label class="text-xs font-medium text-slate-500 uppercase">Endorsements</label>
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @foreach($license->endorsements as $endorsement)
                                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                                        {{ $endorsement->code }} - {{ $endorsement->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <!-- License Images -->
                                    @if($license->getFirstMediaUrl('license_front') || $license->getFirstMediaUrl('license_back'))
                                        <div class="mt-4">
                                            <label class="text-xs font-medium text-slate-500 uppercase">License Documents</label>
                                            <div class="mt-2 flex gap-3">
                                                @if($license->getFirstMediaUrl('license_front'))
                                                    <div class="group relative">
                                                        <img 
                                                            src="{{ $license->getFirstMediaUrl('license_front') }}" 
                                                            alt="License Front"
                                                            class="h-24 w-36 cursor-pointer rounded-lg border border-slate-200 object-cover shadow-sm transition-all hover:shadow-md"
                                                            onclick="openImageModal(this.src)"
                                                        >
                                                        <div class="absolute inset-0 flex items-center justify-center rounded-lg bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
                                                            <x-base.lucide class="h-6 w-6 text-white" icon="zoom-in" />
                                                        </div>
                                                        <p class="mt-1 text-center text-xs text-slate-500">Front</p>
                                                    </div>
                                                @endif
                                                @if($license->getFirstMediaUrl('license_back'))
                                                    <div class="group relative">
                                                        <img 
                                                            src="{{ $license->getFirstMediaUrl('license_back') }}" 
                                                            alt="License Back"
                                                            class="h-24 w-36 cursor-pointer rounded-lg border border-slate-200 object-cover shadow-sm transition-all hover:shadow-md"
                                                            onclick="openImageModal(this.src)"
                                                        >
                                                        <div class="absolute inset-0 flex items-center justify-center rounded-lg bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
                                                            <x-base.lucide class="h-6 w-6 text-white" icon="zoom-in" />
                                                        </div>
                                                        <p class="mt-1 text-center text-xs text-slate-500">Back</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <x-base.lucide class="h-12 w-12 text-slate-300" icon="credit-card" />
                            <p class="mt-3 text-sm text-slate-500">No license information available</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Medical Qualification Card -->
            @if($driver->medicalQualification)
                <div class="box box--stacked">
                    <div class="flex items-center border-b border-slate-200/60 px-5 py-4 dark:border-darkmode-300">
                        <x-base.lucide class="mr-2 h-5 w-5 text-primary" icon="heart-pulse" />
                        <h3 class="text-lg font-semibold text-slate-700 dark:text-slate-300">Medical Qualification</h3>
                    </div>
                    <div class="p-5">
                        <div class="space-y-4">
                            @if($driver->medicalQualification->certificate_number)
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Certificate Number</label>
                                    <p class="mt-1 text-slate-700 dark:text-slate-300">{{ $driver->medicalQualification->certificate_number }}</p>
                                </div>
                            @endif
                            
                            @if($driver->medicalQualification->issue_date)
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="text-xs font-medium text-slate-500 uppercase">Issue Date</label>
                                        <p class="mt-1 text-slate-700 dark:text-slate-300">
                                            {{ $driver->medicalQualification->issue_date->format('M d, Y') }}
                                        </p>
                                    </div>
                                    @if($driver->medicalQualification->expiration_date)
                                        <div>
                                            <label class="text-xs font-medium text-slate-500 uppercase">Expiration Date</label>
                                            <p class="mt-1 text-slate-700 dark:text-slate-300">
                                                {{ $driver->medicalQualification->expiration_date->format('M d, Y') }}
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                            
                            @if($driver->medicalQualification->getFirstMediaUrl('medical_card'))
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Medical Card</label>
                                    <div class="mt-2">
                                        <div class="group relative inline-block">
                                            <img 
                                                src="{{ $driver->medicalQualification->getFirstMediaUrl('medical_card') }}" 
                                                alt="Medical Card"
                                                class="h-32 w-48 cursor-pointer rounded-lg border border-slate-200 object-cover shadow-sm transition-all hover:shadow-md"
                                                onclick="openImageModal(this.src)"
                                            >
                                            <div class="absolute inset-0 flex items-center justify-center rounded-lg bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
                                                <x-base.lucide class="h-6 w-6 text-white" icon="zoom-in" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Full Width Sections -->
        <!-- Assigned Vehicle Section -->
        @if($driver->assignedVehicle || ($driver->activeVehicleAssignment && $driver->activeVehicleAssignment->vehicle))
            <div class="box box--stacked mt-5">
                <div class="flex items-center border-b border-slate-200/60 px-5 py-4 dark:border-darkmode-300">
                    <x-base.lucide class="mr-2 h-5 w-5 text-primary" icon="truck" />
                    <h3 class="text-lg font-semibold text-slate-700 dark:text-slate-300">Assigned Vehicle</h3>
                </div>
                <div class="p-5">
                    @php
                        $vehicle = $driver->assignedVehicle ?? ($driver->activeVehicleAssignment ? $driver->activeVehicleAssignment->vehicle : null);
                    @endphp
                    
                    @if($vehicle)
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label class="text-xs font-medium text-slate-500 uppercase">Vehicle Number</label>
                                <p class="mt-1 font-medium text-slate-700 dark:text-slate-300">{{ $vehicle->vehicle_number ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-slate-500 uppercase">Make & Model</label>
                                <p class="mt-1 text-slate-700 dark:text-slate-300">{{ $vehicle->make ?? 'N/A' }} {{ $vehicle->model ?? '' }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-slate-500 uppercase">Year</label>
                                <p class="mt-1 text-slate-700 dark:text-slate-300">{{ $vehicle->year ?? 'N/A' }}</p>
                            </div>
                            @if($vehicle->vin)
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">VIN</label>
                                    <p class="mt-1 text-slate-700 dark:text-slate-300">{{ $vehicle->vin }}</p>
                                </div>
                            @endif
                            @if($vehicle->license_plate)
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">License Plate</label>
                                    <p class="mt-1 text-slate-700 dark:text-slate-300">{{ $vehicle->license_plate }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Work History Section -->
        @if($driver->workHistories && $driver->workHistories->count() > 0)
            <div class="box box--stacked mt-5">
                <div class="flex items-center border-b border-slate-200/60 px-5 py-4 dark:border-darkmode-300">
                    <x-base.lucide class="mr-2 h-5 w-5 text-primary" icon="briefcase" />
                    <h3 class="text-lg font-semibold text-slate-700 dark:text-slate-300">Employment History</h3>
                </div>
                <div class="p-5">
                    <div class="space-y-4">
                        @foreach($driver->workHistories->sortByDesc('start_date') as $work)
                            <div class="rounded-lg border border-slate-200 p-4 dark:border-darkmode-300">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-slate-700 dark:text-slate-300">{{ $work->employer_name ?? 'N/A' }}</h4>
                                        @if($work->position)
                                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $work->position }}</p>
                                        @endif
                                        @if($work->start_date || $work->end_date)
                                            <p class="mt-2 text-xs text-slate-500">
                                                <x-base.lucide class="inline h-3 w-3 mr-1" icon="calendar" />
                                                {{ $work->start_date ? $work->start_date->format('M Y') : 'N/A' }} - 
                                                {{ $work->end_date ? $work->end_date->format('M Y') : 'Present' }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                @if($work->reason_for_leaving)
                                    <div class="mt-3 rounded-md bg-slate-50 p-3 dark:bg-darkmode-800">
                                        <label class="text-xs font-medium text-slate-500 uppercase">Reason for Leaving</label>
                                        <p class="mt-1 text-sm text-slate-700 dark:text-slate-300">{{ $work->reason_for_leaving }}</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Training Schools Section -->
        @if($driver->trainingSchools && $driver->trainingSchools->count() > 0)
            <div class="box box--stacked mt-5">
                <div class="flex items-center border-b border-slate-200/60 px-5 py-4 dark:border-darkmode-300">
                    <x-base.lucide class="mr-2 h-5 w-5 text-primary" icon="graduation-cap" />
                    <h3 class="text-lg font-semibold text-slate-700 dark:text-slate-300">Training & Education</h3>
                </div>
                <div class="p-5">
                    <div class="space-y-4">
                        @foreach($driver->trainingSchools as $school)
                            <div class="rounded-lg border border-slate-200 p-4 dark:border-darkmode-300">
                                <h4 class="font-semibold text-slate-700 dark:text-slate-300">{{ $school->school_name ?? 'N/A' }}</h4>
                                @if($school->course_name)
                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $school->course_name }}</p>
                                @endif
                                <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    @if($school->start_date)
                                        <div>
                                            <label class="text-xs font-medium text-slate-500 uppercase">Start Date</label>
                                            <p class="mt-1 text-sm text-slate-700 dark:text-slate-300">{{ $school->start_date->format('M d, Y') }}</p>
                                        </div>
                                    @endif
                                    @if($school->completion_date)
                                        <div>
                                            <label class="text-xs font-medium text-slate-500 uppercase">Completion Date</label>
                                            <p class="mt-1 text-sm text-slate-700 dark:text-slate-300">{{ $school->completion_date->format('M d, Y') }}</p>
                                        </div>
                                    @endif
                                </div>
                                @if($school->getFirstMediaUrl('school_certificates'))
                                    <div class="mt-3">
                                        <label class="text-xs font-medium text-slate-500 uppercase">Certificate</label>
                                        <div class="mt-2">
                                            <div class="group relative inline-block">
                                                <img 
                                                    src="{{ $school->getFirstMediaUrl('school_certificates') }}" 
                                                    alt="Training Certificate"
                                                    class="h-24 w-36 cursor-pointer rounded-lg border border-slate-200 object-cover shadow-sm transition-all hover:shadow-md"
                                                    onclick="openImageModal(this.src)"
                                                >
                                                <div class="absolute inset-0 flex items-center justify-center rounded-lg bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
                                                    <x-base.lucide class="h-6 w-6 text-white" icon="zoom-in" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Accidents Section -->
        @if($driver->accidents && $driver->accidents->count() > 0)
            <div class="box box--stacked mt-5">
                <div class="flex items-center border-b border-slate-200/60 px-5 py-4 dark:border-darkmode-300">
                    <x-base.lucide class="mr-2 h-5 w-5 text-danger" icon="alert-triangle" />
                    <h3 class="text-lg font-semibold text-slate-700 dark:text-slate-300">Accident History</h3>
                </div>
                <div class="p-5">
                    <div class="space-y-4">
                        @foreach($driver->accidents->sortByDesc('accident_date') as $accident)
                            <div class="rounded-lg border border-slate-200 p-4 dark:border-darkmode-300">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        @if($accident->accident_date)
                                            <p class="font-medium text-slate-700 dark:text-slate-300">
                                                <x-base.lucide class="inline h-4 w-4 mr-1" icon="calendar" />
                                                {{ $accident->accident_date->format('M d, Y') }}
                                            </p>
                                        @endif
                                        @if($accident->location)
                                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                                                <x-base.lucide class="inline h-3 w-3 mr-1" icon="map-pin" />
                                                {{ $accident->location }}
                                            </p>
                                        @endif
                                    </div>
                                    @if($accident->fatalities > 0 || $accident->injuries > 0)
                                        <div class="ml-4 text-right">
                                            @if($accident->fatalities > 0)
                                                <span class="inline-flex items-center rounded-full bg-danger/10 px-2.5 py-1 text-xs font-medium text-danger">
                                                    {{ $accident->fatalities }} Fatalities
                                                </span>
                                            @endif
                                            @if($accident->injuries > 0)
                                                <span class="mt-1 inline-flex items-center rounded-full bg-warning/10 px-2.5 py-1 text-xs font-medium text-warning">
                                                    {{ $accident->injuries }} Injuries
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                @if($accident->description)
                                    <div class="mt-3 rounded-md bg-slate-50 p-3 dark:bg-darkmode-800">
                                        <p class="text-sm text-slate-700 dark:text-slate-300">{{ $accident->description }}</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Traffic Convictions Section -->
        @if($driver->trafficConvictions && $driver->trafficConvictions->count() > 0)
            <div class="box box--stacked mt-5">
                <div class="flex items-center border-b border-slate-200/60 px-5 py-4 dark:border-darkmode-300">
                    <x-base.lucide class="mr-2 h-5 w-5 text-warning" icon="alert-octagon" />
                    <h3 class="text-lg font-semibold text-slate-700 dark:text-slate-300">Traffic Convictions</h3>
                </div>
                <div class="p-5">
                    <div class="space-y-4">
                        @foreach($driver->trafficConvictions->sortByDesc('conviction_date') as $conviction)
                            <div class="rounded-lg border border-slate-200 p-4 dark:border-darkmode-300">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    @if($conviction->conviction_date)
                                        <div>
                                            <label class="text-xs font-medium text-slate-500 uppercase">Date</label>
                                            <p class="mt-1 text-slate-700 dark:text-slate-300">{{ $conviction->conviction_date->format('M d, Y') }}</p>
                                        </div>
                                    @endif
                                    @if($conviction->violation)
                                        <div>
                                            <label class="text-xs font-medium text-slate-500 uppercase">Violation</label>
                                            <p class="mt-1 text-slate-700 dark:text-slate-300">{{ $conviction->violation }}</p>
                                        </div>
                                    @endif
                                    @if($conviction->location)
                                        <div>
                                            <label class="text-xs font-medium text-slate-500 uppercase">Location</label>
                                            <p class="mt-1 text-slate-700 dark:text-slate-300">{{ $conviction->location }}</p>
                                        </div>
                                    @endif
                                    @if($conviction->penalty)
                                        <div>
                                            <label class="text-xs font-medium text-slate-500 uppercase">Penalty</label>
                                            <p class="mt-1 text-slate-700 dark:text-slate-300">{{ $conviction->penalty }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Image Modal -->
    <x-base.dialog id="image-modal" size="xl" staticBackdrop>
        <x-base.dialog.panel>
            <div class="relative">
                <button 
                    type="button" 
                    data-tw-dismiss="modal"
                    class="absolute right-0 top-0 z-10 mr-3 mt-3 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-slate-600 shadow-lg transition-all hover:bg-white hover:text-slate-800">
                    <x-base.lucide class="h-5 w-5" icon="x" />
                </button>
                <div class="p-5">
                    <img id="modal-image" src="" alt="Document" class="w-full rounded-lg">
                </div>
            </div>
        </x-base.dialog.panel>
    </x-base.dialog>

    @push('scripts')
        <script>
            function openImageModal(imageSrc) {
                const modal = tailwind.Modal.getOrCreateInstance(document.querySelector("#image-modal"));
                const modalImage = document.getElementById('modal-image');
                modalImage.src = imageSrc;
                modal.show();
            }
        </script>
    @endpush
@endsection
