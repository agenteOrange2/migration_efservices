@extends('../themes/' . $activeTheme)
@section('title', 'Medical Qualification - EF Services')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'Medical', 'active' => true],
    ];
    
    $isExpired = false;
    $isExpiringSoon = false;
    $expiryDate = null;
    $daysRemaining = 0;
    
    if ($medical) {
        $dateField = $medical->medical_card_expiration_date;
        if ($dateField) {
            $expiryDate = \Carbon\Carbon::parse($dateField);
            $isExpired = $expiryDate->isPast();
            $daysRemaining = (int) now()->diffInDays($expiryDate, false);
            $isExpiringSoon = !$isExpired && $daysRemaining <= 30;
        }
    }
@endphp

@section('subcontent')

<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

 <!-- Professional Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="Heart" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Medical Qualification</h1>
                    <p class="text-slate-600">View your DOT medical certificate information</p>
                </div>
            </div>
        </div>
    </div>

@if(session('success'))
<div class="box box--stacked p-4 mb-6 border-l-4 border-success bg-success/10">
    <div class="flex items-center gap-3">
        <x-base.lucide class="w-5 h-5 text-success" icon="CheckCircle" />
        <p class="text-success font-medium">{{ session('success') }}</p>
    </div>
</div>
@endif

@if($medical)
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Social Security Information -->
        <div class="box box--stacked p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-primary" icon="CreditCard" />
                Social Security Information
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-slate-500 mb-1">Social Security Number</p>
                    <p class="font-semibold text-slate-800">{{ $medical->social_security_number ?? 'Not provided' }}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">Hire Date</p>
                    <p class="font-semibold text-slate-800">
                        {{ $medical->hire_date ? \Carbon\Carbon::parse($medical->hire_date)->format('F d, Y') : 'Not provided' }}
                    </p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-sm text-slate-500 mb-1">Location</p>
                    <p class="font-semibold text-slate-800">{{ $medical->location ?? 'Not provided' }}</p>
                </div>
            </div>
        </div>

        <!-- Medical Certificate Information -->
        <div class="box box--stacked p-6 {{ $isExpired ? 'border-l-4 border-danger' : ($isExpiringSoon ? 'border-l-4 border-warning' : '') }}">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Heart" />
                Medical Certificate Information
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-slate-500 mb-1">Expiration Date</p>
                    <p class="font-semibold {{ $isExpired ? 'text-danger' : ($isExpiringSoon ? 'text-warning' : 'text-slate-800') }}">
                        {{ $expiryDate ? $expiryDate->format('F d, Y') : 'Not set' }}
                        @if($expiryDate)
                            @if($isExpired)
                                <span class="text-sm">(Expired {{ abs($daysRemaining) }} days ago)</span>
                            @elseif($isExpiringSoon)
                                <span class="text-sm">({{ $daysRemaining }} days left)</span>
                            @endif
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">Status</p>
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
                <div>
                    <p class="text-sm text-slate-500 mb-1">Medical Examiner Name</p>
                    <p class="font-semibold text-slate-800">{{ $medical->medical_examiner_name ?? 'Not provided' }}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">Registry Number</p>
                    <p class="font-semibold text-slate-800">{{ $medical->medical_examiner_registry_number ?? 'Not provided' }}</p>
                </div>
            </div>
        </div>

        <!-- Documents -->
        <div class="box box--stacked p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
                    Medical Documents
                </h3>
            </div>
            
            @php
                $allMedia = collect();
                foreach(['medical_certificate', 'test_results', 'additional_documents', 'medical_documents', 'medical_card', 'social_security_card'] as $collection) {
                    $allMedia = $allMedia->merge($medical->getMedia($collection));
                }
            @endphp
            
            @if($allMedia->count() > 0)
            <div class="space-y-3">
                @foreach($allMedia as $doc)
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white rounded-lg">
                            @if(str_contains($doc->mime_type, 'pdf'))
                                <x-base.lucide class="w-5 h-5 text-danger" icon="FileText" />
                            @else
                                <x-base.lucide class="w-5 h-5 text-info" icon="Image" />
                            @endif
                        </div>
                        <div>
                            <p class="font-medium text-slate-800">{{ $doc->file_name }}</p>
                            <p class="text-xs text-slate-500">{{ $doc->human_readable_size }} • {{ $doc->created_at->format('M d, Y') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ $doc->getUrl() }}" target="_blank" class="p-2 text-slate-400 hover:text-primary rounded-lg hover:bg-white">
                            <x-base.lucide class="w-4 h-4" icon="Eye" />
                        </a>
                        <a href="{{ $doc->getUrl() }}" download class="p-2 text-slate-400 hover:text-primary rounded-lg hover:bg-white">
                            <x-base.lucide class="w-4 h-4" icon="Download" />
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-8 text-slate-400">
                <x-base.lucide class="w-12 h-12 mx-auto mb-2" icon="FileText" />
                <p>No documents uploaded</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Status Card -->
        <div class="box box--stacked p-6">
            <h3 class="font-semibold text-slate-800 mb-4">Compliance Status</h3>
            <div class="text-center py-4">
                @if($isExpired)
                    <div class="w-16 h-16 bg-danger/10 rounded-full flex items-center justify-center mx-auto mb-3">
                        <x-base.lucide class="w-8 h-8 text-danger" icon="AlertCircle" />
                    </div>
                    <p class="font-semibold text-danger">Certificate Expired</p>
                    <p class="text-sm text-slate-500 mt-1">Please renew your medical certificate</p>
                @elseif($isExpiringSoon)
                    <div class="w-16 h-16 bg-warning/10 rounded-full flex items-center justify-center mx-auto mb-3">
                        <x-base.lucide class="w-8 h-8 text-warning" icon="AlertTriangle" />
                    </div>
                    <p class="font-semibold text-warning">Expiring Soon</p>
                    <p class="text-sm text-slate-500 mt-1">{{ $daysRemaining }} days remaining</p>
                @elseif($expiryDate)
                    <div class="w-16 h-16 bg-success/10 rounded-full flex items-center justify-center mx-auto mb-3">
                        <x-base.lucide class="w-8 h-8 text-success" icon="CheckCircle" />
                    </div>
                    <p class="font-semibold text-success">Valid</p>
                    <p class="text-sm text-slate-500 mt-1">Your certificate is up to date</p>
                @else
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <x-base.lucide class="w-8 h-8 text-slate-400" icon="HelpCircle" />
                    </div>
                    <p class="font-semibold text-slate-600">Not Set</p>
                    <p class="text-sm text-slate-500 mt-1">Please add your expiration date</p>
                @endif
            </div>
        </div>

        <!-- Information Notice -->
        <div class="box box--stacked p-6 bg-slate-50">
            <div class="flex items-start gap-3">
                <x-base.lucide class="w-5 h-5 text-info mt-0.5" icon="Info" />
                <div>
                    <h4 class="font-medium text-slate-700 mb-1">Read Only</h4>
                    <p class="text-sm text-slate-500">This information is managed by your carrier. Contact your administrator if you need to update your medical records.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@else
<div class="box box--stacked p-12 text-center">
    <x-base.lucide class="w-16 h-16 text-slate-300 mx-auto mb-4" icon="Heart" />
    <h3 class="text-lg font-semibold text-slate-700 mb-2">No Medical Record</h3>
    <p class="text-slate-500 mb-4">You don't have a medical qualification record yet.</p>
    <div class="flex items-center justify-center gap-2 text-sm text-slate-500 bg-slate-50 rounded-lg p-4 max-w-md mx-auto">
        <x-base.lucide class="w-4 h-4 text-info" icon="Info" />
        <span>Your medical information will be added by your carrier administrator.</span>
    </div>
</div>
@endif

@endsection
