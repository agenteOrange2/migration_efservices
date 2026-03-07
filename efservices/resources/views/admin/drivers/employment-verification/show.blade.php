@extends('../themes/' . $activeTheme)
@section('title', 'Employment Verification Details')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Employment Verifications', 'url' => route('admin.drivers.employment-verification.index')],
        ['label' => 'Details', 'active' => true],
    ];
    
    $latestToken = $employmentCompany->verificationTokens()->latest()->first();
@endphp

@section('subcontent')

<!-- Professional Breadcrumbs -->
<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<!-- Professional Header -->
<div class="box box--stacked p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                <x-base.lucide class="w-8 h-8 text-primary" icon="FileCheck" />
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">Employment Verification</h1>
                <div class="flex items-center gap-3">
                    <p class="text-slate-600">
                        {{ $employmentCompany->userDriverDetail->user->name ?? 'N/A' }}
                        {{ $employmentCompany->userDriverDetail->last_name ?? '' }}
                    </p>
                    @if ($employmentCompany->verification_status == 'verified')
                        <x-base.badge variant="success" class="gap-1.5">
                            <span class="w-2 h-2 bg-success rounded-full"></span>
                            Verified
                        </x-base.badge>
                    @elseif($employmentCompany->verification_status == 'rejected')
                        <x-base.badge variant="danger" class="gap-1.5">
                            <span class="w-2 h-2 bg-danger rounded-full"></span>
                            Rejected
                        </x-base.badge>
                    @else
                        <x-base.badge variant="warning" class="gap-1.5">
                            <span class="w-2 h-2 bg-warning rounded-full"></span>
                            Pending
                        </x-base.badge>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <x-base.button as="a" href="{{ route('admin.drivers.employment-verification.index') }}" variant="secondary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                Back to List
            </x-base.button>
            <x-base.button as="a" href="{{ route('admin.drivers.show', $employmentCompany->user_driver_detail_id) }}" variant="primary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="User" />
                View Driver
            </x-base.button>
        </div>
    </div>
</div>

<div class="grid grid-cols-12 gap-6">
    <!-- Employment Information -->
    <div class="col-span-12 lg:col-span-6">
        <div class="box box--stacked flex flex-col p-6 h-fit">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Briefcase" />
                <h2 class="text-lg font-semibold text-slate-800">Employment Information</h2>
            </div>

            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Driver</label>
                        <p class="text-sm font-semibold text-slate-800">
                            {{ $employmentCompany->userDriverDetail->user->name ?? 'N/A' }}
                            {{ $employmentCompany->userDriverDetail->last_name ?? '' }}
                        </p>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Company</label>
                        <p class="text-sm font-semibold text-slate-800">
                            {{ $employmentCompany->masterCompany ? $employmentCompany->masterCompany->company_name : ($employmentCompany->company_name ?? 'Custom Company') }}
                        </p>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Position</label>
                        <p class="text-sm font-semibold text-slate-800">{{ $employmentCompany->positions_held ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Email</label>
                        <p class="text-sm font-semibold text-slate-800">{{ $employmentCompany->email ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 col-span-2">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Employment Period</label>
                        <p class="text-sm font-semibold text-slate-800">
                            {{ $employmentCompany->employed_from ? $employmentCompany->employed_from->format('M d, Y') : 'N/A' }}
                            -
                            {{ $employmentCompany->employed_to ? $employmentCompany->employed_to->format('M d, Y') : 'Present' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Email & Verification Status -->
    <div class="col-span-12 lg:col-span-6">
        <div class="box box--stacked flex flex-col p-6 h-fit">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Mail" />
                <h2 class="text-lg font-semibold text-slate-800">Email & Verification Status</h2>
            </div>

            <div class="space-y-4">
                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Email Status</label>
                    @if($employmentCompany->email_sent)
                        <x-base.badge variant="success" class="gap-1.5">
                            <span class="w-1.5 h-1.5 bg-success rounded-full"></span>
                            Email Sent
                        </x-base.badge>
                    @else
                        <x-base.badge variant="warning" class="gap-1.5">
                            <span class="w-1.5 h-1.5 bg-warning rounded-full"></span>
                            Not Sent
                        </x-base.badge>
                    @endif
                </div>

                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Verification Status</label>
                    @if ($employmentCompany->verification_status == 'verified')
                        <x-base.badge variant="success" class="gap-1.5">
                            <x-base.lucide class="w-3 h-3" icon="CheckCircle" />
                            Verified
                        </x-base.badge>
                    @elseif($employmentCompany->verification_status == 'rejected')
                        <x-base.badge variant="danger" class="gap-1.5">
                            <x-base.lucide class="w-3 h-3" icon="XCircle" />
                            Rejected
                        </x-base.badge>
                    @else
                        <x-base.badge variant="warning" class="gap-1.5">
                            <x-base.lucide class="w-3 h-3" icon="Clock" />
                            Pending
                        </x-base.badge>
                    @endif
                </div>

                @if($employmentCompany->verification_date)
                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Verification Date</label>
                    <p class="text-sm font-semibold text-slate-800">
                        {{ $employmentCompany->verification_date->format('M d, Y H:i') }}
                    </p>
                </div>
                @endif

                @if($employmentCompany->verification_notes)
                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Notes</label>
                    <p class="text-sm text-slate-700">{{ $employmentCompany->verification_notes }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Verification Token Status -->
    @if($latestToken)
    <div class="col-span-12">
        <div class="box box--stacked flex flex-col p-6">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Key" />
                <h2 class="text-lg font-semibold text-slate-800">Verification Token Status</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Token Status</label>
                    @if($latestToken->isVerified())
                        <x-base.badge variant="success" class="gap-1.5">
                            <x-base.lucide class="w-3 h-3" icon="CheckCircle" />
                            Verified
                        </x-base.badge>
                    @elseif($latestToken->isExpired())
                        <x-base.badge variant="danger" class="gap-1.5">
                            <x-base.lucide class="w-3 h-3" icon="XCircle" />
                            Expired
                        </x-base.badge>
                    @else
                        <x-base.badge variant="primary" class="gap-1.5">
                            <x-base.lucide class="w-3 h-3" icon="Clock" />
                            Active
                        </x-base.badge>
                    @endif
                </div>
                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Created At</label>
                    <p class="text-sm font-semibold text-slate-800">{{ $latestToken->created_at->format('M d, Y H:i') }}</p>
                </div>
                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Expires At</label>
                    <p class="text-sm font-semibold text-slate-800">{{ $latestToken->expires_at->format('M d, Y H:i') }}</p>
                </div>
                @if($latestToken->verified_at)
                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Verified At</label>
                    <p class="text-sm font-semibold text-slate-800">{{ $latestToken->verified_at->format('M d, Y H:i') }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Admin Actions -->
    @if($employmentCompany->email)
    @php
        $totalAttempts = $employmentCompany->verificationTokens()->count();
        $maxAttempts = 3;
        $attemptsRemaining = $maxAttempts - $totalAttempts;
        $canSendMore = $totalAttempts < $maxAttempts;
    @endphp
    <div class="col-span-12">
        <div class="box box--stacked flex flex-col p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Settings" />
                    <h2 class="text-lg font-semibold text-slate-800">Admin Actions</h2>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-slate-600">Attempts:</span>
                    <x-base.badge variant="{{ $canSendMore ? 'primary' : 'danger' }}">
                        {{ $totalAttempts }}/{{ $maxAttempts }}
                    </x-base.badge>
                </div>
            </div>

            @if(!$canSendMore)
            <div class="mb-4 p-4 bg-danger/10 border border-danger/20 rounded-lg">
                <div class="flex items-center gap-2 text-danger">
                    <x-base.lucide class="w-5 h-5" icon="AlertTriangle" />
                    <span class="font-medium">Maximum verification attempts (3) reached. No more emails can be sent.</span>
                </div>
            </div>
            @endif

            <div class="flex flex-wrap gap-3">
                @if($canSendMore)
                <form method="POST" action="{{ route('admin.drivers.employment-verification.resend', $employmentCompany->id) }}">
                    @csrf
                    <x-base.button type="submit" variant="primary" class="gap-2"
                        onclick="return confirm('Are you sure you want to {{ $employmentCompany->email_sent ? 'resend' : 'send' }} the verification email? ({{ $attemptsRemaining }} attempt(s) remaining)')">
                        <x-base.lucide class="w-4 h-4" icon="Mail" />
                        {{ $employmentCompany->email_sent ? 'Resend Email' : 'Send Email' }} ({{ $attemptsRemaining }} left)
                    </x-base.button>
                </form>
                @else
                <x-base.button variant="secondary" class="gap-2 opacity-50 cursor-not-allowed" disabled>
                    <x-base.lucide class="w-4 h-4" icon="Mail" />
                    No Attempts Remaining
                </x-base.button>
                @endif
                
                <form method="POST" action="{{ route('admin.drivers.employment-verification.toggle-email-flag', $employmentCompany->id) }}">
                    @csrf
                    @method('PATCH')
                    <x-base.button type="submit" 
                        variant="{{ $employmentCompany->email_sent ? 'secondary' : 'success' }}" 
                        class="gap-2"
                        onclick="return confirm('Are you sure you want to {{ $employmentCompany->email_sent ? 'mark as not sent' : 'mark as sent' }}?')">
                        <x-base.lucide class="w-4 h-4" icon="{{ $employmentCompany->email_sent ? 'X' : 'Check' }}" />
                        {{ $employmentCompany->email_sent ? 'Mark Not Sent' : 'Mark Sent' }}
                    </x-base.button>
                </form>

                @if($employmentCompany->verification_status !== 'verified')
                <form method="POST" action="{{ route('admin.drivers.employment-verification.mark-verified', $employmentCompany->id) }}">
                    @csrf
                    <x-base.button type="submit" variant="success" class="gap-2"
                        onclick="return confirm('Are you sure you want to mark this as verified?')">
                        <x-base.lucide class="w-4 h-4" icon="CheckCircle" />
                        Mark as Verified
                    </x-base.button>
                </form>
                @endif

                @if($employmentCompany->verification_status !== 'rejected')
                <form method="POST" action="{{ route('admin.drivers.employment-verification.mark-rejected', $employmentCompany->id) }}">
                    @csrf
                    <x-base.button type="submit" variant="danger" class="gap-2"
                        onclick="return confirm('Are you sure you want to mark this as rejected?')">
                        <x-base.lucide class="w-4 h-4" icon="XCircle" />
                        Mark as Rejected
                    </x-base.button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Upload Documents Section -->
    <div class="col-span-12">
        <div class="box box--stacked flex flex-col p-6">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Upload" />
                <h2 class="text-lg font-semibold text-slate-800">Upload Additional Documents</h2>
            </div>

            <form action="{{ route('admin.drivers.employment-verification.upload-manual-verification', $employmentCompany->id) }}" 
                  method="POST" 
                  enctype="multipart/form-data"
                  class="space-y-4">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="verification_document" class="block text-sm font-medium text-slate-700 mb-2">
                            Verification Document *
                        </label>
                        <input type="file" 
                               id="verification_document" 
                               name="verification_document" 
                               accept=".pdf,.jpg,.jpeg,.png"
                               required
                               class="w-full text-sm border-slate-200 shadow-sm rounded-lg file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary/90">
                        <p class="mt-1 text-xs text-slate-500">Accepted formats: PDF, JPG, PNG (Max: 10MB)</p>
                    </div>

                    <div>
                        <label for="verification_date" class="block text-sm font-medium text-slate-700 mb-2">
                            Verification Date *
                        </label>
                        <input type="date" 
                               id="verification_date" 
                               name="verification_date" 
                               required
                               value="{{ date('Y-m-d') }}"
                               class="w-full text-sm border-slate-200 shadow-sm rounded-lg py-2.5 px-3">
                    </div>
                </div>

                <div>
                    <label for="verification_notes" class="block text-sm font-medium text-slate-700 mb-2">
                        Notes (Optional)
                    </label>
                    <textarea id="verification_notes" 
                              name="verification_notes" 
                              rows="3"
                              maxlength="500"
                              placeholder="Add any additional notes about this verification..."
                              class="w-full text-sm border-slate-200 shadow-sm rounded-lg py-2.5 px-3"></textarea>
                    <p class="mt-1 text-xs text-slate-500">Maximum 500 characters</p>
                </div>

                <div class="flex justify-end">
                    <x-base.button type="submit" variant="primary" class="gap-2">
                        <x-base.lucide class="w-4 h-4" icon="Upload" />
                        Upload Document
                    </x-base.button>
                </div>
            </form>

            <!-- Uploaded Documents List -->
            @if($employmentCompany->getMedia('employment_verification_documents')->count() > 0)
            <div class="mt-8 pt-6 border-t border-slate-200/60">
                <h3 class="text-sm font-semibold text-slate-700 mb-4">Uploaded Documents ({{ $employmentCompany->getMedia('employment_verification_documents')->count() }})</h3>
                <div class="space-y-2">
                    @foreach($employmentCompany->getMedia('employment_verification_documents') as $document)
                    <div class="flex items-center justify-between p-3 bg-slate-50/50 rounded-lg border border-slate-100 hover:border-slate-200 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-primary/10 rounded-lg">
                                <x-base.lucide class="w-4 h-4 text-primary" icon="FileText" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-800">
                                    {{ $document->getCustomProperty('original_name') ?? $document->file_name }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    Uploaded {{ $document->created_at->format('M d, Y H:i') }}
                                    @if($document->getCustomProperty('uploaded_by'))
                                        by {{ $document->getCustomProperty('uploaded_by') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-base.button as="a" 
                                href="{{ $document->getUrl() }}" 
                                target="_blank"
                                variant="outline-primary" 
                                size="sm"
                                class="gap-1.5">
                                <x-base.lucide class="w-3 h-3" icon="Eye" />
                                View
                            </x-base.button>
                            <x-base.button as="a" 
                                href="{{ $document->getUrl() }}" 
                                download
                                variant="outline-secondary" 
                                size="sm"
                                class="gap-1.5">
                                <x-base.lucide class="w-3 h-3" icon="Download" />
                                Download
                            </x-base.button>
                            <form method="POST" 
                                  action="{{ route('admin.drivers.employment-verification.delete-document', [$employmentCompany->id, $document->id]) }}"
                                  class="inline"
                                  onsubmit="return confirm('Are you sure you want to delete this document?');">
                                @csrf
                                @method('DELETE')
                                <x-base.button type="submit"
                                    variant="outline-danger" 
                                    size="sm"
                                    class="gap-1.5">
                                    <x-base.lucide class="w-3 h-3" icon="Trash2" />
                                    Delete
                                </x-base.button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Verification History -->
    @if($employmentCompany->verificationTokens->count() > 0)
    <div class="col-span-12">
        <div class="box box--stacked">
            <div class="p-6 border-b border-slate-200/60">
                <div class="flex items-center gap-3">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="History" />
                    <h2 class="text-lg font-semibold text-slate-800">Verification History</h2>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200/60">
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">#</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Sent To</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Sent Date</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Expires</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Verified At</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">PDF</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/60">
                        @php
                            $tokens = $employmentCompany->verificationTokens()->orderBy('created_at', 'asc')->get();
                            $attemptPdfs = $employmentCompany->userDriverDetail ? $employmentCompany->userDriverDetail->getMedia('employment_verification_attempts')->filter(function($media) use ($employmentCompany) {
                                return $media->getCustomProperty('company_id') == $employmentCompany->id;
                            })->sortBy('created_at') : collect();
                        @endphp
                        @foreach($tokens as $index => $token)
                        @php
                            $attemptNumber = $index + 1;
                            // Find PDF by matching token creation time (within 60 seconds)
                            $tokenTime = $token->created_at;
                            $attemptPdf = $attemptPdfs->first(function($media) use ($tokenTime) {
                                $pdfTime = $media->created_at;
                                return abs($tokenTime->diffInSeconds($pdfTime)) < 60;
                            });
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm font-semibold text-slate-700">{{ $attemptNumber }}</td>
                            <td class="px-6 py-4 text-sm text-slate-700">{{ $token->email ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-slate-700">{{ $token->created_at->format('M d, Y H:i') }}</td>
                            <td class="px-6 py-4 text-sm text-slate-700">{{ $token->expires_at->format('M d, Y H:i') }}</td>
                            <td class="px-6 py-4">
                                @if($token->isVerified())
                                    <x-base.badge variant="success" class="gap-1.5">
                                        <span class="w-1.5 h-1.5 bg-success rounded-full"></span>
                                        Verified
                                    </x-base.badge>
                                @elseif($token->isExpired())
                                    <x-base.badge variant="danger" class="gap-1.5">
                                        <span class="w-1.5 h-1.5 bg-danger rounded-full"></span>
                                        Expired
                                    </x-base.badge>
                                @else
                                    <x-base.badge variant="primary" class="gap-1.5">
                                        <span class="w-1.5 h-1.5 bg-primary rounded-full"></span>
                                        Active
                                    </x-base.badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-700">
                                {{ $token->verified_at ? $token->verified_at->format('M d, Y H:i') : '-' }}
                            </td>
                            <td class="px-6 py-4">
                                @if($attemptPdf)
                                    <x-base.button as="a" 
                                        href="{{ $attemptPdf->getUrl() }}" 
                                        target="_blank"
                                        variant="outline-primary" 
                                        size="sm"
                                        class="gap-1.5">
                                        <x-base.lucide class="w-3 h-3" icon="FileText" />
                                        View PDF
                                    </x-base.button>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if(!$token->isVerified())
                                    <form method="POST" 
                                          action="{{ route('admin.drivers.employment-verification.delete-token', [$employmentCompany->id, $token->id]) }}"
                                          class="inline"
                                          onsubmit="return confirm('Are you sure you want to delete this verification attempt? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <x-base.button type="submit"
                                            variant="outline-danger" 
                                            size="sm"
                                            class="gap-1.5">
                                            <x-base.lucide class="w-3 h-3" icon="Trash2" />
                                            Delete
                                        </x-base.button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Lucide.createIcons();
        });
    </script>
@endpush
