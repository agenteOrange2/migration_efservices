@extends('../themes/' . $activeTheme)
@section('title', 'User Details')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Users', 'url' => route('admin.users.index')],
        ['label' => 'User Details', 'active' => true],
    ];
    
    // Calculate user statistics
    $rolesCount = $roles ? $roles->count() : 0;
@endphp

@section('subcontent')

<!-- Professional Breadcrumbs using x-base component -->
<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<!-- Professional Header with x-base components -->
<div class="box box--stacked p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-center lg:items-center justify-between gap-6">
        <div class="flex flex-col lg:flex-row items-center gap-4">
            <div class="p-3">
                @if ($profilePhotoUrl)
                    <img class="w-20 h-20 rounded-full object-cover border-2 border-white shadow"
                         src="{{ $profilePhotoUrl }}"
                         alt="{{ $user->name }}" />
                @else
                    <x-base.lucide class="w-8 h-8 text-primary" icon="User" />
                @endif
            </div>
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-800 mb-2">{{ $user->name }}</h2>
                <div class="flex items-center gap-3">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="mail" />
                    <p class="text-slate-500">{{ $user->email }}</p>
                </div>
                <div class="flex">
                    <div class="flex flex-col sm:flex-row gap-4 justify-center md:justify-start mt-2">
                        <div class="flex items-center text-slate-500 text-sm">
                            <i data-lucide="calendar" class="w-4 h-4 mr-1"></i>
                            Joined {{ $user->created_at->format('M d, Y') }}
                        </div>
                        <div class="flex items-center text-slate-500 text-sm">
                            @if ($user->status)
                                <x-base.badge variant="success" class="gap-1.5">
                                    <span class="w-2 h-2 bg-success rounded-full"></span>
                                    Active
                                </x-base.badge>
                            @else
                                <x-base.badge variant="danger" class="gap-1.5">
                                    <span class="w-2 h-2 bg-danger rounded-full"></span>
                                    Inactive
                                </x-base.badge>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <x-base.button as="a" href="{{ route('admin.users.index') }}" variant="secondary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                Back to List
            </x-base.button>
            <x-base.button as="a" href="{{ route('admin.users.edit', $user->id) }}" variant="primary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="Edit" />
                Edit User
            </x-base.button>
        </div>
    </div>
</div>

<div class="grid grid-cols-12 gap-6 mt-5">
    <!-- Professional Information Section -->
    <div class="col-span-12 lg:col-span-6">
        <div class="box box--stacked flex flex-col p-6 h-fit">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Info" />
                <h2 class="text-lg font-semibold text-slate-800">Main Information</h2>
            </div>

            <!-- Professional Photo Section -->
            <div class="flex justify-center md:justify-start mb-6">
                @if ($profilePhotoUrl)
                    <div class="relative group">
                        <img src="{{ $profilePhotoUrl }}" alt="User Photo"
                             class="w-32 h-32 object-cover border-2 border-dashed border-primary/20 rounded-xl p-1 bg-slate-50/50 group-hover:border-primary/40 transition-colors">
                    </div>
                @else
                    <div class="w-32 h-32 bg-gradient-to-br from-slate-50 to-slate-100 rounded-xl flex items-center justify-center border-2 border-dashed border-slate-200">
                        <x-base.lucide class="w-12 h-12 text-slate-400" icon="User" />
                    </div>
                @endif
            </div>

            <!-- Professional Information Grid -->
            <div class="space-y-3">
                <h3 class="text-lg font-semibold text-slate-800 mb-4">Account Information</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Name</label>
                        <p class="text-sm font-semibold text-slate-800">{{ $user->name }}</p>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Email</label>
                        <p class="text-sm font-semibold text-slate-800">{{ $user->email }}</p>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Created</label>
                        <p class="text-sm font-semibold text-slate-800">{{ $user->created_at->format('M d, Y') }}</p>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Last Updated</label>
                        <p class="text-sm font-semibold text-slate-800">{{ $user->updated_at->format('M d, Y') }}</p>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-slate-800 mb-4 mt-6">Status</h3>
                <div class="grid grid-cols-1 gap-3">
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Account Status</label>
                        <div class="mt-2">
                            @if ($user->status)
                                <x-base.badge variant="success" class="gap-1.5">
                                    <span class="w-1.5 h-1.5 bg-success rounded-full"></span>
                                    Active
                                </x-base.badge>
                            @else
                                <x-base.badge variant="danger" class="gap-1.5">
                                    <span class="w-1.5 h-1.5 bg-danger rounded-full"></span>
                                    Inactive
                                </x-base.badge>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Columna Central - Estadísticas -->
    <div class="col-span-12 lg:col-span-6 space-y-6">
        <!-- Professional Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Total Roles Card -->
            <div class="box box--stacked p-6 hover:shadow-lg transition-all duration-200 group">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-500 mb-1">Assigned Roles</p>
                        <h3 class="text-3xl font-bold text-slate-800 group-hover:text-primary transition-colors">{{ $rolesCount }}</h3>
                    </div>
                    <div class="p-3 bg-primary/10 rounded-xl group-hover:bg-primary/20 transition-colors">
                        <x-base.lucide class="w-7 h-7 text-primary" icon="Shield" />
                    </div>
                </div>
                <div class="mt-4 flex items-center text-xs text-slate-500">
                    <x-base.lucide class="w-3 h-3 mr-1" icon="Users" />
                    User roles
                </div>
            </div>

            <!-- Account Age Card -->
            <div class="box box--stacked p-6 hover:shadow-lg transition-all duration-200 group">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-500 mb-1">Account Age</p>
                        <h3 class="text-3xl font-bold text-slate-800 group-hover:text-info transition-colors">{{ $user->created_at->diffInDays(now()) }}</h3>
                    </div>
                    <div class="p-3 bg-info/10 rounded-xl group-hover:bg-info/20 transition-colors">
                        <x-base.lucide class="w-7 h-7 text-info" icon="Calendar" />
                    </div>
                </div>
                <div class="mt-4 flex items-center text-xs text-slate-500">
                    <x-base.lucide class="w-3 h-3 mr-1" icon="Clock" />
                    Days since creation
                </div>
            </div>
        </div>

        <!-- Professional Roles Section -->
        <div class="box box--stacked flex flex-col p-6">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Shield" />
                <h2 class="text-lg font-semibold text-slate-800">Roles & Permissions</h2>
            </div>

            <div class="space-y-3">
                @forelse($roles as $role)
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 hover:border-primary/30 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-primary/10 rounded-lg">
                                    <x-base.lucide class="w-4 h-4 text-primary" icon="Shield" />
                                </div>
                                <div>
                                    <h4 class="font-semibold text-slate-800">{{ $role->name }}</h4>
                                </div>
                            </div>
                            <x-base.badge variant="primary" class="text-xs">Role</x-base.badge>
                        </div>
                    </div>
                @empty
                    <div class="bg-slate-50/50 rounded-lg p-6 border border-slate-100 text-center">
                        <x-base.lucide class="w-12 h-12 text-slate-400 mx-auto mb-2" icon="ShieldOff" />
                        <p class="text-slate-500 italic">No roles have been assigned yet.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection
