{{-- Inspections Tab Content --}}
<div class="space-y-6">
    <h3 class="text-lg font-semibold text-slate-800 mb-4">Vehicle Inspections</h3>
    
    @if($driver->inspections && $driver->inspections->count() > 0)
        <div class="space-y-4">
            @foreach($driver->inspections as $inspection)
                <div class="bg-slate-50/50 rounded-lg p-5 border border-slate-100">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-3">
                                <x-base.lucide class="w-5 h-5 text-info" icon="Search" />
                                <h4 class="font-semibold text-slate-800">{{ $inspection->inspection_type ?? 'Inspection' }}</h4>
                                @if($inspection->status == 'passed' || $inspection->is_vehicle_safe_to_operate)
                                    <x-base.badge variant="success">Passed</x-base.badge>
                                @elseif($inspection->status == 'failed')
                                    <x-base.badge variant="danger">Failed</x-base.badge>
                                @else
                                    <x-base.badge variant="secondary">{{ ucfirst($inspection->status ?? 'Pending') }}</x-base.badge>
                                @endif
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Inspection Date</label>
                                    <p class="text-sm font-semibold text-slate-800">{{ $inspection->inspection_date ? $inspection->inspection_date->format('M d, Y') : 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Inspector</label>
                                    <p class="text-sm font-semibold text-slate-800">{{ $inspection->inspector_name ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Location</label>
                                    <p class="text-sm font-semibold text-slate-800">{{ $inspection->location ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Level</label>
                                    <p class="text-sm font-semibold text-slate-800">{{ $inspection->inspection_level ?? 'N/A' }}</p>
                                </div>
                            </div>
                            @if($inspection->defects_found)
                                <div class="mt-3">
                                    <label class="text-xs font-medium text-slate-500 uppercase">Defects Found</label>
                                    <p class="text-sm text-slate-800">{{ $inspection->defects_found }}</p>
                                </div>
                            @endif
                            @if($inspection->corrective_actions)
                                <div class="mt-2">
                                    <label class="text-xs font-medium text-slate-500 uppercase">Corrective Actions</label>
                                    <p class="text-sm text-slate-800">{{ $inspection->corrective_actions }}</p>
                                </div>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            @if($inspection->getMedia('inspection_documents')->count() > 0)
                                @foreach($inspection->getMedia('inspection_documents') as $doc)
                                    <a href="{{ $doc->getUrl() }}" target="_blank" class="text-sm text-primary hover:underline flex items-center gap-1">
                                        <x-base.lucide class="w-4 h-4" icon="Download" /> Doc
                                    </a>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <x-base.lucide class="w-16 h-16 text-slate-300 mx-auto mb-4" icon="Search" />
            <h4 class="text-lg font-semibold text-slate-700 mb-2">No Inspection Records</h4>
            <p class="text-slate-500">You don't have any vehicle inspection records on file.</p>
        </div>
    @endif
</div>
