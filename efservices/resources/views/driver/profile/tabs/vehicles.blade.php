{{-- Vehicles Tab Content --}}
<div class="space-y-6">
    <h3 class="text-lg font-semibold text-slate-800 mb-4">Assigned Vehicles</h3>
    
    @php
        $vehicles = collect();
        if($driver->vehicles && $driver->vehicles->count() > 0) {
            $vehicles = $driver->vehicles;
        } elseif($driver->assignedVehicle) {
            $vehicles = collect([$driver->assignedVehicle]);
        } elseif($driver->activeVehicleAssignment && $driver->activeVehicleAssignment->vehicle) {
            $vehicles = collect([$driver->activeVehicleAssignment->vehicle]);
        }
    @endphp
    
    @if($vehicles->count() > 0)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach($vehicles as $vehicle)
                <div class="bg-slate-50/50 rounded-lg p-5 border border-slate-100">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-info/10 rounded-xl">
                            <x-base.lucide class="w-8 h-8 text-info" icon="Truck" />
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-slate-800 mb-2">
                                {{ $vehicle->year ?? '' }} {{ $vehicle->make ?? '' }} {{ $vehicle->model ?? 'Vehicle' }}
                            </h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">VIN</label>
                                    <p class="text-sm font-semibold text-slate-800">{{ $vehicle->vin ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Type</label>
                                    <p class="text-sm font-semibold text-slate-800">{{ $vehicle->type ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Status</label>
                                    <p class="text-sm font-semibold text-slate-800">
                                        @if($vehicle->status == 'active')
                                            <x-base.badge variant="success">Active</x-base.badge>
                                        @else
                                            <x-base.badge variant="secondary">{{ ucfirst($vehicle->status ?? 'Unknown') }}</x-base.badge>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <x-base.lucide class="w-16 h-16 text-slate-300 mx-auto mb-4" icon="Truck" />
            <h4 class="text-lg font-semibold text-slate-700 mb-2">No Vehicles Assigned</h4>
            <p class="text-slate-500">You don't have any vehicles assigned to you at this time.</p>
        </div>
    @endif
</div>
