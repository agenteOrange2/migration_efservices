@props([
    'imageUrl' => null,
    'name' => '',
    'subtitle' => '',
    'fallbackIcon' => 'User',
    'size' => 'md'
])

@php
    $sizes = [
        'sm' => 'w-8 h-8',
        'md' => 'w-10 h-10',
        'lg' => 'w-12 h-12',
        'xl' => 'w-16 h-16'
    ];
    
    $iconSizes = [
        'sm' => 'h-3 w-3',
        'md' => 'h-4 w-4',
        'lg' => 'h-5 w-5',
        'xl' => 'h-6 w-6'
    ];
    
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $iconSize = $iconSizes[$size] ?? $iconSizes['md'];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center']) }}>
    <div class="{{ $sizeClass }} image-fit flex-shrink-0">
        @if($imageUrl)
            <img alt="{{ $name }}" 
                 class="rounded-full shadow-sm object-cover w-full h-full" 
                 src="{{ $imageUrl }}"
                 loading="lazy">
        @else
            <div class="{{ $sizeClass }} bg-slate-200 rounded-full flex items-center justify-center shadow-sm">
                <x-base.lucide class="{{ $iconSize }} text-slate-600" icon="{{ $fallbackIcon }}" />
            </div>
        @endif
    </div>
    @if($name || $subtitle)
        <div class="ml-4">
            @if($name)
                <span class="font-medium whitespace-nowrap text-slate-700">{{ $name }}</span>
            @endif
            @if($subtitle)
                <div class="text-slate-500 text-xs whitespace-nowrap mt-0.5">
                    {{ $subtitle }}
                </div>
            @endif
        </div>
    @endif
</div>
