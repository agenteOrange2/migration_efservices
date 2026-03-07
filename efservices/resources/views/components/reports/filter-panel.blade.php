@props([
    'action' => '',
    'method' => 'GET',
    'activeFiltersCount' => 0,
    'clearRoute' => '',
    'buttonText' => 'Filter Options'
])

<x-base.popover class="inline-block">
    <x-base.popover.button class="w-full sm:w-auto" as="x-base.button" variant="outline-secondary">
        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowDownWideNarrow" />
        {{ $buttonText }}
        @if($activeFiltersCount > 0)
            <span class="ml-2 flex h-5 items-center justify-center rounded-full border bg-slate-100 px-1.5 text-xs font-medium">
                {{ $activeFiltersCount }}
            </span>
        @endif
    </x-base.popover.button>
    <x-base.popover.panel placement="bottom-end">
        <div class="p-4 w-80">
            <form method="{{ $method }}" action="{{ $action }}">
                @if($method !== 'GET')
                    @csrf
                    @method($method)
                @endif
                
                {{ $slot }}
                
                <div class="mt-4 flex items-center gap-2">
                    @if($clearRoute)
                        <x-base.button class="flex-1" variant="secondary" as="a" href="{{ $clearRoute }}">
                            <x-base.lucide class="mr-2 h-4 w-4" icon="X" />
                            Clear
                        </x-base.button>
                    @endif
                    <x-base.button class="flex-1" variant="primary" type="submit">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="Check" />
                        Apply
                    </x-base.button>
                </div>
            </form>
        </div>
    </x-base.popover.panel>
</x-base.popover>
