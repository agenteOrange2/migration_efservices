@php
    $documentsByCategory = $documentsByCategory ?? [];
    $totalDocuments = array_sum(array_column($documentsByCategory, 'count'));
@endphp

<div class="space-y-6">
    <div>
        <div class="flex items-center gap-3 mb-6">
            <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
            <h3 class="text-lg font-semibold text-slate-800">Archived Documents</h3>
            <x-base.badge variant="info" class="gap-1.5">
                {{ $totalDocuments }} document{{ $totalDocuments !== 1 ? 's' : '' }}
            </x-base.badge>
        </div>

        <div class="box box--stacked p-4 mb-6 bg-info/5 border border-info/20">
            <div class="flex items-start gap-3">
                <x-base.lucide class="w-5 h-5 text-info mt-0.5 flex-shrink-0" icon="Info" />
                <div>
                    <p class="font-semibold text-slate-800 mb-1">Document Archive Information</p>
                    <p class="text-sm text-slate-600">
                        These documents represent all files that existed at the time of driver inactivation. 
                        Documents are organized by category for easy access. Click on any document to view or download it.
                    </p>
                </div>
            </div>
        </div>
        
        @if(!empty($documentsByCategory))
            <div class="space-y-4">
                @foreach($documentsByCategory as $categoryData)
                    <div class="box box--stacked">
                        <!-- Category Header -->
                        <div class="box-header flex items-center justify-between p-5 border-b border-slate-200/60">
                            <div class="flex items-center gap-3">
                                <x-base.lucide class="w-5 h-5 text-primary" icon="Folder" />
                                <h4 class="text-base font-semibold text-slate-800">
                                    {{ $categoryData['category'] }}
                                </h4>
                            </div>
                            <x-base.badge variant="secondary" class="gap-1.5">
                                {{ $categoryData['count'] }} document{{ $categoryData['count'] !== 1 ? 's' : '' }}
                            </x-base.badge>
                        </div>

                        <!-- Documents List -->
                        <div class="box-body p-0">
                            <div class="divide-y divide-slate-200/60">
                                @foreach($categoryData['documents'] as $document)
                                    <div class="px-6 py-4 hover:bg-slate-50/50 transition-colors">
                                        <div class="flex items-center justify-between gap-4">
                                            <div class="flex items-center gap-4 flex-1 min-w-0">
                                                <!-- File Icon -->
                                                <div class="flex-shrink-0">
                                                    @php
                                                        $mimeType = $document['mime_type'] ?? 'application/octet-stream';
                                                        $icon = 'File';
                                                        $iconColor = 'text-slate-500';
                                                        
                                                        if (str_contains($mimeType, 'pdf')) {
                                                            $icon = 'FileText';
                                                            $iconColor = 'text-danger';
                                                        } elseif (str_contains($mimeType, 'image')) {
                                                            $icon = 'Image';
                                                            $iconColor = 'text-primary';
                                                        } elseif (str_contains($mimeType, 'word') || str_contains($mimeType, 'document')) {
                                                            $icon = 'FileText';
                                                            $iconColor = 'text-info';
                                                        } elseif (str_contains($mimeType, 'excel') || str_contains($mimeType, 'spreadsheet')) {
                                                            $icon = 'FileSpreadsheet';
                                                            $iconColor = 'text-success';
                                                        }
                                                    @endphp
                                                    <div class="p-2 bg-{{ str_replace('text-', '', $iconColor) }}/10 rounded-lg">
                                                        <x-base.lucide class="h-6 w-6 {{ $iconColor }}" icon="{{ $icon }}" />
                                                    </div>
                                                </div>

                                                <!-- Document Info -->
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-semibold text-slate-800 truncate">
                                                        {{ $document['name'] }}
                                                    </p>
                                                    <div class="flex items-center gap-3 mt-1 text-xs text-slate-500">
                                                        <div class="flex items-center gap-1">
                                                            <x-base.lucide class="w-3 h-3" icon="Calendar" />
                                                            <span>{{ \Carbon\Carbon::parse($document['created_at'])->format('M j, Y') }}</span>
                                                        </div>
                                                        @if(isset($document['size']))
                                                            <span>•</span>
                                                            <div class="flex items-center gap-1">
                                                                <x-base.lucide class="w-3 h-3" icon="HardDrive" />
                                                                <span>
                                                                    {{ $document['size'] >= 1048576 
                                                                        ? number_format($document['size'] / 1048576, 2) . ' MB' 
                                                                        : number_format($document['size'] / 1024, 2) . ' KB' }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Action Button -->
                                            <div class="flex-shrink-0">
                                                @if(isset($document['url']) && !empty($document['url']))
                                                    <x-base.button 
                                                        as="a" 
                                                        href="{{ $document['url'] }}" 
                                                        target="_blank"
                                                        variant="outline-primary" 
                                                        class="gap-2">
                                                        <x-base.lucide class="w-4 h-4" icon="ExternalLink" />
                                                        View
                                                    </x-base.button>
                                                @else
                                                    <x-base.badge variant="secondary" class="gap-1.5">
                                                        <x-base.lucide class="w-4 h-4" icon="AlertCircle" />
                                                        Unavailable
                                                    </x-base.badge>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Download All Section -->
            <div class="box box--stacked p-6 bg-primary/5 border border-primary/20">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div>
                        <h4 class="text-lg font-semibold text-slate-800 mb-2">Download Complete Archive</h4>
                        <p class="text-sm text-slate-600">
                            Download all documents and a comprehensive PDF report in a single ZIP file
                        </p>
                    </div>
                    <x-base.button 
                        as="a" 
                        href="{{ route('carrier.drivers.inactive.download', $archive ?? 0) }}" 
                        variant="primary" 
                        class="gap-2">
                        <x-base.lucide class="w-5 h-5" icon="Download" />
                        Download ZIP Archive
                    </x-base.button>
                </div>
            </div>
        @else
            <div class="text-center py-16">
                <x-base.lucide class="w-16 h-16 mx-auto text-slate-300 mb-4" icon="FileX" />
                <h3 class="text-lg font-semibold text-slate-800 mb-2">No Documents Available</h3>
                <p class="text-slate-500 text-sm">
                    No documents were found for this archived driver record
                </p>
            </div>
        @endif
    </div>
</div>
