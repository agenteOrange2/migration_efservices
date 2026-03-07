@extends('../themes/' . $activeTheme)
@section('title', 'Repair Details')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'Repairs', 'url' => route('carrier.emergency-repairs.index')],
        ['label' => 'Details', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Repair Details: {{ $emergencyRepair->repair_name }}
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <form action="{{ route('carrier.emergency-repairs.generate-report', $emergencyRepair->vehicle->id) }}" method="POST" class="inline">
                        @csrf
                        <x-base.button type="submit"
                            class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                            variant="soft-success">
                            <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="FileText" />
                            Generate Report
                        </x-base.button>
                    </form>
                    <x-base.button as="a" href="{{ route('carrier.emergency-repairs.edit', $emergencyRepair) }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="Pencil" />
                        Edit
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('carrier.emergency-repairs.index') }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowLeft" />
                        Back to List
                    </x-base.button>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success mt-5">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger mt-5">{{ session('error') }}</div>
            @endif

            <div class="box box--stacked mt-5">
                <div class="box-body p-5">
                    <!-- Status Badge -->
                    <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200/60">
                        <div class="flex items-center gap-3">
                            <div class="p-3 bg-primary/10 rounded-xl">
                                <x-base.lucide class="w-6 h-6 text-primary" icon="Wrench" />
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-800">{{ $emergencyRepair->repair_name }}</h2>
                                <p class="text-sm text-slate-500">Created {{ $emergencyRepair->created_at->format('M d, Y') }}</p>
                            </div>
                        </div>
                        <span class="px-3 py-1.5 rounded-full text-sm font-medium
                            @if($emergencyRepair->status == 'completed') bg-green-100 text-green-700
                            @elseif($emergencyRepair->status == 'in_progress') bg-blue-100 text-blue-700
                            @else bg-yellow-100 text-yellow-700
                            @endif">
                            {{ ucfirst(str_replace('_', ' ', $emergencyRepair->status)) }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Left Column - Details -->
                        <div class="space-y-6">
                            <!-- Vehicle Information -->
                            <div class="p-4 bg-slate-50 rounded-lg">
                                <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center">
                                    <x-base.lucide class="w-4 h-4 mr-2 text-primary" icon="Truck" />
                                    Vehicle Information
                                </h3>
                                @if($emergencyRepair->vehicle)
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-slate-500">Vehicle:</span>
                                            <span class="font-medium">{{ $emergencyRepair->vehicle->make }} {{ $emergencyRepair->vehicle->model }} {{ $emergencyRepair->vehicle->year }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-500">Unit #:</span>
                                            <span class="font-medium">{{ $emergencyRepair->vehicle->company_unit_number ?? 'N/A' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-500">VIN:</span>
                                            <span class="font-medium">{{ $emergencyRepair->vehicle->vin ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-sm text-slate-500">No vehicle assigned</p>
                                @endif
                            </div>

                            <!-- Repair Details -->
                            <div class="p-4 bg-slate-50 rounded-lg">
                                <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center">
                                    <x-base.lucide class="w-4 h-4 mr-2 text-primary" icon="FileText" />
                                    Repair Details
                                </h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Repair Date:</span>
                                        <span class="font-medium">{{ $emergencyRepair->repair_date->format('M d, Y') }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Cost:</span>
                                        <span class="font-medium text-green-600">${{ number_format($emergencyRepair->cost, 2) }}</span>
                                    </div>
                                    @if($emergencyRepair->odometer)
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Odometer:</span>
                                        <span class="font-medium">{{ number_format($emergencyRepair->odometer) }} miles</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Description & Notes -->
                        <div class="space-y-6">
                            @if($emergencyRepair->description)
                            <div class="p-4 bg-slate-50 rounded-lg">
                                <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center">
                                    <x-base.lucide class="w-4 h-4 mr-2 text-primary" icon="AlignLeft" />
                                    Description
                                </h3>
                                <p class="text-sm text-slate-600 whitespace-pre-line">{{ $emergencyRepair->description }}</p>
                            </div>
                            @endif

                            @if($emergencyRepair->notes)
                            <div class="p-4 bg-slate-50 rounded-lg">
                                <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center">
                                    <x-base.lucide class="w-4 h-4 mr-2 text-primary" icon="StickyNote" />
                                    Notes
                                </h3>
                                <p class="text-sm text-slate-600 whitespace-pre-line">{{ $emergencyRepair->notes }}</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Documents Section -->
                    @php
                        $documents = $emergencyRepair->getMedia('emergency_repair_files');
                    @endphp
                    @if($documents->count() > 0)
                    <div class="mt-6 pt-6 border-t border-slate-200/60">
                        <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center">
                            <x-base.lucide class="w-4 h-4 mr-2 text-primary" icon="Paperclip" />
                            Documents ({{ $documents->count() }})
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($documents as $media)
                                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200/60">
                                    <div class="flex items-center gap-3 min-w-0">
                                        @if(str_starts_with($media->mime_type, 'image/'))
                                            <x-base.lucide class="w-5 h-5 text-blue-500 flex-shrink-0" icon="Image" />
                                        @elseif($media->mime_type === 'application/pdf')
                                            <x-base.lucide class="w-5 h-5 text-red-500 flex-shrink-0" icon="FileText" />
                                        @else
                                            <x-base.lucide class="w-5 h-5 text-slate-500 flex-shrink-0" icon="File" />
                                        @endif
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium text-slate-800 truncate">{{ $media->file_name }}</div>
                                            <div class="text-xs text-slate-500">{{ $media->human_readable_size }}</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <a href="{{ $media->getUrl() }}" target="_blank" 
                                           class="p-1.5 text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="View">
                                            <x-base.lucide class="w-4 h-4" icon="ExternalLink" />
                                        </a>
                                        <a href="{{ $media->getUrl() }}" download 
                                           class="p-1.5 text-green-500 hover:bg-green-50 rounded-lg transition-colors" title="Download">
                                            <x-base.lucide class="w-4 h-4" icon="Download" />
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Generated Reports Section -->
                    <div class="mt-6 pt-6 border-t border-slate-200/60">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold text-slate-700 flex items-center">
                                <x-base.lucide class="w-4 h-4 mr-2 text-red-500" icon="FileText" />
                                Generated Reports
                            </h3>
                            <form action="{{ route('carrier.emergency-repairs.generate-report', $emergencyRepair->vehicle->id) }}" method="POST" class="inline">
                                @csrf
                                <x-base.button type="submit" variant="soft-success" class="btn-sm">
                                    <x-base.lucide class="mr-1 h-4 w-4" icon="FileText" />
                                    Generate Report
                                </x-base.button>
                            </form>
                        </div>

                        @php
                            $repairReports = $emergencyRepair->vehicle->documents()
                                ->where('document_type', 'repair_record')
                                ->orderBy('created_at', 'desc')
                                ->get();
                        @endphp

                        @if ($repairReports->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3">Report</th>
                                            <th class="px-4 py-3">Document #</th>
                                            <th class="px-4 py-3">Generated</th>
                                            <th class="px-4 py-3 text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($repairReports as $report)
                                            <tr class="bg-white border-b border-gray-200">
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-2">
                                                        <div class="p-1.5 bg-red-500/10 rounded">
                                                            <x-base.lucide class="h-4 w-4 text-red-600" icon="FileText" />
                                                        </div>
                                                        <span class="font-medium">Repair Record</span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">{{ $report->document_number ?? 'N/A' }}</td>
                                                <td class="px-4 py-3">
                                                    {{ $report->issued_date ? $report->issued_date->format('m/d/Y') : ($report->created_at ? $report->created_at->format('m/d/Y') : 'N/A') }}
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <div class="flex justify-center gap-2">
                                                        @php $reportMedia = $report->getFirstMedia('document_files'); @endphp
                                                        @if ($reportMedia)
                                                            <a href="{{ $reportMedia->getUrl() }}" target="_blank"
                                                                class="btn btn-primary btn-sm" title="Preview">
                                                                <x-base.lucide class="h-4 w-4" icon="Eye" />
                                                            </a>
                                                            <a href="{{ $reportMedia->getUrl() }}" download
                                                                class="btn btn-success btn-sm" title="Download">
                                                                <x-base.lucide class="h-4 w-4" icon="Download" />
                                                            </a>
                                                        @endif
                                                        <form action="{{ route('carrier.emergency-repairs.delete-report', [$emergencyRepair->vehicle->id, $report->id]) }}" method="POST" class="inline"
                                                            onsubmit="return confirm('Are you sure you want to delete this report?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete Report">
                                                                <x-base.lucide class="h-4 w-4" icon="Trash2" />
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-6">
                                <x-base.lucide class="h-10 w-10 mx-auto text-slate-300" icon="FileText" />
                                <div class="mt-2 text-slate-500 text-sm">No reports generated yet</div>
                                <div class="mt-1 text-xs text-slate-400">Click "Generate Report" to create a PDF report.</div>
                            </div>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row justify-end gap-3 mt-8 pt-6 border-t border-slate-200/60">
                        <x-base.button as="a" href="{{ route('carrier.emergency-repairs.index') }}" 
                            variant="outline-secondary" class="w-full sm:w-auto">
                            <x-base.lucide class="mr-2 h-4 w-4" icon="ArrowLeft" />
                            Back to List
                        </x-base.button>
                        <x-base.button as="a" href="{{ route('carrier.emergency-repairs.edit', $emergencyRepair) }}" 
                            variant="primary" class="w-full sm:w-auto">
                            <x-base.lucide class="mr-2 h-4 w-4" icon="Pencil" />
                            Edit Repair
                        </x-base.button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
