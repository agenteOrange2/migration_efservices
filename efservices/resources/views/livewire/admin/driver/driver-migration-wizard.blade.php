<div>
    <!-- Professional Header -->
    <div class="box box--stacked p-8 mb-6">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Driver Migration</h1>
                    <p class="text-slate-600">Transfer a driver to a different carrier while preserving historical
                        records</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Steps -->
    <div class="box box--stacked mb-6">
        <div class="p-6">
            <div class="flex items-center justify-between">
                @foreach ([1 => 'Select Carrier', 2 => 'Validation', 3 => 'Confirm', 4 => 'Complete'] as $step => $label)
                    <div class="flex items-center {{ $step < 4 ? 'flex-1' : '' }}">
                        <div class="flex flex-col items-center">
                            <div
                                class="flex items-center justify-center w-12 h-12 rounded-full transition-all duration-300
                                {{ $currentStep >= $step ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'bg-slate-100 text-slate-400' }}">
                                @if ($currentStep > $step)
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                @else
                                    <span class="text-sm font-semibold">{{ $step }}</span>
                                @endif
                            </div>
                            <span
                                class="mt-2 text-xs font-medium {{ $currentStep >= $step ? 'text-primary' : 'text-slate-400' }}">
                                {{ $label }}
                            </span>
                        </div>
                        @if ($step < 4)
                            <div
                                class="flex-1 h-1 mx-4 rounded-full transition-all duration-300
                                {{ $currentStep > $step ? 'bg-primary' : 'bg-slate-200' }}">
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Driver Info Card -->
    @if ($driver)
        <div class="box box--stacked mb-6">
            <div class="p-5">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        @if ($driver->getFirstMediaUrl('profile_photo_driver'))
                            <img class="h-16 w-16 rounded-xl object-cover border-2 border-slate-200"
                                src="{{ $driver->getFirstMediaUrl('profile_photo_driver') }}"
                                alt="{{ $driver->full_name }}">
                        @else
                            <div
                                class="h-16 w-16 rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center border-2 border-slate-200">
                                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor"
                                    stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <circle cx="12" cy="7" r="4" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-slate-900">{{ $driver->full_name }}</h3>
                        <p class="text-sm text-slate-600 mt-1">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v0M9 13v0M9 17v0"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Current Carrier: <span
                                class="font-medium text-slate-800">{{ $driver->carrier->name }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Error Messages -->
    @if (!empty($validationErrors))
        <div class="box box--stacked mb-6 border-l-4 border-red-500">
            <div class="p-5 bg-red-50">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor"
                        stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M12 8v4M12 16h.01" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-red-800 mb-2">Validation Errors</h3>
                        <ul class="text-sm text-red-700 space-y-1">
                            @foreach ($validationErrors as $error)
                                <li class="flex items-start gap-2">
                                    <span class="text-red-500 mt-1">•</span>
                                    <span>{{ $error }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Step Content -->
    <div class="box box--stacked">
        @if ($currentStep === 1)
            <!-- Step 1: Select Target Carrier -->
            <div class="p-6">
                <div class="flex items-center gap-3 mb-6">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v0M9 13v0M9 17v0" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                    <h2 class="text-lg font-semibold text-slate-800">Select Target Carrier</h2>
                </div>

                <!-- Search -->
                <div class="mb-6">
                    <x-base.form-label for="carrierSearch">Search Carriers</x-base.form-label>
                    <x-base.form-input id="carrierSearch" type="text" wire:model.live.debounce.300ms="carrierSearch"
                        placeholder="Search carriers by name or DOT number..." class="w-full" />
                </div>

                <!-- Carrier List -->
                <div class="space-y-3 max-h-96 overflow-y-auto mb-6">
                    @forelse($this->availableCarriers as $carrier)
                        <div wire:click="selectTargetCarrier({{ $carrier->id }})"
                            class="p-4 border rounded-lg cursor-pointer transition-all duration-200
                                {{ $targetCarrierId === $carrier->id
                                    ? 'border-primary bg-primary/5 shadow-md'
                                    : 'border-slate-200 hover:border-primary/50 hover:bg-slate-50' }}">
                            <div class="flex justify-between items-center">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-slate-900 mb-1">{{ $carrier->name }}</h3>
                                    <p class="text-sm text-slate-600">
                                        <span class="font-medium">DOT:</span> {{ $carrier->dot_number ?? 'N/A' }} |
                                        <span class="font-medium">MC:</span> {{ $carrier->mc_number ?? 'N/A' }}
                                    </p>
                                </div>
                                <div class="ml-4">
                                    <x-base.badge variant="success" class="text-xs">
                                        {{ $carrier->userDrivers()->active()->count() }} /
                                        {{ $carrier->membership->max_drivers ?? '∞' }} drivers
                                    </x-base.badge>
                                </div>
                            </div>
                            @if ($targetCarrierId === $carrier->id)
                                <div class="mt-3 pt-3 border-t border-primary/20">
                                    <div class="flex items-center gap-2 text-primary text-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <path d="M22 4L12 14.01l-3-3" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>
                                        <span class="font-medium">Selected</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-12 bg-slate-50 rounded-lg">
                            <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor"
                                stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v0M9 13v0M9 17v0"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <p class="text-slate-500 font-medium">No available carriers found</p>
                            <p class="text-slate-400 text-sm mt-1">Try adjusting your search</p>
                        </div>
                    @endforelse
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                    <x-base.button wire:click="validateMigration" variant="primary" class="gap-2" :disabled="!$targetCarrierId">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 12h14M12 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Continue
                    </x-base.button>
                </div>
            </div>
        @elseif($currentStep === 2)
            <!-- Step 2: Validation & Warnings -->
            <div class="p-6">
                <div class="flex items-center gap-3 mb-6">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path d="M9 12l2 2 4-4" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <h2 class="text-lg font-semibold text-slate-800">Validation Results</h2>
                </div>

                @if (!empty($validationWarnings))
                    <div class="mb-6 p-5 bg-warning/10 border border-warning/20 rounded-lg">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-warning mt-0.5 flex-shrink-0" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"
                                    stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M12 9v4M12 17h.01" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex-1">
                                <h3 class="text-sm font-semibold text-warning mb-3">Warnings</h3>
                                <ul class="text-sm text-warning space-y-2">
                                    @foreach ($validationWarnings as $warning)
                                        <li class="flex items-start gap-2">
                                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none"
                                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <circle cx="12" cy="12" r="10" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                                <path d="M12 8v4M12 16h.01" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                            <span>{{ $warning }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6 p-4 bg-slate-50 rounded-lg border border-slate-200">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <x-base.form-check.input type="checkbox" wire:model.live="acknowledgedWarnings"
                                class="mt-1" />
                            <span class="text-sm text-slate-700">
                                I acknowledge these warnings and wish to proceed with the migration.
                            </span>
                        </label>
                    </div>
                @else
                    <div class="mb-6 p-5 bg-success/10 border border-success/20 rounded-lg">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path d="M22 4L12 14.01l-3-3" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <p class="text-sm font-medium text-success">
                                All validations passed. You can proceed with the migration.
                            </p>
                        </div>
                    </div>
                @endif

                <!-- Actions -->
                <div class="flex justify-between gap-3 pt-4 border-t border-slate-200">
                    <x-base.button wire:click="goToStep(1)" variant="outline-secondary" class="gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Back
                    </x-base.button>
                    <x-base.button wire:click="proceedToConfirmation" variant="primary" class="gap-2"
                        :disabled="!empty($validationWarnings) && !$acknowledgedWarnings">
                        Continue
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 12h14M12 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </x-base.button>
                </div>
            </div>
        @elseif($currentStep === 3)
            <!-- Step 3: Confirmation -->
            <div class="p-6">
                <div class="flex items-center gap-3 mb-6">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M9 15l2 2 4-4" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <h2 class="text-lg font-semibold text-slate-800">Confirm Migration</h2>
                </div>

                <!-- Migration Summary -->
                <div class="mb-6 p-5 bg-gradient-to-br from-slate-50 to-slate-100 rounded-lg border border-slate-200">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4 uppercase tracking-wide">Migration Summary
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-start gap-3 p-3 bg-white rounded-lg">
                            <svg class="w-5 h-5 text-slate-400 mt-0.5 flex-shrink-0" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <circle cx="12" cy="7" r="4" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            <div>
                                <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Driver</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $driver->full_name }}</dd>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-3 bg-white rounded-lg">
                            <svg class="w-5 h-5 text-slate-400 mt-0.5 flex-shrink-0" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div>
                                <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">From Carrier
                                </dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $driver->carrier->name }}
                                </dd>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-3 bg-white rounded-lg">
                            <svg class="w-5 h-5 text-primary mt-0.5 flex-shrink-0" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v0M9 13v0M9 17v0"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div>
                                <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">To Carrier</dt>
                                <dd class="mt-1 text-sm font-semibold text-primary">{{ $this->targetCarrier?->name }}
                                </dd>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-3 bg-white rounded-lg">
                            <svg class="w-5 h-5 text-slate-400 mt-0.5 flex-shrink-0" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M16 2v4M8 2v4M3 10h18" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div>
                                <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Migration Date
                                </dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ now()->format('F j, Y') }}
                                </dd>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reason & Notes -->
                <div class="space-y-4 mb-6">
                    <div>
                        <x-base.form-label for="reason">Reason for Migration (Optional)</x-base.form-label>
                        <x-base.form-input id="reason" type="text" wire:model="reason"
                            placeholder="e.g., Driver requested transfer, Company restructuring" class="w-full" />
                    </div>
                    <div>
                        <x-base.form-label for="notes">Additional Notes (Optional)</x-base.form-label>
                        <textarea id="notes" wire:model="notes" rows="3"
                            placeholder="Any additional information about this migration..."
                            class="w-full border-slate-200 focus:border-primary focus:ring-primary rounded-md"></textarea>
                    </div>
                </div>

                <!-- Warning -->
                <div class="mb-6 p-5 bg-warning/10 border border-warning/20 rounded-lg">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-warning mt-0.5 flex-shrink-0" fill="none" stroke="currentColor"
                            stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"
                                stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M12 9v4M12 17h.01" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-warning mb-1">Important Notice</p>
                            <p class="text-sm text-warning/80">
                                This action will archive the driver's records in the current carrier and transfer them
                                to the new carrier.
                                You will have 24 hours to rollback this migration if needed.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-between gap-3 pt-4 border-t border-slate-200">
                    <x-base.button wire:click="goToStep(2)" variant="outline-secondary" class="gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Back
                    </x-base.button>
                    <x-base.button wire:click="confirmMigration" variant="primary" class="gap-2"
                        wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span wire:loading.remove wire:target="confirmMigration">
                            Confirm Migration
                        </span>
                        <span wire:loading wire:target="confirmMigration" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Processing...
                        </span>
                    </x-base.button>
                </div>
            </div>
        @elseif($currentStep === 4)
            <!-- Step 4: Complete -->
            <div class="p-6 text-center">
                <div
                    class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-success/10 border-4 border-success/20 mb-6">
                    <svg class="w-10 h-10 text-success" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M22 4L12 14.01l-3-3" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-slate-900 mb-2">Migration Completed!</h2>
                <p class="text-slate-600 mb-8 max-w-md mx-auto">
                    The driver has been successfully transferred to the new carrier. All historical records have been
                    preserved and archived.
                </p>

                @if ($canRollback)
                    <div class="mb-8 p-5 bg-primary/10 border border-primary/20 rounded-lg max-w-md mx-auto">
                        <div class="flex items-start gap-3 mb-3">
                            <svg class="w-5 h-5 text-primary mt-0.5 flex-shrink-0" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path d="M12 16v-4M12 8h.01" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="text-left">
                                <p class="text-sm font-medium text-primary mb-1">Rollback Available</p>
                                <p class="text-sm text-primary/80">
                                    You can rollback this migration within the next 24 hours if needed.
                                </p>
                            </div>
                        </div>
                        <x-base.button wire:click="rollbackMigration" variant="outline-primary" size="sm"
                            wire:confirm="Are you sure you want to rollback this migration?">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 4v6h6M23 20v-6h-6" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Rollback Migration
                        </x-base.button>
                    </div>
                @endif

                <div class="flex justify-center gap-3">
                    <x-base.button as="a" href="{{ route('admin.drivers.index') }}"
                        variant="outline-secondary" class="gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Back to Drivers
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('admin.drivers.archived.index') }}"
                        variant="primary" class="gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M5 8h14M5 8a2 2 0 1 0 0-4H3a2 2 0 0 0 0 4h2zM5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8M9 12h6"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        View Archived Drivers
                    </x-base.button>
                </div>
            </div>
        @endif
    </div>
</div>
