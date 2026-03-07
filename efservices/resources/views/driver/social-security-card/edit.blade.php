@extends('../themes/' . $activeTheme)
@section('title', 'Update Social Security Card')
@php
$breadcrumbLinks = [
['label' => 'Dashboard', 'url' => route('driver.dashboard')],
['label' => 'Social Security Card', 'url' => route('driver.social-security-card.index')],
['label' => 'Update', 'active' => true],
];
@endphp

@section('subcontent')
<div class="container-fluid">
    <!-- Professional Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="CreditCard" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">{{ $ssnCard ? 'Update' : 'Upload' }} Social Security Card</h1>
                    <p class="text-slate-600">{{ $ssnCard ? 'Update your social security card document' : 'Upload your social security card document' }}</p>
                </div>
            </div>
            <div class="flex flex-col sm:justify-end sm:flex-row gap-3 w-full md:w-[300px]">
                <x-base.button as="a" href="{{ route('driver.social-security-card.index') }}" variant="outline-secondary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="ArrowLeft" />
                    Back
                </x-base.button>
            </div>
        </div>
    </div>

    @if($errors->any())
    <div class="box box--stacked p-4 mb-6 border-l-4 border-danger bg-danger/10">
        <div class="flex items-start gap-3">
            <x-base.lucide class="w-5 h-5 text-danger mt-0.5" icon="AlertCircle" />
            <div>
                <p class="text-danger font-medium mb-2">Please fix the following errors:</p>
                <ul class="list-disc list-inside text-sm text-danger">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <form action="{{ route('driver.social-security-card.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <div class="box box--stacked p-6">
            <h3 class="text-lg font-semibold mb-4 text-slate-800 border-b pb-2 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
                Social Security Card Information
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-base.form-label for="social_security_number">Social Security Number</x-base.form-label>
                    <x-base.form-input type="text" id="social_security_number" name="social_security_number" 
                        value="{{ old('social_security_number', $medicalRecord->social_security_number ?? '') }}" placeholder="XXX-XX-XXXX" />
                    <p class="text-xs text-slate-400 mt-1">Optional - Enter your SSN if you wish to store it</p>
                </div>
                
                <div>
                    <x-base.form-label for="social_security_card">Social Security Card Document {{ $ssnCard ? '' : '*' }}</x-base.form-label>
                    @if($ssnCard)
                    <div class="mb-3 p-3 bg-slate-50 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
                                <a href="{{ $ssnCard->getUrl() }}" target="_blank" class="text-primary hover:underline">
                                    {{ $ssnCard->file_name }}
                                </a>
                            </div>
                            <span class="text-xs text-slate-400">{{ number_format($ssnCard->size / 1024, 1) }} KB</span>
                        </div>
                    </div>
                    @endif
                    <input type="file" id="social_security_card" name="social_security_card" accept=".pdf,.jpg,.jpeg,.png" {{ $ssnCard ? '' : 'required' }}
                        class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20" />
                    <p class="text-xs text-slate-400 mt-1">{{ $ssnCard ? 'Upload new to replace current document.' : '' }} Max 10MB, PDF/JPG/PNG</p>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 mt-6 pt-6 border-t">
                <x-base.button as="a" href="{{ route('driver.social-security-card.index') }}" variant="outline-secondary">
                    Cancel
                </x-base.button>
                <x-base.button type="submit" variant="primary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="Save" />
                    {{ $ssnCard ? 'Update' : 'Upload' }} Social Security Card
                </x-base.button>
            </div>
        </div>
    </form>
</div>
@endsection
