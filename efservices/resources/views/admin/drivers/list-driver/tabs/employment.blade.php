{{-- Employment Tab --}}
<div class="space-y-6">
    {{-- Employment History --}}
    @if ($driver->employmentCompanies && $driver->employmentCompanies->count() > 0)
    <x-driver.info-card title="Employment History" icon="history">
        <div class="space-y-4">
            @foreach ($driver->employmentCompanies as $employment)
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h4 class="font-medium text-gray-900">{{ $employment->company ? $employment->company->company_name : 'Company' }}</h4>
                            
                            {{-- Email Status Badge --}}
                            @if($employment->email)
                                @if($employment->email_sent)
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>Email Sent
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-exclamation-circle mr-1"></i>Not Sent
                                    </span>
                                @endif
                            @else
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">
                                    <i class="fas fa-times-circle mr-1"></i>No Email
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600">Position: {{ $employment->positions_held }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-900">
                            {{ $employment->employed_from ? $employment->employed_from->format('M Y') : '' }}
                            -
                            {{ $employment->employed_to ? $employment->employed_to->format('M Y') : 'Present' }}
                        </p>
                        <p class="text-xs text-gray-500">
                            @if($employment->employed_from && $employment->employed_to)
                            {{ round($employment->employed_from->diffInMonths($employment->employed_to)) }} months
                            @endif
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <label class="text-xs font-medium text-gray-500">Address</label>
                        <p class="text-gray-900">{{ $employment->company->address ?? '' }}</p>
                        <p class="text-gray-900">{{ $employment->company->city ?? '' }},
                            {{ $employment->company->state ?? '' }}
                            {{ $employment->company->zip ?? '' }}
                        </p>
                    </div>
                    <div>
                        @if ($employment->company->email)
                        <label class="text-xs font-medium text-gray-500">Email</label>
                        <p class="text-gray-900">{{ $employment->company->email ?? 'N/A' }}</p>
                        @else
                        <label class="text-xs font-medium text-gray-500">Email</label>
                        <p class="text-gray-900">N/A</p>
                        @endif
                    </div>
                    <div>
                        @if ($employment->company->phone)
                        <label class="text-xs font-medium text-gray-500">Contact</label>
                        <p class="text-gray-900">{{ $employment->company->phone ?? 'N/A' }}</p>
                        @else
                        <label class="text-xs font-medium text-gray-500">Contact</label>
                        <p class="text-gray-900">N/A</p>
                        @endif
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500">Driver Type</label>
                        <p>
                            <span class="bg-primary text-white text-xs font-medium px-2.5 py-0.5 rounded">
                                {{ $employment->positions_held }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-t border-gray-200">
                    <label class="text-xs font-medium text-gray-500">Regulatory and Safety Designations</label>
                    <p>
                        <span
                            class="bg-{{ $employment->subject_to_fmcsr ? 'green' : 'red' }}-100 text-{{ $employment->subject_to_fmcsr ? 'green' : 'red' }}-800 text-xs font-medium px-2.5 py-0.5 rounded">
                            {{ $employment->subject_to_fmcsr ? 'Subject to FMCSR' : 'Not subject to FMCSR' }}
                        </span>
                    </p>
                    <p>
                        <span
                            class="bg-{{ $employment->safety_sensitive_function ? 'green' : 'red' }}-100 text-{{ $employment->safety_sensitive_function ? 'green' : 'red' }}-800 text-xs font-medium px-2.5 py-0.5 rounded">
                            {{ $employment->safety_sensitive_function ? 'Safety Sensitive Function' : 'No Safety Sensitive Function' }}
                        </span>
                    </p>
                </div>

                @if($employment->reason_for_leaving)
                <div class="mt-3 pt-3 border-t border-gray-200">
                    <label class="text-xs font-medium text-gray-500">Reason for Leaving</label>
                    <p class="text-sm text-gray-900">{{ $employment->reason_for_leaving }}</p>
                </div>
                @endif
                @if($employment->comment)
                <div class="mt-3 pt-3 border-t border-gray-200">
                    <label class="text-xs font-medium text-gray-500">Comment</label>
                    <p class="text-sm text-gray-900">{{ $employment->comment }}</p>
                </div>
                @endif

                {{-- Admin Actions Section --}}
                @if($employment->email)
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="text-xs font-medium text-gray-500">Admin Actions</label>
                            
                            {{-- Verification Token Status --}}
                            @php
                                $latestToken = $employment->verificationTokens()->latest()->first();
                            @endphp
                            
                            @if($latestToken)
                                <div class="mt-2 text-sm">
                                    <p class="text-gray-700">
                                        <span class="font-medium">Token Status:</span>
                                        @if($latestToken->isVerified())
                                            <span class="text-green-600">
                                                <i class="fas fa-check-circle"></i> Verified on {{ $latestToken->verified_at->format('M d, Y') }}
                                            </span>
                                        @elseif($latestToken->isExpired())
                                            <span class="text-red-600">
                                                <i class="fas fa-times-circle"></i> Expired on {{ $latestToken->expires_at->format('M d, Y') }}
                                            </span>
                                        @else
                                            <span class="text-blue-600">
                                                <i class="fas fa-clock"></i> Active (expires {{ $latestToken->expires_at->format('M d, Y') }})
                                            </span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Token created: {{ $latestToken->created_at->format('M d, Y H:i') }}
                                    </p>
                                </div>
                            @else
                                <p class="mt-2 text-sm text-gray-500">No verification token generated yet</p>
                            @endif
                        </div>
                        
                        <div class="flex gap-2">
                            {{-- Resend Email Button --}}
                            <form method="POST" action="{{ route('admin.drivers.employment-verification.resend', $employment->id) }}" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="px-3 py-2 text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                        onclick="return confirm('Are you sure you want to {{ $employment->email_sent ? 'resend' : 'send' }} the verification email?')">
                                    <i class="fas fa-envelope mr-1"></i>
                                    {{ $employment->email_sent ? 'Resend Email' : 'Send Email' }}
                                </button>
                            </form>
                            
                            {{-- Toggle Email Sent Flag --}}
                            <form method="POST" action="{{ route('admin.drivers.employment-verification.toggle-email-flag', $employment->id) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" 
                                        class="px-3 py-2 text-sm font-medium rounded-md {{ $employment->email_sent ? 'text-gray-700 bg-gray-200 hover:bg-gray-300' : 'text-white bg-green-600 hover:bg-green-700' }} focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                                        onclick="return confirm('Are you sure you want to {{ $employment->email_sent ? 'mark as not sent' : 'mark as sent' }}?')">
                                    <i class="fas fa-{{ $employment->email_sent ? 'times' : 'check' }} mr-1"></i>
                                    {{ $employment->email_sent ? 'Mark Not Sent' : 'Mark Sent' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </x-driver.info-card>
    @endif

    {{-- Related Employment --}}
    @if ($driver->relatedEmployments && $driver->relatedEmployments->count() > 0)
    <x-driver.info-card title="Related Employment" icon="briefcase">
        <div class="space-y-4">
            @foreach ($driver->relatedEmployments as $related)
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <label class="text-xs font-medium text-gray-500">Period</label>
                        <h4 class="font-medium text-gray-900">
                            {{ $related->start_date ? $related->start_date->format('M d, Y') : '' }}
                            -
                            {{ $related->end_date ? $related->end_date->format('M d, Y') : 'Present' }}
                        </h4>
                        <p class="text-sm text-gray-600">Related Employment</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-900">
                            {{ $related->start_date ? $related->start_date->format('M d, Y') : '' }}
                            -
                            {{ $related->end_date ? $related->end_date->format('M d, Y') : 'Present' }}
                        </p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="text-xs font-medium text-gray-500">Work Position</label>
                        <p class="text-gray-900">{{ $related->position }}</p>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-gray-200">
                    <label class="text-xs font-medium text-gray-500">Comments</label>
                    <p class="text-gray-900">{{ $related->comments }}</p>
                </div>

            </div>
            @endforeach
        </div>
    </x-driver.info-card>
    @endif

    {{-- Unemployment Employment --}}
    @if ($driver->unemploymentPeriods && $driver->unemploymentPeriods->count() > 0)
    <x-driver.info-card title="Unemployment Periods" icon="briefcase">
        <div class="space-y-4">
            @foreach ($driver->unemploymentPeriods as $period)
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <label class="text-xs font-medium text-gray-500">Period</label>
                        <h4 class="font-medium text-gray-900">
                            {{ $period->start_date ? $period->start_date->format('M d, Y') : '' }}
                            -
                            {{ $period->end_date ? $period->end_date->format('M d, Y') : 'Present' }}
                        </h4>
                        <p class="text-sm text-gray-600">Unemployment Period</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-900">
                            {{ $period->start_date ? $period->start_date->format('M d, Y') : '' }}
                            -
                            {{ $period->end_date ? $period->end_date->format('M d, Y') : 'Present' }}
                        </p>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-t border-gray-200">
                    <label class="text-xs font-medium text-gray-500">Comments</label>
                    <p class="text-gray-900">{{ $period->comments }}</p>
                </div>

                <div class="mt-3 pt-3 border-t border-gray-200">
                    <label class="text-xs font-medium text-gray-500">Type</label>
                    <p class="text-gray-900">
                        <span
                            class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded">
                            Unemployment
                        </span>
                    </p>
                </div>

            </div>
            @endforeach
        </div>
    </x-driver.info-card>
    @endif

    @if ($driver->getMedia('driving_records')->count() > 0)
    <div class="mt-6 pt-6 border-t border-gray-200">
        <h4 class="text-sm font-medium text-gray-900 mb-4">View Driving Records</h4>
        <div class="flex flex-wrap gap-4">
            <x-ui.action-button
                href="{{ $driver->getFirstMediaUrl('driving_records') }}"
                icon="file-text"
                variant="secondary"
                size="sm"
                target="_blank">
                View Driving Records
            </x-ui.action-button>
        </div>
    </div>
    @else
    <p class="text-slate-500">No driving records uploaded</p>
    @endif

</div>