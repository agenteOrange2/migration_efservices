@php
    $data = $data ?? [];
@endphp

<div class="space-y-6">
    <div>
        <div class="flex items-center gap-3 mb-6">
            <x-base.lucide class="w-5 h-5 text-primary" icon="Briefcase" />
            <h3 class="text-lg font-semibold text-slate-800">Employment History</h3>
        </div>
        
        @if(!empty($data) && is_array($data))
            <div class="space-y-4">
                @foreach($data as $employment)
                    <div class="box box--stacked p-6">
                        <div class="flex items-start justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-primary/10 rounded-lg">
                                    <x-base.lucide class="w-5 h-5 text-primary" icon="Building" />
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold text-slate-800">
                                        {{ $employment['employer_name'] ?? 'Unknown Employer' }}
                                    </h4>
                                    @if(isset($employment['position']))
                                        <p class="text-sm text-slate-600 mt-1">{{ $employment['position'] }}</p>
                                    @endif
                                </div>
                            </div>
                            @if(isset($employment['is_verified']) && $employment['is_verified'])
                                <x-base.badge variant="success" class="gap-1.5">
                                    <span class="w-1.5 h-1.5 bg-success rounded-full"></span>
                                    Verified
                                </x-base.badge>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Employment Period -->
                            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Employment Period</label>
                                <div class="flex items-center gap-2">
                                    <x-base.lucide class="w-4 h-4 text-slate-400" icon="Calendar" />
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">
                                            @if(isset($employment['start_date']))
                                                {{ \Carbon\Carbon::parse($employment['start_date'])->format('M d, Y') }}
                                            @else
                                                N/A
                                            @endif
                                            <span class="text-slate-400 mx-2">-</span>
                                            @if(isset($employment['end_date']))
                                                {{ \Carbon\Carbon::parse($employment['end_date'])->format('M d, Y') }}
                                            @else
                                                Present
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            @if(isset($employment['contact_name']) || isset($employment['phone']) || isset($employment['email']))
                            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Contact</label>
                                <div class="space-y-1">
                                    @if(isset($employment['contact_name']) && $employment['contact_name'])
                                        <div class="flex items-center gap-2">
                                            <x-base.lucide class="w-4 h-4 text-slate-400" icon="User" />
                                            <p class="text-sm font-semibold text-slate-800">{{ $employment['contact_name'] }}</p>
                                        </div>
                                    @endif
                                    @if(isset($employment['phone']) && $employment['phone'])
                                        <div class="flex items-center gap-2">
                                            <x-base.lucide class="w-4 h-4 text-slate-400" icon="Phone" />
                                            <p class="text-sm font-semibold text-slate-800">{{ $employment['phone'] }}</p>
                                        </div>
                                    @endif
                                    @if(isset($employment['email']) && $employment['email'])
                                        <div class="flex items-center gap-2">
                                            <x-base.lucide class="w-4 h-4 text-slate-400" icon="Mail" />
                                            <p class="text-sm font-semibold text-slate-800">{{ $employment['email'] }}</p>
                                        </div>
                                    @endif
                                    @if(isset($employment['fax']) && $employment['fax'])
                                        <div class="flex items-center gap-2">
                                            <x-base.lucide class="w-4 h-4 text-slate-400" icon="Printer" />
                                            <p class="text-sm text-slate-600">Fax: {{ $employment['fax'] }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Address -->
                            @if(isset($employment['address']))
                            <div class="md:col-span-2 bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Address</label>
                                <div class="flex items-start gap-2">
                                    <x-base.lucide class="w-4 h-4 text-slate-400 mt-0.5" icon="MapPin" />
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">{{ $employment['address'] }}</p>
                                        @if(isset($employment['city']) || isset($employment['state']) || isset($employment['zip']))
                                            <p class="text-sm text-slate-600 mt-1">
                                                {{ $employment['city'] ?? '' }}{{ isset($employment['city']) && isset($employment['state']) ? ', ' : '' }}{{ $employment['state'] ?? '' }} {{ $employment['zip'] ?? '' }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Reason for Leaving -->
                            @if(isset($employment['reason_for_leaving']))
                            <div class="md:col-span-2 bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Reason for Leaving</label>
                                <p class="text-sm font-semibold text-slate-800">{{ $employment['reason_for_leaving'] }}</p>
                            </div>
                            @endif

                            <!-- FMCSR and Drug Testing Info -->
                            @if(isset($employment['was_subject_to_fmcsr']) || isset($employment['was_subject_to_drug_testing']))
                            <div class="md:col-span-2 bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Regulatory Compliance</label>
                                <div class="flex flex-wrap gap-4">
                                    @if(isset($employment['was_subject_to_fmcsr']))
                                        <div class="flex items-center gap-2">
                                            @if($employment['was_subject_to_fmcsr'])
                                                <x-base.lucide class="w-4 h-4 text-success" icon="CheckCircle" />
                                                <span class="text-sm text-slate-700">Subject to FMCSR</span>
                                            @else
                                                <x-base.lucide class="w-4 h-4 text-slate-400" icon="XCircle" />
                                                <span class="text-sm text-slate-500">Not Subject to FMCSR</span>
                                            @endif
                                        </div>
                                    @endif
                                    @if(isset($employment['was_subject_to_drug_testing']))
                                        <div class="flex items-center gap-2">
                                            @if($employment['was_subject_to_drug_testing'])
                                                <x-base.lucide class="w-4 h-4 text-success" icon="CheckCircle" />
                                                <span class="text-sm text-slate-700">Drug/Alcohol Testing Required</span>
                                            @else
                                                <x-base.lucide class="w-4 h-4 text-slate-400" icon="XCircle" />
                                                <span class="text-sm text-slate-500">No Drug/Alcohol Testing</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Verification Details -->
                            @if(isset($employment['is_verified']) && $employment['is_verified'])
                            <div class="md:col-span-2 pt-4 border-t border-slate-200/60">
                                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Verification Details</label>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <x-base.lucide class="w-4 h-4 text-success" icon="CheckCircle" />
                                        <p class="text-sm text-slate-700">Employment Verified</p>
                                    </div>
                                    @if(isset($employment['verified_at']) && $employment['verified_at'])
                                        <div class="flex items-center gap-2">
                                            <x-base.lucide class="w-4 h-4 text-slate-400" icon="Calendar" />
                                            <p class="text-sm text-slate-700">Date: {{ \Carbon\Carbon::parse($employment['verified_at'])->format('M d, Y') }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Notes -->
                            @if(isset($employment['notes']) && !empty($employment['notes']))
                            <div class="md:col-span-2 bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Notes</label>
                                <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $employment['notes'] }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <x-base.lucide class="w-16 h-16 mx-auto text-slate-300 mb-4" icon="Briefcase" />
                <h3 class="text-lg font-semibold text-slate-800 mb-2">No Employment History Available</h3>
                <p class="text-slate-500 text-sm">This archived record does not contain employment history information.</p>
            </div>
        @endif
    </div>
</div>
