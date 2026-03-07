@php
    $data = $data ?? [];
@endphp

<div class="space-y-6">
    <!-- Personal Information Section -->
    <div>
        <div class="flex items-center gap-3 mb-6">
            <x-base.lucide class="w-5 h-5 text-primary" icon="User" />
            <h3 class="text-lg font-semibold text-slate-800">Personal Information</h3>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Full Name -->
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Full Name</label>
                <p class="text-sm font-semibold text-slate-800">
                    {{ trim(($data['name'] ?? '') . ' ' . ($data['middle_name'] ?? '') . ' ' . ($data['last_name'] ?? '')) ?: 'N/A' }}
                </p>
            </div>

            <!-- Email -->
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Email Address</label>
                <div class="flex items-center gap-2">
                    <x-base.lucide class="w-4 h-4 text-slate-400" icon="Mail" />
                    <p class="text-sm font-semibold text-slate-800">{{ $data['email'] ?? 'N/A' }}</p>
                </div>
            </div>

            <!-- Phone -->
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Phone Number</label>
                <div class="flex items-center gap-2">
                    <x-base.lucide class="w-4 h-4 text-slate-400" icon="Phone" />
                    <p class="text-sm font-semibold text-slate-800">{{ $data['phone'] ?? 'N/A' }}</p>
                </div>
            </div>

            <!-- Date of Birth -->
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Date of Birth</label>
                <div class="flex items-center gap-2">
                    <x-base.lucide class="w-4 h-4 text-slate-400" icon="Calendar" />
                    <div>
                        @if(isset($data['date_of_birth']))
                            <p class="text-sm font-semibold text-slate-800">
                                {{ \Carbon\Carbon::parse($data['date_of_birth'])->format('M d, Y') }}
                            </p>
                            <p class="text-xs text-slate-500">
                                Age: {{ \Carbon\Carbon::parse($data['date_of_birth'])->age }} years
                            </p>
                        @else
                            <p class="text-sm font-semibold text-slate-800">N/A</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- SSN (Last 4) -->
            @if(isset($data['ssn_last_four']))
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">SSN (Last 4)</label>
                <p class="text-sm font-semibold text-slate-800">***-**-{{ $data['ssn_last_four'] }}</p>
            </div>
            @endif

            <!-- Driver License Number -->
            @if(isset($data['driver_license_number']))
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Driver License Number</label>
                <p class="text-sm font-semibold text-slate-800">{{ $data['driver_license_number'] }}</p>
            </div>
            @endif

            <!-- License State -->
            @if(isset($data['driver_license_state']))
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">License State</label>
                <p class="text-sm font-semibold text-slate-800">{{ $data['driver_license_state'] }}</p>
            </div>
            @endif

            <!-- License Expiration -->
            @if(isset($data['driver_license_expiration']))
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">License Expiration</label>
                <div class="flex items-center gap-2">
                    <x-base.lucide class="w-4 h-4 text-slate-400" icon="Calendar" />
                    <p class="text-sm font-semibold text-slate-800">
                        {{ \Carbon\Carbon::parse($data['driver_license_expiration'])->format('M d, Y') }}
                    </p>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Address Information -->
    @if(isset($data['address']) || isset($data['city']) || isset($data['state']) || isset($data['zip']))
    <div class="border-t border-slate-200/60 pt-6">
        <div class="flex items-center gap-3 mb-4">
            <x-base.lucide class="w-5 h-5 text-primary" icon="MapPin" />
            <h3 class="text-lg font-semibold text-slate-800">Address</h3>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Street Address -->
            @if(isset($data['address']))
            <div class="md:col-span-2 bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Street Address</label>
                <div class="flex items-start gap-2">
                    <x-base.lucide class="w-4 h-4 text-slate-400 mt-0.5" icon="MapPin" />
                    <p class="text-sm font-semibold text-slate-800">{{ $data['address'] }}</p>
                </div>
            </div>
            @endif

            <!-- City -->
            @if(isset($data['city']))
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">City</label>
                <p class="text-sm font-semibold text-slate-800">{{ $data['city'] }}</p>
            </div>
            @endif

            <!-- State -->
            @if(isset($data['state']))
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">State</label>
                <p class="text-sm font-semibold text-slate-800">{{ $data['state'] }}</p>
            </div>
            @endif

            <!-- ZIP Code -->
            @if(isset($data['zip']))
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">ZIP Code</label>
                <p class="text-sm font-semibold text-slate-800">{{ $data['zip'] }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Emergency Contact -->
    @if(isset($data['emergency_contact_name']) || isset($data['emergency_contact_phone']))
    <div class="border-t border-slate-200/60 pt-6">
        <div class="flex items-center gap-3 mb-4">
            <x-base.lucide class="w-5 h-5 text-primary" icon="Phone" />
            <h3 class="text-lg font-semibold text-slate-800">Emergency Contact</h3>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if(isset($data['emergency_contact_name']))
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Contact Name</label>
                <p class="text-sm font-semibold text-slate-800">{{ $data['emergency_contact_name'] }}</p>
            </div>
            @endif

            @if(isset($data['emergency_contact_phone']))
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Contact Phone</label>
                <div class="flex items-center gap-2">
                    <x-base.lucide class="w-4 h-4 text-slate-400" icon="Phone" />
                    <p class="text-sm font-semibold text-slate-800">{{ $data['emergency_contact_phone'] }}</p>
                </div>
            </div>
            @endif

            @if(isset($data['emergency_contact_relationship']))
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Relationship</label>
                <p class="text-sm font-semibold text-slate-800">{{ $data['emergency_contact_relationship'] }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif

    @if(empty($data))
    <div class="text-center py-12">
        <x-base.lucide class="w-16 h-16 mx-auto text-slate-300 mb-4" icon="User" />
        <h3 class="text-lg font-semibold text-slate-800 mb-2">No Personal Information Available</h3>
        <p class="text-slate-500 text-sm">This archived record does not contain personal information.</p>
    </div>
    @endif
</div>
