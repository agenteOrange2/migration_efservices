@props([
    'percentage' => 0,
    'size' => 'md',
    'title' => 'Progress',
    'color' => 'primary'
])

@php
    $sizeClasses = [
        'sm' => ['container' => 'w-24 h-24', 'text' => 'text-sm', 'stroke' => '4'],
        'md' => ['container' => 'w-32 h-32', 'text' => 'text-lg', 'stroke' => '6'],
        'lg' => ['container' => 'w-40 h-40', 'text' => 'text-xl', 'stroke' => '8']
    ];
    
    $colorConfig = [
        'primary' => [
            'stroke' => 'stroke-primary', 
            'text' => 'text-primary',
            'icon' => 'TrendingUp',
            'bgColor' => 'bg-primary/10',
            'borderColor' => 'border-primary/20',
            'badgeVariant' => 'primary'
        ],
        'green' => [
            'stroke' => 'stroke-green-500', 
            'text' => 'text-green-600',
            'icon' => 'CheckCircle',
            'bgColor' => 'bg-green-500/10',
            'borderColor' => 'border-green-500/20',
            'badgeVariant' => 'success'
        ],
        'yellow' => [
            'stroke' => 'stroke-yellow-500', 
            'text' => 'text-yellow-600',
            'icon' => 'Clock',
            'bgColor' => 'bg-yellow-500/10',
            'borderColor' => 'border-yellow-500/20',
            'badgeVariant' => 'warning'
        ],
        'red' => [
            'stroke' => 'stroke-red-500', 
            'text' => 'text-red-600',
            'icon' => 'AlertCircle',
            'bgColor' => 'bg-red-500/10',
            'borderColor' => 'border-red-500/20',
            'badgeVariant' => 'danger'
        ]
    ];
    
    $currentSize = $sizeClasses[$size] ?? $sizeClasses['md'];
    $currentColor = $colorConfig[$color] ?? $colorConfig['primary'];
    
    $radius = 45;
    $circumference = 2 * pi() * $radius;
    $strokeDasharray = $circumference;
    $strokeDashoffset = $circumference - ($percentage / 100) * $circumference;
@endphp

<div class="box box--stacked flex flex-col p-6 hover:shadow-lg transition-all duration-200 group">
    <div class="flex items-center gap-3 mb-6">
        <div class="p-3 {{ $currentColor['bgColor'] }} rounded-xl {{ $currentColor['borderColor'] }} border group-hover:{{ str_replace('/10', '/20', $currentColor['bgColor']) }} transition-colors">
            <x-base.lucide class="w-5 h-5 {{ $currentColor['text'] }}" icon="{{ $currentColor['icon'] }}" />
        </div>
        <h2 class="text-lg font-semibold text-slate-800">{{ $title }}</h2>
        <x-base.badge variant="{{ $currentColor['badgeVariant'] }}" class="ml-auto gap-1.5">
            <span class="w-1.5 h-1.5 bg-{{ $color === 'primary' ? 'primary' : ($color === 'green' ? 'green-500' : ($color === 'yellow' ? 'yellow-500' : 'red-500')) }} rounded-full"></span>
            {{ number_format($percentage, 0) }}%
        </x-base.badge>
    </div>
    
    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
        <div class="flex flex-col items-center justify-center">
            <!-- Progress Circle -->
            <div class="relative {{ $currentSize['container'] }} mb-4">
                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                    <!-- Background Circle -->
                    <circle
                        cx="50"
                        cy="50"
                        r="{{ $radius }}"
                        stroke="currentColor"
                        stroke-width="{{ $currentSize['stroke'] }}"
                        fill="none"
                        class="text-slate-200"
                    />
                    <!-- Progress Circle -->
                    <circle
                        cx="50"
                        cy="50"
                        r="{{ $radius }}"
                        stroke="currentColor"
                        stroke-width="{{ $currentSize['stroke'] }}"
                        fill="none"
                        class="{{ $currentColor['stroke'] }}"
                        stroke-dasharray="{{ $strokeDasharray }}"
                        stroke-dashoffset="{{ $strokeDashoffset }}"
                        stroke-linecap="round"
                        style="transition: stroke-dashoffset 0.5s ease-in-out;"
                    />
                </svg>
                
                <!-- Percentage Text -->
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="font-bold {{ $currentSize['text'] }} {{ $currentColor['text'] }}">
                        {{ number_format($percentage, 0) }}%
                    </span>
                </div>
            </div>
            
            <!-- Progress Details -->
            <div class="text-center">
                <p class="text-sm font-medium text-slate-700 mb-2">Current Progress</p>
                <div class="flex items-center justify-center gap-4 text-xs text-slate-500">
                    <div class="flex items-center gap-1">
                        <x-base.lucide class="w-3 h-3" icon="Target" />
                        <span>{{ $percentage }}% Complete</span>
                    </div>
                    @if($percentage < 100)
                        <span>•</span>
                        <div class="flex items-center gap-1">
                            <x-base.lucide class="w-3 h-3" icon="Clock" />
                            <span>{{ 100 - $percentage }}% Remaining</span>
                        </div>
                    @else
                        <span>•</span>
                        <div class="flex items-center gap-1 text-green-600">
                            <x-base.lucide class="w-3 h-3" icon="CheckCircle" />
                            <span class="font-medium">Complete!</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>