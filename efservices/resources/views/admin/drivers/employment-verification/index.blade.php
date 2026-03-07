@extends('../themes/' . $activeTheme)
@section('title', 'Employment Verifications')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Employment Verifications', 'active' => true],
    ];
@endphp

@section('subcontent')

<!-- Professional Breadcrumbs -->
<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<!-- Professional Header -->
<div class="box box--stacked p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
            <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                <x-base.lucide class="w-8 h-8 text-primary" icon="FileCheck" />
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">Employment Verifications</h1>
                <p class="text-slate-600">Manage and track employment verification requests</p>
            </div>
        </div>
        <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
            <x-base.button as="a" href="{{ route('admin.drivers.index') }}" variant="secondary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                Back to Drivers
            </x-base.button>
            <x-base.button as="a" href="{{ route('admin.drivers.employment-verification.new') }}" variant="primary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="Plus" />
                New Verification
            </x-base.button>
        </div>
    </div>
</div>

<!-- Filters Section -->
<div class="box box--stacked p-6 mb-6">
    <div class="flex items-center gap-3 mb-6">
        <x-base.lucide class="w-5 h-5 text-primary" icon="Filter" />
        <h2 class="text-lg font-semibold text-slate-800">Filters</h2>
    </div>
    
    <form action="{{ route('admin.drivers.employment-verification.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Status Filter -->
        <div>
            <label for="status" class="block text-sm font-medium text-slate-700 mb-2">Verification Status</label>
            <select id="status" name="status" class="w-full text-sm border-slate-200 shadow-sm rounded-lg py-2.5 px-3">
                <option value="">All Statuses</option>
                <option value="verified" @selected(request('status') === 'verified')>Verified</option>
                <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
            </select>
        </div>

        <!-- Driver Filter -->
        <div>
            <label for="driver" class="block text-sm font-medium text-slate-700 mb-2">Driver</label>
            <select id="driver" name="driver" class="w-full text-sm border-slate-200 shadow-sm rounded-lg py-2.5 px-3">
                <option value="">All Drivers</option>
                @foreach ($drivers as $driver)
                    <option value="{{ $driver->id }}" @selected(request('driver') == $driver->id)>
                        {{ $driver->user->name }} {{ $driver->last_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-end gap-2">
            <x-base.button type="submit" variant="primary" class="w-full md:w-auto gap-2">
                <x-base.lucide class="w-4 h-4" icon="Search" />
                Apply Filters
            </x-base.button>
            <x-base.button as="a" href="{{ route('admin.drivers.employment-verification.index') }}" variant="secondary" class="w-full md:w-auto gap-2">
                <x-base.lucide class="w-4 h-4" icon="X" />
                Clear
            </x-base.button>
        </div>
    </form>
</div>

<!-- Results Section -->
<div class="box box--stacked">
    <div class="p-6 border-b border-slate-200/60">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <x-base.lucide class="w-5 h-5 text-primary" icon="List" />
                <h2 class="text-lg font-semibold text-slate-800">Verification Requests</h2>
            </div>
            <x-base.badge variant="primary" class="px-3 py-1.5">
                {{ $employmentVerifications->total() }} Total
            </x-base.badge>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200/60">
                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Driver</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Company</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Email Status</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Verification</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/60">
                @forelse($employmentVerifications as $verification)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.drivers.show', $verification->userDriverDetail->id) }}" 
                               class="font-medium text-primary hover:text-primary/80 transition-colors">
                                {{ $verification->userDriverDetail->user->name }}
                                {{ $verification->userDriverDetail->last_name }}
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-slate-700">
                                {{ $verification->masterCompany ? $verification->masterCompany->company_name : ($verification->company_name ?? 'Custom company') }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <x-base.lucide class="w-4 h-4 text-slate-400" icon="Mail" />
                                <span class="text-sm text-slate-700">{{ $verification->email }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($verification->email_sent)
                                <x-base.badge variant="success" class="gap-1.5">
                                    <span class="w-1.5 h-1.5 bg-success rounded-full"></span>
                                    Sent
                                </x-base.badge>
                            @else
                                <x-base.badge variant="warning" class="gap-1.5">
                                    <span class="w-1.5 h-1.5 bg-warning rounded-full"></span>
                                    Not Sent
                                </x-base.badge>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if ($verification->verification_status == 'verified')
                                <x-base.badge variant="success" class="gap-1.5">
                                    <x-base.lucide class="w-3 h-3" icon="CheckCircle" />
                                    Verified
                                </x-base.badge>
                            @elseif($verification->verification_status == 'rejected')
                                <x-base.badge variant="danger" class="gap-1.5">
                                    <x-base.lucide class="w-3 h-3" icon="XCircle" />
                                    Rejected
                                </x-base.badge>
                            @else
                                <x-base.badge variant="warning" class="gap-1.5">
                                    <x-base.lucide class="w-3 h-3" icon="Clock" />
                                    Pending
                                </x-base.badge>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-slate-600">
                                {{ $verification->updated_at->format('M d, Y') }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <x-base.button as="a" 
                                    href="{{ route('admin.drivers.employment-verification.show', $verification->id) }}"
                                    variant="primary" 
                                    size="sm"
                                    class="gap-1.5"
                                    title="View details">
                                    <x-base.lucide class="w-4 h-4" icon="Eye" />
                                </x-base.button>

                                <form action="{{ route('admin.drivers.employment-verification.resend', $verification->id) }}"
                                    method="POST" class="inline">
                                    @csrf
                                    <x-base.button type="submit" 
                                        variant="secondary" 
                                        size="sm"
                                        class="gap-1.5"
                                        title="Resend email">
                                        <x-base.lucide class="w-4 h-4" icon="Mail" />
                                    </x-base.button>
                                </form>
                                
                                <form action="{{ route('admin.drivers.employment-verification.toggle-email-flag', $verification->id) }}"
                                    method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <x-base.button type="submit" 
                                        variant="{{ $verification->email_sent ? 'outline-secondary' : 'success' }}" 
                                        size="sm"
                                        class="gap-1.5"
                                        title="{{ $verification->email_sent ? 'Mark as not sent' : 'Mark as sent' }}"
                                        onclick="return confirm('Are you sure you want to {{ $verification->email_sent ? 'mark as not sent' : 'mark as sent' }}?')">
                                        <x-base.lucide class="w-4 h-4" icon="{{ $verification->email_sent ? 'X' : 'Check' }}" />
                                    </x-base.button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <x-base.lucide class="w-12 h-12 text-slate-300" icon="FileX" />
                                <p class="text-slate-500 font-medium">No employment verifications found</p>
                                <p class="text-sm text-slate-400">Try adjusting your filters or create a new verification</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($employmentVerifications->hasPages())
        <div class="p-6 border-t border-slate-200/60">
            {{ $employmentVerifications->links('custom.pagination') }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Lucide.createIcons();
        });
    </script>
@endpush
