@props([
    'percentage' => 0,
    'gradientFrom' => 'green-400',
    'gradientTo' => 'green-600',
    'height' => 'h-2',
    'width' => 'w-16',
    'showPercentage' => true,
    'bgColor' => 'bg-slate-200'
])

@php
    $percentage = max(0, min(100, $percentage));
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center']) }}>
    <div class="{{ $width }} {{ $height }} {{ $bgColor }} rounded-full overflow-hidden">
        <div class="h-full bg-gradient-to-r from-{{ $gradientFrom }} to-{{ $gradientTo }} rounded-full transition-all duration-300" 
             style="width: {{ $percentage }}%">
        </div>
    </div>
    @if($showPercentage)
        <span class="text-xs text-slate-500 mt-1">{{ number_format($percentage, 1) }}%</span>
    @endif
</div>
