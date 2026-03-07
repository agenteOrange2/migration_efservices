@extends('../themes/' . $activeTheme)
@section('title', 'Assign Training to Drivers')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('carrier.dashboard')],
        ['label' => 'Driver Trainings Management', 'url' => route('carrier.trainings.index')],
        ['label' => 'Assign Training', 'url' => route('carrier.trainings.assign.select')],
        ['label' => 'Assignment Form', 'active' => true],
    ];
@endphp

@section('subcontent')
    <div class="py-5">
        <!-- Toast Notifications -->
        <x-toast-notifications />

        <!-- Page Header -->
        <div class="flex flex-col gap-3 sm:gap-4 sm:flex-row sm:items-center justify-between mt-6 sm:mt-8">
            <h2 class="text-lg sm:text-xl font-medium">
                Assign Training to Drivers
            </h2>
            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                <x-base.button as="a" href="{{ route('carrier.trainings.assign.select') }}" variant="outline-secondary" class="w-full sm:w-auto">
                    <x-base.lucide class="w-4 h-4 mr-1" icon="arrow-left" />
                    <span class="hidden sm:inline">Back to Training Selection</span>
                    <span class="sm:hidden">Back</span>
                </x-base.button>
            </div>
        </div>

        <!-- Training Information Card -->
        <div class="box box--stacked mt-4 sm:mt-5">
            <div class="box-body p-4 sm:p-5">
                <h4 class="text-base sm:text-lg font-semibold mb-3 sm:mb-4 text-gray-800 border-b pb-2 flex items-center gap-2">
                    <x-base.lucide class="w-4 h-4 sm:w-5 sm:h-5 text-primary" icon="book-open" />
                    Training Information
                </h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    <!-- Title -->
                    <div class="md:col-span-2">
                        <label class="text-xs sm:text-sm font-medium text-gray-600">Title</label>
                        <p class="text-sm sm:text-base text-gray-900 mt-1 font-semibold break-words">{{ $training->title }}</p>
                    </div>

                    <!-- Description -->
                    <div class="md:col-span-2">
                        <label class="text-xs sm:text-sm font-medium text-gray-600">Description</label>
                        <p class="text-sm sm:text-base text-gray-900 mt-1 whitespace-pre-wrap break-words">{{ $training->description }}</p>
                    </div>

                    <!-- Content Type -->
                    <div>
                        <label class="text-xs sm:text-sm font-medium text-gray-600">Content Type</label>
                        <p class="text-sm sm:text-base text-gray-900 mt-1">
                            <span class="inline-flex items-center px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm font-medium
                                @if($training->content_type === 'file') bg-blue-100 text-blue-800
                                @elseif($training->content_type === 'video') bg-purple-100 text-purple-800
                                @else bg-green-100 text-green-800
                                @endif">
                                @if($training->content_type === 'file')
                                    <x-base.lucide class="w-3 h-3 sm:w-4 sm:h-4 mr-1" icon="file-text" />
                                @elseif($training->content_type === 'video')
                                    <x-base.lucide class="w-3 h-3 sm:w-4 sm:h-4 mr-1" icon="video" />
                                @else
                                    <x-base.lucide class="w-3 h-3 sm:w-4 sm:h-4 mr-1" icon="link" />
                                @endif
                                {{ ucfirst($training->content_type) }}
                            </span>
                        </p>
                    </div>

                    <!-- Created By -->
                    <div>
                        <label class="text-xs sm:text-sm font-medium text-gray-600">Created By</label>
                        <p class="text-sm sm:text-base text-gray-900 mt-1 break-words">{{ $training->creator->name ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignment Form -->
        <div class="box box--stacked mt-4 sm:mt-5">
            <div class="box-body p-4 sm:p-5">
                <h4 class="text-base sm:text-lg font-semibold mb-3 sm:mb-4 text-gray-800 border-b pb-2 flex items-center gap-2">
                    <x-base.lucide class="w-4 h-4 sm:w-5 sm:h-5 text-primary" icon="user-plus" />
                    Assignment Details
                </h4>

                <form action="{{ route('carrier.trainings.assign', $training->id) }}" method="POST" id="assignmentForm" data-assignment-form="true">
                    @csrf

                    <!-- Driver Selection -->
                    <div class="mb-4 sm:mb-6">
                        <x-base.form-label for="driver_ids" class="form-label required text-sm font-medium">
                            Select Drivers
                        </x-base.form-label>
                        <p class="text-xs sm:text-sm text-slate-500 mb-3">
                            Select one or more drivers to assign this training to. You can use the search box to filter drivers.
                        </p>

                        <!-- Search Box -->
                        <div class="mb-3">
                            <div class="relative">
                                <x-base.lucide class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" icon="search" />
                                <input 
                                    type="text" 
                                    id="driverSearch" 
                                    class="form-control pl-10 text-sm" 
                                    placeholder="Search drivers by name or email..."
                                />
                            </div>
                        </div>

                        <!-- Drivers List with Checkboxes -->
                        <div class="border rounded-lg max-h-80 sm:max-h-96 overflow-y-auto bg-gray-50 p-3 sm:p-4" id="driversContainer">
                            @if($drivers->count() > 0)
                                <!-- Select All Option -->
                                <div class="mb-3 pb-3 border-b border-gray-200">
                                    <label class="flex items-center p-2 sm:p-3 bg-white rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                        <input 
                                            type="checkbox" 
                                            id="selectAll" 
                                            class="form-checkbox h-4 w-4 sm:h-5 sm:w-5 text-primary border-gray-300 rounded"
                                        />
                                        <span class="ml-2 sm:ml-3 font-semibold text-gray-900 text-sm sm:text-base">Select All Drivers</span>
                                    </label>
                                </div>

                                <!-- Driver Checkboxes -->
                                <div class="space-y-1 sm:space-y-2" id="driversList">
                                    @foreach($drivers as $driver)
                                        <label class="flex items-center p-2 sm:p-3 bg-white rounded-lg hover:bg-gray-50 cursor-pointer transition-colors driver-item" 
                                               data-driver-name="{{ strtolower($driver->user->name ?? '') }}"
                                               data-driver-email="{{ strtolower($driver->user->email ?? '') }}">
                                            <input 
                                                type="checkbox" 
                                                name="driver_ids[]" 
                                                value="{{ $driver->id }}" 
                                                class="form-checkbox h-4 w-4 sm:h-5 sm:w-5 text-primary border-gray-300 rounded driver-checkbox"
                                                {{ in_array($driver->id, old('driver_ids', [])) ? 'checked' : '' }}
                                            />
                                            <div class="ml-2 sm:ml-3 flex-1 min-w-0">
                                                <div class="font-medium text-gray-900 text-sm sm:text-base truncate">
                                                    {{ $driver->user->name ?? 'N/A' }}
                                                </div>
                                                @if($driver->user->email)
                                                    <div class="text-xs sm:text-sm text-gray-500 truncate">
                                                        {{ $driver->user->email }}
                                                    </div>
                                                @endif
                                            </div>
                                            <x-base.lucide class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 flex-shrink-0" icon="user" />
                                        </label>
                                    @endforeach
                                </div>

                                <!-- No Results Message (hidden by default) -->
                                <div id="noResultsMessage" class="text-center py-8 text-gray-500 hidden">
                                    <x-base.lucide class="w-12 h-12 mx-auto mb-3 text-gray-400" icon="search-x" />
                                    <p>No drivers found matching your search</p>
                                </div>
                            @else
                                <!-- Empty State -->
                                <div class="text-center py-8 text-gray-500">
                                    <x-base.lucide class="w-12 h-12 mx-auto mb-3 text-gray-400" icon="users-x" />
                                    <p>No active drivers available</p>
                                    <p class="text-sm mt-2">Please add drivers to your carrier before assigning trainings.</p>
                                </div>
                            @endif
                        </div>

                        @error('driver_ids')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                        @error('driver_ids.*')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror

                        <!-- Selected Count -->
                        <div class="mt-3 text-xs sm:text-sm text-gray-600">
                            <span id="selectedCount">0</span> driver(s) selected
                        </div>
                    </div>

                    <!-- Due Date -->
                    <div class="mb-4 sm:mb-6">
                        <x-base.form-label for="due_date" class="form-label required text-sm font-medium">
                            Due Date
                        </x-base.form-label>
                        <x-base.form-input 
                            type="date" 
                            id="due_date" 
                            name="due_date" 
                            class="form-control @error('due_date') is-invalid @enderror mt-1" 
                            value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}" 
                            min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                            required 
                        />
                        <p class="text-xs sm:text-sm text-slate-500 mt-1">
                            The date by which drivers should complete this training
                        </p>
                        @error('due_date')
                            <div class="text-danger mt-2 text-sm">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Optional Notes -->
                    <div class="mb-4 sm:mb-6">
                        <x-base.form-label for="notes" class="form-label text-sm font-medium">
                            Notes (Optional)
                        </x-base.form-label>
                        <textarea 
                            id="notes" 
                            name="notes" 
                            class="form-control @error('notes') is-invalid @enderror mt-1" 
                            rows="3" 
                            maxlength="1000"
                            placeholder="Add any additional notes or instructions for the drivers..."
                        >{{ old('notes') }}</textarea>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1">
                            Maximum 1000 characters
                        </p>
                        @error('notes')
                            <div class="text-danger mt-2 text-sm">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Form Actions -->
                    <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3 mt-6 sm:mt-8 pt-4 sm:pt-5 border-t">
                        <x-base.button as="a" href="{{ route('carrier.trainings.assign.select') }}" variant="outline-secondary" class="w-full sm:w-auto order-2 sm:order-1">
                            <x-base.lucide class="w-4 h-4 mr-1" icon="x" />
                            Cancel
                        </x-base.button>
                        <x-base.button type="submit" variant="primary" id="submitBtn" class="w-full sm:w-auto order-1 sm:order-2">
                            <x-base.lucide class="w-4 h-4 mr-1" icon="user-plus" />
                            Assign Training
                        </x-base.button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@pushOnce('scripts')
    @vite('resources/js/carrier-trainings-notifications.js')
@endPushOnce

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elements
        const selectAllCheckbox = document.getElementById('selectAll');
        const driverCheckboxes = document.querySelectorAll('.driver-checkbox');
        const selectedCountSpan = document.getElementById('selectedCount');
        const driverSearchInput = document.getElementById('driverSearch');
        const driverItems = document.querySelectorAll('.driver-item');
        const noResultsMessage = document.getElementById('noResultsMessage');
        const submitBtn = document.getElementById('submitBtn');
        const assignmentForm = document.getElementById('assignmentForm');

        // Update selected count
        function updateSelectedCount() {
            const checkedCount = document.querySelectorAll('.driver-checkbox:checked').length;
            selectedCountSpan.textContent = checkedCount;
            
            // Update select all checkbox state
            if (selectAllCheckbox) {
                const visibleCheckboxes = Array.from(driverCheckboxes).filter(cb => {
                    const item = cb.closest('.driver-item');
                    return item && !item.classList.contains('hidden');
                });
                
                const visibleCheckedCount = visibleCheckboxes.filter(cb => cb.checked).length;
                selectAllCheckbox.checked = visibleCheckboxes.length > 0 && visibleCheckedCount === visibleCheckboxes.length;
                selectAllCheckbox.indeterminate = visibleCheckedCount > 0 && visibleCheckedCount < visibleCheckboxes.length;
            }
        }

        // Select all functionality
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const visibleCheckboxes = Array.from(driverCheckboxes).filter(cb => {
                    const item = cb.closest('.driver-item');
                    return item && !item.classList.contains('hidden');
                });
                
                visibleCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateSelectedCount();
            });
        }

        // Individual checkbox change
        driverCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedCount);
        });

        // Search functionality
        if (driverSearchInput) {
            driverSearchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                let visibleCount = 0;

                driverItems.forEach(item => {
                    const driverName = item.getAttribute('data-driver-name') || '';
                    const driverEmail = item.getAttribute('data-driver-email') || '';
                    
                    if (driverName.includes(searchTerm) || driverEmail.includes(searchTerm)) {
                        item.classList.remove('hidden');
                        visibleCount++;
                    } else {
                        item.classList.add('hidden');
                    }
                });

                // Show/hide no results message
                if (noResultsMessage) {
                    if (visibleCount === 0 && searchTerm !== '') {
                        noResultsMessage.classList.remove('hidden');
                    } else {
                        noResultsMessage.classList.add('hidden');
                    }
                }

                // Update select all checkbox state after search
                updateSelectedCount();
            });
        }

        // Form validation
        if (assignmentForm) {
            assignmentForm.addEventListener('submit', function(e) {
                const checkedCount = document.querySelectorAll('.driver-checkbox:checked').length;
                
                if (checkedCount === 0) {
                    e.preventDefault();
                    alert('Please select at least one driver to assign this training to.');
                    return false;
                }

                // Disable submit button to prevent double submission
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="inline-block animate-spin mr-2">⏳</span> Assigning...';
                }
            });
        }

        // Initial count update
        updateSelectedCount();
    });
</script>
@endpush

@endsection
