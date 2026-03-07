@extends('../themes/' . $activeTheme)
@section('title', isset($message) ? 'Edit Message' : 'Create New Message')
@php
$breadcrumbLinks = [
['label' => 'App', 'url' => route('admin.dashboard')],
['label' => 'Messages', 'url' => route('admin.messages.index')],
['label' => isset($message) ? 'Edit Message' : 'Create Message', 'active' => true],
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
            <h2 class="text-lg font-medium">{{ isset($message) ? 'Edit Message' : 'Create New Message' }}</h2>
            <div class="text-slate-500 text-sm mt-1">
                {{ isset($message) ? 'Update message details and recipients' : 'Compose and send a new message to drivers, carriers or custom recipients' }}
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            <x-base.button as="a" href="{{ route('admin.messages.index') }}" variant="outline-secondary" class="w-full sm:w-auto">
                <x-base.lucide class="w-4 h-4 mr-2" icon="arrow-left" />
                Back to Messages
            </x-base.button>
        </div>
    </div>

    <!-- Message Form -->
    <form action="{{ isset($message) ? route('admin.messages.update', $message) : route('admin.messages.store') }}" method="POST" class="mt-5">
        @csrf
        @if(isset($message))
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Form -->
            <div class="lg:col-span-2">
                <div class="box box--stacked">
                    <div class="box-header p-5 border-b border-slate-200/60">
                        <h3 class="box-title">Message Details</h3>
                    </div>
                    <div class="box-body p-5">
                        <!-- Subject -->
                        <div class="mb-5">
                            <x-base.form-label for="subject">Subject *</x-base.form-label>
                            <x-base.form-input 
                                type="text" 
                                name="subject" 
                                id="subject" 
                                value="{{ old('subject', $message->subject ?? '') }}" 
                                placeholder="Enter message subject..."
                                class="@error('subject') border-red-500 @enderror"
                                required 
                            />
                            @error('subject')
                                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Message Content -->
                        <div class="mb-5">
                            <x-base.form-label for="message">Message Content *</x-base.form-label>
                            <x-base.form-textarea 
                                name="message" 
                                id="message" 
                                rows="8"
                                placeholder="Enter your message content here..."
                                class="@error('message') border-red-500 @enderror"
                                required
                            >{{ old('message', $message->message ?? '') }}</x-base.form-textarea>
                            @error('message')
                                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                            <div class="text-slate-500 text-sm mt-1">
                                Maximum 2000 characters. Current: <span id="messageCount">{{ strlen(old('message', $message->message ?? '')) }}</span>
                            </div>
                        </div>

                        <!-- Priority -->
                        <div class="mb-5">
                            <x-base.form-label for="priority">Priority *</x-base.form-label>
                            <x-base.form-select 
                                name="priority" 
                                id="priority"
                                class="@error('priority') border-red-500 @enderror"
                                required
                            >
                                <option value="normal" {{ old('priority', $message->priority ?? 'normal') == 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="high" {{ old('priority', $message->priority ?? '') == 'high' ? 'selected' : '' }}>High Priority</option>
                                <option value="low" {{ old('priority', $message->priority ?? '') == 'low' ? 'selected' : '' }}>Low Priority</option>
                            </x-base.form-select>
                            @error('priority')
                                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Recipients Section -->
                <div class="box box--stacked mt-5">
                    <div class="box-header p-5 border-b border-slate-200/60">
                        <h3 class="box-title">Recipients</h3>
                    </div>
                    <div class="box-body p-5">
                        <!-- Recipient Type Selection -->
                        <div class="mb-5">
                            <x-base.form-label class="text-base font-semibold text-slate-700 mb-3">Recipient Type *</x-base.form-label>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-3">
                                <!-- All Drivers Card -->
                                <div class="recipient-card">
                                    <input 
                                        type="radio" 
                                        name="recipient_type" 
                                        value="all_drivers" 
                                        id="all_drivers"
                                        class="hidden recipient-radio"
                                        {{ old('recipient_type') == 'all_drivers' ? 'checked' : '' }}
                                    />
                                    <label for="all_drivers" class="recipient-label cursor-pointer block p-4 border-2 border-slate-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                        <div class="flex items-center justify-center flex-col text-center">
                                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-3">
                                                <x-base.lucide class="w-6 h-6 text-blue-600" icon="users" />
                                            </div>
                                            <h3 class="font-semibold text-slate-700 mb-1">All Drivers</h3>
                                            <p class="text-sm text-slate-500">Send to all active drivers</p>
                                        </div>
                                    </label>
                                </div>

                                <!-- Specific Drivers Card -->
                                <div class="recipient-card">
                                    <input 
                                        type="radio" 
                                        name="recipient_type" 
                                        value="specific_drivers" 
                                        id="specific_drivers"
                                        class="hidden recipient-radio"
                                        {{ old('recipient_type') == 'specific_drivers' ? 'checked' : '' }}
                                    />
                                    <label for="specific_drivers" class="recipient-label cursor-pointer block p-4 border-2 border-slate-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                        <div class="flex items-center justify-center flex-col text-center">
                                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-3">
                                                <x-base.lucide class="w-6 h-6 text-green-600" icon="user-check" />
                                            </div>
                                            <h3 class="font-semibold text-slate-700 mb-1">Specific Drivers</h3>
                                            <p class="text-sm text-slate-500">Choose individual drivers</p>
                                        </div>
                                    </label>
                                </div>

                                <!-- Specific Carriers Card -->
                                <div class="recipient-card">
                                    <input 
                                        type="radio" 
                                        name="recipient_type" 
                                        value="specific_carriers" 
                                        id="specific_carriers"
                                        class="hidden recipient-radio"
                                        {{ old('recipient_type') == 'specific_carriers' ? 'checked' : '' }}
                                    />
                                    <label for="specific_carriers" class="recipient-label cursor-pointer block p-4 border-2 border-slate-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                        <div class="flex items-center justify-center flex-col text-center">
                                            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mb-3">
                                                <x-base.lucide class="w-6 h-6 text-orange-600" icon="truck" />
                                            </div>
                                            <h3 class="font-semibold text-slate-700 mb-1">Carriers</h3>
                                            <p class="text-sm text-slate-500">Send to carriers</p>
                                        </div>
                                    </label>
                                </div>

                                <!-- Custom Emails Card -->
                                <div class="recipient-card">
                                    <input 
                                        type="radio" 
                                        name="recipient_type" 
                                        value="custom_emails" 
                                        id="custom_emails"
                                        class="hidden recipient-radio"
                                        {{ old('recipient_type') == 'custom_emails' ? 'checked' : '' }}
                                    />
                                    <label for="custom_emails" class="recipient-label cursor-pointer block p-4 border-2 border-slate-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                        <div class="flex items-center justify-center flex-col text-center">
                                            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mb-3">
                                                <x-base.lucide class="w-6 h-6 text-purple-600" icon="mail" />
                                            </div>
                                            <h3 class="font-semibold text-slate-700 mb-1">Custom Emails</h3>
                                            <p class="text-sm text-slate-500">Enter email addresses</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            @error('recipient_type')
                                <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Carrier Filter for All Drivers -->
                        <div id="carrier_filter_section" class="mb-5" style="display: none;">
                            <x-base.form-label for="carrier_filter">Filter by Carrier (Optional)</x-base.form-label>
                            <x-base.form-select name="carrier_filter" id="carrier_filter">
                                <option value="">All Carriers</option>
                                @foreach($carriers as $carrier)
                                <option value="{{ $carrier->id }}" {{ old('carrier_filter') == $carrier->id ? 'selected' : '' }}>
                                    {{ $carrier->name }}
                                </option>
                                @endforeach
                            </x-base.form-select>
                        </div>

                        <!-- Specific Drivers Selection with Search -->
                        <div id="specific_drivers_section" class="mb-5" style="display: none;">
                            <x-base.form-label for="driver_search">Select Drivers</x-base.form-label>
                            
                            <!-- Search Box -->
                            <div class="mb-3">
                                <x-base.form-input 
                                    type="text" 
                                    id="driver_search" 
                                    placeholder="Search by name, email or carrier..."
                                    class="w-full"
                                />
                            </div>

                            <!-- Driver List with Checkboxes -->
                            <div class="border rounded-lg p-3 bg-slate-50 max-h-96 overflow-y-auto">
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
                                           data-email="{{ strtolower($driver->user->email ?? '') }}"
                                           data-carrier="{{ strtolower($driver->carrier->name ?? '') }}">
                                        <input 
                                            type="checkbox" 
                                            name="driver_ids[]" 
                                            value="{{ $driver->id }}" 
                                            class="driver-checkbox form-checkbox"
                                            {{ in_array($driver->id, old('driver_ids', [])) ? 'checked' : '' }}
                                        />
                                        <div class="flex-1">
                                            <div class="font-medium text-slate-800">{{ $driver->user->name ?? 'N/A' }}</div>
                                            <div class="text-xs text-slate-500">{{ $driver->user->email ?? 'N/A' }} • {{ $driver->carrier->name ?? 'N/A' }}</div>
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

                        <!-- Specific Carriers Selection -->
                        <div id="specific_carriers_section" class="mb-5" style="display: none;">
                            <x-base.form-label for="carrier_search">Select Carriers</x-base.form-label>
                            
                            <!-- Search Box -->
                            <div class="mb-3">
                                <x-base.form-input 
                                    type="text" 
                                    id="carrier_search" 
                                    placeholder="Search carriers by name..."
                                    class="w-full"
                                />
                            </div>

                            <!-- Carrier List with Checkboxes -->
                            <div class="border rounded-lg p-3 bg-slate-50 max-h-96 overflow-y-auto">
                                <div class="mb-2">
                                    <label class="flex items-center gap-2 p-2 hover:bg-white rounded cursor-pointer">
                                        <input type="checkbox" id="select_all_carriers" class="form-checkbox">
                                        <span class="font-semibold text-slate-700">Select All (<span id="carrier_list_count">{{ $carriers->count() }}</span>)</span>
                                    </label>
                                </div>
                                <div id="carrier_list" class="space-y-1">
                                    @foreach($carriers as $carrier)
                                    <label class="carrier-item flex items-center gap-2 p-2 hover:bg-white rounded cursor-pointer transition-colors"
                                           data-name="{{ strtolower($carrier->name) }}">
                                        <input 
                                            type="checkbox" 
                                            name="carrier_ids[]" 
                                            value="{{ $carrier->id }}" 
                                            class="carrier-checkbox form-checkbox"
                                            {{ in_array($carrier->id, old('carrier_ids', [])) ? 'checked' : '' }}
                                        />
                                        <div class="flex-1">
                                            <div class="font-medium text-slate-800">{{ $carrier->name }}</div>
                                            <div class="text-xs text-slate-500">{{ $carrier->email ?? 'N/A' }}</div>
                                        </div>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="text-slate-500 text-sm mt-2">
                                Selected: <span id="selected_carriers_count">0</span> carrier(s)
                            </div>
                            @error('carrier_ids')
                                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Custom Emails -->
                        <div id="custom_emails_section" class="mb-5" style="display: none;">
                            <x-base.form-label for="custom_emails">Custom Email Addresses</x-base.form-label>
                            <x-base.form-textarea 
                                name="custom_emails" 
                                id="custom_emails_input" 
                                rows="4"
                                placeholder="Enter email addresses separated by commas or new lines..."
                            >{{ old('custom_emails') }}</x-base.form-textarea>
                            <div class="text-slate-500 text-sm mt-1">
                                Enter email addresses separated by commas or new lines (e.g., user1@example.com, user2@example.com)
                            </div>
                            @error('custom_emails')
                                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Actions -->
                <div class="box box--stacked">
                    <div class="box-header p-5 border-b border-slate-200/60">
                        <h3 class="box-title">Actions</h3>
                    </div>
                    <div class="box-body p-5">
                        <div class="space-y-3">
                            <x-base.button type="submit" name="status" value="sent" variant="primary" class="w-full">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="send" />
                                Send Message
                            </x-base.button>
                            
                            <x-base.button type="submit" name="status" value="draft" variant="outline-primary" class="w-full">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="save" />
                                Save as Draft
                            </x-base.button>

                            <x-base.button type="button" onclick="previewMessage()" variant="outline-secondary" class="w-full">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="eye" />
                                Preview
                            </x-base.button>
                        </div>
                    </div>
                </div>

                <!-- Tips -->
                <div class="box box--stacked mt-5">
                    <div class="box-header p-5 border-b border-slate-200/60">
                        <h3 class="box-title">Tips</h3>
                    </div>
                    <div class="box-body p-5">
                        <div class="space-y-3 text-sm text-slate-600">
                            <div class="flex items-start gap-2">
                                <x-base.lucide class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" icon="info" />
                                <div>Use clear and concise subject lines for better engagement.</div>
                            </div>
                            <div class="flex items-start gap-2">
                                <x-base.lucide class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" icon="check" />
                                <div>High priority messages will be highlighted in the recipient's inbox.</div>
                            </div>
                            <div class="flex items-start gap-2">
                                <x-base.lucide class="w-4 h-4 text-yellow-500 mt-0.5 flex-shrink-0" icon="clock" />
                                <div>Draft messages can be edited and sent later.</div>
                            </div>
                            <div class="flex items-start gap-2">
                                <x-base.lucide class="w-4 h-4 text-purple-500 mt-0.5 flex-shrink-0" icon="search" />
                                <div>Use the search box to quickly find drivers or carriers.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Preview Modal -->
<x-base.dialog id="previewModal" size="xl">
    <x-base.dialog.panel>
        <x-base.dialog.title>
            <h2 class="mr-auto text-base font-medium">Message Preview</h2>
        </x-base.dialog.title>
        <x-base.dialog.description class="grid grid-cols-12 gap-4 gap-y-3">
            <div class="col-span-12">
                <div class="border rounded-lg p-4 bg-slate-50">
                    <div class="mb-3">
                        <strong>Subject:</strong> <span id="preview-subject"></span>
                    </div>
                    <div class="mb-3">
                        <strong>Priority:</strong> <span id="preview-priority" class="px-2 py-1 rounded-full text-xs font-medium"></span>
                    </div>
                    <div class="mb-3">
                        <strong>Message:</strong>
                        <div id="preview-message" class="mt-2 p-3 bg-white rounded border whitespace-pre-wrap"></div>
                    </div>
                    <div>
                        <strong>Recipients:</strong> <span id="preview-recipients"></span>
                    </div>
                </div>
            </div>
        </x-base.dialog.description>
        <x-base.dialog.footer>
            <x-base.button type="button" variant="outline-secondary" class="w-20 mr-1" data-tw-dismiss="modal">
                Close
            </x-base.button>
        </x-base.dialog.footer>
    </x-base.dialog.panel>
</x-base.dialog>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Character counter for message
    const messageTextarea = document.getElementById('message');
    const messageCount = document.getElementById('messageCount');
    
    messageTextarea.addEventListener('input', function() {
        messageCount.textContent = this.value.length;
        if (this.value.length > 2000) {
            messageCount.classList.add('text-red-500');
        } else {
            messageCount.classList.remove('text-red-500');
        }
    });

    // Recipient type handling
    const recipientTypeRadios = document.querySelectorAll('input[name="recipient_type"]');
    const specificDriversSection = document.getElementById('specific_drivers_section');
    const specificCarriersSection = document.getElementById('specific_carriers_section');
    const customEmailsSection = document.getElementById('custom_emails_section');
    const carrierFilterSection = document.getElementById('carrier_filter_section');

    recipientTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            // Hide all sections first
            specificDriversSection.style.display = 'none';
            specificCarriersSection.style.display = 'none';
            customEmailsSection.style.display = 'none';
            carrierFilterSection.style.display = 'none';

            // Show relevant section
            if (this.value === 'specific_drivers') {
                specificDriversSection.style.display = 'block';
            } else if (this.value === 'specific_carriers') {
                specificCarriersSection.style.display = 'block';
            } else if (this.value === 'custom_emails') {
                customEmailsSection.style.display = 'block';
            } else if (this.value === 'all_drivers') {
                carrierFilterSection.style.display = 'block';
            }
        });
    });

    // Trigger change event on page load to show correct section
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
                const carrier = item.dataset.carrier || '';
                
                if (name.includes(searchTerm) || email.includes(searchTerm) || carrier.includes(searchTerm)) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            document.getElementById('driver_count').textContent = visibleCount;
        });
    }

    // Carrier Search Functionality
    const carrierSearch = document.getElementById('carrier_search');
    const carrierItems = document.querySelectorAll('.carrier-item');
    
    if (carrierSearch) {
        carrierSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            let visibleCount = 0;

            carrierItems.forEach(item => {
                const name = item.dataset.name || '';
                
                if (name.includes(searchTerm)) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            document.getElementById('carrier_list_count').textContent = visibleCount;
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

    // Select All Carriers
    const selectAllCarriers = document.getElementById('select_all_carriers');
    const carrierCheckboxes = document.querySelectorAll('.carrier-checkbox');
    
    if (selectAllCarriers) {
        selectAllCarriers.addEventListener('change', function() {
            carrierCheckboxes.forEach(checkbox => {
                if (checkbox.closest('.carrier-item').style.display !== 'none') {
                    checkbox.checked = this.checked;
                }
            });
            updateCarrierCount();
        });
    }

    // Update selected count for drivers
    driverCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateDriverCount);
    });

    // Update selected count for carriers
    carrierCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateCarrierCount);
    });

    function updateDriverCount() {
        const selectedCount = document.querySelectorAll('.driver-checkbox:checked').length;
        document.getElementById('selected_count').textContent = selectedCount;
    }

    function updateCarrierCount() {
        const selectedCount = document.querySelectorAll('.carrier-checkbox:checked').length;
        document.getElementById('selected_carriers_count').textContent = selectedCount;
    }

    // Initial count
    updateDriverCount();
    updateCarrierCount();
});

function previewMessage() {
    const subject = document.getElementById('subject').value;
    const message = document.getElementById('message').value;
    const priority = document.getElementById('priority').value;
    const recipientType = document.querySelector('input[name="recipient_type"]:checked')?.value;

    // Update preview content
    document.getElementById('preview-subject').textContent = subject || 'No subject';
    document.getElementById('preview-message').textContent = message || 'No message content';
    
    const prioritySpan = document.getElementById('preview-priority');
    prioritySpan.textContent = priority ? priority.charAt(0).toUpperCase() + priority.slice(1) : 'Not selected';
    
    // Set priority color
    prioritySpan.className = 'px-2 py-1 rounded-full text-xs font-medium ';
    if (priority === 'high') {
        prioritySpan.className += 'bg-red-100 text-red-800';
    } else if (priority === 'normal') {
        prioritySpan.className += 'bg-blue-100 text-blue-800';
    } else if (priority === 'low') {
        prioritySpan.className += 'bg-gray-100 text-gray-800';
    } else {
        prioritySpan.className += 'bg-slate-100 text-slate-800';
    }

    // Update recipients info
    let recipientsText = 'No recipients selected';
    if (recipientType === 'all_drivers') {
        const carrierFilter = document.getElementById('carrier_filter').value;
        recipientsText = carrierFilter ? 'All drivers from selected carrier' : 'All drivers';
    } else if (recipientType === 'specific_drivers') {
        const selectedDrivers = document.querySelectorAll('.driver-checkbox:checked').length;
        recipientsText = `${selectedDrivers} selected driver(s)`;
    } else if (recipientType === 'specific_carriers') {
        const selectedCarriers = document.querySelectorAll('.carrier-checkbox:checked').length;
        recipientsText = `${selectedCarriers} selected carrier(s)`;
    } else if (recipientType === 'custom_emails') {
        const customEmails = document.getElementById('custom_emails_input').value;
        const emailCount = customEmails.split(/[,\n]/).filter(email => email.trim()).length;
        recipientsText = `${emailCount} custom email(s)`;
    }
    
    document.getElementById('preview-recipients').textContent = recipientsText;

    // Show modal
    const modal = tailwind.Modal.getOrCreateInstance(document.querySelector("#previewModal"));
    modal.show();
}
</script>

<style>
/* Custom styles for recipient cards */
.recipient-card .recipient-radio:checked + .recipient-label {
    border-color: #3b82f6 !important;
    background-color: #eff6ff !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.recipient-card .recipient-radio:checked + .recipient-label h3 {
    color: #1d4ed8 !important;
}

.recipient-card .recipient-radio:checked + .recipient-label p {
    color: #3730a3 !important;
}

.recipient-card .recipient-label:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.recipient-card .recipient-radio:checked + .recipient-label:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
}

/* Animation for smooth transitions */
.recipient-card .recipient-label {
    transition: all 0.2s ease-in-out;
}
</style>

@endpush
@endsection
