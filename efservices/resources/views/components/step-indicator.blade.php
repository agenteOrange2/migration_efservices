{{--
    Step Indicator Component
    Shows visual status of each step (completed, current, pending)
    
    Usage: <x-step-indicator :current="$currentStep" :completed="$completedSteps" />
--}}

@props(['current' => 1, 'completed' => []])

<div {{ $attributes->merge(['class' => 'flex items-center justify-between']) }}>
    @for ($i = 1; $i <= 14; $i++)
        <div class="flex items-center {{ $i < 14 ? 'flex-1' : '' }}">
            <!-- Step Circle -->
            <div class="relative flex items-center justify-center">
                @if (in_array($i, $completed))
                    <!-- Completed Step -->
                    <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                @elseif ($i == $current)
                    <!-- Current Step -->
                    <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center ring-4 ring-blue-100">
                        <span class="text-white text-sm font-bold">{{ $i }}</span>
                    </div>
                @else
                    <!-- Pending Step -->
                    <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center">
                        <span class="text-gray-500 text-sm font-medium">{{ $i }}</span>
                    </div>
                @endif
            </div>
            
            <!-- Connector Line -->
            @if ($i < 14)
                <div class="flex-1 h-1 mx-2 {{ in_array($i, $completed) ? 'bg-green-500' : 'bg-gray-200' }}"></div>
            @endif
        </div>
    @endfor
</div>
