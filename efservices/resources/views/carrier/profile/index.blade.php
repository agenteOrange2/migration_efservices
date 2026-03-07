@extends('../themes/' . $activeTheme)
@section('title', 'Company Profile - ' . $carrier->name)

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'Company Profile', 'active' => true],
    ];
@endphp

@section('subcontent')
<div class="grid grid-cols-12 gap-x-6 gap-y-10">
    
    {{-- Header con Logo y Info Principal --}}
    <div class="col-span-12">
        <div class="box box--stacked flex flex-col p-1.5">
            <div class="relative h-56 w-full rounded-t-[0.6rem] bg-gradient-to-r from-primary/90 via-primary to-primary/80">
                <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.05\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-30"></div>
                
                {{-- Botones de acción --}}
                <div class="absolute top-4 right-4 flex gap-2">
                    <a href="{{ route('carrier.profile.edit') }}" class="flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg backdrop-blur-sm transition-all">
                        <x-base.lucide class="w-4 h-4" icon="Edit" />
                        <span class="hidden sm:inline">Edit Profile</span>
                    </a>
                </div>
                
                {{-- Avatar/Logo --}}
                <div class="absolute inset-x-0 -bottom-16 mx-auto flex justify-center">
                    <div class="relative">
                        <div class="box image-fit h-32 w-32 overflow-hidden rounded-full border-[6px] border-white shadow-lg bg-white">
                            <img src="{{ $carrier->getFirstMediaUrl('logo_carrier') ?: asset('build/assets/images/placeholders/200x200.jpg') }}"
                                alt="{{ $carrier->name }}" class="w-full h-full object-cover">
                        </div>
                        <div class="absolute bottom-1 right-1 w-6 h-6 rounded-full {{ $carrier->status == 1 ? 'bg-success' : ($carrier->status == 0 ? 'bg-danger' : 'bg-warning') }} border-2 border-white"></div>
                    </div>
                </div>
            </div>
            
            <div class="rounded-b-[0.6rem] bg-slate-50 pb-8 pt-20">
                <div class="text-center">
                    <div class="flex items-center justify-center gap-2 text-2xl font-bold text-slate-800">
                        {{ $carrier->name }}
                        @if($carrier->status == 1)
                            <x-base.lucide class="w-6 h-6 fill-success/30 text-success" icon="BadgeCheck" />
                        @endif
                    </div>
                    <div class="mt-1 text-slate-500">{{ $carrier->address }}, {{ $carrier->state }} {{ $carrier->zipcode }}</div>
                    
                    {{-- Quick Stats Inline --}}
                    <div class="mt-4 flex flex-wrap items-center justify-center gap-4 text-sm">
                        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-white rounded-full shadow-sm">
                            <x-base.lucide class="w-4 h-4 text-primary" icon="FileText" />
                            <span class="text-slate-600">DOT:</span>
                            <span class="font-semibold text-slate-800">{{ $carrier->dot_number }}</span>
                        </div>
                        @if($carrier->mc_number)
                        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-white rounded-full shadow-sm">
                            <x-base.lucide class="w-4 h-4 text-primary" icon="Shield" />
                            <span class="text-slate-600">MC:</span>
                            <span class="font-semibold text-slate-800">{{ $carrier->mc_number }}</span>
                        </div>
                        @endif
                        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-white rounded-full shadow-sm">
                            <x-base.lucide class="w-4 h-4 text-primary" icon="Phone" />
                            <span class="font-semibold text-slate-800">{{ $carrierDetail->phone ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="col-span-12">
        <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-4">
            {{-- Drivers --}}
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-primary/10">
                        <x-base.lucide class="w-6 h-6 text-primary" icon="Users" />
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ $driversCount }}</div>
                        <div class="text-xs text-slate-500">Drivers</div>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs">
                    <span class="text-success">{{ $activeDrivers }} active</span>
                    <span class="text-slate-400">/ {{ $membership->max_drivers ?? '∞' }} max</span>
                </div>
                <div class="mt-2 h-1.5 w-full rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-primary transition-all" style="width: {{ $membershipLimits['drivers']['percentage'] }}%"></div>
                </div>
            </div>

            {{-- Vehicles --}}
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-info/10">
                        <x-base.lucide class="w-6 h-6 text-info" icon="Truck" />
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ $vehiclesCount }}</div>
                        <div class="text-xs text-slate-500">Vehicles</div>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs">
                    <span class="text-success">{{ $activeVehicles }} active</span>
                    <span class="text-slate-400">/ {{ $membership->max_vehicles ?? '∞' }} max</span>
                </div>
                <div class="mt-2 h-1.5 w-full rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-info transition-all" style="width: {{ $membershipLimits['vehicles']['percentage'] }}%"></div>
                </div>
            </div>

            {{-- Documents --}}
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-success/10">
                        <x-base.lucide class="w-6 h-6 text-success" icon="FileCheck" />
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ $documentStats['approved'] }}</div>
                        <div class="text-xs text-slate-500">Documents</div>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs">
                    <span class="text-warning">{{ $documentStats['pending'] }} pending</span>
                    <span class="text-slate-400">/ {{ $totalDocuments }} required</span>
                </div>
                <div class="mt-2 h-1.5 w-full rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-success transition-all" style="width: {{ $documentProgress }}%"></div>
                </div>
            </div>

            {{-- Licenses --}}
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 rounded-xl {{ $licenseStats['expired'] > 0 ? 'bg-danger/10' : 'bg-warning/10' }}">
                        <x-base.lucide class="w-6 h-6 {{ $licenseStats['expired'] > 0 ? 'text-danger' : 'text-warning' }}" icon="CreditCard" />
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ $licenseStats['total'] }}</div>
                        <div class="text-xs text-slate-500">Licenses</div>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2 text-xs flex-wrap">
                    @if($licenseStats['expired'] > 0)
                        <span class="px-2 py-0.5 rounded-full bg-danger/10 text-danger">{{ $licenseStats['expired'] }} expired</span>
                    @endif
                    @if($licenseStats['expiring_soon'] > 0)
                        <span class="px-2 py-0.5 rounded-full bg-warning/10 text-warning">{{ $licenseStats['expiring_soon'] }} expiring</span>
                    @endif
                    @if($licenseStats['expired'] == 0 && $licenseStats['expiring_soon'] == 0)
                        <span class="px-2 py-0.5 rounded-full bg-success/10 text-success">All valid</span>
                    @endif
                </div>
            </div>

            {{-- Medical Records --}}
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 rounded-xl {{ $medicalStats['expired'] > 0 ? 'bg-danger/10' : 'bg-emerald/10' }}">
                        <x-base.lucide class="w-6 h-6 {{ $medicalStats['expired'] > 0 ? 'text-danger' : 'text-emerald-500' }}" icon="Heart" />
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ $medicalStats['total'] }}</div>
                        <div class="text-xs text-slate-500">Medical</div>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2 text-xs flex-wrap">
                    @if($medicalStats['expired'] > 0)
                        <span class="px-2 py-0.5 rounded-full bg-danger/10 text-danger">{{ $medicalStats['expired'] }} expired</span>
                    @endif
                    @if($medicalStats['expiring_soon'] > 0)
                        <span class="px-2 py-0.5 rounded-full bg-warning/10 text-warning">{{ $medicalStats['expiring_soon'] }} expiring</span>
                    @endif
                    @if($medicalStats['expired'] == 0 && $medicalStats['expiring_soon'] == 0)
                        <span class="px-2 py-0.5 rounded-full bg-success/10 text-success">All valid</span>
                    @endif
                </div>
            </div>

            {{-- Accidents --}}
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 rounded-xl {{ $accidentStats['this_month'] > 0 ? 'bg-danger/10' : 'bg-slate-100' }}">
                        <x-base.lucide class="w-6 h-6 {{ $accidentStats['this_month'] > 0 ? 'text-danger' : 'text-slate-400' }}" icon="AlertTriangle" />
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ $accidentStats['total'] }}</div>
                        <div class="text-xs text-slate-500">Accidents</div>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2 text-xs flex-wrap">
                    <span class="text-slate-500">This month: {{ $accidentStats['this_month'] }}</span>
                    <span class="text-slate-400">|</span>
                    <span class="text-slate-500">YTD: {{ $accidentStats['this_year'] }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="col-span-12 xl:col-span-8">
        {{-- Company Information --}}
        <div class="box box--stacked p-5 mb-6">
            <div class="flex items-center justify-between border-b border-slate-200/60 pb-4 mb-5">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary/10">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="Building2" />
                    </div>
                    <h3 class="text-base font-medium">Company Information</h3>
                </div>
                <a href="{{ route('carrier.profile.edit') }}" class="text-primary hover:text-primary/80 text-sm font-medium">
                    Edit
                </a>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="p-4 bg-slate-50/50 rounded-lg border border-slate-100">
                    <div class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">Company Name</div>
                    <div class="font-semibold text-slate-700">{{ $carrier->name }}</div>
                </div>
                <div class="p-4 bg-slate-50/50 rounded-lg border border-slate-100">
                    <div class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">EIN Number</div>
                    <div class="font-semibold text-slate-700">{{ $carrier->ein_number ?? 'N/A' }}</div>
                </div>
                <div class="p-4 bg-slate-50/50 rounded-lg border border-slate-100">
                    <div class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">DOT Number</div>
                    <div class="font-semibold text-slate-700">{{ $carrier->dot_number }}</div>
                </div>
                <div class="p-4 bg-slate-50/50 rounded-lg border border-slate-100">
                    <div class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">MC Number</div>
                    <div class="font-semibold text-slate-700">{{ $carrier->mc_number ?? 'N/A' }}</div>
                </div>
                <div class="p-4 bg-slate-50/50 rounded-lg border border-slate-100">
                    <div class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">State DOT</div>
                    <div class="font-semibold text-slate-700">{{ $carrier->state_dot ?? 'N/A' }}</div>
                </div>
                <div class="p-4 bg-slate-50/50 rounded-lg border border-slate-100">
                    <div class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">IFTA Account</div>
                    <div class="font-semibold text-slate-700">{{ $carrier->ifta_account ?? 'N/A' }}</div>
                </div>
                <div class="p-4 bg-slate-50/50 rounded-lg border border-slate-100 md:col-span-2 lg:col-span-3">
                    <div class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">Address</div>
                    <div class="font-semibold text-slate-700">{{ $carrier->address }}, {{ $carrier->state }} {{ $carrier->zipcode }}</div>
                </div>
            </div>
        </div>

        {{-- Team Members --}}
        <div class="box box--stacked p-5 mb-6">
            <div class="flex items-center justify-between border-b border-slate-200/60 pb-4 mb-5">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-info/10">
                        <x-base.lucide class="w-5 h-5 text-info" icon="Users" />
                    </div>
                    <div>
                        <h3 class="text-base font-medium">Team Members</h3>
                        <p class="text-xs text-slate-500">{{ $userCarriers->count() }} of {{ $membership->max_carrier ?? '∞' }} users</p>
                    </div>
                </div>
            </div>
            
            <div class="space-y-3">
                @forelse($userCarriers as $userCarrier)
                <div class="flex items-center justify-between p-3 bg-slate-50/50 rounded-lg hover:bg-slate-100/50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full overflow-hidden border-2 border-white shadow-sm">
                            <img src="{{ $userCarrier->user->profile_photo_url ?? asset('build/assets/images/placeholders/200x200.jpg') }}" 
                                 alt="{{ $userCarrier->user->name }}" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <div class="font-medium text-slate-700">{{ $userCarrier->user->name }}</div>
                            <div class="text-xs text-slate-500">{{ $userCarrier->user->email }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-primary/10 text-primary">
                            {{ $userCarrier->job_position ?? 'Team Member' }}
                        </span>
                        <div class="w-2 h-2 rounded-full {{ $userCarrier->status == 1 ? 'bg-success' : 'bg-slate-300' }}"></div>
                    </div>
                </div>
                @empty
                <div class="text-center py-8 text-slate-400">
                    <x-base.lucide class="w-12 h-12 mx-auto mb-3 opacity-50" icon="Users" />
                    <p>No team members yet</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="box box--stacked p-5">
            <div class="flex items-center gap-3 border-b border-slate-200/60 pb-4 mb-5">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-slate-100">
                    <x-base.lucide class="w-5 h-5 text-slate-500" icon="Activity" />
                </div>
                <h3 class="text-base font-medium">Recent Activity</h3>
            </div>
            
            <div class="space-y-4 max-h-[400px] overflow-y-auto pr-2">
                @forelse($recentActivity as $activity)
                <div class="flex gap-3">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-{{ $activity['color'] }}/10 flex-shrink-0">
                        <x-base.lucide class="w-4 h-4 text-{{ $activity['color'] }}" icon="{{ $activity['icon'] }}" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-slate-700">{{ $activity['title'] }}</div>
                        <div class="text-xs text-slate-500 truncate">{{ $activity['description'] }}</div>
                        <div class="text-xs text-slate-400 mt-1">{{ $activity['date']->diffForHumans() }}</div>
                    </div>
                </div>
                @empty
                <div class="text-center py-8 text-slate-400">
                    <x-base.lucide class="w-12 h-12 mx-auto mb-3 opacity-50" icon="Activity" />
                    <p>No recent activity</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-span-12 xl:col-span-4">
        {{-- Safety Data System Card --}}
        @if($carrier->dot_number)
        <div class="box box--stacked overflow-hidden rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-300 mb-6">
            <div class="relative">
                <!-- Imagen de fondo -->
                <div class="relative h-48 bg-gradient-to-br from-primary via-primary/90 to-primary/80 flex items-center justify-center overflow-hidden">
                    @if($carrier->hasSafetyDataSystemImage())
                        <img src="{{ $carrier->getSafetyDataSystemImageUrl() }}" 
                             alt="Safety Data System" 
                             class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                    @else
                        <!-- Patrón de fondo por defecto -->
                        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.05\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-30"></div>
                        <x-base.lucide class="w-20 h-20 text-white/30 relative z-10" icon="Shield" />
                    @endif
                </div>
                
                <!-- Contenido de la card -->
                <div class="p-5 bg-white">
                    <div class="mb-3">
                        <h3 class="text-lg font-bold text-slate-800 mb-1">{{ $carrier->name }}</h3>
                        <p class="text-xs text-slate-500 flex items-center gap-2">
                            <x-base.lucide class="w-3.5 h-3.5" icon="Shield" />
                            Safety Data System
                        </p>
                    </div>
                    
                    <!-- Botón de acción -->
                    <x-base.button 
                        as="a" 
                        href="{{ $carrier->safety_data_system_url }}" 
                        target="_blank"
                        variant="primary" 
                        class="w-full gap-2 justify-center py-2.5"
                    >
                        <x-base.lucide class="w-4 h-4" icon="ExternalLink" />
                        Consulting Safety
                    </x-base.button>
                    
                    <!-- Información adicional -->
                    <div class="mt-3 pt-3 border-t border-slate-100">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500">DOT Number</span>
                            <span class="font-semibold text-slate-800">{{ $carrier->dot_number }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Current Plan --}}
        <div class="box box--stacked p-5 mb-6 border-2 border-primary/20 bg-gradient-to-br from-primary/5 to-transparent">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-primary text-white">
                        <x-base.lucide class="w-6 h-6" icon="Crown" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">{{ $membership->name ?? 'Free Plan' }}</h3>
                        <p class="text-xs text-slate-500">Current Plan</p>
                    </div>
                </div>
            </div>
            
            <div class="space-y-3 mb-5">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500">Drivers</span>
                    <span class="font-semibold">{{ $driversCount }} / {{ $membership->max_drivers ?? '∞' }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500">Vehicles</span>
                    <span class="font-semibold">{{ $vehiclesCount }} / {{ $membership->max_vehicles ?? '∞' }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500">Users</span>
                    <span class="font-semibold">{{ $userCarriers->count() }} / {{ $membership->max_carrier ?? '∞' }}</span>
                </div>
                @if($membership->price)
                <div class="pt-3 border-t border-slate-200">
                    <div class="flex items-baseline justify-between">
                        <span class="text-slate-500 text-sm">Monthly Cost</span>
                        <span class="text-2xl font-bold text-primary">${{ number_format($membership->price, 2) }}</span>
                    </div>
                </div>
                @endif
            </div>
            
            <button type="button" 
                    onclick="openUpgradeModal()"
                    class="w-full py-3 px-4 bg-primary hover:bg-primary/90 text-white font-medium rounded-lg transition-colors flex items-center justify-center gap-2">
                <x-base.lucide class="w-4 h-4" icon="Zap" />
                Upgrade Plan
            </button>
        </div>

        {{-- Referral Token --}}
        <div class="box box--stacked p-5 mb-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-success/10">
                    <x-base.lucide class="w-5 h-5 text-success" icon="Share2" />
                </div>
                <div>
                    <h3 class="text-base font-medium">Driver Registration Link</h3>
                    <p class="text-xs text-slate-500">Share this link with new drivers to join your carrier</p>
                </div>
            </div>
        
            {{-- Registration URL --}}
            <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/60">
                <label class="text-xs font-medium text-slate-500 mb-2 block">Registration URL</label>
                <div class="flex items-center gap-2">
                    <div class="flex-1 overflow-hidden">
                        <input type="text" 
                               id="referralUrl"
                               class="form-control bg-white text-xs font-mono truncate" 
                               value="{{ url('/driver/register/' . $carrier->slug . '?token=' . $carrier->referrer_token) }}" 
                               readonly>
                    </div>
                    <button type="button" 
                            onclick="copyReferralUrl()"
                            class="flex-shrink-0 px-3 py-2 bg-primary text-white hover:bg-primary/90 rounded-lg transition-colors flex items-center gap-1.5"
                            title="Copy URL">
                        <x-base.lucide class="w-4 h-4" icon="Copy" />
                        <span class="text-xs font-medium">Copy</span>
                    </button>
                    <a href="{{ url('/driver/register/' . $carrier->slug . '?token=' . $carrier->referrer_token) }}" 
                       target="_blank"
                       class="flex-shrink-0 px-3 py-2 bg-success text-white hover:bg-success/90 rounded-lg transition-colors flex items-center gap-1.5"
                       title="Open Registration Page">
                        <x-base.lucide class="w-4 h-4" icon="ExternalLink" />
                        <span class="text-xs font-medium">Open</span>
                    </a>
                </div>
                <p class="text-xs text-slate-400 mt-2 flex items-center gap-1">
                    <x-base.lucide class="w-3 h-3" icon="Info" />
                    Share this URL with drivers so they can register directly to your carrier.
                </p>
            </div>
        </div>

        {{-- Document Progress --}}
        <div class="box box--stacked p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-warning/10">
                        <x-base.lucide class="w-5 h-5 text-warning" icon="FolderOpen" />
                    </div>
                    <h3 class="text-base font-medium">Documents</h3>
                </div>
                <a href="{{ route('carrier.documents.index', $carrier->slug) }}" class="text-primary hover:text-primary/80 text-sm">
                    View All
                </a>
            </div>
            
            <div class="mb-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-slate-500">Completion</span>
                    <span class="text-sm font-semibold">{{ number_format($documentProgress, 0) }}%</span>
                </div>
                <div class="h-2 w-full rounded-full bg-slate-100">
                    <div class="h-full rounded-full {{ $documentProgress >= 100 ? 'bg-success' : ($documentProgress >= 50 ? 'bg-warning' : 'bg-danger') }} transition-all" 
                         style="width: {{ min($documentProgress, 100) }}%"></div>
                </div>
            </div>
            
            <div class="grid grid-cols-3 gap-2 text-center">
                <div class="p-2 bg-success/10 rounded-lg">
                    <div class="text-lg font-bold text-success">{{ $documentStats['approved'] }}</div>
                    <div class="text-xs text-slate-500">Approved</div>
                </div>
                <div class="p-2 bg-warning/10 rounded-lg">
                    <div class="text-lg font-bold text-warning">{{ $documentStats['pending'] }}</div>
                    <div class="text-xs text-slate-500">Pending</div>
                </div>
                <div class="p-2 bg-danger/10 rounded-lg">
                    <div class="text-lg font-bold text-danger">{{ $documentStats['rejected'] }}</div>
                    <div class="text-xs text-slate-500">Rejected</div>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="box box--stacked p-5">
            <h3 class="text-base font-medium mb-4">Quick Actions</h3>
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('carrier.driver-management.create') }}" 
                   class="flex flex-col items-center justify-center p-4 bg-slate-50 hover:bg-primary/5 hover:border-primary/20 rounded-lg border border-slate-100 transition-all group">
                    <x-base.lucide class="w-6 h-6 text-slate-400 group-hover:text-primary mb-2" icon="UserPlus" />
                    <span class="text-xs text-slate-600 group-hover:text-primary font-medium">Add Driver</span>
                </a>
                <a href="{{ route('carrier.vehicles.create') }}" 
                   class="flex flex-col items-center justify-center p-4 bg-slate-50 hover:bg-info/5 hover:border-info/20 rounded-lg border border-slate-100 transition-all group">
                    <x-base.lucide class="w-6 h-6 text-slate-400 group-hover:text-info mb-2" icon="Truck" />
                    <span class="text-xs text-slate-600 group-hover:text-info font-medium">Add Vehicle</span>
                </a>
                <a href="{{ route('carrier.documents.index', $carrier->slug) }}" 
                   class="flex flex-col items-center justify-center p-4 bg-slate-50 hover:bg-success/5 hover:border-success/20 rounded-lg border border-slate-100 transition-all group">
                    <x-base.lucide class="w-6 h-6 text-slate-400 group-hover:text-success mb-2" icon="Upload" />
                    <span class="text-xs text-slate-600 group-hover:text-success font-medium">Documents</span>
                </a>
                <a href="{{ route('carrier.reports.index') }}" 
                   class="flex flex-col items-center justify-center p-4 bg-slate-50 hover:bg-warning/5 hover:border-warning/20 rounded-lg border border-slate-100 transition-all group">
                    <x-base.lucide class="w-6 h-6 text-slate-400 group-hover:text-warning mb-2" icon="BarChart3" />
                    <span class="text-xs text-slate-600 group-hover:text-warning font-medium">Reports</span>
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Upgrade Plan Modal --}}
<div id="upgradeModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeUpgradeModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto pointer-events-auto">
            <div class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-slate-800">Upgrade Your Plan</h2>
                    <p class="text-sm text-slate-500">Choose the plan that best fits your needs</p>
                </div>
                <button type="button" onclick="closeUpgradeModal()" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                    <x-base.lucide class="w-5 h-5 text-slate-500" icon="X" />
                </button>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($availableMemberships as $plan)
                    <div class="border-2 border-slate-200 hover:border-primary/50 rounded-xl p-5 transition-all {{ $plan->id == $carrier->id_plan ? 'border-primary bg-primary/5' : '' }}">
                        @if($plan->id == $carrier->id_plan)
                        <div class="inline-block px-2 py-1 bg-primary text-white text-xs font-medium rounded-full mb-3">Current Plan</div>
                        @endif
                        
                        <h3 class="text-lg font-bold text-slate-800">{{ $plan->name }}</h3>
                        <div class="mt-2 mb-4">
                            <span class="text-3xl font-bold text-primary">${{ number_format($plan->price, 2) }}</span>
                            <span class="text-slate-500">/month</span>
                        </div>
                        
                        <ul class="space-y-2 mb-5">
                            <li class="flex items-center gap-2 text-sm text-slate-600">
                                <x-base.lucide class="w-4 h-4 text-success" icon="Check" />
                                Up to {{ $plan->max_drivers }} drivers
                            </li>
                            <li class="flex items-center gap-2 text-sm text-slate-600">
                                <x-base.lucide class="w-4 h-4 text-success" icon="Check" />
                                Up to {{ $plan->max_vehicles }} vehicles
                            </li>
                            <li class="flex items-center gap-2 text-sm text-slate-600">
                                <x-base.lucide class="w-4 h-4 text-success" icon="Check" />
                                Up to {{ $plan->max_carrier }} users
                            </li>
                        </ul>
                        
                        @if($plan->id != $carrier->id_plan)
                        <button type="button" 
                                class="w-full py-2.5 px-4 bg-primary hover:bg-primary/90 text-white font-medium rounded-lg transition-colors">
                            Select Plan
                        </button>
                        @else
                        <button type="button" disabled
                                class="w-full py-2.5 px-4 bg-slate-100 text-slate-400 font-medium rounded-lg cursor-not-allowed">
                            Current Plan
                        </button>
                        @endif
                    </div>
                    @empty
                    <div class="col-span-full text-center py-8 text-slate-400">
                        <x-base.lucide class="w-12 h-12 mx-auto mb-3 opacity-50" icon="Package" />
                        <p>No other plans available at this time</p>
                    </div>
                    @endforelse
                </div>
                
                <div class="mt-6 p-4 bg-slate-50 rounded-lg">
                    <p class="text-sm text-slate-600">
                        <x-base.lucide class="w-4 h-4 inline-block mr-1 text-info" icon="Info" />
                        Need a custom plan? Contact our sales team at <a href="mailto:sales@efct.com" class="text-primary font-medium">sales@efct.com</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function copyReferralUrl() {
        const urlInput = document.getElementById('referralUrl');
        const textToCopy = urlInput.value;
        
        // Try modern clipboard API first
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textToCopy).then(function() {
                showCopySuccess("Registration URL copied to clipboard!");
            }).catch(function(err) {
                fallbackCopy(urlInput);
            });
        } else {
            fallbackCopy(urlInput);
        }
    }

    function copyReferralToken() {
        const tokenInput = document.getElementById('referralToken');
        const textToCopy = tokenInput.value;
        
        // Try modern clipboard API first
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textToCopy).then(function() {
                showCopySuccess("Referral token copied to clipboard!");
            }).catch(function(err) {
                fallbackCopy(tokenInput);
            });
        } else {
            fallbackCopy(tokenInput);
        }
    }
    
    function fallbackCopy(inputElement) {
        inputElement.select();
        inputElement.setSelectionRange(0, 99999);
        try {
            document.execCommand('copy');
            showCopySuccess("Copied to clipboard!");
        } catch (err) {
            alert('Failed to copy. Please copy manually.');
        }
    }
    
    function showCopySuccess(message) {
        if (typeof Toastify !== 'undefined') {
            Toastify({
                text: message,
                duration: 3000,
                close: true,
                gravity: "top",
                position: "right",
                backgroundColor: "#10B981",
            }).showToast();
        } else {
            alert(message);
        }
    }
    
    function openUpgradeModal() {
        document.getElementById('upgradeModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    
    function closeUpgradeModal() {
        document.getElementById('upgradeModal').classList.add('hidden');
        document.body.style.overflow = '';
    }
    
    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeUpgradeModal();
        }
    });
</script>
@endpush
