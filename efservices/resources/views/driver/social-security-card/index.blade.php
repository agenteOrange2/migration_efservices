@extends('../themes/' . $activeTheme)
@section('title', 'Social Security Card')
@php
$breadcrumbLinks = [
['label' => 'Dashboard', 'url' => route('driver.dashboard')],
['label' => 'Social Security Card', 'active' => true],
];
@endphp

@section('subcontent')
<div class="container-fluid">
    <!-- Alerts -->
    <div class="pb-4">
        @if(session('success'))
        <x-base.alert variant="success" dismissible class="flex items-center gap-3">
            <x-base.lucide class="w-8 h-8 text-white" icon="check-circle" />
            <span class="text-white">{{ session('success') }}</span>
            <x-base.alert.dismiss-button class="btn-close">
                <x-base.lucide class="h-4 w-4 text-white" icon="X" />
            </x-base.alert.dismiss-button>
        </x-base.alert>
        @endif

        @if(session('error'))
        <x-base.alert variant="danger" dismissible>
            <span class="text-white">{{ session('error') }}</span>
            <x-base.alert.dismiss-button class="btn-close">
                <x-base.lucide class="h-4 w-4 text-white" icon="X" />
            </x-base.alert.dismiss-button>
        </x-base.alert>
        @endif
    </div>

    <!-- Professional Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="CreditCard" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">My Social Security Card</h1>
                    <p class="text-slate-600">View and manage your social security card document</p>
                </div>
            </div>
            <div class="flex flex-col sm:justify-end sm:flex-row gap-3 w-full md:w-[300px]">
                <x-base.button as="a" href="{{ route('driver.social-security-card.edit') }}" variant="primary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="Edit" />
                    {{ $ssnCard ? 'Update' : 'Upload' }} Card
                </x-base.button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- SSN Information -->
        <div class="box box--stacked p-6">
            <h3 class="text-lg font-semibold mb-4 text-slate-800 border-b pb-2 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Info" />
                Social Security Information
            </h3>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-500">Social Security Number</span>
                    @if($medicalRecord && $medicalRecord->social_security_number)
                        <span class="font-medium">***-**-{{ substr($medicalRecord->social_security_number, -4) }}</span>
                    @else
                        <span class="text-slate-400">Not provided</span>
                    @endif
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-500">Document Status</span>
                    @if($ssnCard)
                        <x-base.badge variant="success">Uploaded</x-base.badge>
                    @else
                        <x-base.badge variant="warning">Not Uploaded</x-base.badge>
                    @endif
                </div>
                @if($ssnCard)
                <div class="flex justify-between items-center py-2">
                    <span class="text-slate-500">Last Updated</span>
                    <span class="font-medium">{{ $ssnCard->created_at->format('M d, Y') }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Document -->
        <div class="box box--stacked p-6">
            <h3 class="text-lg font-semibold mb-4 text-slate-800 border-b pb-2 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
                Social Security Card Document
            </h3>
            
            @if($ssnCard)
            <div class="space-y-4">
                <div class="p-4 bg-slate-50 rounded-lg">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            @if(str_contains($ssnCard->mime_type, 'pdf'))
                                <div class="p-2 bg-red-100 rounded-lg">
                                    <x-base.lucide class="w-6 h-6 text-red-500" icon="FileText" />
                                </div>
                            @else
                                <div class="p-2 bg-blue-100 rounded-lg">
                                    <x-base.lucide class="w-6 h-6 text-blue-500" icon="Image" />
                                </div>
                            @endif
                            <div>
                                <p class="font-medium text-slate-700">{{ $ssnCard->file_name }}</p>
                                <p class="text-xs text-slate-400">{{ number_format($ssnCard->size / 1024, 1) }} KB</p>
                            </div>
                        </div>
                    </div>
                    
                    @if(str_contains($ssnCard->mime_type, 'image'))
                    <div class="mt-4">
                        <img src="{{ $ssnCard->getUrl() }}" alt="Social Security Card" class="max-w-full rounded-lg border border-slate-200" />
                    </div>
                    @endif
                    
                    <div class="mt-4 flex gap-2">
                        <x-base.button as="a" href="{{ $ssnCard->getUrl() }}" target="_blank" variant="primary" class="flex-1">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="ExternalLink" />
                            View Document
                        </x-base.button>
                        <x-base.button as="a" href="{{ $ssnCard->getUrl() }}" download variant="outline-secondary" class="flex-1">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="Download" />
                            Download
                        </x-base.button>
                    </div>
                </div>
                
                <form action="{{ route('driver.social-security-card.destroy') }}" method="POST" 
                    onsubmit="return confirm('Are you sure you want to delete your social security card document?');">
                    @csrf
                    @method('DELETE')
                    <x-base.button type="submit" variant="outline-danger" class="w-full">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="Trash2" />
                        Delete Document
                    </x-base.button>
                </form>
            </div>
            @else
            <div class="text-center py-8 text-slate-500">
                <x-base.lucide class="w-12 h-12 mx-auto mb-3 text-slate-300" icon="FileX" />
                <p class="mb-4">No social security card uploaded yet</p>
                <x-base.button as="a" href="{{ route('driver.social-security-card.edit') }}" variant="primary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="Upload" />
                    Upload Now
                </x-base.button>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
