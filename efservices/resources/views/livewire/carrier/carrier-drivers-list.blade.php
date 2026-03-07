<div>
    <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">        
        <div class="md:ml-auto flex flex-col sm:flex-row gap-3">
            <div class="flex items-center">
                <x-base.form-input type="text" class="w-64" wire:model.live.debounce.300ms="search"
                    placeholder="Search drivers..." />

                <x-base.form-select class="w-48 ml-2" wire:model.live="statusFilter">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="pending_review">Pending Review</option>
                    <option value="draft">Draft</option>
                    <option value="rejected">Rejected</option>
                    <option value="inactive">Inactive</option>
                </x-base.form-select>
            </div>

            <x-base.button as="a" href="{{ route('carrier.drivers.create') }}" variant="primary">
                <x-base.lucide class="h-4 w-4 mr-2" icon="Plus" />
                New Driver
            </x-base.button>
        </div>
    </div>

    <!-- Membership Statistics -->
    <div class="box box--stacked mt-3.5 p-5 mb-5">
        <div class="mb-3 flex flex-col sm:flex-row sm:items-center justify-between">
            <div>
                <h3 class="text-lg font-medium">Driver Limit</h3>
                <p class="text-slate-500 mt-1">
                    Your current plan allows you to have up to {{ $membershipStats['maxDrivers'] }} drivers.
                </p>
            </div>

            <div class="mt-3 sm:mt-0 flex items-center">
                <span class="font-medium">{{ $membershipStats['currentDrivers'] }} /
                    {{ $membershipStats['maxDrivers'] }}</span>
                <div class="ml-3 w-36 h-2 rounded bg-slate-200">
                    <div class="h-full rounded {{ $membershipStats['percentage'] > 90 ? 'bg-danger' : 'bg-success' }}"
                        style="width: {{ min(100, $membershipStats['percentage']) }}%"></div>
                </div>
            </div>
        </div>

        @if ($membershipStats['exceededLimit'])
            <div class="mt-3 bg-warning/20 text-warning rounded p-3 flex items-start">
                <x-base.lucide class="h-5 w-5 mr-2 mt-0.5" icon="AlertTriangle" />
                <div>
                    <p class="font-medium">You have reached the driver limit for your plan</p>
                    <p class="mt-1">To add more drivers, upgrade your membership plan.</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Drivers Table -->
    <div class="box box--stacked mt-3.5">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b-2 border-slate-200/60 bg-slate-50">
                        <th class="px-5 py-4 font-medium text-slate-700 whitespace-nowrap">Driver</th>
                        <th class="px-5 py-4 font-medium text-slate-700 whitespace-nowrap">Contact</th>
                        <th class="px-5 py-4 font-medium text-slate-700 whitespace-nowrap">Registration Date</th>
                        <th class="px-5 py-4 font-medium text-slate-700 whitespace-nowrap">Status</th>
                        <th class="px-5 py-4 font-medium text-slate-700 whitespace-nowrap text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($drivers as $driver)
                        <tr class="border-b border-slate-200/60 hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <div class="w-11 h-11 rounded-full overflow-hidden bg-slate-100 flex items-center justify-center">
                                            @if($driver->getFirstMediaUrl('profile_photo_driver'))
                                                <img class="w-full h-full object-cover" 
                                                     src="{{ $driver->getFirstMediaUrl('profile_photo_driver') }}" 
                                                     alt="{{ $driver->user->name }}">
                                            @else
                                                <span class="text-primary font-semibold text-sm">
                                                    {{ strtoupper(substr($driver->user->name ?? 'D', 0, 1)) }}{{ strtoupper(substr($driver->last_name ?? 'R', 0, 1)) }}
                                                </span>
                                            @endif
                                        </div>
                                        @if($driver->getEffectiveStatus() === 'active')
                                            <div class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-success border-2 border-white rounded-full"></div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-medium text-slate-700">{{ $driver->user->name }} {{ $driver->last_name }}</div>
                                        <div class="text-slate-500 text-xs flex items-center gap-1 mt-0.5">
                                            <x-base.lucide class="h-3 w-3" icon="Calendar" />
                                            DOB: {{ $driver->date_of_birth ? $driver->date_of_birth->format('m/d/Y') : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-1 text-slate-600 text-sm">
                                    <x-base.lucide class="h-3.5 w-3.5 text-slate-400" icon="Mail" />
                                    {{ $driver->user->email }}
                                </div>
                                @if($driver->phone)
                                    <div class="flex items-center gap-1 text-slate-500 text-xs mt-1">
                                        <x-base.lucide class="h-3 w-3 text-slate-400" icon="Phone" />
                                        {{ $driver->phone }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-1 text-slate-600">
                                    <x-base.lucide class="h-3.5 w-3.5 text-slate-400" icon="Clock" />
                                    {{ $driver->created_at->format('m/d/Y') }}
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    {{ $driver->created_at->diffForHumans() }}
                                </div>
                            </td>
                            <td class="px-5 py-4">
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
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end items-center gap-2">
                                    <a href="{{ route('carrier.drivers.show', $driver->id) }}" 
                                       class="flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors"
                                       title="View Details">
                                        <x-base.lucide class="w-4 h-4 text-slate-600" icon="Eye" />
                                    </a>
                                    <a href="{{ route('carrier.drivers.edit', $driver->id) }}" 
                                       class="flex items-center justify-center w-8 h-8 rounded-lg border border-primary/20 bg-primary/5 hover:bg-primary/10 transition-colors"
                                       title="Edit Driver">
                                        <x-base.lucide class="w-4 h-4 text-primary" icon="Edit" />
                                    </a>
                                    <form action="{{ route('carrier.drivers.destroy', $driver->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="flex items-center justify-center w-8 h-8 rounded-lg border border-danger/20 bg-danger/5 hover:bg-danger/10 transition-colors" 
                                                onclick="return confirm('Are you sure you want to delete this driver? This action cannot be undone.')"
                                                title="Delete Driver">
                                            <x-base.lucide class="w-4 h-4 text-danger" icon="Trash2" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                                        <x-base.lucide class="h-8 w-8 text-slate-400" icon="Users" />
                                    </div>
                                    <div class="text-base font-medium text-slate-700">No drivers found</div>
                                    <div class="text-sm mt-1 text-slate-500">Try adjusting your search or filters</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($drivers->hasPages())
            <div class="flex flex-col md:flex-row items-center justify-between px-5 py-4 border-t border-slate-200/60">
                <div class="text-sm text-slate-500 mb-3 md:mb-0">
                    Showing {{ $drivers->firstItem() }} to {{ $drivers->lastItem() }} of {{ $drivers->total() }} drivers
                </div>
                <div>
                    {{ $drivers->links() }}
                </div>
            </div>
        @endif
    </div>
</div>