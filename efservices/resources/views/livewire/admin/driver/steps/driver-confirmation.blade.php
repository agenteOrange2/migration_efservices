<!-- resources/views/livewire/admin/driver/steps/driver-confirmation.blade.php -->
<div class="bg-white p-6 rounded-lg shadow-md">
    {{-- Progress Summary --}}
    <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">Overall Completion</span>
            <span class="text-sm font-bold {{ $totalCompletionPercentage >= 100 ? 'text-green-600' : 'text-blue-600' }}">
                {{ number_format($totalCompletionPercentage, 0) }}%
            </span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="h-3 rounded-full transition-all duration-500 {{ $totalCompletionPercentage >= 100 ? 'bg-green-500' : 'bg-blue-500' }}" 
                 style="width: {{ min($totalCompletionPercentage, 100) }}%"></div>
        </div>
    </div>

    {{-- Steps Needing Attention --}}
    @if(count($stepsNeedingAttention) > 0)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="ml-3 w-full">
                    <h3 class="text-sm font-medium text-yellow-800">Steps Requiring Attention</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p class="mb-2">The following sections have missing required fields:</p>
                        <ul class="space-y-2">
                            @foreach($stepsNeedingAttention as $step)
                                <li class="flex items-center justify-between bg-white rounded p-2 border border-yellow-200">
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-yellow-100 text-yellow-800 text-xs font-medium mr-2">
                                            {{ $step['step'] }}
                                        </span>
                                        <span class="font-medium">{{ $step['name'] }}</span>
                                        <span class="ml-2 text-xs text-gray-500">({{ $step['percentage'] }}% complete)</span>
                                    </div>
                                    <button wire:click="goToStep({{ $step['step'] }})" 
                                            class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                        Go to step →
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="flex items-center mb-6">
        <div class="{{ $isComplete ? 'bg-green-100' : 'bg-blue-100' }} p-2 rounded-full">
            @if($isComplete)
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            @endif
        </div>
        <h2 class="text-xl font-bold ml-3 text-gray-800">
            {{ $isComplete ? 'Registration Complete!' : 'Registration In Progress' }}
        </h2>
    </div>

    <div class="mb-8">
        <p class="text-gray-700 mb-4">
            @if($isComplete)
                The driver registration has been completed successfully. All information has been saved.
            @else
                Please complete all required fields in the sections listed above before finalizing the registration.
            @endif
        </p>

        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-medium text-primary">Important Notice</h3>
                    <div class="mt-2 text-sm text-primary">
                        <p class="font-semibold">FMCSA's Drug and Alcohol Clearinghouse Electronic Consent Required</p>
                        <p class="mt-2">
                            Beginning on January 6, 2020, the driver must provide <strong>electronic consent</strong>
                            for a prospective employer to view their information in the FMCSA's Drug and Alcohol
                            Clearinghouse.
                        </p>
                        <p class="mt-2">
                            To do this, the driver must register for the Drug and Alcohol Clearinghouse using the link
                            below and provide electronic consent when requested by the prospective employer. If they do
                            not do this, they will be prohibited from operating a commercial motor vehicle for their
                            prospective employer.
                        </p>
                        <div class="mt-3">
                            <a href="https://clearinghouse.fmcsa.dot.gov/register" target="_blank"
                                class="text-primary hover:text-primary font-medium underline">
                                Register for the FMCSA Clearinghouse
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-between mt-6">

        <x-base.button type="button" wire:click="previous" class="w-full sm:w-44" variant="secondary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                    d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z"
                    clip-rule="evenodd" />
            </svg> Previous
        </x-base.button>

        <button type="button" wire:click="finish"
            class="px-6 py-2 {{ $isComplete ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-400 cursor-not-allowed' }} text-white rounded transition flex items-center"
            wire:loading.attr="disabled" wire:loading.class="opacity-75 cursor-not-allowed"
            {{ !$isComplete ? 'disabled' : '' }}>
            <span wire:loading.remove wire:target="finish">Complete Registration</span>
            <span wire:loading wire:target="finish" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                Processing...
            </span>
        </button>
    </div>

</div>
