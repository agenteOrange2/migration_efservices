@extends('../themes/' . $activeTheme)
@section('title', 'Emergency Repair Details')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Vehicles', 'url' => route('admin.vehicles.index')],
        ['label' => 'Emergency Repairs', 'url' => route('admin.vehicles.emergency-repairs.index')],
        ['label' => 'Details', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Emergency Repair Details: {{ $emergencyRepair->repair_name }}
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <form action="{{ route('admin.vehicles.emergency-repairs.generate-single-report', $emergencyRepair->id) }}" method="POST" class="inline">
                        @csrf
                        <x-base.button type="submit"
                            class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                            variant="soft-success">
                            <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="FileText" />
                            Generate Report
                        </x-base.button>
                    </form>
                    <x-base.button as="a" href="{{ route('admin.vehicles.emergency-repairs.edit', $emergencyRepair) }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="primary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="Edit" />
                        Edit Repair
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('admin.vehicles.emergency-repairs.index') }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowLeft" />
                        Back to List
                    </x-base.button>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success-soft show flex items-center mb-2 mt-3" role="alert">
                    <x-base.lucide class="w-6 h-6 mr-2" icon="CheckCircle" />
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger-soft show flex items-center mb-2 mt-3" role="alert">
                    <x-base.lucide class="w-6 h-6 mr-2" icon="AlertOctagon" />
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-5">
                <!-- Main Details -->
                <div class="lg:col-span-2">
                    <div class="box box--stacked">
                        <div class="box-body p-5">
                            <div class="flex items-center border-b border-slate-200/60 dark:border-darkmode-400 pb-5 mb-5">
                                <div class="font-medium text-base truncate">Repair Information</div>
                                @php
                                    $statusClasses = [
                                        'pending' => 'bg-warning/20 text-warning',
                                        'in_progress' => 'bg-primary/20 text-primary',
                                        'completed' => 'bg-success/20 text-success'
                                    ];
                                @endphp
                                <div class="ml-auto flex items-center {{ $statusClasses[$emergencyRepair->status] ?? 'bg-slate-100 text-slate-500' }} rounded-full px-3 py-1 text-sm font-medium">
                                    {{ ucfirst(str_replace('_', ' ', $emergencyRepair->status)) }}
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <div class="text-slate-500 text-sm mb-1">Repair Name</div>
                                    <div class="font-medium text-lg">{{ $emergencyRepair->repair_name }}</div>
                                </div>

                                <div>
                                    <div class="text-slate-500 text-sm mb-1">Repair Date</div>
                                    <div class="font-medium">{{ $emergencyRepair->repair_date->format('m/d/Y') }}</div>
                                </div>

                                <div>
                                    <div class="text-slate-500 text-sm mb-1">Cost</div>
                                    <div class="font-medium text-lg text-success">${{ number_format($emergencyRepair->cost, 2) }}</div>
                                </div>

                                <div>
                                    <div class="text-slate-500 text-sm mb-1">Odometer</div>
                                    <div class="font-medium">{{ $emergencyRepair->odometer ? number_format($emergencyRepair->odometer) . ' miles' : 'N/A' }}</div>
                                </div>

                                <div>
                                    <div class="text-slate-500 text-sm mb-1">Created</div>
                                    <div class="font-medium">{{ $emergencyRepair->created_at->format('m/d/Y g:i A') }}</div>
                                </div>

                                @if($emergencyRepair->updated_at != $emergencyRepair->created_at)
                                    <div>
                                        <div class="text-slate-500 text-sm mb-1">Last Updated</div>
                                        <div class="font-medium">{{ $emergencyRepair->updated_at->format('m/d/Y g:i A') }}</div>
                                    </div>
                                @endif
                            </div>

                            @if($emergencyRepair->description)
                                <div class="mt-6">
                                    <div class="text-slate-500 text-sm mb-2">Description</div>
                                    <div class="bg-slate-50 dark:bg-darkmode-400 rounded-md p-4">
                                        <p class="text-slate-700 dark:text-slate-300 leading-relaxed">{{ $emergencyRepair->description }}</p>
                                    </div>
                                </div>
                            @endif

                            @if($emergencyRepair->notes)
                                <div class="mt-6">
                                    <div class="text-slate-500 text-sm mb-2">Notes</div>
                                    <div class="bg-slate-50 dark:bg-darkmode-400 rounded-md p-4">
                                        <p class="text-slate-700 dark:text-slate-300 leading-relaxed">{{ $emergencyRepair->notes }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Files Section -->
                    <div class="box box--stacked mt-6">
                        <div class="box-body p-5">
                            <div class="flex items-center border-b border-slate-200/60 dark:border-darkmode-400 pb-5 mb-5">
                                <div class="font-medium text-base truncate">Attached Files</div>
                                @if($emergencyRepair->getMedia('emergency_repair_files')->count() > 0)
                                    <div class="ml-auto text-slate-500 text-sm">
                                        {{ $emergencyRepair->getMedia('emergency_repair_files')->count() }} file(s)
                                    </div>
                                @endif
                            </div>

                            @if($emergencyRepair->getMedia('emergency_repair_files')->count() > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                    @foreach($emergencyRepair->getMedia('emergency_repair_files') as $media)
                                        <div class="border border-slate-200/60 dark:border-darkmode-400 rounded-lg p-4 hover:bg-slate-50 dark:hover:bg-darkmode-400 transition-colors">
                                            <div class="flex items-start gap-3">
                                                <div class="flex-shrink-0">
                                                    @if(str_starts_with($media->mime_type, 'image/'))
                                                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                            <x-base.lucide class="w-6 h-6 text-blue-600" icon="Image" />
                                                        </div>
                                                    @elseif($media->mime_type === 'application/pdf')
                                                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                                            <x-base.lucide class="w-6 h-6 text-red-600" icon="FileText" />
                                                        </div>
                                                    @else
                                                        <div class="w-12 h-12 bg-slate-100 rounded-lg flex items-center justify-center">
                                                            <x-base.lucide class="w-6 h-6 text-slate-600" icon="File" />
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="font-medium text-sm truncate">{{ $media->name }}</div>
                                                    <div class="text-xs text-slate-500 mt-1">{{ $media->human_readable_size }}</div>
                                                    <div class="text-xs text-slate-400 mt-1">{{ $media->created_at->format('m/d/Y') }}</div>
                                                    @if($media->getCustomProperty('uploaded_by_admin'))
                                                        <div class="text-xs text-primary mt-1">
                                                            Uploaded by: {{ $media->getCustomProperty('admin_name', 'Admin') }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="flex-shrink-0 flex items-center gap-2">
                                                    <a href="{{ $media->getUrl() }}" target="_blank" 
                                                       class="inline-flex items-center justify-center w-8 h-8 text-slate-500 hover:text-primary hover:bg-primary/10 rounded-lg transition-colors">
                                                        <x-base.lucide class="w-4 h-4" icon="ExternalLink" />
                                                    </a>
                                                    <form action="{{ route('admin.vehicles.emergency-repairs.delete-file', [$emergencyRepair->id, $media->id]) }}" 
                                                          method="POST" class="inline" 
                                                          onsubmit="return confirm('Are you sure you want to delete this file?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                class="inline-flex items-center justify-center w-8 h-8 text-danger hover:text-danger/80 hover:bg-danger/10 rounded-lg transition-colors">
                                                            <x-base.lucide class="w-4 h-4" icon="Trash2" />
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            
                                            @if(str_starts_with($media->mime_type, 'image/'))
                                                <div class="mt-3">
                                                    <img src="{{ $media->getUrl() }}" alt="{{ $media->name }}" 
                                                         class="w-full h-32 object-cover rounded-lg cursor-pointer" 
                                                         onclick="openImageModal('{{ $media->getUrl() }}', '{{ $media->name }}')">
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8 text-slate-500">
                                    <x-base.lucide class="w-12 h-12 mx-auto mb-3 text-slate-400" icon="FileText" />
                                    <p>No files attached yet.</p>
                                </div>
                            @endif

                            <!-- Upload Document Form -->
                            <div class="border-t border-slate-200/60 dark:border-darkmode-400 pt-5 mt-5">
                                <div class="flex items-center gap-2 mb-4">
                                    <x-base.lucide class="w-4 h-4 text-primary" icon="Upload" />
                                    <div class="font-medium text-base">Upload New Document</div>
                                </div>
                                <form action="{{ route('admin.vehicles.emergency-repairs.upload-document', $emergencyRepair) }}" 
                                      method="POST" enctype="multipart/form-data" class="space-y-4">
                                    @csrf
                                    
                                    <div class="bg-slate-50 dark:bg-darkmode-800 rounded-lg p-4 border border-slate-200/60">
                                        <x-base.form-label for="document" class="flex items-center gap-2 mb-3">
                                            <x-base.lucide class="w-4 h-4 text-primary" icon="Paperclip" />
                                            Select Document *
                                        </x-base.form-label>
                                        <input 
                                            id="document" 
                                            name="document" 
                                            type="file" 
                                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                            class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary/90 cursor-pointer border border-slate-200 rounded-lg @error('document') border-danger @enderror" 
                                            required />
                                        <div class="flex items-center gap-2 mt-2 text-xs text-slate-500">
                                            <x-base.lucide class="w-3 h-3" icon="Info" />
                                            <span>Accepted: PDF, JPG, PNG, DOC, DOCX (Max 10MB)</span>
                                        </div>
                                        @error('document')
                                            <div class="text-danger mt-2 text-sm flex items-center gap-1">
                                                <x-base.lucide class="w-4 h-4" icon="AlertCircle" />
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div>
                                        <x-base.form-label for="document_description">Description (Optional)</x-base.form-label>
                                        <x-base.form-input id="document_description" name="document_description" type="text"
                                            class="w-full" 
                                            placeholder="Document description" 
                                            value="{{ old('document_description') }}" />
                                    </div>

                                    <div class="flex justify-end">
                                        <x-base.button type="submit" variant="primary">
                                            <x-base.lucide class="mr-2 h-4 w-4" icon="Upload" />
                                            Upload Document
                                        </x-base.button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                    <!-- Generated Reports Section -->
                    <div class="box box--stacked mt-6">
                        <div class="box-body p-5">
                            <div class="flex items-center border-b border-slate-200/60 dark:border-darkmode-400 pb-5 mb-5">
                                <div class="font-medium text-base truncate">Generated Reports</div>
                                <div class="ml-auto">
                                    <form action="{{ route('admin.vehicles.emergency-repairs.generate-single-report', $emergencyRepair->id) }}" method="POST" class="inline">
                                        @csrf
                                        <x-base.button type="submit" variant="soft-success" class="btn-sm">
                                            <x-base.lucide class="mr-1 h-4 w-4" icon="FileText" />
                                            Generate Report
                                        </x-base.button>
                                    </form>
                                </div>
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
                                                            <form action="{{ route('admin.vehicles.delete-repair-report', [$emergencyRepair->vehicle->id, $report->id]) }}" method="POST" class="inline"
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
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <!-- Vehicle Information -->
                    <div class="box box--stacked">
                        <div class="box-body p-5">
                            <div class="flex items-center border-b border-slate-200/60 dark:border-darkmode-400 pb-5 mb-5">
                                <div class="font-medium text-base truncate">Vehicle Information</div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <div class="text-slate-500 text-sm mb-1">Vehicle</div>
                                    <div class="font-medium">{{ $emergencyRepair->vehicle->make }} {{ $emergencyRepair->vehicle->model }}</div>
                                    <div class="text-sm text-slate-500">{{ $emergencyRepair->vehicle->year }}</div>
                                </div>

                                <div>
                                    <div class="text-slate-500 text-sm mb-1">Unit Number</div>
                                    <div class="font-medium">{{ $emergencyRepair->vehicle->company_unit_number ?? 'N/A' }}</div>
                                </div>

                                <div>
                                    <div class="text-slate-500 text-sm mb-1">VIN</div>
                                    <div class="font-medium text-xs break-all">{{ $emergencyRepair->vehicle->vin ?? 'N/A' }}</div>
                                </div>

                                <div class="pt-3 border-t border-slate-200/60 dark:border-darkmode-400">
                                    <x-base.button as="a" href="{{ route('admin.vehicles.show', $emergencyRepair->vehicle) }}" 
                                        variant="outline-primary" class="w-full">
                                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="Eye" />
                                        View Vehicle Details
                                    </x-base.button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Carrier Information -->
                    @if($emergencyRepair->vehicle->carrier)
                        <div class="box box--stacked mt-6">
                            <div class="box-body p-5">
                                <div class="flex items-center border-b border-slate-200/60 dark:border-darkmode-400 pb-5 mb-5">
                                    <div class="font-medium text-base truncate">Carrier Information</div>
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <div class="text-slate-500 text-sm mb-1">Company Name</div>
                                        <div class="font-medium">{{ $emergencyRepair->vehicle->carrier->name }}</div>
                                    </div>

                                    @if($emergencyRepair->vehicle->carrier->dot_number)
                                        <div>
                                            <div class="text-slate-500 text-sm mb-1">DOT Number</div>
                                            <div class="font-medium">{{ $emergencyRepair->vehicle->carrier->dot_number }}</div>
                                        </div>
                                    @endif

                                    @if($emergencyRepair->vehicle->carrier->mc_number)
                                        <div>
                                            <div class="text-slate-500 text-sm mb-1">MC Number</div>
                                            <div class="font-medium">{{ $emergencyRepair->vehicle->carrier->mc_number }}</div>
                                        </div>
                                    @endif

                                    <div class="pt-3 border-t border-slate-200/60 dark:border-darkmode-400">
                                        <x-base.button as="a" href="{{ route('admin.carrier.show', $emergencyRepair->vehicle->carrier) }}" 
                                            variant="outline-primary" class="w-full">
                                            <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="Building" />
                                            View Carrier Details
                                        </x-base.button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Driver Information -->
                    @if($emergencyRepair->vehicle->driver)
                        <div class="box box--stacked mt-6">
                            <div class="box-body p-5">
                                <div class="flex items-center border-b border-slate-200/60 dark:border-darkmode-400 pb-5 mb-5">
                                    <div class="font-medium text-base truncate">Driver Information</div>
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <div class="text-slate-500 text-sm mb-1">Driver Name</div>
                                        <div class="font-medium">{{ $emergencyRepair->vehicle->driver->first_name }} {{ $emergencyRepair->vehicle->driver->last_name }}</div>
                                    </div>

                                    @if($emergencyRepair->vehicle->driver->email)
                                        <div>
                                            <div class="text-slate-500 text-sm mb-1">Email</div>
                                            <div class="font-medium text-sm break-all">{{ $emergencyRepair->vehicle->driver->email }}</div>
                                        </div>
                                    @endif

                                    @if($emergencyRepair->vehicle->driver->phone)
                                        <div>
                                            <div class="text-slate-500 text-sm mb-1">Phone</div>
                                            <div class="font-medium">{{ $emergencyRepair->vehicle->driver->phone }}</div>
                                        </div>
                                    @endif

                                    <div class="pt-3 border-t border-slate-200/60 dark:border-darkmode-400">
                                        <x-base.button as="a" href="{{ route('admin.drivers.show', $emergencyRepair->vehicle->driver) }}" 
                                            variant="outline-primary" class="w-full">
                                            <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="User" />
                                            View Driver Details
                                        </x-base.button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="box box--stacked mt-6">
                        <div class="box-body p-5">
                            <div class="flex items-center border-b border-slate-200/60 dark:border-darkmode-400 pb-5 mb-5">
                                <div class="font-medium text-base truncate">Actions</div>
                            </div>

                            <div class="space-y-3">
                                <x-base.button as="a" href="{{ route('admin.vehicles.emergency-repairs.edit', $emergencyRepair) }}" 
                                    variant="primary" class="w-full">
                                    <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="Edit" />
                                    Edit Repair
                                </x-base.button>

                                <form action="{{ route('admin.vehicles.emergency-repairs.destroy', $emergencyRepair) }}" 
                                      method="POST" onsubmit="return confirm('Are you sure you want to delete this emergency repair? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <x-base.button type="submit" variant="outline-danger" class="w-full">
                                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="Trash2" />
                                        Delete Repair
                                    </x-base.button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-75 flex items-center justify-center p-4">
        <div class="relative max-w-4xl max-h-full">
            <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white hover:text-gray-300 z-10">
                <x-base.lucide class="w-8 h-8" icon="X" />
            </button>
            <img id="modalImage" src="" alt="" class="max-w-full max-h-full object-contain">
            <div id="modalCaption" class="absolute bottom-4 left-4 text-white bg-black bg-opacity-50 px-3 py-1 rounded"></div>
        </div>
    </div>

    @push('scripts')
        <script>
            function openImageModal(src, caption) {
                document.getElementById('modalImage').src = src;
                document.getElementById('modalCaption').textContent = caption;
                document.getElementById('imageModal').classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }

            function closeImageModal() {
                document.getElementById('imageModal').classList.add('hidden');
                document.body.style.overflow = 'auto';
            }

            // Close modal on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeImageModal();
                }
            });

            // Close modal on background click
            document.getElementById('imageModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeImageModal();
                }
            });
        </script>
    @endpush
@endsection