@extends('../themes/' . $activeTheme)
@section('title', 'Membership Details - ' . $membership->name)
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Memberships', 'url' => route('admin.membership.index')],
        ['label' => $membership->name, 'active' => true],
    ];
@endphp

@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <!-- Professional Header -->
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                        @php
                            $membershipImage = $membership->getFirstMediaUrl('image_membership');
                        @endphp
                        @if($membershipImage)
                            <div class="w-20 h-20 rounded-xl overflow-hidden border-2 border-primary/20">
                                <img src="{{ $membershipImage }}" alt="{{ $membership->name }}" class="w-full h-full object-cover">
                            </div>
                        @else
                            <div class="p-4 bg-primary/10 rounded-xl border border-primary/20">
                                <x-base.lucide class="w-10 h-10 text-primary" icon="Crown" />
                            </div>
                        @endif
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <h1 class="text-2xl lg:text-3xl font-bold text-slate-800">{{ $membership->name }}</h1>
                                @if($membership->status)
                                    <span class="px-3 py-1 text-xs font-medium rounded-full bg-success/10 text-success">Active</span>
                                @else
                                    <span class="px-3 py-1 text-xs font-medium rounded-full bg-danger/10 text-danger">Inactive</span>
                                @endif
                            </div>
                            <p class="text-slate-600">{{ $membership->description }}</p>
                        </div>
                    </div>
                    <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                        <x-base.button as="a" href="{{ route('admin.membership.edit', $membership) }}" variant="primary">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="Edit" />
                            Edit Membership
                        </x-base.button>
                        <x-base.button as="a" href="{{ route('admin.membership.index') }}" variant="outline-primary">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="ArrowLeft" />
                            Back to Memberships
                        </x-base.button>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="box box--stacked p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-primary/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-primary" icon="Building2" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-slate-800">{{ $stats['total_carriers'] }}</div>
                            <div class="text-xs text-slate-500">Total Carriers</div>
                        </div>
                    </div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-success/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-success" icon="CheckCircle" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-success">{{ $stats['active_carriers'] }}</div>
                            <div class="text-xs text-slate-500">Active Carriers</div>
                        </div>
                    </div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-info/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-info" icon="Users" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-info">{{ $stats['total_drivers'] }}</div>
                            <div class="text-xs text-slate-500">Total Drivers</div>
                        </div>
                    </div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-warning/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-warning" icon="Truck" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-warning">{{ $stats['total_vehicles'] }}</div>
                            <div class="text-xs text-slate-500">Total Vehicles</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Pricing & Limits -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Pricing Card -->
                    <div class="box box--stacked">
                        <div class="box-header p-5 border-b border-slate-200/60">
                            <h3 class="text-lg font-medium flex items-center gap-2">
                                <x-base.lucide class="w-5 h-5 text-primary" icon="DollarSign" />
                                Pricing
                            </h3>
                        </div>
                        <div class="box-body p-5">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                                    <span class="text-slate-600">Pricing Type</span>
                                    <span class="px-3 py-1 text-xs font-medium rounded-full bg-primary/10 text-primary">
                                        {{ ucfirst($membership->pricing_type) }}
                                    </span>
                                </div>
                                @if($membership->pricing_type === 'plan')
                                    <div class="flex items-center justify-between p-3 bg-success/5 rounded-lg border border-success/20">
                                        <span class="text-slate-600">Plan Price</span>
                                        <span class="text-2xl font-bold text-success">${{ number_format($membership->price, 2) }}</span>
                                    </div>
                                @else
                                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                                        <span class="text-slate-600">Per Carrier</span>
                                        <span class="font-bold text-slate-800">${{ number_format($membership->carrier_price ?? 0, 2) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                                        <span class="text-slate-600">Per Driver</span>
                                        <span class="font-bold text-slate-800">${{ number_format($membership->driver_price ?? 0, 2) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                                        <span class="text-slate-600">Per Vehicle</span>
                                        <span class="font-bold text-slate-800">${{ number_format($membership->vehicle_price ?? 0, 2) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Limits Card -->
                    <div class="box box--stacked">
                        <div class="box-header p-5 border-b border-slate-200/60">
                            <h3 class="text-lg font-medium flex items-center gap-2">
                                <x-base.lucide class="w-5 h-5 text-primary" icon="Gauge" />
                                Plan Limits
                            </h3>
                        </div>
                        <div class="box-body p-5">
                            <div class="space-y-4">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm text-slate-600">Carriers</span>
                                        <span class="text-sm font-medium">{{ $stats['total_carriers'] }} / {{ $membership->max_carrier }}</span>
                                    </div>
                                    @php
                                        $carrierPercent = $membership->max_carrier > 0 ? min(($stats['total_carriers'] / $membership->max_carrier) * 100, 100) : 0;
                                    @endphp
                                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-primary rounded-full transition-all" style="width: {{ $carrierPercent }}%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm text-slate-600">Drivers</span>
                                        <span class="text-sm font-medium">{{ $stats['total_drivers'] }} / {{ $membership->max_drivers }}</span>
                                    </div>
                                    @php
                                        $driverPercent = $membership->max_drivers > 0 ? min(($stats['total_drivers'] / $membership->max_drivers) * 100, 100) : 0;
                                    @endphp
                                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-info rounded-full transition-all" style="width: {{ $driverPercent }}%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm text-slate-600">Vehicles</span>
                                        <span class="text-sm font-medium">{{ $stats['total_vehicles'] }} / {{ $membership->max_vehicles }}</span>
                                    </div>
                                    @php
                                        $vehiclePercent = $membership->max_vehicles > 0 ? min(($stats['total_vehicles'] / $membership->max_vehicles) * 100, 100) : 0;
                                    @endphp
                                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-warning rounded-full transition-all" style="width: {{ $vehiclePercent }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Settings Card -->
                    <div class="box box--stacked">
                        <div class="box-header p-5 border-b border-slate-200/60">
                            <h3 class="text-lg font-medium flex items-center gap-2">
                                <x-base.lucide class="w-5 h-5 text-primary" icon="Settings" />
                                Settings
                            </h3>
                        </div>
                        <div class="box-body p-5">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                                    <span class="text-slate-600">Status</span>
                                    @if($membership->status)
                                        <span class="px-3 py-1 text-xs font-medium rounded-full bg-success/10 text-success">Active</span>
                                    @else
                                        <span class="px-3 py-1 text-xs font-medium rounded-full bg-danger/10 text-danger">Inactive</span>
                                    @endif
                                </div>
                                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                                    <span class="text-slate-600">Show in Register</span>
                                    @if($membership->show_in_register)
                                        <span class="px-3 py-1 text-xs font-medium rounded-full bg-success/10 text-success">Yes</span>
                                    @else
                                        <span class="px-3 py-1 text-xs font-medium rounded-full bg-slate-200 text-slate-600">No</span>
                                    @endif
                                </div>
                                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                                    <span class="text-slate-600">Created</span>
                                    <span class="text-sm text-slate-800">{{ $membership->created_at->format('M d, Y') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Carriers List -->
                <div class="lg:col-span-2">
                    <div class="box box--stacked">
                        <div class="box-header p-5 border-b border-slate-200/60">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium flex items-center gap-2">
                                    <x-base.lucide class="w-5 h-5 text-primary" icon="Building2" />
                                    Carriers Using This Plan
                                </h3>
                                <span class="px-3 py-1 text-sm font-medium rounded-full bg-primary/10 text-primary">
                                    {{ $carriers->total() }} carriers
                                </span>
                            </div>
                        </div>
                        <div class="box-body p-5">
                            @if($carriers->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left">
                                        <thead>
                                            <tr class="border-b border-slate-200/60">
                                                <th class="py-3 px-4 font-medium text-slate-500">Carrier</th>
                                                <th class="py-3 px-4 font-medium text-slate-500">Contact</th>
                                                <th class="py-3 px-4 font-medium text-slate-500 text-center">Drivers</th>
                                                <th class="py-3 px-4 font-medium text-slate-500 text-center">Vehicles</th>
                                                <th class="py-3 px-4 font-medium text-slate-500 text-center">Status</th>
                                                <th class="py-3 px-4 font-medium text-slate-500 text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($carriers as $carrier)
                                                <tr class="border-b border-slate-100 hover:bg-slate-50/50">
                                                    <td class="py-3 px-4">
                                                        <div class="flex items-center gap-3">
                                                            @php
                                                                $carrierLogo = $carrier->getFirstMediaUrl('logo_carrier');
                                                            @endphp
                                                            @if($carrierLogo)
                                                                <img src="{{ $carrierLogo }}" alt="{{ $carrier->name }}" class="w-10 h-10 rounded-lg object-cover">
                                                            @else
                                                                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                                                                    <x-base.lucide class="w-5 h-5 text-primary" icon="Building2" />
                                                                </div>
                                                            @endif
                                                            <div>
                                                                <div class="font-medium text-slate-800">{{ $carrier->name }}</div>
                                                                <div class="text-xs text-slate-500">{{ $carrier->mc_number ?? 'N/A' }}</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="py-3 px-4">
                                                        <div class="text-sm text-slate-600">{{ $carrier->users->first()->email ?? 'N/A' }}</div>
                                                        <div class="text-xs text-slate-500">{{ $carrier->phone ?? '' }}</div>
                                                    </td>
                                                    <td class="py-3 px-4 text-center">
                                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-info/10 text-info">
                                                            {{ $carrier->userDrivers()->count() }}
                                                        </span>
                                                    </td>
                                                    <td class="py-3 px-4 text-center">
                                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-warning/10 text-warning">
                                                            {{ $carrier->vehicles()->count() }}
                                                        </span>
                                                    </td>
                                                    <td class="py-3 px-4 text-center">
                                                        @if($carrier->status == 1)
                                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-success/10 text-success">Active</span>
                                                        @else
                                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-danger/10 text-danger">Inactive</span>
                                                        @endif
                                                    </td>
                                                    <td class="py-3 px-4 text-center">
                                                        <x-base.button as="a" href="{{ route('admin.carrier.edit', $carrier) }}" variant="outline-primary" size="sm">
                                                            <x-base.lucide class="w-4 h-4" icon="Eye" />
                                                        </x-base.button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Pagination -->
                                @if($carriers->hasPages())
                                    <div class="mt-5 pt-5 border-t border-slate-200/60">
                                        {{ $carriers->links() }}
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-12">
                                    <div class="p-4 bg-slate-100 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                                        <x-base.lucide class="w-8 h-8 text-slate-400" icon="Building2" />
                                    </div>
                                    <h4 class="text-lg font-medium text-slate-600 mb-2">No Carriers Yet</h4>
                                    <p class="text-slate-500">No carriers are currently using this membership plan.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
