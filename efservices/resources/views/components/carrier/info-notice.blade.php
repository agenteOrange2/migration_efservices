@props([
    'type' => 'info',
    'title' => 'Important Notice',
    'message' => '',
    'icon' => null
])

@php
    $typeConfig = [
        'info' => [
            'icon' => $icon ?? 'Info',
            'badgeVariant' => 'primary',
            'iconColor' => 'text-primary',
            'bgColor' => 'bg-primary/10',
            'borderColor' => 'border-primary/20'
        ],
        'warning' => [
            'icon' => $icon ?? 'AlertTriangle',
            'badgeVariant' => 'warning',
            'iconColor' => 'text-warning',
            'bgColor' => 'bg-warning/10',
            'borderColor' => 'border-warning/20'
        ],
        'success' => [
            'icon' => $icon ?? 'CheckCircle',
            'badgeVariant' => 'success',
            'iconColor' => 'text-success',
            'bgColor' => 'bg-success/10',
            'borderColor' => 'border-success/20'
        ],
        'danger' => [
            'icon' => $icon ?? 'XCircle',
            'badgeVariant' => 'danger',
            'iconColor' => 'text-danger',
            'bgColor' => 'bg-danger/10',
            'borderColor' => 'border-danger/20'
        ]
    ];
    
    $config = $typeConfig[$type] ?? $typeConfig['info'];
@endphp

<div class="box box--stacked flex flex-col p-6 hover:shadow-lg transition-all duration-200 group">
    <div class="flex items-center gap-3 mb-6">
        <div class="p-3 {{ $config['bgColor'] }} rounded-xl {{ $config['borderColor'] }} border group-hover:{{ str_replace('/10', '/20', $config['bgColor']) }} transition-colors">
            <x-base.lucide class="w-5 h-5 {{ $config['iconColor'] }}" icon="{{ $config['icon'] }}" />
        </div>
        <h2 class="text-lg font-semibold text-slate-800">{{ $title }}</h2>
        <x-base.badge variant="{{ $config['badgeVariant'] }}" class="ml-auto gap-1.5">
            <span class="w-1.5 h-1.5 bg-{{ $type === 'info' ? 'primary' : $type }} rounded-full"></span>
            {{ ucfirst($type) }}
        </x-base.badge>
    </div>

    @if($message)
    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
        <div class="flex items-start gap-3">
            <x-base.lucide class="w-4 h-4 {{ $config['iconColor'] }} mt-0.5 flex-shrink-0" icon="{{ $config['icon'] }}" />
            <div class="flex-1">
                <p class="text-sm font-medium text-slate-700 leading-relaxed">{{ $message }}</p>
            </div>
        </div>
    </div>
    @endif

    @if($slot->isNotEmpty())
    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
        <div class="flex items-start gap-3">
            <x-base.lucide class="w-4 h-4 {{ $config['iconColor'] }} mt-0.5 flex-shrink-0" icon="{{ $config['icon'] }}" />
            <div class="flex-1 text-sm font-medium text-slate-700 leading-relaxed">
                {{ $slot }}
            </div>
        </div>
    </div>
    @endif

    @if(!$message && $slot->isEmpty())
    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
        <div class="flex items-start gap-3">
            <x-base.lucide class="w-4 h-4 {{ $config['iconColor'] }} mt-0.5 flex-shrink-0" icon="{{ $config['icon'] }}" />
            <div class="flex-1">
                <p class="text-sm font-medium text-slate-700 leading-relaxed">
                    This is an important notice that requires your attention. Please review the information carefully.
                </p>
            </div>
        </div>
    </div>
    @endif
</div>