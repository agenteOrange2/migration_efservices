@extends('../themes/' . $activeTheme)
@section('title', 'Company Details: ' . $company->company_name)

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Companies', 'url' => route('admin.companies.index')],
        ['label' => 'Details', 'active' => true],
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
        <div class="flex items-center gap-4">
            <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                <x-base.lucide class="w-8 h-8 text-primary" icon="Building2" />
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">{{ $company->company_name }}</h1>
                <p class="text-slate-600">Company Details & Employment History</p>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <x-base.button as="a" href="{{ route('admin.companies.index') }}" variant="secondary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                Back to List
            </x-base.button>
            <x-base.button as="a" href="{{ route('admin.companies.edit', $company) }}" variant="primary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="Edit" />
                Edit Company
            </x-base.button>
            <form action="{{ route('admin.companies.destroy', $company) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this company?');">
                @csrf
                @method('DELETE')
                <x-base.button type="submit" variant="danger" class="gap-2">
                    <x-base.lucide class="w-4 h-4" icon="Trash2" />
                    Delete
                </x-base.button>
            </form>
        </div>
    </div>
</div>

<div class="grid grid-cols-12 gap-6">
    <!-- Company Information -->
    <div class="col-span-12 lg:col-span-6">
        <div class="box box--stacked flex flex-col p-6 h-fit">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Info" />
                <h2 class="text-lg font-semibold text-slate-800">Company Information</h2>
            </div>

            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 col-span-2">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Company Name</label>
                        <p class="text-sm font-semibold text-slate-800">{{ $company->company_name ?: 'Not specified' }}</p>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 col-span-2">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Contact Person</label>
                        <p class="text-sm font-semibold text-slate-800">{{ $company->contact ?: 'N/A' }}</p>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 col-span-2">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Address</label>
                        <p class="text-sm font-semibold text-slate-800">
                            @if($company->address || $company->city || $company->state || $company->zip)
                                {{ $company->address ? $company->address . ', ' : '' }}
                                {{ $company->city ? $company->city . ', ' : '' }}
                                {{ $company->state ? $company->state . ' ' : '' }}
                                {{ $company->zip ?? '' }}
                            @else
                                Not specified
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="col-span-12 lg:col-span-6">
        <div class="box box--stacked flex flex-col p-6 h-fit">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Phone" />
                <h2 class="text-lg font-semibold text-slate-800">Contact Information</h2>
            </div>

            <div class="space-y-3">
                <div class="grid grid-cols-1 gap-3">
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Phone</label>
                        <div class="flex items-center gap-2">
                            <x-base.lucide class="w-4 h-4 text-slate-400" icon="Phone" />
                            <p class="text-sm font-semibold text-slate-800">{{ $company->phone ?: 'Not specified' }}</p>
                        </div>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Email</label>
                        <div class="flex items-center gap-2">
                            <x-base.lucide class="w-4 h-4 text-slate-400" icon="Mail" />
                            <p class="text-sm font-semibold text-slate-800">{{ $company->email ?: 'Not specified' }}</p>
                        </div>
                    </div>
                    @if($company->fax)
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Fax</label>
                        <div class="flex items-center gap-2">
                            <x-base.lucide class="w-4 h-4 text-slate-400" icon="Printer" />
                            <p class="text-sm font-semibold text-slate-800">{{ $company->fax }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Employment History Records -->
    <div class="col-span-12">
        <div class="box box--stacked">
            <div class="p-6 border-b border-slate-200/60">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="Users" />
                        <h2 class="text-lg font-semibold text-slate-800">Employment History Records</h2>
                    </div>
                    <x-base.badge variant="primary" class="px-3 py-1.5">
                        {{ $employmentHistory->total() }} Total
                    </x-base.badge>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200/60">
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Driver</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Position</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Period</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Email Status</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/60">
                        @forelse($employmentHistory as $history)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                @if($history->userDriverDetail && $history->userDriverDetail->user)
                                    <div>
                                        <a href="{{ url('admin/drivers/' . $history->userDriverDetail->id) }}" 
                                           class="font-medium text-primary hover:text-primary/80 transition-colors">
                                            {{ $history->userDriverDetail->user->name }}
                                        </a>
                                        <div class="flex items-center gap-1.5 text-xs text-slate-500 mt-1">
                                            <x-base.lucide class="w-3 h-3" icon="Mail" />
                                            {{ $history->userDriverDetail->user->email }}
                                        </div>
                                    </div>
                                @else
                                    <span class="text-sm text-slate-400">Driver data not found</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-slate-700">{{ $history->positions_held ?? 'N/A' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-slate-700">
                                    {{ $history->employed_from ? $history->employed_from->format('M Y') : 'N/A' }} - 
                                    {{ $history->employed_to ? $history->employed_to->format('M Y') : 'Present' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if(!$history->email)
                                    <x-base.badge variant="secondary" class="gap-1.5">
                                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                                        No Email
                                    </x-base.badge>
                                @elseif($history->email_sent)
                                    <x-base.badge variant="success" class="gap-1.5">
                                        <span class="w-1.5 h-1.5 bg-success rounded-full"></span>
                                        Email Sent
                                    </x-base.badge>
                                @else
                                    <x-base.badge variant="warning" class="gap-1.5">
                                        <span class="w-1.5 h-1.5 bg-warning rounded-full"></span>
                                        Not Sent
                                    </x-base.badge>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    @if($history->userDriverDetail)
                                    <x-base.button as="a" 
                                        href="{{ url('admin/drivers/' . $history->userDriverDetail->id) }}"
                                        variant="primary" 
                                        size="sm"
                                        class="gap-1.5"
                                        title="View driver">
                                        <x-base.lucide class="w-4 h-4" icon="Eye" />
                                    </x-base.button>
                                    @endif
                                    
                                    @if($history->email)
                                    <form method="POST" action="{{ route('admin.drivers.employment-verification.resend', $history->id) }}" class="inline">
                                        @csrf
                                        <x-base.button type="submit" 
                                            variant="success" 
                                            size="sm"
                                            class="gap-1.5"
                                            title="Send verification email">
                                            <x-base.lucide class="w-4 h-4" icon="Mail" />
                                        </x-base.button>
                                    </form>
                                    @else
                                    <x-base.button 
                                        variant="outline-secondary" 
                                        size="sm"
                                        disabled
                                        class="gap-1.5"
                                        title="No email available">
                                        <x-base.lucide class="w-4 h-4" icon="MailX" />
                                    </x-base.button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <x-base.lucide class="w-12 h-12 text-slate-300" icon="Users" />
                                    <p class="text-slate-500 font-medium">No employment history records found</p>
                                    <p class="text-sm text-slate-400">This company has no associated employment records yet</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($employmentHistory->hasPages())
                <div class="p-6 border-t border-slate-200/60">
                    {{ $employmentHistory->links('custom.pagination') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Lucide.createIcons();
        });
    </script>
@endpush
