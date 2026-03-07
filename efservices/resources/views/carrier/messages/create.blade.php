@extends('../themes/' . $activeTheme)
@section('title', 'Create New Message')
@php
$breadcrumbLinks = [
['label' => 'App', 'url' => route('carrier.dashboard')],
['label' => 'Messages', 'url' => route('carrier.messages.index')],
['label' => 'Create Message', 'active' => true],
];
@endphp

@section('subcontent')
<div>
    <!-- Flash Messages -->
    @if (session()->has('success'))
    <div class="alert alert-success flex items-center mb-5">
        <x-base.lucide class="w-6 h-6 mr-2" icon="check-circle" />
        {{ session('success') }}
    </div>
    @endif

    @if (session()->has('error'))
    <div class="alert alert-danger flex items-center mb-5">
        <x-base.lucide class="w-6 h-6 mr-2" icon="alert-circle" />
        {{ session('error') }}
    </div>
    @endif

    <!-- Validation Errors -->
    @if ($errors->any())
    <div class="alert alert-danger mb-5">
        <div class="flex items-start">
            <x-base.lucide class="w-6 h-6 mr-2 flex-shrink-0" icon="alert-circle" />
            <div class="flex-1">
                <strong class="block mb-2">Please fix the following errors:</strong>
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center justify-between mt-8">
        <div>
            <h2 class="text-lg font-medium">Create New Message</h2>
            <div class="text-slate-500 text-sm mt-1">
                Send a message to your drivers or to admin
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            <x-base.button as="a" href="{{ route('carrier.messages.index') }}" variant="outline-secondary" class="w-full sm:w-auto">
                <x-base.lucide class="w-4 h-4 mr-2" icon="arrow-left" />
                Back to Messages
            </x-base.button>
        </div>
    </div>

    <!-- Message Form -->
    <form action="{{ route('carrier.messages.store') }}" method="POST" class="mt-5">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <div class="box box--stacked">
                    <div class="box-header p-5 border-b">
                        <h3 class="box-title">Message Content</h3>
                    </div>
                    <div class="box-body p-5 space-y-5">
                        <!-- Subject -->
                        <div>
                            <x-base.form-label for="subject">Subject <span class="text-red-500">*</span></x-base.form-label>
                            <x-base.form-input 
                                type="text" 
                                name="subject" 
                                id="subject" 
                                value="{{ old('subject') }}" 
                                placeholder="Enter message subject" 
                                required 
                            />
                            @error('subject')
                                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Message -->
                        <div>
                            <x-base.form-label for="message">Message <span class="text-red-500">*</span></x-base.form-label>
                            <x-base.form-textarea 
                                name="message" 
                                id="message" 
                                rows="8" 
                                placeholder="Enter your message here..." 
                                required
                            >{{ old('message') }}</x-base.form-textarea>
                            @error('message')
                                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Priority -->
                        <div>
                            <x-base.form-label for="priority">Priority <span class="text-red-500">*</span></x-base.form-label>
                            <x-base.form-select name="priority" id="priority" required>
                                <option value="normal" {{ old('priority') == 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                                <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                            </x-base.form-select>
                            @error('priority')
                                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Recipients Section -->
                <div class="box box--stacked mt-5">
                    <div class="box-header p-5 border-b">
                        <h3 class="box-title">Recipients</h3>
                    </div>
                    <div class="box-body p-5 space-y-5">
                        <!-- Recipient Type -->
                        <div>
                            <x-base.form-label for="recipient_type">Send To <span class="text-red-500">*</span></x-base.form-label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-2">
                                <!-- All My Drivers -->
                                <div class="recipient-card">
                                    <input 
                                        type="radio" 
                                        name="recipient_type" 
                                        value="all_my_drivers" 
                                        id="all_my_drivers"
                                        class="hidden recipient-radio"
                                        {{ old('recipient_type') == 'all_my_drivers' ? 'checked' : '' }}
                                    />
                                    <label for="all_my_drivers" class="recipient-label cursor-pointer block p-4 border-2 border-slate-200 rounded-lg hover:border-blue-300 transition-all">
                                        <div class="text-center">
                                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mb-2 mx-auto">
                                                <x-base.lucide class="w-5 h-5 text-blue-600" icon="users" />
                                            </div>
                                            <h4 class="font-semibold text-sm">All My Drivers</h4>
                                            <p class="text-xs text-slate-500 mt-1">{{ $drivers->count() }} drivers</p>
                                        </div>
                                    </label>
                                </div>

                                <!-- Specific Drivers -->
                                <div class="recipient-card">
                                    <input 
                                        type="radio" 
                                        name="recipient_type" 
                                        value="specific_drivers" 
                                        id="specific_drivers"
                                        class="hidden recipient-radio"
                                        {{ old('recipient_type') == 'specific_drivers' ? 'checked' : '' }}
                                    />
                                    <label for="specific_drivers" class="recipient-label cursor-pointer block p-4 border-2 border-slate-200 rounded-lg hover:border-green-300 transition-all">
                                        <div class="text-center">
                                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mb-2 mx-auto">
                                                <x-base.lucide class="w-5 h-5 text-green-600" icon="user-check" />
                                            </div>
                                            <h4 class="font-semibold text-sm">Specific Drivers</h4>
                                            <p class="text-xs text-slate-500 mt-1">Choose drivers</p>
                                        </div>
                                    </label>
                                </div>

                                <!-- Send to Admin -->
                                <div class="recipient-card">
                                    <input 
                                        type="radio" 
                                        name="recipient_type" 
                                        value="admin" 
                                        id="admin"
                                        class="hidden recipient-radio"
                                        {{ old('recipient_type') == 'admin' ? 'checked' : '' }}
                                    />
                                    <label for="admin" class="recipient-label cursor-pointer block p-4 border-2 border-slate-200 rounded-lg hover:border-purple-300 transition-all">
                                        <div class="text-center">
                                            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mb-2 mx-auto">
                                                <x-base.lucide class="w-5 h-5 text-purple-600" icon="shield" />
                                            </div>
                                            <h4 class="font-semibold text-sm">Send to Admin</h4>
                                            <p class="text-xs text-slate-500 mt-1">Administrator</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            @error('recipient_type')
                                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Specific Drivers Selection with Search -->
                        <div id="specific_drivers_section" class="hidden">
                            <x-base.form-label>Select Drivers</x-base.form-label>
                            
                            <!-- Search Box -->
                            <div class="mb-3">
                                <x-base.form-input 
                                    type="text" 
                                    id="driver_search" 
                                    placeholder="Search by name or email..."
                                    class="w-full"
                                />
                            </div>

                            <div class="border rounded-lg p-3 bg-slate-50 max-h-64 overflow-y-auto">
                                <div class="mb-2">
                                    <label class="flex items-center gap-2 p-2 hover:bg-white rounded cursor-pointer">
                                        <input type="checkbox" id="select_all_drivers" class="form-checkbox">
                                        <span class="font-semibold text-slate-700">Select All (<span id="driver_count">{{ $drivers->count() }}</span>)</span>
                                    </label>
                                </div>
                                <div id="driver_list" class="space-y-1">
                                    @foreach($drivers as $driver)
                                    <label class="driver-item flex items-center gap-2 p-2 hover:bg-white rounded cursor-pointer transition-colors"
                                           data-name="{{ strtolower($driver->user->name ?? '') }}"
                                           data-email="{{ strtolower($driver->user->email ?? '') }}">
                                        <input 
                                            type="checkbox" 
                                            name="driver_ids[]" 
                                            value="{{ $driver->id }}" 
                                            class="driver-checkbox form-checkbox"
                                            {{ in_array($driver->id, old('driver_ids', [])) ? 'checked' : '' }}
                                        />
                                        <div class="flex-1">
                                            <div class="font-medium text-slate-800">{{ $driver->user->name ?? 'N/A' }}</div>
                                            <div class="text-xs text-slate-500">{{ $driver->user->email ?? 'N/A' }}</div>
                                        </div>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="text-slate-500 text-sm mt-2">
                                Selected: <span id="selected_count">0</span> driver(s)
                            </div>
                            @error('driver_ids')
                                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Admin Notice -->
                        <div id="admin_notice" class="hidden">
                            <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                <p class="text-sm text-blue-700">
                                    <x-base.lucide class="w-4 h-4 inline mr-1" icon="info" />
                                    This message will be sent to the Administrator
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions Sidebar -->
            <div class="lg:col-span-1">
                <div class="box box--stacked">
                    <div class="box-body p-5 space-y-3">
                        <x-base.button type="submit" name="status" value="sent" variant="primary" class="w-full">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="send" />
                            Send Message
                        </x-base.button>
                        <x-base.button type="submit" name="status" value="draft" variant="outline-secondary" class="w-full">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="save" />
                            Save as Draft
                        </x-base.button>
                    </div>
                </div>

                <!-- Tips -->
                <div class="box box--stacked mt-5">
                    <div class="box-header p-5 border-b">
                        <h3 class="box-title">Tips</h3>
                    </div>
                    <div class="box-body p-5">
                        <div class="space-y-3 text-sm text-slate-600">
                            <div class="flex items-start gap-2">
                                <x-base.lucide class="w-4 h-4 text-blue-500 mt-0.5" icon="info" />
                                <div>Use the search to find drivers quickly</div>
                            </div>
                            <div class="flex items-start gap-2">
                                <x-base.lucide class="w-4 h-4 text-green-500 mt-0.5" icon="check" />
                                <div>High priority messages get special attention</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const recipientType = document.getElementById('recipient_type');
        const specificDriversSection = document.getElementById('specific_drivers_section');
        const adminNotice = document.getElementById('admin_notice');

        // Recipient type change handler
        const recipientTypeRadios = document.querySelectorAll('input[name="recipient_type"]');
        
        recipientTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                // Hide all sections
                specificDriversSection.classList.add('hidden');
                adminNotice.classList.add('hidden');

                // Show relevant section
                if (this.value === 'specific_drivers') {
                    specificDriversSection.classList.remove('hidden');
                } else if (this.value === 'admin') {
                    adminNotice.classList.remove('hidden');
                }
            });
        });

        // Trigger initial
        const checkedRadio = document.querySelector('input[name="recipient_type"]:checked');
        if (checkedRadio) {
            checkedRadio.dispatchEvent(new Event('change'));
        }

        // Driver Search Functionality
        const driverSearch = document.getElementById('driver_search');
        const driverItems = document.querySelectorAll('.driver-item');
        
        if (driverSearch) {
            driverSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                let visibleCount = 0;

                driverItems.forEach(item => {
                    const name = item.dataset.name || '';
                    const email = item.dataset.email || '';
                    
                    if (name.includes(searchTerm) || email.includes(searchTerm)) {
                        item.style.display = 'flex';
                        visibleCount++;
                    } else {
                        item.style.display = 'none';
                    }
                });

                document.getElementById('driver_count').textContent = visibleCount;
            });
        }

        // Select All Drivers
        const selectAllDrivers = document.getElementById('select_all_drivers');
        const driverCheckboxes = document.querySelectorAll('.driver-checkbox');
        
        if (selectAllDrivers) {
            selectAllDrivers.addEventListener('change', function() {
                driverCheckboxes.forEach(checkbox => {
                    if (checkbox.closest('.driver-item').style.display !== 'none') {
                        checkbox.checked = this.checked;
                    }
                });
                updateDriverCount();
            });
        }

        // Update count when checkboxes change
        driverCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateDriverCount);
        });

        function updateDriverCount() {
            const selectedCount = document.querySelectorAll('.driver-checkbox:checked').length;
            document.getElementById('selected_count').textContent = selectedCount;
        }

        // Initial count
        updateDriverCount();
    });
</script>

<style>
/* Custom styles for recipient cards */
.recipient-card .recipient-radio:checked + .recipient-label {
    border-color: #3b82f6 !important;
    background-color: #eff6ff !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.recipient-card .recipient-label {
    transition: all 0.2s ease-in-out;
}
</style>
@endpush
@endsection
