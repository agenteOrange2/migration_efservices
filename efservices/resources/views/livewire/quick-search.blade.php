<div
    id="quick-search"
    x-data="quickSearchController()"
    x-init="initQuickSearch()"
    x-on:keydown.window="handleGlobalKeydown($event)"
    x-on:keydown.escape="close()"
    x-on:keydown.arrow-down.prevent="navigateDown()"
    x-on:keydown.arrow-up.prevent="navigateUp()"
    x-on:keydown.enter.prevent="selectCurrent()"
    wire:ignore.self
    aria-hidden="true"
    tabindex="-1"
    @class([
        'modal group bg-gradient-to-b from-theme-1/50 via-theme-2/50 to-black/50 transition-[visibility,opacity] w-screen h-screen fixed left-0 top-0 overflow-y-hidden z-[60]',
        '[&:not(.show)]:duration-[0s,0.2s] [&:not(.show)]:delay-[0.2s,0s] [&:not(.show)]:invisible [&:not(.show)]:opacity-0',
        '[&.show]:visible [&.show]:opacity-100 [&.show]:duration-[0s,0.1s]',
    ])
>
    <div class="relative mx-auto my-2 w-[95%] scale-95 transition-transform group-[.show]:scale-100 sm:mt-40 sm:w-[600px] lg:w-[700px]">
        {{-- Search Input --}}
        <div class="relative">
            <div class="absolute inset-y-0 left-0 flex w-12 items-center justify-center">
                <svg class="-mr-1.5 h-5 w-5 stroke-[1] text-slate-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
            </div>
            <input
                type="text"
                wire:model.live.debounce.300ms="query"
                x-ref="searchInput"
                class="w-full rounded-lg border-0 py-3.5 pl-12 pr-14 text-base shadow-lg focus:ring-0"
                placeholder="Quick search..."
                autocomplete="off"
            />
            <div class="absolute inset-y-0 right-0 flex w-14 items-center">
                @if($isLoading)
                    <div class="mr-auto">
                        <svg class="w-5 h-5 text-slate-400 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                @else
                    <div class="mr-auto rounded-[0.4rem] border bg-slate-100 px-2 py-1 text-xs text-slate-500/80">
                        ESC
                    </div>
                @endif
            </div>
        </div>

        {{-- Results Container --}}
        <div class="global-search group relative z-10 mt-1 max-h-[468px] overflow-y-auto rounded-lg bg-white pb-1 shadow-lg sm:max-h-[615px]">
            
            {{-- No Results State --}}
            @if(!$hasResults && strlen($query) > 0)
                <div class="flex flex-col items-center justify-center pb-28 pt-20">
                    <svg class="h-20 w-20 fill-theme-1/5 stroke-[0.5] text-theme-1/20" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path><path d="m8 8 6 6"></path><path d="m14 8-6 6"></path></svg>
                    <div class="mt-5 text-xl font-medium">
                        No results found
                    </div>
                    <div class="mt-3 w-2/3 text-center leading-relaxed text-slate-500">
                        No results found for
                        <span class="font-medium italic">"{{ $query }}"</span>.
                        Please try a different search term.
                    </div>
                </div>
            @else
                {{-- Quick Actions (when no query) --}}
                @if(empty($query) && !empty($quickActions))
                    <div class="px-5 py-4">
                        <div class="flex items-center">
                            <div class="text-xs uppercase text-slate-500">
                                Quick Actions
                            </div>
                        </div>
                        <div class="mt-3.5 flex flex-wrap gap-2">
                            @foreach($quickActions as $action)
                                <a
                                    href="{{ $action['url'] }}"
                                    class="flex items-center gap-x-1.5 rounded-full border border-slate-300/70 px-3 py-0.5 hover:bg-slate-50 transition-colors"
                                >
                                    @if($action['icon'] === 'Plus')
                                        <svg class="h-4 w-4 stroke-[1.3]" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                    @elseif($action['icon'] === 'UserPlus')
                                        <svg class="h-4 w-4 stroke-[1.3]" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
                                    @elseif($action['icon'] === 'BarChart3')
                                        <svg class="h-4 w-4 stroke-[1.3]" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
                                    @elseif($action['icon'] === 'Truck')
                                        <svg class="h-4 w-4 stroke-[1.3]" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
                                    @elseif($action['icon'] === 'Clock')
                                        <svg class="h-4 w-4 stroke-[1.3]" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    @elseif($action['icon'] === 'FileText')
                                        <svg class="h-4 w-4 stroke-[1.3]" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><line x1="10" x2="8" y1="9" y2="9"/></svg>
                                    @elseif($action['icon'] === 'MapPin')
                                        <svg class="h-4 w-4 stroke-[1.3]" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                    @else
                                        <svg class="h-4 w-4 stroke-[1.3]" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>
                                    @endif
                                    {{ $action['title'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Navigation Results --}}
                @if(!empty($filteredNavigation))
                    @foreach($groupedNavigation as $section => $items)
                        <div class="border-t border-dashed px-5 py-4 first:border-t-0">
                            <div class="flex items-center">
                                <div class="text-xs uppercase text-slate-500">
                                    {{ $section }}
                                </div>
                            </div>
                            <div class="mt-3.5 flex flex-col gap-1">
                                @foreach($items as $index => $item)
                                    <a
                                        href="{{ $item['url'] }}"
                                        x-on:click="close()"
                                        class="search-result-item flex items-center gap-2.5 rounded-md border border-transparent p-2 hover:border-slate-100 hover:bg-slate-50/80 transition-colors"
                                        :class="{ 'border-theme-1/20 bg-theme-1/5': selectedIndex === {{ $loop->parent->index * 100 + $index }} }"
                                        x-on:mouseenter="selectedIndex = {{ $loop->parent->index * 100 + $index }}"
                                        data-index="{{ $loop->parent->index * 100 + $index }}"
                                    >
                                        <div class="flex h-8 w-8 items-center justify-center rounded-md border border-theme-1/10 bg-theme-1/10">
                                            {{-- Navigation icon --}}
                                            <svg class="h-4 w-4 stroke-[1.3] text-theme-1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="truncate font-medium text-slate-700">
                                                {{ $item['title'] }}
                                            </div>
                                            @if($item['fullPath'] !== $item['title'])
                                                <div class="truncate text-xs text-slate-400">
                                                    {{ $item['fullPath'] }}
                                                </div>
                                            @endif
                                        </div>
                                        <svg class="h-4 w-4 text-slate-300" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"></path></svg>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif

                {{-- Entity Results --}}
                @if(!empty($entityResults))
                    @foreach($entityResults as $category => $items)
                        <div class="border-t border-dashed px-5 py-4">
                            <div class="flex items-center">
                                <div class="text-xs uppercase text-slate-500">
                                    {{ ucfirst($category) }}
                                </div>
                                <span class="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">
                                    {{ count($items) }}
                                </span>
                            </div>
                            <div class="mt-3.5 flex flex-col gap-1">
                                @foreach($items as $item)
                                    <a
                                        href="{{ $item['url'] }}"
                                        x-on:click.prevent="close(); window.location.href = '{{ $item['url'] }}';"
                                        class="search-result-item flex items-center gap-2.5 rounded-md border border-transparent p-2 hover:border-slate-100 hover:bg-slate-50/80 transition-colors"
                                    >
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-slate-100">
                                            @if($item['icon'] === 'Building2')
                                                {{-- Carrier icon --}}
                                                <svg class="h-4 w-4 stroke-[1.3] text-slate-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                                            @elseif($item['icon'] === 'User')
                                                {{-- Driver icon --}}
                                                <svg class="h-4 w-4 stroke-[1.3] text-slate-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                            @elseif($item['icon'] === 'Truck')
                                                {{-- Vehicle icon --}}
                                                <svg class="h-4 w-4 stroke-[1.3] text-slate-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
                                            @elseif($item['icon'] === 'UserCircle')
                                                {{-- User icon --}}
                                                <svg class="h-4 w-4 stroke-[1.3] text-slate-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="10" r="3"/><path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"/></svg>
                                            @else
                                                {{-- Default icon --}}
                                                <svg class="h-4 w-4 stroke-[1.3] text-slate-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="truncate font-medium text-slate-700">
                                                {{ $item['title'] }}
                                            </div>
                                            @if(!empty($item['subtitle']))
                                                <div class="truncate text-xs text-slate-400">
                                                    {{ $item['subtitle'] }}
                                                </div>
                                            @endif
                                        </div>
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">
                                            {{ $item['category'] }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif

                {{-- Empty State (no query) --}}
                @if(empty($query) && empty($quickActions))
                    <div class="px-5 py-4">
                        <div class="text-center text-slate-500">
                            Start typing to search...
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

@pushOnce('vendors')
    @vite('resources/js/vendors/modal.js')
@endPushOnce

@pushOnce('scripts')
<script>
function initQuickSearch() {
    // No need for Lucide refresh anymore
}

function quickSearchController() {
    return {
        selectedIndex: -1,
        recentSearches: [],
        
        init() {
            this.loadRecentSearches();
            
            // Focus input when modal opens
            const modal = document.getElementById('quick-search');
            if (modal) {
                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        if (mutation.attributeName === 'class') {
                            if (modal.classList.contains('show')) {
                                this.$nextTick(() => {
                                    this.$refs.searchInput?.focus();
                                });
                            }
                        }
                    });
                });
                observer.observe(modal, { attributes: true });
            }
        },
        
        handleGlobalKeydown(event) {
            // Open modal with Cmd+K or Ctrl+K
            if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
                event.preventDefault();
                const modal = tailwind.Modal.getOrCreateInstance(document.getElementById('quick-search'));
                modal.show();
            }
        },
        
        close() {
            const modal = tailwind.Modal.getOrCreateInstance(document.getElementById('quick-search'));
            modal.hide();
        },
        
        navigateDown() {
            const items = document.querySelectorAll('.search-result-item');
            if (items.length > 0) {
                this.selectedIndex = Math.min(this.selectedIndex + 1, items.length - 1);
                items[this.selectedIndex]?.scrollIntoView({ block: 'nearest' });
            }
        },
        
        navigateUp() {
            const items = document.querySelectorAll('.search-result-item');
            if (items.length > 0) {
                this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
                items[this.selectedIndex]?.scrollIntoView({ block: 'nearest' });
            }
        },
        
        selectCurrent() {
            const items = document.querySelectorAll('.search-result-item');
            if (this.selectedIndex >= 0 && items[this.selectedIndex]) {
                const item = items[this.selectedIndex];
                this.saveToRecent({
                    title: item.querySelector('.font-medium')?.textContent?.trim(),
                    url: item.href
                });
                this.close();
                window.location.href = item.href;
            }
        },
        
        saveToRecent(item) {
            if (!item.title || !item.url) return;
            
            // Remove if already exists
            this.recentSearches = this.recentSearches.filter(s => s.url !== item.url);
            
            // Add to beginning
            this.recentSearches.unshift({
                ...item,
                timestamp: Date.now()
            });
            
            // Keep only last 5
            this.recentSearches = this.recentSearches.slice(0, 5);
            
            // Save to localStorage
            localStorage.setItem('quick_search_recent', JSON.stringify(this.recentSearches));
        },
        
        loadRecentSearches() {
            try {
                const stored = localStorage.getItem('quick_search_recent');
                if (stored) {
                    this.recentSearches = JSON.parse(stored);
                }
            } catch (e) {
                this.recentSearches = [];
            }
        }
    };
}
</script>
@endPushOnce
