{{-- Documents Tab Content --}}
<div class="space-y-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-slate-800">All Documents</h3>
        @if($stats['total_documents'] > 0)
            <x-base.button as="a" href="{{ route('driver.profile.download-documents') }}" variant="primary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="Download" />
                Download All
            </x-base.button>
        @endif
    </div>
    
    @php
        $documentCategories = [];
        
        // License Documents
        $licenseMedia = collect();
        foreach($driver->licenses as $license) {
            $licenseMedia = $licenseMedia->merge($license->getMedia('license_front'));
            $licenseMedia = $licenseMedia->merge($license->getMedia('license_back'));
            $licenseMedia = $licenseMedia->merge($license->getMedia('license_documents'));
        }
        if($licenseMedia->count() > 0) {
            $documentCategories['Licenses'] = $licenseMedia;
        }
        
        // Medical Documents
        if($driver->medicalQualification) {
            $medicalMedia = collect();
            foreach(['medical_certificate', 'test_results', 'additional_documents', 'medical_documents', 'medical_card'] as $collection) {
                $medicalMedia = $medicalMedia->merge($driver->medicalQualification->getMedia($collection));
            }
            if($medicalMedia->count() > 0) {
                $documentCategories['Medical'] = $medicalMedia;
            }
        }
        
        // Training Documents
        $trainingMedia = collect();
        foreach($driver->trainingSchools as $school) {
            $trainingMedia = $trainingMedia->merge($school->getMedia('school_certificates'));
        }
        foreach($driver->courses as $course) {
            $trainingMedia = $trainingMedia->merge($course->getMedia('course_certificates'));
        }
        if($trainingMedia->count() > 0) {
            $documentCategories['Training'] = $trainingMedia;
        }
        
        // Testing Documents
        $testingMedia = collect();
        if($driver->testings) {
            foreach($driver->testings as $testing) {
                $testingMedia = $testingMedia->merge($testing->getMedia('drug_test_pdf'));
                $testingMedia = $testingMedia->merge($testing->getMedia('test_results'));
                $testingMedia = $testingMedia->merge($testing->getMedia('test_certificates'));
            }
        }
        if($testingMedia->count() > 0) {
            $documentCategories['Testing'] = $testingMedia;
        }
        
        // Inspection Documents
        $inspectionMedia = collect();
        if($driver->inspections) {
            foreach($driver->inspections as $inspection) {
                $inspectionMedia = $inspectionMedia->merge($inspection->getMedia('inspection_documents'));
            }
        }
        if($inspectionMedia->count() > 0) {
            $documentCategories['Inspections'] = $inspectionMedia;
        }
        
        // Records
        $recordsMedia = collect();
        foreach(['driving_records', 'criminal_records', 'medical_records', 'clearing_house'] as $collection) {
            $recordsMedia = $recordsMedia->merge($driver->getMedia($collection));
        }
        if($recordsMedia->count() > 0) {
            $documentCategories['Records'] = $recordsMedia;
        }
        
        // Application Documents
        $applicationMedia = collect();
        if($driver->application) {
            $applicationMedia = $applicationMedia->merge($driver->application->getMedia('application_pdf'));
            $applicationMedia = $applicationMedia->merge($driver->application->getMedia('signed_application'));
        }
        foreach(['signed_application', 'application_pdf', 'lease_agreement', 'contract_documents'] as $collection) {
            $applicationMedia = $applicationMedia->merge($driver->getMedia($collection));
        }
        if($applicationMedia->count() > 0) {
            $documentCategories['Application'] = $applicationMedia;
        }
        
        // Other Documents
        $otherMedia = collect();
        foreach(['other', 'miscellaneous'] as $collection) {
            $otherMedia = $otherMedia->merge($driver->getMedia($collection));
        }
        if($otherMedia->count() > 0) {
            $documentCategories['Other'] = $otherMedia;
        }
    @endphp
    
    @if(count($documentCategories) > 0)
        <div class="space-y-6">
            @foreach($documentCategories as $category => $documents)
                <div>
                    <h4 class="font-semibold text-slate-700 mb-3 flex items-center gap-2">
                        <x-base.lucide class="w-4 h-4 text-primary" icon="Folder" />
                        {{ $category }} ({{ $documents->count() }})
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($documents as $doc)
                            <div class="bg-white rounded-lg p-4 border border-slate-200 flex items-center gap-3 hover:shadow-md transition-shadow">
                                <div class="p-2 bg-primary/10 rounded-lg">
                                    @if(str_contains($doc->mime_type, 'pdf'))
                                        <x-base.lucide class="w-5 h-5 text-danger" icon="FileText" />
                                    @elseif(str_contains($doc->mime_type, 'image'))
                                        <x-base.lucide class="w-5 h-5 text-info" icon="Image" />
                                    @else
                                        <x-base.lucide class="w-5 h-5 text-primary" icon="File" />
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-800 truncate" title="{{ $doc->file_name }}">{{ $doc->file_name }}</p>
                                    <p class="text-xs text-slate-500">{{ $doc->human_readable_size }} • {{ $doc->created_at->format('M d, Y') }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <a href="{{ $doc->getUrl() }}" target="_blank" class="text-slate-400 hover:text-primary" title="View">
                                        <x-base.lucide class="w-5 h-5" icon="Eye" />
                                    </a>
                                    <a href="{{ $doc->getUrl() }}" download class="text-slate-400 hover:text-primary" title="Download">
                                        <x-base.lucide class="w-5 h-5" icon="Download" />
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <x-base.lucide class="w-16 h-16 text-slate-300 mx-auto mb-4" icon="FileText" />
            <h4 class="text-lg font-semibold text-slate-700 mb-2">No Documents</h4>
            <p class="text-slate-500">You don't have any documents uploaded yet.</p>
        </div>
    @endif
</div>
