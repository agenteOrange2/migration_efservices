{{-- General Tab Content --}}
<div class="space-y-6">
    <!-- Personal Information -->
    <div>
        <h3 class="text-lg font-semibold text-slate-800 mb-4">Personal Information</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Full Name</label>
                <p class="text-sm font-semibold text-slate-800">{{ $driver->user->name ?? 'Unknown' }} {{ $driver->middle_name }} {{ $driver->last_name }}</p>
            </div>
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Email</label>
                <p class="text-sm font-semibold text-slate-800">{{ $driver->user->email ?? 'N/A' }}</p>
            </div>
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Phone</label>
                <p class="text-sm font-semibold text-slate-800">{{ $driver->phone ?? 'N/A' }}</p>
            </div>
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Date of Birth</label>
                <p class="text-sm font-semibold text-slate-800">{{ $driver->date_of_birth ? $driver->date_of_birth->format('M d, Y') : 'N/A' }}</p>
            </div>
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Status</label>
                <div class="mt-1">
                    @php $effectiveStatus = $driver->getEffectiveStatus(); @endphp
                    @switch($effectiveStatus)
                        @case('active')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-success/10 text-success">
                                <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                                Active
                            </span>
                            @break
                        @case('pending_review')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-warning/10 text-warning">
                                <span class="w-1.5 h-1.5 rounded-full bg-warning"></span>
                                Pending Review
                            </span>
                            @break
                        @case('draft')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-slate-200/80 text-slate-600">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                Draft
                            </span>
                            @break
                        @case('rejected')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-danger/10 text-danger">
                                <span class="w-1.5 h-1.5 rounded-full bg-danger"></span>
                                Rejected
                            </span>
                            @break
                        @default
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-red-100 text-red-600">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
                                Inactive
                            </span>
                    @endswitch
                </div>
            </div>
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Join Date</label>
                <p class="text-sm font-semibold text-slate-800">{{ $driver->created_at->format('M d, Y') }}</p>
            </div>
        </div>
    </div>

    <!-- Carrier Information -->
    <div>
        <h3 class="text-lg font-semibold text-slate-800 mb-4">Carrier Information</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Carrier Name</label>
                <p class="text-sm font-semibold text-slate-800">{{ $driver->carrier->name ?? 'No carrier' }}</p>
            </div>
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">DOT Number</label>
                <p class="text-sm font-semibold text-slate-800">{{ $driver->carrier->dot_number ?? 'N/A' }}</p>
            </div>
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">MC Number</label>
                <p class="text-sm font-semibold text-slate-800">{{ $driver->carrier->mc_number ?? 'N/A' }}</p>
            </div>
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 sm:col-span-2 lg:col-span-3">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Address</label>
                <p class="text-sm font-semibold text-slate-800">{{ $driver->carrier->address ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    <!-- Application Status -->
    @if($driver->application)
    <div>
        <h3 class="text-lg font-semibold text-slate-800 mb-4">Application Status</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Status</label>
                <div class="mt-1">
                    @if($driver->application->status == 'approved')
                        <x-base.badge variant="success">Approved</x-base.badge>
                    @elseif($driver->application->status == 'pending')
                        <x-base.badge variant="warning">Pending</x-base.badge>
                    @else
                        <x-base.badge variant="danger">{{ ucfirst($driver->application->status) }}</x-base.badge>
                    @endif
                </div>
            </div>
            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Submitted Date</label>
                <p class="text-sm font-semibold text-slate-800">{{ $driver->application->created_at->format('M d, Y') }}</p>
            </div>
        </div>
    </div>
    @endif
</div>
