@extends('../themes/' . $activeTheme)
@section('title', 'Contact Driver - Medical Record')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Medical Records', 'url' => route('admin.medical-records.index')],
        ['label' => 'Contact Driver', 'active' => true],
    ];
@endphp

@section('subcontent')
    <div class="grid grid-cols-12 gap-y-10 gap-x-6">
        <div class="col-span-12">

            <!-- Professional Header -->
            <div class="box box--stacked p-8 mb-8">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <x-base.lucide class="w-8 h-8 text-primary" icon="Heart" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-2">Contact Driver</h1>                                                        
                            <p class="text-slate-600">Contact driver: {{ $driver->user->name ?? 'N/A' }} {{ $driver->middle_name ?? 'N/A' }} {{ $driver->last_name ?? 'N/A' }}</p>
                            
                        </div>
                    </div>
                    <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                        <x-base.button as="a" href="{{ route('admin.medical-records.index') }}" variant="primary">
                            <x-base.lucide class="mr-2 h-4 w-4" icon="arrow-left" />
                            Back To List
                        </x-base.button>
                        <x-base.button as="a" href="{{ route('admin.medical-records.show', $medicalRecord->id) }}"
                            variant="outline-primary">
                            <x-base.lucide class="mr-2 h-4 w-4" icon="eye" />
                            View Medical Record
                        </x-base.button>
                    </div>
                </div>
            </div>

            <!-- Driver Information Summary -->
            <div class="box box--stacked mt-5">
                <div class="box-header p-5">
                    <h3 class="box-title">Driver Information</h3>
                </div>
                <div class="box-body p-5">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-slate-200 rounded-full flex items-center justify-center">
                            <x-base.lucide class="w-6 h-6 text-slate-500" icon="user" />
                        </div>
                        <div>
                            <div class="font-medium text-lg">{{ $driver->user->name ?? 'N/A' }}</div>
                            <div class="text-slate-500">
                                <x-base.lucide class="w-4 h-4 inline mr-1" icon="mail" />
                                {{ $driver->user->email ?? 'N/A' }} |
                                <x-base.lucide class="w-4 h-4 inline mr-1" icon="building" />
                                Carrier: {{ $driver->carrier->name ?? 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medical Record Status -->
            <div class="box box--stacked mt-5">
                <div class="box-header p-5">
                    <h3 class="box-title">Medical Record Status</h3>
                </div>
                <div class="box-body p-5">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <span class="text-slate-600">Expiration Date:</span>
                            <span class="ml-2 font-medium">
                                {{ $medicalRecord->medical_card_expiration_date ? \Carbon\Carbon::parse($medicalRecord->medical_card_expiration_date)->format('M d, Y') : 'N/A' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-600">Status:</span>
                            @php
                                $expirationDate = $medicalRecord->medical_card_expiration_date
                                    ? \Carbon\Carbon::parse($medicalRecord->medical_card_expiration_date)
                                    : null;
                                $now = \Carbon\Carbon::now();
                                $daysUntilExpiration = $expirationDate
                                    ? $now->diffInDays($expirationDate, false)
                                    : null;

                                if ($daysUntilExpiration === null) {
                                    $statusClass = 'bg-gray-100 text-gray-800';
                                    $statusText = 'No Date';
                                } elseif ($daysUntilExpiration < 0) {
                                    $statusClass = 'bg-red-100 text-red-800';
                                    $statusText = 'Expired';
                                } elseif ($daysUntilExpiration <= 30) {
                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                    $statusText = 'Expiring Soon';
                                } else {
                                    $statusClass = 'bg-green-100 text-green-800';
                                    $statusText = 'Active';
                                }
                            @endphp
                            <span
                                class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                {{ $statusText }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-600">Examiner:</span>
                            <span class="ml-2 font-medium">{{ $medicalRecord->medical_examiner_name ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="box box--stacked mt-5">
                <div class="box-header p-5">
                    <h3 class="box-title">Send Message</h3>
                </div>
                <div class="box-body p-5">
                    @if (session('error'))
                        <div class="alert alert-danger mb-4">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="alert-circle" />
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('admin.medical-records.send-contact', $medicalRecord) }}" method="POST">
                        @csrf
                        <div class="space-y-6">
                            <!-- Subject and Priority -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="md:col-span-2">
                                    <x-base.form-label for="subject">Subject *</x-base.form-label>
                                    <x-base.form-input type="text" id="subject" name="subject"
                                        value="{{ old('subject') }}" placeholder="Enter message subject..." required />
                                    @error('subject')
                                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <x-base.form-label for="priority">Priority *</x-base.form-label>
                                    <x-base.form-select id="priority" name="priority" required>
                                        <option value="">Select priority...</option>
                                        <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>
                                            🟢 Low Priority
                                        </option>
                                        <option value="normal" {{ old('priority') == 'normal' ? 'selected' : '' }}>
                                            🟡 Normal Priority
                                        </option>
                                        <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>
                                            🔴 High Priority
                                        </option>
                                    </x-base.form-select>
                                    @error('priority')
                                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Message -->
                            <div>
                                <x-base.form-label for="message">Message *</x-base.form-label>
                                <x-base.form-textarea id="message" name="message" rows="8"
                                    placeholder="Enter your message to the driver..."
                                    required>{{ old('message') }}</x-base.form-textarea>
                                @error('message')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-slate-500 text-sm mt-1">Maximum 2000 characters</div>
                            </div>

                            <!-- Message Templates (Optional) -->
                            <div class="bg-slate-50 p-4 rounded-lg">
                                <h4 class="font-medium text-slate-700 mb-3">Quick Message Templates</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    <x-base.button type="button" variant="outline-secondary" size="sm"
                                        onclick="setTemplate('medical_expired', 'Medical Certificate Expired', 'Hello {{ $driver->user->name }},\n\nYour medical certificate has expired. Please schedule a medical examination and update your records as soon as possible.\n\nBest regards,\nAdmin Team')">
                                        Medical Expired
                                    </x-base.button>
                                    <x-base.button type="button" variant="outline-secondary" size="sm"
                                        onclick="setTemplate('medical_expiring', 'Medical Certificate Expiring Soon', 'Hello {{ $driver->user->name }},\n\nYour medical certificate will expire soon. Please schedule a medical examination to renew your certification.\n\nBest regards,\nAdmin Team')">
                                        Medical Expiring
                                    </x-base.button>
                                    <x-base.button type="button" variant="outline-secondary" size="sm"
                                        onclick="setTemplate('document_update', 'Medical Document Update Required', 'Hello {{ $driver->user->name }},\n\nWe need you to update your medical documents. Please log in to your account and upload the required files.\n\nBest regards,\nAdmin Team')">
                                        Document Update
                                    </x-base.button>
                                    <x-base.button type="button" variant="outline-secondary" size="sm"
                                        onclick="setTemplate('general_inquiry', 'Medical Record Inquiry', 'Hello {{ $driver->user->name }},\n\nWe wanted to reach out regarding your medical record status. Please contact us at your earliest convenience.\n\nBest regards,\nAdmin Team')">
                                        General Inquiry
                                    </x-base.button>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex justify-end gap-3 pt-6 border-t">
                                <x-base.button as="a" href="{{ route('admin.medical-records.index') }}"
                                    variant="outline-secondary">
                                    Cancel
                                </x-base.button>
                                <x-base.button type="submit" variant="primary">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="send" />
                                    Send Message
                                </x-base.button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="box box--stacked mt-5">
                <div class="box-header p-5">
                    <h3 class="box-title">Contact Information</h3>
                </div>
                <div class="box-body p-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-medium text-slate-700 mb-3">Driver Details</h4>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <x-base.lucide class="w-4 h-4 text-slate-500 mr-2" icon="user" />
                                    <span class="text-slate-600">Name:</span>
                                    <span class="ml-2 font-medium">{{ $driver->user->name ?? 'N/A' }}</span>
                                </div>
                                <div class="flex items-center">
                                    <x-base.lucide class="w-4 h-4 text-slate-500 mr-2" icon="mail" />
                                    <span class="text-slate-600">Email:</span>
                                    <span class="ml-2 font-medium">{{ $driver->user->email ?? 'N/A' }}</span>
                                </div>
                                <div class="flex items-center">
                                    <x-base.lucide class="w-4 h-4 text-slate-500 mr-2" icon="phone" />
                                    <span class="text-slate-600">Phone:</span>
                                    <span class="ml-2 font-medium">{{ $driver->phone ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium text-slate-700 mb-3">Medical Record Details</h4>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <x-base.lucide class="w-4 h-4 text-slate-500 mr-2" icon="building" />
                                    <span class="text-slate-600">Carrier:</span>
                                    <span class="ml-2 font-medium">{{ $driver->carrier->name ?? 'N/A' }}</span>
                                </div>
                                <div class="flex items-center">
                                    <x-base.lucide class="w-4 h-4 text-slate-500 mr-2" icon="calendar" />
                                    <span class="text-slate-600">Record Created:</span>
                                    <span
                                        class="ml-2 font-medium">{{ $medicalRecord->created_at->format('M d, Y') }}</span>
                                </div>
                                <div class="flex items-center">
                                    <x-base.lucide class="w-4 h-4 text-slate-500 mr-2" icon="file-text" />
                                    <span class="text-slate-600">Registry Number:</span>
                                    <span
                                        class="ml-2 font-medium">{{ $medicalRecord->medical_examiner_registry_number ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function setTemplate(type, subject, message) {
            document.getElementById('subject').value = subject;
            document.getElementById('message').value = message;

            // Set priority based on template type
            const prioritySelect = document.getElementById('priority');
            switch (type) {
                case 'medical_expired':
                    prioritySelect.value = 'high';
                    break;
                case 'medical_expiring':
                    prioritySelect.value = 'normal';
                    break;
                case 'document_update':
                    prioritySelect.value = 'normal';
                    break;
                default:
                    prioritySelect.value = 'low';
            }
        }
    </script>
@endsection
