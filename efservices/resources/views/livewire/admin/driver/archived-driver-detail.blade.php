<div>
    @php
        $isAdmin = auth()->user()->hasRole('superadmin');
        $backRoute = $isAdmin ? route('admin.drivers.archived.index') : route('carrier.drivers.inactive.index');
        $profilePhotoUrl = $archive->driver_data_snapshot['profile_photo_url'] ?? null;
    @endphp

    <!-- Flash Messages -->
    @if(session()->has('success'))
        <div class="box p-4 mb-4 bg-success/10 border border-success/30 rounded-lg flex items-center gap-3">
            <x-base.lucide class="w-5 h-5 text-success flex-shrink-0" icon="CheckCircle" />
            <span class="text-sm text-success font-medium">{{ session('success') }}</span>
        </div>
    @endif
    @if(session()->has('error'))
        <div class="box p-4 mb-4 bg-danger/10 border border-danger/30 rounded-lg flex items-center gap-3">
            <x-base.lucide class="w-5 h-5 text-danger flex-shrink-0" icon="AlertCircle" />
            <span class="text-sm text-danger font-medium">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Archive Banner -->
    <div class="box box--stacked p-6 mb-6 bg-amber-50/50 border-2 border-amber-200">
        <div class="flex items-start gap-4">
            <div class="p-3 bg-amber-100 rounded-xl flex-shrink-0">
                <x-base.lucide class="w-6 h-6 text-amber-600" icon="Archive" />
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-amber-900 text-lg mb-2">Archived Driver Record</h3>
                <p class="text-sm text-amber-700">
                    This is a historical, read-only record of driver information as it existed on 
                    <strong>{{ $archive->archived_at->format('F j, Y \a\t g:i A') }}</strong>.
                    This driver is no longer active with your carrier.
                </p>
            </div>
        </div>
    </div>

    <!-- Professional Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                @if($profilePhotoUrl)
                    <img 
                        src="{{ $profilePhotoUrl }}" 
                        alt="{{ $archive->full_name }}"
                        class="w-20 h-20 rounded-xl object-cover border-2 border-primary/20 shadow-sm"
                        onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'p-3 bg-primary/10 rounded-xl border border-primary/20\'><x-base.lucide class=\'w-14 h-14 text-primary\' icon=\'User\' /></div>';"
                    >
                @else
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <x-base.lucide class="w-14 h-14 text-primary" icon="User" />
                    </div>
                @endif
                
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">{{ $archive->full_name }}</h1>
                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-4 text-slate-600">
                        @if($archive->email)
                            <div class="flex items-center gap-2">
                                <x-base.lucide class="w-4 h-4" icon="Mail" />
                                <span class="text-sm">{{ $archive->email }}</span>
                            </div>
                        @endif
                        @if($archive->phone)
                            <div class="flex items-center gap-2">
                                <x-base.lucide class="w-4 h-4" icon="Phone" />
                                <span class="text-sm">{{ $archive->phone }}</span>
                            </div>
                        @endif
                        <x-base.badge variant="warning" class="gap-1.5">
                            <span class="w-1.5 h-1.5 bg-warning rounded-full"></span>
                            Archived
                        </x-base.badge>
                    </div>
                    @if($archive->carrier)
                        <p class="text-sm text-slate-500 mt-2 flex items-center gap-1 justify-center md:justify-start">
                            <x-base.lucide class="w-4 h-4" icon="Building" />
                            {{ $archive->carrier->name }}
                        </p>
                    @endif
                </div>
            </div>
            <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                @if($isAdmin)
                <x-base.button 
                    type="button"
                    variant="outline-warning" 
                    class="w-full sm:w-auto gap-2"
                    wire:click="refreshSnapshots"
                    wire:confirm="This will re-populate all archive snapshots from the original driver record. Continue?"
                    wire:loading.attr="disabled">
                    <x-base.lucide class="w-4 h-4" icon="RefreshCw" wire:loading.class="animate-spin" wire:target="refreshSnapshots" />
                    <span wire:loading.remove wire:target="refreshSnapshots">Refresh Data</span>
                    <span wire:loading wire:target="refreshSnapshots">Refreshing...</span>
                </x-base.button>
                @endif
                <x-base.button 
                    as="a" 
                    href="{{ $backRoute }}" 
                    variant="outline-secondary" 
                    class="w-full sm:w-auto gap-2">
                    <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                    Back to List
                </x-base.button>
            </div>
        </div>
        
        <!-- Document Count Info -->
        <div class="mt-6 pt-6 border-t border-slate-200/60">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <x-base.lucide class="w-5 h-5 text-slate-400" icon="FileText" />
                    <span class="text-sm text-slate-600">
                        <strong>{{ $this->documentCount }}</strong> document{{ $this->documentCount !== 1 ? 's' : '' }} available in archive
                    </span>
                </div>
                <div class="text-xs text-slate-500">
                    Archived: {{ $archive->archived_at->format('M d, Y') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Section -->
    <div class="box box--stacked" 
         x-data="{ 
             activeTab: @entangle('activeTab'),
             canScrollLeft: false,
             canScrollRight: false,
             checkScroll() {
                 const container = this.$refs.tabsContainer;
                 if (container) {
                     const hasScroll = container.scrollWidth > container.clientWidth;
                     this.canScrollLeft = hasScroll && container.scrollLeft > 5;
                     this.canScrollRight = hasScroll && container.scrollLeft < (container.scrollWidth - container.clientWidth - 5);
                 }
             },
             scrollLeft() {
                 const container = this.$refs.tabsContainer;
                 if (container) {
                     container.scrollBy({ left: -250, behavior: 'smooth' });
                 }
             },
             scrollRight() {
                 const container = this.$refs.tabsContainer;
                 if (container) {
                     container.scrollBy({ left: 250, behavior: 'smooth' });
                 }
             },
             scrollToActiveTab() {
                 const container = this.$refs.tabsContainer;
                 const activeTabElement = container?.querySelector('[aria-selected=\'true\']');
                 if (container && activeTabElement) {
                     const containerRect = container.getBoundingClientRect();
                     const tabRect = activeTabElement.getBoundingClientRect();
                     const scrollLeft = container.scrollLeft + (tabRect.left - containerRect.left) - (containerRect.width / 2) + (tabRect.width / 2);
                     container.scrollTo({ left: scrollLeft, behavior: 'smooth' });
                     setTimeout(() => this.checkScroll(), 300);
                 }
             }
         }"
         x-init="
             setTimeout(() => {
                 checkScroll();
                 const container = $refs.tabsContainer;
                 if (container) {
                     container.addEventListener('scroll', () => checkScroll());
                     window.addEventListener('resize', () => {
                         setTimeout(() => checkScroll(), 100);
                     });
                 }
                 scrollToActiveTab();
             }, 100);
         "
         x-effect="
             if (activeTab) {
                 setTimeout(() => {
                     scrollToActiveTab();
                 }, 150);
             }
         ">
        <div class="overflow-hidden">
            <!-- Responsive Tabs Container -->
            <div class="relative overflow-hidden">
                <!-- Scroll Buttons -->
                <div class="absolute left-0 top-0 bottom-0 w-8 bg-gradient-to-r from-white via-white/95 to-transparent z-20 pointer-events-none flex items-center" 
                     x-show="canScrollLeft"
                     x-transition>
                    <button 
                        type="button"
                        @click="scrollLeft()"
                        class="w-7 h-7 rounded-full bg-white border border-slate-300 shadow-md flex items-center justify-center text-slate-700 hover:text-primary hover:border-primary transition-all pointer-events-auto hover:scale-110"
                        aria-label="Scroll tabs left">
                        <x-base.lucide class="w-4 h-4" icon="ChevronLeft" />
                    </button>
                </div>

                <div class="absolute right-0 top-0 bottom-0 w-8 bg-gradient-to-l from-white via-white/95 to-transparent z-20 pointer-events-none flex items-center justify-end"
                     x-show="canScrollRight"
                     x-transition>
                    <button 
                        type="button"
                        @click="scrollRight()"
                        class="w-7 h-7 rounded-full bg-white border border-slate-300 shadow-md flex items-center justify-center text-slate-700 hover:text-primary hover:border-primary transition-all pointer-events-auto hover:scale-110"
                        aria-label="Scroll tabs right">
                        <x-base.lucide class="w-4 h-4" icon="ChevronRight" />
                    </button>
                </div>

                <!-- Scrollable Tabs -->
                <div 
                    x-ref="tabsContainer"
                    class="overflow-x-auto scrollbar-hide"
                    style="scroll-behavior: smooth; -webkit-overflow-scrolling: touch;"
                    role="tablist"
                    @scroll="checkScroll()"
                >
                    <div class="flex border-b border-slate-200/60 bg-white whitespace-nowrap inline-flex">
                        @foreach($this->tabs as $key => $label)
                            @php
                                $icons = [
                                    'personal' => 'User',
                                    'licenses' => 'CreditCard',
                                    'medical' => 'Heart',
                                    'employment' => 'Briefcase',
                                    'training' => 'GraduationCap',
                                    'testing' => 'TestTube',
                                    'safety' => 'AlertTriangle',
                                    'hos' => 'Clock',
                                    'vehicles' => 'Truck',
                                    'documents' => 'FileText',
                                    'migration' => 'ArrowRightLeft',
                                ];
                                $icon = $icons[$key] ?? 'FileText';
                            @endphp
                            <button
                                type="button"
                                role="tab"
                                id="{{ $key }}-tab"
                                x-bind:aria-selected="activeTab === '{{ $key }}'"
                                x-bind:class="activeTab === '{{ $key }}' 
                                    ? 'border-b-2 border-primary text-primary bg-primary/5' 
                                    : 'border-b-2 border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300 hover:bg-slate-50'"
                                class="flex-shrink-0 flex items-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-3 transition-all duration-200 whitespace-nowrap relative group"
                                wire:click="setActiveTab('{{ $key }}')"
                            >
                                <x-base.lucide 
                                    class="h-4 w-4 flex-shrink-0 transition-transform group-active:scale-90" 
                                    icon="{{ $icon }}" 
                                />
                                <span class="text-sm sm:text-base font-medium">{{ $label }}</span>
                                <span 
                                    x-show="activeTab === '{{ $key }}'"
                                    x-transition
                                    class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary rounded-t"
                                ></span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="p-4 sm:p-5 md:p-6">
                <!-- Personal Info Tab -->
                <div x-show="activeTab === 'personal'">
                    @include('livewire.admin.driver.partials.archived-personal-info', ['data' => $this->personalInfo])
                </div>

                <!-- Licenses Tab -->
                <div x-show="activeTab === 'licenses'" style="display: none;">
                    @include('livewire.admin.driver.partials.archived-licenses', ['data' => $this->licenses])
                </div>

                <!-- Medical Tab -->
                <div x-show="activeTab === 'medical'" style="display: none;">
                    @include('livewire.admin.driver.partials.archived-medical', ['data' => $this->medical])
                </div>

                <!-- Employment Tab -->
                <div x-show="activeTab === 'employment'" style="display: none;">
                    @include('livewire.admin.driver.partials.archived-employment', ['data' => $this->employmentHistory])
                </div>

                <!-- Training Tab -->
                <div x-show="activeTab === 'training'" style="display: none;">
                    @include('livewire.admin.driver.partials.archived-training', ['data' => $this->training])
                </div>

                <!-- Testing Tab -->
                <div x-show="activeTab === 'testing'" style="display: none;">
                    @include('livewire.admin.driver.partials.archived-testing', ['data' => $this->testing])
                </div>

                <!-- Safety Tab -->
                <div x-show="activeTab === 'safety'" style="display: none;">
                    @include('livewire.admin.driver.partials.archived-safety', [
                        'accidents' => $this->accidents,
                        'convictions' => $this->convictions,
                        'inspections' => $this->inspections
                    ])
                </div>

                <!-- HOS Tab -->
                <div x-show="activeTab === 'hos'" style="display: none;">
                    @include('livewire.admin.driver.partials.archived-hos', ['data' => $this->hos])
                </div>

                <!-- Vehicles Tab -->
                <div x-show="activeTab === 'vehicles'" style="display: none;">
                    @include('livewire.admin.driver.partials.archived-vehicles', ['data' => $this->vehicleAssignments])
                </div>

                <!-- Documents Tab -->
                <div x-show="activeTab === 'documents'" style="display: none;">
                    @include('livewire.admin.driver.partials.archived-documents', ['documentsByCategory' => $this->documents, 'archive' => $archive])
                </div>

                <!-- Migration Tab -->
                <div x-show="activeTab === 'migration'" style="display: none;">
                    @include('livewire.admin.driver.partials.archived-migration', ['migrationInfo' => $this->migrationInfo])
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <style>
        /* Hide scrollbar but keep functionality */
        .scrollbar-hide {
            -ms-overflow-style: none !important;
            scrollbar-width: none !important;
        }
        .scrollbar-hide::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }
        
        /* Ensure tabs container allows horizontal scroll */
        [x-ref="tabsContainer"] {
            overflow-x: auto !important;
            overflow-y: hidden !important;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            max-width: 100%;
        }
        
        /* Tab active indicator animation */
        [role="tab"][aria-selected="true"] {
            position: relative;
        }
        
        /* Better touch targets on mobile */
        @media (max-width: 640px) {
            [role="tab"] {
                min-height: 44px;
                min-width: 44px;
            }
        }
        
        /* Tablet optimizations */
        @media (min-width: 640px) and (max-width: 1024px) {
            [role="tab"] {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }
        
        /* Focus styles for accessibility */
        [role="tab"]:focus-visible {
            outline: 2px solid theme('colors.primary.DEFAULT');
            outline-offset: -2px;
            border-radius: 0.25rem 0.25rem 0 0;
        }
    </style>
    @endpush
</div>
