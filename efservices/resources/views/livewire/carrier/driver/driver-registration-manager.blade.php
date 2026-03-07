<div class="flex flex-col md:p-5 p-0 box box--stacked">
    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Header Section -->
    <div class="mb-6 p-5 md:p-0">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 space-y-3 md:space-y-0">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900">
                        {{ $isEditMode ? 'Edit Driver' : 'Driver Registration' }}
                    </h1>
                    <p class="text-xs md:text-sm text-gray-500">Complete all sections to register the driver</p>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mb-4">
            <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                <span class="font-medium">Step {{ $currentStep }} of {{ $totalSteps }}</span>
                <span class="font-semibold text-blue-600">{{ round(($currentStep / $totalSteps) * 100) }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-2.5 rounded-full transition-all duration-300" 
                     style="width: {{ ($currentStep / $totalSteps) * 100 }}%"></div>
            </div>
        </div>
        
        <!-- Compact Tabs Navigation -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
                <nav class="flex min-w-max divide-x divide-gray-200" aria-label="Tabs">
                    @php
                        $tabs = [
                            ['id' => 1, 'name' => 'General', 'shortName' => 'Gen', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                            ['id' => 2, 'name' => 'Address', 'shortName' => 'Addr', 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z'],
                            ['id' => 3, 'name' => 'Application', 'shortName' => 'App', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                            ['id' => 4, 'name' => 'License', 'shortName' => 'Lic', 'icon' => 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2'],
                            ['id' => 5, 'name' => 'Medical', 'shortName' => 'Med', 'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
                            ['id' => 6, 'name' => 'Training', 'shortName' => 'Train', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                            ['id' => 7, 'name' => 'Traffic', 'shortName' => 'Traf', 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
                            ['id' => 8, 'name' => 'Accident', 'shortName' => 'Acc', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                            ['id' => 9, 'name' => 'FMCSR', 'shortName' => 'FMCSR', 'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
                            ['id' => 10, 'name' => 'Employment', 'shortName' => 'Emp', 'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0H8m8 0v2a2 2 0 01-2 2H10a2 2 0 01-2-2V6m8 0H8m0 0v.01M8 6v.01'],
                            ['id' => 11, 'name' => 'Policy', 'shortName' => 'Pol', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                            ['id' => 12, 'name' => 'Criminal', 'shortName' => 'Crim', 'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
                            ['id' => 13, 'name' => 'W-9', 'shortName' => 'W-9', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                            ['id' => 14, 'name' => 'Certification', 'shortName' => 'Cert', 'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
                            ['id' => 15, 'name' => 'Clearinghouse', 'shortName' => 'Clear', 'icon' => 'M5 13l4 4L19 7'],
                        ];
                    @endphp

                    @foreach($tabs as $tab)
                        <button wire:click="goToTab({{ $tab['id'] }})" 
                                class="group relative flex flex-col items-center justify-center px-3 md:px-4 py-3 text-xs font-medium transition-all duration-200 min-w-[70px] md:min-w-[90px] hover:bg-gray-50
                                       {{ $currentStep == $tab['id'] 
                                          ? 'bg-blue-50 text-blue-700' 
                                          : 'text-gray-600' }}">
                            
                            <!-- Icon with Status Badge -->
                            <div class="relative mb-1.5">
                                <svg class="w-4 h-4 md:w-5 md:h-5 {{ $currentStep == $tab['id'] ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600' }}" 
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $tab['icon'] }}"></path>
                                </svg>
                                
                                <!-- Completed Badge -->
                                @if($currentStep > $tab['id'])
                                    <div class="absolute -top-1 -right-1">
                                        <svg class="w-3 h-3 md:w-3.5 md:h-3.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Label - Show short name on mobile, full name on desktop -->
                            <span class="whitespace-nowrap text-center leading-tight block md:hidden">{{ $tab['shortName'] }}</span>
                            <span class="whitespace-nowrap text-center leading-tight hidden md:block">{{ $tab['name'] }}</span>
                            
                            <!-- Active Indicator -->
                            @if($currentStep == $tab['id'])
                                <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600"></div>
                            @endif
                        </button>
                    @endforeach
                </nav>
            </div>
        </div>
    </div>
    
    <!-- Custom Scrollbar Styles -->
    <style>
        .scrollbar-thin::-webkit-scrollbar {
            height: 6px;
        }
        .scrollbar-thin::-webkit-scrollbar-track {
            background: #f7fafc;
            border-radius: 10px;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 10px;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }
        
        /* Smooth scroll behavior */
        .overflow-x-auto {
            scroll-behavior: smooth;
        }
        
        /* Tab hover effects */
        button[wire\:click^="goToTab"] {
            position: relative;
            overflow: hidden;
        }
        
        button[wire\:click^="goToTab"]::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
            transition: left 0.5s;
        }
        
        button[wire\:click^="goToTab"]:hover::before {
            left: 100%;
        }
    </style>

    <!-- Step Content -->
    @if ($currentStep == 1)
        <livewire:driver.steps.step-general :driverId="$driverId" :isIndependent="false" :carrier="$carrier" />
    @elseif ($currentStep == 2)
        <livewire:driver.steps.address-step :driverId="$driverId" />
    @elseif ($currentStep == 3)
        <livewire:driver.steps.application-step :driver-id="$driverId" :key="'app-step-'.$driverId" />
    @elseif ($currentStep == 4)
        <livewire:driver.steps.license-step :driverId="$driverId" />
    @elseif ($currentStep == 5)
        <livewire:driver.steps.medical-step :driverId="$driverId" />
    @elseif ($currentStep == 6)
        <livewire:driver.steps.training-step :driverId="$driverId" />
    @elseif ($currentStep == 7)
        <livewire:driver.steps.traffic-step :driverId="$driverId" />
    @elseif ($currentStep == 8)
        <livewire:driver.steps.accident-step :driverId="$driverId" />
    @elseif ($currentStep == 9)
        <livewire:driver.steps.fmcsr-step :driverId="$driverId" />
    @elseif ($currentStep == 10)
        <livewire:driver.steps.employment-history-step :driverId="$driverId" />
    @elseif ($currentStep == 11)
        <livewire:driver.steps.company-policy-step :driverId="$driverId" />
    @elseif ($currentStep == 12)
        <livewire:driver.steps.criminal-history-step :driverId="$driverId" />
    @elseif ($currentStep == 13)
        <livewire:driver.steps.w9-step :driverId="$driverId" />
    @elseif ($currentStep == 14)
        <livewire:driver.steps.certification-step :driverId="$driverId" />
    @elseif ($currentStep == 15)
        <livewire:driver.steps.f-m-c-s-a-clearinghouse-step :driverId="$driverId" />
    @endif
</div>
