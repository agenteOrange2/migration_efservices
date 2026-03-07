@props([
    'variant' => 'info',
    'icon' => null,
    'text' => ''
])

@php
    $variants = [
        'success' => [
            'class' => 'bg-green-100 text-green-800',
            'icon' => $icon ?? 'CheckCircle'
        ],
        'warning' => [
            'class' => 'bg-yellow-100 text-yellow-800',
            'icon' => $icon ?? 'AlertTriangle'
        ],
        'danger' => [
            'class' => 'bg-red-100 text-red-800',
            'icon' => $icon ?? 'XCircle'
        ],
        'info' => [
            'class' => 'bg-blue-100 text-blue-800',
            'icon' => $icon ?? 'Info'
        ],
        'active' => [
            'class' => 'bg-green-100 text-green-800',
            'icon' => $icon ?? 'CheckCircle'
        ],
        'inactive' => [
            'class' => 'bg-slate-100 text-slate-800',
            'icon' => $icon ?? 'MinusCircle'
        ],
        'pending' => [
            'class' => 'bg-yellow-100 text-yellow-800',
            'icon' => $icon ?? 'Clock'
        ],
        'expired' => [
            'class' => 'bg-red-100 text-red-800',
            'icon' => $icon ?? 'AlertTriangle'
        ],
        'approved' => [
            'class' => 'bg-green-100 text-green-800',
            'icon' => $icon ?? 'CheckCircle'
        ],
        'rejected' => [
            'class' => 'bg-red-100 text-red-800',
            'icon' => $icon ?? 'XCircle'
        ]
    ];
    
    $config = $variants[$variant] ?? $variants['info'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $config['class']]) }}>
    @if($config['icon'])
        <x-base.lucide class="h-3 w-3 mr-1" icon="{{ $config['icon'] }}" />
    @endif
    {{ $text ?: ucfirst($variant) }}
</span>
