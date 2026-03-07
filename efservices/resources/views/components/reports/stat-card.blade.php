@props([
    'title' => '',
    'value' => '',
    'subtitle' => '',
    'icon' => 'TrendingUp',
    'gradientFrom' => 'blue-500',
    'gradientTo' => 'blue-600',
    'iconBg' => 'blue-400'
])

<div {{ $attributes->merge(['class' => 'bg-gradient-to-r from-' . $gradientFrom . ' to-' . $gradientTo . ' rounded-lg p-4 text-white shadow-sm']) }}>
    <div class="flex items-center justify-between">
        <div class="flex-1">
            <p class="text-white/80 text-sm font-medium">{{ $title }}</p>
            <p class="text-2xl font-bold mt-1">{{ $value }}</p>
            @if($subtitle)
                <p class="text-white/70 text-xs mt-1">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="bg-{{ $iconBg }} bg-opacity-30 rounded-full p-3 flex-shrink-0">
            <x-base.lucide icon="{{ $icon }}" class="h-6 w-6" />
        </div>
    </div>
</div>
