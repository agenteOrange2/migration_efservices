@props(['currentStatus' => 'pending'])

@php
$steps = [
    'pending' => [
        'title' => 'Pending Validation',
        'description' => 'Your application is being reviewed by our team',
        'icon' => 'Clock',
        'count' => 1,
        'bgColor' => 'bg-yellow-500/10',
        'borderColor' => 'border-yellow-500/20',
        'iconColor' => 'text-yellow-600',
        'badgeVariant' => 'warning'
    ],
    'approved' => [
        'title' => 'Approved',
        'description' => 'Your application has been successfully approved',
        'icon' => 'CheckCircle',
        'count' => 1,
        'bgColor' => 'bg-green-500/10',
        'borderColor' => 'border-green-500/20',
        'iconColor' => 'text-green-600',
        'badgeVariant' => 'success'
    ],
    'rejected' => [
        'title' => 'Rejected',
        'description' => 'Your application needs revision and resubmission',
        'icon' => 'XCircle',
        'count' => 0,
        'bgColor' => 'bg-red-500/10',
        'borderColor' => 'border-red-500/20',
        'iconColor' => 'text-red-600',
        'badgeVariant' => 'danger'
    ]
];

$stepOrder = ['pending', 'approved'];
$currentIndex = array_search($currentStatus, $stepOrder);
@endphp

<div class="box box--stacked flex flex-col p-3 md:p-6 hover:shadow-lg transition-all duration-200 group">
    <div class="flex flex-col sm:flex-row items-center gap-3 mb-6">
        <div class="p-3 flex items-center gap-2 {{ $steps[$currentStatus]['bgColor'] }} rounded-xl {{ $steps[$currentStatus]['borderColor'] }} border group-hover:{{ str_replace('/10', '/20', $steps[$currentStatus]['bgColor']) }} transition-colors">
            <x-base.lucide class="w-5 h-5 {{ $steps[$currentStatus]['iconColor'] }}" icon="{{ $steps[$currentStatus]['icon'] }}" />
            <h2 class="text-lg font-semibold text-slate-800">Application Status Timeline</h2>
        </div>
        
        <x-base.badge variant="{{ $steps[$currentStatus]['badgeVariant'] }}" class="ml-auto gap-1.5">
            <span class="w-1.5 h-1.5 bg-{{ $currentStatus === 'pending' ? 'yellow-500' : ($currentStatus === 'approved' ? 'green-500' : 'red-500') }} rounded-full"></span>
            {{ $steps[$currentStatus]['title'] }}
        </x-base.badge>
    </div>

    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 mb-4">
        <div class="flex items-start gap-3 mb-4">
            <x-base.lucide class="w-4 h-4 {{ $steps[$currentStatus]['iconColor'] }} mt-0.5 flex-shrink-0" icon="{{ $steps[$currentStatus]['icon'] }}" />
            <div class="flex-1">
                <p class="text-sm font-medium text-slate-700 leading-relaxed mb-2">
                    {{ $steps[$currentStatus]['description'] }}
                </p>
                <div class="flex flex-col sm:flex-row items-center gap-2">
                    <x-base.badge variant="soft" class="text-xs">Current Status</x-base.badge>                    
                    <div class="flex items-center gap-1 text-xs text-slate-500">
                        <span class="text-xs text-slate-500">•</span>
                        <x-base.lucide class="w-3 h-3" icon="Calendar" />
                        <span>Updated today</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Status Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        @foreach($stepOrder as $index => $step)
            @php
                $isActive = $step === $currentStatus;
                $isCompleted = $index < $currentIndex;
                $stepData = $steps[$step];
            @endphp
            
            <div class="relative p-4 rounded-lg border transition-all duration-200 hover:shadow-md
                {{ $isActive ? $stepData['bgColor'] . ' ' . $stepData['borderColor'] . ' ring-2 ring-' . ($step === 'pending' ? 'yellow-500' : ($step === 'approved' ? 'green-500' : 'red-500')) . '/20' : 'bg-slate-50 border-slate-200' }}
                {{ $isCompleted ? 'bg-green-50 border-green-200' : '' }}">
                
                <div class="flex items-start gap-3">
                    <div class="p-2 rounded-lg {{ $isActive || $isCompleted ? $stepData['bgColor'] : 'bg-slate-100' }} {{ $isActive || $isCompleted ? $stepData['borderColor'] : 'border-slate-200' }} border">
                        <x-base.lucide class="w-4 h-4 {{ $isActive || $isCompleted ? $stepData['iconColor'] : 'text-slate-400' }}" icon="{{ $stepData['icon'] }}" />
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="text-sm font-semibold {{ $isActive ? $stepData['iconColor'] : ($isCompleted ? 'text-green-600' : 'text-slate-600') }}">
                                {{ $stepData['title'] }}
                            </h3>
                            @if($isActive)
                                <x-base.badge variant="{{ $stepData['badgeVariant'] }}" class="text-xs">Current</x-base.badge>
                            @elseif($isCompleted)
                                <x-base.badge variant="success" class="text-xs">Complete</x-base.badge>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">{{ $stepData['description'] }}</p>
                    </div>
                </div>
                
                @if($isActive)
                    <div class="absolute -top-1 -right-1">
                        <div class="w-3 h-3 bg-{{ $step === 'pending' ? 'yellow-500' : ($step === 'approved' ? 'green-500' : 'red-500') }} rounded-full animate-pulse"></div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Progress Summary -->
    <div class="pt-4 border-t border-slate-100">
        <div class="flex items-center justify-between text-xs text-slate-500">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-1">
                    <x-base.lucide class="w-3 h-3" icon="CheckCircle" />
                    <span>{{ $currentIndex >= 0 ? $currentIndex + 1 : 0 }} of {{ count($stepOrder) }} steps</span>
                </div>
                <span>•</span>
                <div class="flex items-center gap-1">
                    <x-base.lucide class="w-3 h-3" icon="Clock" />
                    <span>Last updated: {{ now()->format('M j, Y') }}</span>
                </div>
            </div>
            <div class="flex items-center gap-1">
                <x-base.lucide class="w-3 h-3" icon="TrendingUp" />
                <span>{{ $currentIndex >= 0 ? round((($currentIndex + 1) / count($stepOrder)) * 100) : 0 }}% Complete</span>
            </div>
        </div>
    </div>
</div>