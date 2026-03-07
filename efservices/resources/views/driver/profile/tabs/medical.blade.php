{{-- Medical Tab Content --}}
<div class="space-y-6">
    <h3 class="text-lg font-semibold text-slate-800 mb-4">Medical Qualification</h3>
    
    @if($driver->medicalQualification)
        @php
            $medical = $driver->medicalQualification;
            $expiryDate = $medical->medical_card_expiration_date;
            $isExpired = $expiryDate && \Carbon\Carbon::parse($expiryDate)->isPast();
            $isExpiringSoon = $expiryDate && !$isExpired && \Carbon\Carbon::parse($expiryDate)->diffInDays(now()) <= 30;
        @endphp
        
        <!-- Medical Status Card -->
        <div class="bg-slate-50/50 rounded-lg p-5 border border-slate-100 {{ $isExpired ? 'border-l-4 border-danger' : ($isExpiringSoon ? 'border-l-4 border-warning' : ($expiryDate ? 'border-l-4 border-success' : '')) }}">
            <div class="flex items-center gap-3 mb-4">
                <x-base.lucide class="w-6 h-6 text-{{ $isExpired ? 'danger' : ($isExpiringSoon ? 'warning' : 'success') }}" icon="Heart" />
                <h4 class="font-semibold text-slate-800">Medical Certificate</h4>
                @if($isExpired)
                    <x-base.badge variant="danger">Expired</x-base.badge>
                @elseif($isExpiringSoon)
                    <x-base.badge variant="warning">Expiring Soon</x-base.badge>
                @elseif($expiryDate)
                    <x-base.badge variant="success">Valid</x-base.badge>
                @else
                    <x-base.badge variant="secondary">Not Set</x-base.badge>
                @endif
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="text-xs font-medium text-slate-500 uppercase">Expiration Date</label>
                    <p class="text-sm font-semibold {{ $isExpired ? 'text-danger' : ($isExpiringSoon ? 'text-warning' : 'text-slate-800') }}">
                        {{ $expiryDate ? \Carbon\Carbon::parse($expiryDate)->format('M d, Y') : 'N/A' }}
                    </p>
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-500 uppercase">Examiner Name</label>
                    <p class="text-sm font-semibold text-slate-800">{{ $medical->medical_examiner_name ?? $medical->examiner_name ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-500 uppercase">Registry Number</label>
                    <p class="text-sm font-semibold text-slate-800">{{ $medical->medical_examiner_registry_number ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Medical Documents -->
        @php
            $medicalDocs = collect();
            foreach(['medical_certificate', 'test_results', 'additional_documents', 'medical_documents', 'medical_card'] as $collection) {
                $medicalDocs = $medicalDocs->merge($medical->getMedia($collection));
            }
        @endphp
        
        @if($medicalDocs->count() > 0)
            <div>
                <h4 class="font-semibold text-slate-800 mb-3">Medical Documents</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($medicalDocs as $doc)
                        <div class="bg-white rounded-lg p-4 border border-slate-200 flex items-center gap-3">
                            <div class="p-2 bg-primary/10 rounded-lg">
                                <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-800 truncate">{{ $doc->file_name }}</p>
                                <p class="text-xs text-slate-500">{{ $doc->human_readable_size }}</p>
                            </div>
                            <a href="{{ $doc->getUrl() }}" target="_blank" class="text-primary hover:text-primary/80">
                                <x-base.lucide class="w-5 h-5" icon="Download" />
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <div class="text-center py-12">
            <x-base.lucide class="w-16 h-16 text-slate-300 mx-auto mb-4" icon="Heart" />
            <h4 class="text-lg font-semibold text-slate-700 mb-2">No Medical Records</h4>
            <p class="text-slate-500">You don't have any medical qualification records on file.</p>
        </div>
    @endif
</div>
