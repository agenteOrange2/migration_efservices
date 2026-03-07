@extends('../themes/' . $activeTheme)
@section('title', 'Inactive Driver Details - ' . $archive->full_name)
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('carrier.dashboard')],
        ['label' => 'Drivers', 'url' => route('carrier.drivers.index')],
        ['label' => 'Inactive Drivers', 'url' => route('carrier.drivers.inactive.index')],
        ['label' => $archive->full_name, 'active' => true],
    ];
@endphp

@section('subcontent')
    <!-- Professional Breadcrumbs -->
    <div class="mb-6">
        <x-base.breadcrumb :links="$breadcrumbLinks" />
    </div>

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
                @php
                    // Get profile photo URL from snapshot
                    $profilePhotoUrl = $archive->driver_data_snapshot['profile_photo_url'] ?? null;
                @endphp
                
                @if($profilePhotoUrl)
                    <img 
                        src="{{ $profilePhotoUrl }}" 
                        alt="{{ $archive->full_name }}"
                        class="w-20 h-20 rounded-xl object-cover border-2 border-primary/20 shadow-sm"
                        onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'p-3 bg-primary/10 rounded-xl border border-primary/20\'><svg class=\'w-14 h-14 text-primary\' xmlns=\'http://www.w3.org/2000/svg\' width=\'24\' height=\'24\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><path d=\'M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2\'/><circle cx=\'12\' cy=\'7\' r=\'4\'/></svg></div>';"
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
                </div>
            </div>
            <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                <x-base.button 
                    as="a" 
                    href="{{ route('carrier.drivers.inactive.index') }}" 
                    variant="outline-secondary" 
                    class="w-full sm:w-auto gap-2">
                    <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                    Back to Inactive Drivers
                </x-base.button>
                <x-base.button 
                    as="a" 
                    href="{{ route('carrier.drivers.inactive.download', $archive) }}" 
                    variant="primary" 
                    class="w-full sm:w-auto gap-2">
                    <x-base.lucide class="w-4 h-4" icon="Download" />
                    Download Archive
                </x-base.button>
            </div>
        </div>
        
        <!-- Document Count Info -->
        <div class="mt-6 pt-6 border-t border-slate-200/60">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <x-base.lucide class="w-5 h-5 text-slate-400" icon="FileText" />
                    <span class="text-sm text-slate-600">
                        <strong>{{ $documentCount }}</strong> document{{ $documentCount !== 1 ? 's' : '' }} available in archive
                    </span>
                </div>
                <div class="text-xs text-slate-500">
                    Archived: {{ $archive->archived_at->format('M d, Y') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="alert alert-success flex items-center mb-6">
            <x-base.lucide class="w-6 h-6 mr-2" icon="CheckCircle" />
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger flex items-center mb-6">
            <x-base.lucide class="w-6 h-6 mr-2" icon="AlertCircle" />
            {{ session('error') }}
        </div>
    @endif

    <!-- Tabs Section -->
    <div class="box box--stacked">
        <x-base.tab.group>
            <!-- Mobile: Select Dropdown -->
            <div class="lg:hidden mb-4">
                <x-base.form-label for="mobile-tab-selector" class="sr-only">Select a section</x-base.form-label>
                <x-base.form-select 
                    id="mobile-tab-selector"
                    class="w-full"
                    onchange="switchMobileTab(this.value)"
                >
                    <option value="personal-info">👤 Personal Information</option>
                    <option value="employment">💼 Employment</option>
                    <option value="licenses">🪪 Licenses</option>
                    <option value="medical">❤️ Medical</option>
                    <option value="certifications">🎓 Certifications & Training</option>
                    <option value="testing">🧪 Testing</option>
                    <option value="accidents">⚠️ Accidents & Violations</option>
                    <option value="inspections">📋 Inspections</option>
                    <option value="vehicles">🚚 Vehicle Assignments</option>
                    <option value="documents">📄 Documents</option>
                </x-base.form-select>
            </div>

            <!-- Desktop: Horizontal Scrollable Tabs -->
            <div class="hidden lg:block overflow-hidden">
                <div class="relative">
                    <!-- Scroll Container -->
                    <div class="overflow-x-auto scrollbar-hide" id="tabs-scroll-container">
                        <x-base.tab.list
                            class="border-b border-slate-200/60 bg-white flex-nowrap whitespace-nowrap"
                            variant="boxed-tabs"
                        >
                            <x-base.tab
                                class="flex-shrink-0 [&[aria-selected='true']_button]:border-b-primary [&[aria-selected='true']_button]:text-primary"
                                id="personal-info-tab"
                                :fullWidth="false"
                                selected
                            >
                                <x-base.tab.button
                                    class="flex items-center gap-2 px-4 py-3 border-b-2 border-transparent hover:text-primary transition-colors whitespace-nowrap"
                                    as="button"
                                >
                                    <x-base.lucide class="h-4 w-4 flex-shrink-0" icon="User" />
                                    <span class="hidden xl:inline">Personal Information</span>
                                    <span class="xl:hidden">Personal</span>
                                </x-base.tab.button>
                            </x-base.tab>

                            <x-base.tab
                                class="flex-shrink-0 [&[aria-selected='true']_button]:border-b-primary [&[aria-selected='true']_button]:text-primary"
                                id="employment-tab"
                                :fullWidth="false"
                            >
                                <x-base.tab.button
                                    class="flex items-center gap-2 px-4 py-3 border-b-2 border-transparent hover:text-primary transition-colors whitespace-nowrap"
                                    as="button"
                                >
                                    <x-base.lucide class="h-4 w-4 flex-shrink-0" icon="Briefcase" />
                                    <span>Employment</span>
                                </x-base.tab.button>
                            </x-base.tab>

                            <x-base.tab
                                class="flex-shrink-0 [&[aria-selected='true']_button]:border-b-primary [&[aria-selected='true']_button]:text-primary"
                                id="licenses-tab"
                                :fullWidth="false"
                            >
                                <x-base.tab.button
                                    class="flex items-center gap-2 px-4 py-3 border-b-2 border-transparent hover:text-primary transition-colors whitespace-nowrap"
                                    as="button"
                                >
                                    <x-base.lucide class="h-4 w-4 flex-shrink-0" icon="CreditCard" />
                                    <span>Licenses</span>
                                </x-base.tab.button>
                            </x-base.tab>

                            <x-base.tab
                                class="flex-shrink-0 [&[aria-selected='true']_button]:border-b-primary [&[aria-selected='true']_button]:text-primary"
                                id="medical-tab"
                                :fullWidth="false"
                            >
                                <x-base.tab.button
                                    class="flex items-center gap-2 px-4 py-3 border-b-2 border-transparent hover:text-primary transition-colors whitespace-nowrap"
                                    as="button"
                                >
                                    <x-base.lucide class="h-4 w-4 flex-shrink-0" icon="Heart" />
                                    <span>Medical</span>
                                </x-base.tab.button>
                            </x-base.tab>

                            <x-base.tab
                                class="flex-shrink-0 [&[aria-selected='true']_button]:border-b-primary [&[aria-selected='true']_button]:text-primary"
                                id="certifications-tab"
                                :fullWidth="false"
                            >
                                <x-base.tab.button
                                    class="flex items-center gap-2 px-4 py-3 border-b-2 border-transparent hover:text-primary transition-colors whitespace-nowrap"
                                    as="button"
                                >
                                    <x-base.lucide class="h-4 w-4 flex-shrink-0" icon="Award" />
                                    <span class="hidden 2xl:inline">Certifications & Training</span>
                                    <span class="2xl:hidden">Training</span>
                                </x-base.tab.button>
                            </x-base.tab>

                            <x-base.tab
                                class="flex-shrink-0 [&[aria-selected='true']_button]:border-b-primary [&[aria-selected='true']_button]:text-primary"
                                id="testing-tab"
                                :fullWidth="false"
                            >
                                <x-base.tab.button
                                    class="flex items-center gap-2 px-4 py-3 border-b-2 border-transparent hover:text-primary transition-colors whitespace-nowrap"
                                    as="button"
                                >
                                    <x-base.lucide class="h-4 w-4 flex-shrink-0" icon="TestTube" />
                                    <span>Testing</span>
                                </x-base.tab.button>
                            </x-base.tab>

                            <x-base.tab
                                class="flex-shrink-0 [&[aria-selected='true']_button]:border-b-primary [&[aria-selected='true']_button]:text-primary"
                                id="accidents-tab"
                                :fullWidth="false"
                            >
                                <x-base.tab.button
                                    class="flex items-center gap-2 px-4 py-3 border-b-2 border-transparent hover:text-primary transition-colors whitespace-nowrap"
                                    as="button"
                                >
                                    <x-base.lucide class="h-4 w-4 flex-shrink-0" icon="AlertTriangle" />
                                    <span class="hidden 2xl:inline">Accidents & Violations</span>
                                    <span class="2xl:hidden">Accidents</span>
                                </x-base.tab.button>
                            </x-base.tab>

                            <x-base.tab
                                class="flex-shrink-0 [&[aria-selected='true']_button]:border-b-primary [&[aria-selected='true']_button]:text-primary"
                                id="inspections-tab"
                                :fullWidth="false"
                            >
                                <x-base.tab.button
                                    class="flex items-center gap-2 px-4 py-3 border-b-2 border-transparent hover:text-primary transition-colors whitespace-nowrap"
                                    as="button"
                                >
                                    <x-base.lucide class="h-4 w-4 flex-shrink-0" icon="ClipboardCheck" />
                                    <span>Inspections</span>
                                </x-base.tab.button>
                            </x-base.tab>

                            <x-base.tab
                                class="flex-shrink-0 [&[aria-selected='true']_button]:border-b-primary [&[aria-selected='true']_button]:text-primary"
                                id="vehicles-tab"
                                :fullWidth="false"
                            >
                                <x-base.tab.button
                                    class="flex items-center gap-2 px-4 py-3 border-b-2 border-transparent hover:text-primary transition-colors whitespace-nowrap"
                                    as="button"
                                >
                                    <x-base.lucide class="h-4 w-4 flex-shrink-0" icon="Truck" />
                                    <span class="hidden 2xl:inline">Vehicle Assignments</span>
                                    <span class="2xl:hidden">Vehicles</span>
                                </x-base.tab.button>
                            </x-base.tab>

                            <x-base.tab
                                class="flex-shrink-0 [&[aria-selected='true']_button]:border-b-primary [&[aria-selected='true']_button]:text-primary"
                                id="documents-tab"
                                :fullWidth="false"
                            >
                                <x-base.tab.button
                                    class="flex items-center gap-2 px-4 py-3 border-b-2 border-transparent hover:text-primary transition-colors whitespace-nowrap"
                                    as="button"
                                >
                                    <x-base.lucide class="h-4 w-4 flex-shrink-0" icon="FileText" />
                                    <span>Documents</span>
                                </x-base.tab.button>
                            </x-base.tab>
                        </x-base.tab.list>
                    </div>
                </div>
            </div>

            <x-base.tab.panels class="p-4 lg:p-6">
                <!-- Personal Information Tab -->
                <x-base.tab.panel id="personal-info" selected>
                    @include('carrier.drivers.inactive.partials.personal-info', ['data' => $archive->driver_data_snapshot])
                </x-base.tab.panel>

                <!-- Employment Information Tab -->
                <x-base.tab.panel id="employment">
                    @include('carrier.drivers.inactive.partials.employment', ['data' => $archive->employment_history_snapshot])
                </x-base.tab.panel>

                <!-- Licenses Tab -->
                <x-base.tab.panel id="licenses">
                    @include('carrier.drivers.inactive.partials.licenses', ['data' => $archive->licenses_snapshot])
                </x-base.tab.panel>

                <!-- Medical Tab -->
                <x-base.tab.panel id="medical">
                    @include('carrier.drivers.inactive.partials.medical', ['data' => $archive->medical_snapshot])
                </x-base.tab.panel>

                <!-- Certifications & Training Tab -->
                <x-base.tab.panel id="certifications">
                    @include('carrier.drivers.inactive.partials.certifications', [
                        'certifications' => $archive->certifications_snapshot,
                        'training' => $archive->training_snapshot
                    ])
                </x-base.tab.panel>

                <!-- Testing Tab -->
                <x-base.tab.panel id="testing">
                    @include('carrier.drivers.inactive.partials.testing', ['data' => $archive->testing_snapshot])
                </x-base.tab.panel>

                <!-- Accidents & Violations Tab -->
                <x-base.tab.panel id="accidents">
                    @include('carrier.drivers.inactive.partials.accidents', [
                        'accidents' => $archive->accidents_snapshot,
                        'convictions' => $archive->convictions_snapshot
                    ])
                </x-base.tab.panel>

                <!-- Inspections Tab -->
                <x-base.tab.panel id="inspections">
                    @include('carrier.drivers.inactive.partials.inspections', ['data' => $archive->inspections_snapshot])
                </x-base.tab.panel>

                <!-- Vehicle Assignments Tab -->
                <x-base.tab.panel id="vehicles">
                    @include('carrier.drivers.inactive.partials.vehicles', ['data' => $archive->vehicle_assignments_snapshot])
                </x-base.tab.panel>

                <!-- Documents Tab -->
                <x-base.tab.panel id="documents">
                    @include('carrier.drivers.inactive.partials.documents', ['documentsByCategory' => $documentsByCategory])
                </x-base.tab.panel>
            </x-base.tab.panels>
        </x-base.tab.group>
    </div>

    @push('scripts')
    <script>
        // Mobile tab switcher - works with tab component system
        function switchMobileTab(tabId) {
            // Find the corresponding desktop tab button
            const tabButton = document.querySelector(`#${tabId}-tab button, #${tabId}-tab [role="tab"]`);
            
            if (tabButton) {
                // Trigger click on the desktop tab button to use the existing tab system
                tabButton.click();
            } else {
                // Fallback: manually show/hide panels
                document.querySelectorAll('[role="tabpanel"]').forEach(panel => {
                    const isSelected = panel.id === tabId;
                    panel.style.display = isSelected ? 'block' : 'none';
                    panel.setAttribute('aria-hidden', !isSelected);
                });
                
                // Update all tab buttons
                document.querySelectorAll('[role="tab"]').forEach(btn => {
                    const isSelected = btn.id === `${tabId}-tab`;
                    btn.setAttribute('aria-selected', isSelected);
                    btn.classList.toggle('active', isSelected);
                });
            }
            
            // Update mobile select value
            const mobileSelect = document.getElementById('mobile-tab-selector');
            if (mobileSelect) {
                mobileSelect.value = tabId;
            }
            
            // Scroll to top of tab content smoothly
            setTimeout(() => {
                const selectedPanel = document.getElementById(tabId);
                if (selectedPanel) {
                    selectedPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 100);
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Sync desktop tabs with mobile selector when tabs change
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'aria-selected') {
                        const tab = mutation.target;
                        if (tab.getAttribute('aria-selected') === 'true') {
                            const tabId = tab.id.replace('-tab', '');
                            const mobileSelect = document.getElementById('mobile-tab-selector');
                            if (mobileSelect && mobileSelect.value !== tabId) {
                                mobileSelect.value = tabId;
                            }
                        }
                    }
                });
            });

            // Observe all tab buttons
            document.querySelectorAll('[role="tab"]').forEach(tab => {
                observer.observe(tab, { attributes: true, attributeFilter: ['aria-selected'] });
            });

            // Set initial mobile select value
            const activeTab = document.querySelector('[role="tab"][aria-selected="true"]');
            if (activeTab) {
                const activeTabId = activeTab.id.replace('-tab', '');
                const mobileSelect = document.getElementById('mobile-tab-selector');
                if (mobileSelect) {
                    mobileSelect.value = activeTabId;
                }
            }
        });
    </script>
    <style>
        /* Hide scrollbar but keep functionality */
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        
        /* Smooth scrolling for tabs */
        #tabs-scroll-container {
            scroll-behavior: smooth;
            max-width: 100%;
        }
        
        /* Ensure tabs container doesn't overflow parent */
        #tabs-scroll-container [role="tablist"] {
            display: inline-flex;
            flex-wrap: nowrap;
        }
        
        /* Better touch scrolling on mobile */
        @media (max-width: 1024px) {
            #tabs-scroll-container {
                -webkit-overflow-scrolling: touch;
            }
        }
        
        /* Mobile tab panel visibility */
        @media (max-width: 1023px) {
            [role="tabpanel"] {
                display: none;
            }
            [role="tabpanel"][aria-hidden="false"] {
                display: block;
            }
        }
    </style>
    @endpush
@endsection
