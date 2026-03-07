{{-- resources/views/livewire/driver/driver-registration-manager.blade.php --}}
<div class="flex flex-col  driver-registration-manager">
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

    <!-- Progress Bar -->
    <div class="mb-8 bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
            <div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">
                    @if ($isIndependent)
                        Registration Application (Independent)
                    @else
                        Driver Registration for {{ $carrier?->name ?? 'Your Carrier' }}
                    @endif
                </h3>
                <p class="text-sm text-gray-500">Complete all steps to finish your registration</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <div class="text-2xl font-bold text-[#03045E]">{{ round(($currentStep / $totalSteps) * 100) }}%</div>
                    <div class="text-xs text-gray-500">Complete</div>
                </div>
                <div class="text-right border-l border-gray-200 pl-3">
                    <div class="text-lg font-semibold text-gray-900">Step {{ $currentStep }}</div>
                    <div class="text-xs text-gray-500">of {{ $totalSteps }}</div>
                </div>
            </div>
        </div>
        
        <!-- Barra de progreso mejorada -->
        <div class="relative">
            <div class="w-full bg-gray-200 rounded-full h-3.5 overflow-hidden shadow-inner">
                <div 
                    class="bg-[#03045E] h-3.5 rounded-full transition-all duration-500 ease-out shadow-sm relative"
                    style="width: {{ ($currentStep / $totalSteps) * 100 }}%"
                >
                    <div class="absolute top-0 right-0 bottom-0 w-8 bg-gradient-to-r from-transparent to-white/30"></div>
                </div>
            </div>
            <!-- Indicadores de pasos -->
            <div class="flex justify-between mt-3">
                @for($i = 1; $i <= $totalSteps; $i++)
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-2.5 h-2.5 rounded-full transition-all duration-300 {{ $i <= $currentStep ? 'bg-[#03045E] ring-2 ring-[#03045E] ring-offset-2 ring-offset-white scale-125' : 'bg-gray-300' }}"></div>
                        @if($i == 1 || $i == $totalSteps || $i == round($totalSteps / 2))
                            <span class="text-xs text-gray-500 mt-1.5 font-medium hidden sm:block">{{ $i }}</span>
                        @endif
                    </div>
                @endfor
            </div>
        </div>
    </div>

    <!-- Componentes de pasos -->
    @if ($currentStep == 1)
        <livewire:driver.steps.step-general :driver-id="$driverId" :is-independent="$isIndependent" :carrier="$carrier" />
    @elseif ($currentStep == 2)
        <livewire:driver.steps.address-step :driver-id="$driverId" />
    @elseif ($currentStep == 3)
        <livewire:driver.steps.application-step :driver-id="$driverId" />
    @elseif ($currentStep == 4)
        <livewire:driver.steps.license-step :driver-id="$driverId" />
    @elseif ($currentStep == 5)
        <livewire:driver.steps.medical-step :driver-id="$driverId" />
    @elseif ($currentStep == 6)
        <livewire:driver.steps.training-step :driver-id="$driverId" />
    @elseif ($currentStep == 7)
        <livewire:driver.steps.traffic-step :driver-id="$driverId" />
    @elseif ($currentStep == 8)
        <livewire:driver.steps.accident-step :driver-id="$driverId" />
    @elseif ($currentStep == 9)
        <livewire:driver.steps.f-m-c-s-r-step :driver-id="$driverId" />
    @elseif ($currentStep == 10)
        <livewire:driver.steps.employment-history-step :driver-id="$driverId" />
    @elseif ($currentStep == 11)
        <livewire:driver.steps.company-policy-step :driver-id="$driverId" />
    @elseif ($currentStep == 12)
        <livewire:driver.steps.criminal-history-step :driver-id="$driverId" />
    @elseif ($currentStep == 13)
        <livewire:driver.steps.w9-step :driver-id="$driverId" />
    @elseif ($currentStep == 14)
        <livewire:driver.steps.certification-step :driver-id="$driverId" />
    @elseif ($currentStep == 15)
        <livewire:driver.steps.fmcsaclearinghouse-step :driver-id="$driverId" />
    @endif

    
</div>
