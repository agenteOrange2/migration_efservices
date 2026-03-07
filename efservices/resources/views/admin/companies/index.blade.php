@extends('../themes/' . $activeTheme)
@section('title', 'Companies')
@php
$breadcrumbLinks = [
['label' => 'App', 'url' => route('admin.dashboard')],
['label' => 'Companies', 'active' => true],
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
                <x-base.lucide class="w-8 h-8 text-primary" icon="Building2" />
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">Master Companies</h1>
                <p class="text-slate-600">Manage employment verification companies database</p>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-[300px]">
            <x-base.button as="a" href="{{ route('admin.companies.create') }}" variant="primary" class="gap-2 w-full">
                <x-base.lucide class="w-4 h-4" icon="Plus" />
                Add New Company
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

    <form action="{{ route('admin.companies.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Search -->
        <div class="md:col-span-2">
            <label for="search" class="block text-sm font-medium text-slate-700 mb-2">Search</label>
            <input type="text"
                id="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Company name, address, contact..."
                class="w-full text-sm border-slate-200 shadow-sm rounded-lg py-2.5 px-3">
        </div>

        <!-- State Filter -->
        <div>
            <label for="state" class="block text-sm font-medium text-slate-700 mb-2">State</label>
            <select id="state" name="state" class="w-full text-sm border-slate-200 shadow-sm rounded-lg py-2.5 px-3">
                <option value="">All States</option>
                @foreach($allStates as $state)
                <option value="{{ $state }}" {{ request('state') == $state ? 'selected' : '' }}>
                    {{ $state }}
                </option>
                @endforeach
            </select>
        </div>

        <!-- City Filter -->
        <div>
            <label for="city" class="block text-sm font-medium text-slate-700 mb-2">City</label>
            <select id="city" name="city" class="w-full text-sm border-slate-200 shadow-sm rounded-lg py-2.5 px-3">
                <option value="">All Cities</option>
                @foreach($allCities as $city)
                <option value="{{ $city }}" {{ request('city') == $city ? 'selected' : '' }}>
                    {{ $city }}
                </option>
                @endforeach
            </select>
        </div>

        <!-- Action Buttons -->
        <div class="md:col-span-4 flex gap-2">
            <x-base.button type="submit" variant="primary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="Search" />
                Apply Filters
            </x-base.button>
            <x-base.button as="a" href="{{ route('admin.companies.index') }}" variant="secondary" class="gap-2">
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
                <h2 class="text-lg font-semibold text-slate-800">Companies List</h2>
            </div>
            <x-base.badge variant="primary" class="px-3 py-1.5">
                {{ $companies->total() }} Total
            </x-base.badge>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200/60">
                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Company</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Contact</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Location</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Phone</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Drivers</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/60">
                @forelse($companies as $company)
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-primary/10 rounded-lg">
                                <x-base.lucide class="w-4 h-4 text-primary" icon="Building2" />
                            </div>
                            <div>
                                <a href="{{ route('admin.companies.show', $company->id) }}"
                                    class="font-medium text-primary hover:text-primary/80 transition-colors">
                                    {{ $company->company_name }}
                                </a>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-slate-700">{{ $company->contact ?? '---' }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-slate-700">
                            @if($company->city || $company->state)
                            <div class="flex items-center gap-1.5">
                                <x-base.lucide class="w-3 h-3 text-slate-400" icon="MapPin" />
                                {{ $company->city }}{{ $company->city && $company->state ? ', ' : '' }}{{ $company->state }}
                            </div>
                            @else
                            <span class="text-slate-400">---</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @if($company->phone)
                        <div class="flex items-center gap-1.5 text-sm text-slate-700">
                            <x-base.lucide class="w-3 h-3 text-slate-400" icon="Phone" />
                            {{ $company->phone }}
                        </div>
                        @else
                        <span class="text-sm text-slate-400">---</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($company->email)
                        <div class="flex items-center gap-1.5 text-sm text-slate-700">
                            <x-base.lucide class="w-3 h-3 text-slate-400" icon="Mail" />
                            {{ $company->email }}
                        </div>
                        @else
                        <span class="text-sm text-slate-400">---</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <x-base.badge variant="primary" class="gap-1.5">
                            <x-base.lucide class="w-3 h-3" icon="Users" />
                            {{ $company->driver_employment_companies_count ?? 0 }}
                        </x-base.badge>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-center gap-2">
                            <x-base.button as="a"
                                href="{{ route('admin.companies.show', $company->id) }}"
                                variant="primary"
                                size="sm"
                                class="gap-1.5"
                                title="View details">
                                <x-base.lucide class="w-4 h-4" icon="Eye" />
                            </x-base.button>

                            <x-base.button as="a"
                                href="{{ route('admin.companies.edit', $company->id) }}"
                                variant="secondary"
                                size="sm"
                                class="gap-1.5"
                                title="Edit company">
                                <x-base.lucide class="w-4 h-4" icon="Edit" />
                            </x-base.button>

                            <form action="{{ route('admin.companies.destroy', $company->id) }}"
                                method="POST"
                                class="inline"
                                onsubmit="return confirm('Are you sure you want to delete this company?');">
                                @csrf
                                @method('DELETE')
                                <x-base.button type="submit"
                                    variant="danger"
                                    size="sm"
                                    class="gap-1.5"
                                    title="Delete company">
                                    <x-base.lucide class="w-4 h-4" icon="Trash2" />
                                </x-base.button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <x-base.lucide class="w-12 h-12 text-slate-300" icon="Building2" />
                            <p class="text-slate-500 font-medium">No companies found</p>
                            <p class="text-sm text-slate-400">Try adjusting your filters or add a new company</p>
                            <x-base.button as="a" href="{{ route('admin.companies.create') }}" variant="primary" class="mt-2 gap-2">
                                <x-base.lucide class="w-4 h-4" icon="Plus" />
                                Add Company
                            </x-base.button>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($companies->hasPages())
    <div class="p-6 border-t border-slate-200/60">
        {{ $companies->appends(request()->query())->links('custom.pagination') }}
    </div>
    @endif>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Lucide.createIcons();
    });
</script>
@endpush