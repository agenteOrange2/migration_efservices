@extends('themes.base')

@section('title', 'Pending Validation - ' . $carrier->name)

@section('content')
<div class="min-h-screen bg-slate-100 py-4 px-4">
    <div class="max-w-6xl mx-auto">
        <!-- Header Section -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-slate-800 mb-2">Welcome, {{ auth()->user()->name }}</h1>
            <p class="text-lg text-slate-600">Your application is being processed</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Status Timeline -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Status Timeline Component -->
                <x-carrier.status-timeline current-status="pending" />

                <!-- Security Notice -->
                <x-carrier.info-notice type="info" title="Security Notice">                    
                        Your banking information is encrypted and secure. Our team is validating the details to ensure account safety and compliance with all regulatory requirements.                    
                </x-carrier.info-notice>

                <!-- Process Information -->
                <div class="box box--stacked flex flex-col p-6 hover:shadow-lg transition-all duration-200 group">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-primary/10 rounded-xl border-primary/20 border group-hover:bg-primary/20 transition-colors">
                            <x-base.lucide class="w-5 h-5 text-primary" icon="clock" />
                        </div>
                        <h2 class="text-lg font-semibold text-slate-800">What happens next?</h2>
                        <x-base.badge variant="primary" class="ml-auto gap-1.5">
                            <span class="w-1.5 h-1.5 bg-primary rounded-full"></span>
                            Process
                        </x-base.badge>
                    </div>

                    <div class="space-y-4">
                        <!-- Banking Verification Step -->
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 hover:bg-slate-50 transition-colors">
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-blue-100 rounded-lg border border-blue-200">
                                    <x-base.lucide icon="credit-card" class="w-4 h-4 text-blue-600" />
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-slate-800 mb-1">Banking Verification</h4>
                                    <p class="text-sm text-slate-600 leading-relaxed">We verify your banking information with financial institutions for security and compliance</p>
                                </div>
                            </div>
                        </div>

                        <!-- Regulatory Review Step -->
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 hover:bg-slate-50 transition-colors">
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-green-100 rounded-lg border border-green-200">
                                    <x-base.lucide icon="shield-check" class="w-4 h-4 text-green-600" />
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-slate-800 mb-1">Regulatory Review</h4>
                                    <p class="text-sm text-slate-600 leading-relaxed">Final compliance checks and regulatory validation of your carrier application</p>
                                </div>
                            </div>
                        </div>

                        <!-- Account Activation Step -->
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 hover:bg-slate-50 transition-colors">
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-purple-100 rounded-lg border border-purple-200">
                                    <x-base.lucide icon="mail" class="w-4 h-4 text-purple-600" />
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-slate-800 mb-1">Account Activation</h4>
                                    <p class="text-sm text-slate-600 leading-relaxed">You'll receive an email confirmation once your carrier account is approved and activated</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Application Summary -->
                <x-base.box class="box--stacked">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-slate-800 mb-4">Application Summary</h3>
                        <div class="space-y-3">
                            <!-- Registration Step -->
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-slate-600">Registration</span>
                                <x-base.badge variant="success" class="text-xs">
                                    <x-base.lucide icon="check" class="w-3 h-3 mr-1" />
                                    Complete
                                </x-base.badge>
                            </div>

                            <!-- Company Info Step -->
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-slate-600">Company Info</span>
                                @if($carrier->company_name && $carrier->address)
                                    <x-base.badge variant="success" class="text-xs">
                                        <x-base.lucide icon="check" class="w-3 h-3 mr-1" />
                                        Complete
                                    </x-base.badge>
                                @else
                                    <x-base.badge variant="pending" class="text-xs">
                                        <x-base.lucide icon="clock" class="w-3 h-3 mr-1" />
                                        Pending
                                    </x-base.badge>
                                @endif
                            </div>

                            <!-- Banking Info Step -->
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-slate-600">Banking Info</span>
                                @if($carrier->bankingDetails)
                                    <x-base.badge variant="success" class="text-xs">
                                        <x-base.lucide icon="check" class="w-3 h-3 mr-1" />
                                        Submitted
                                    </x-base.badge>
                                @else
                                    <x-base.badge variant="pending" class="text-xs">
                                        <x-base.lucide icon="clock" class="w-3 h-3 mr-1" />
                                        Pending
                                    </x-base.badge>
                                @endif
                            </div>

                            <!-- Banking Validation Step -->
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-slate-600">Banking Validation</span>
                                @if($carrier->bankingDetails && $carrier->bankingDetails->status === 'approved')
                                    <x-base.badge variant="success" class="text-xs">
                                        <x-base.lucide icon="check" class="w-3 h-3 mr-1" />
                                        Approved
                                    </x-base.badge>
                                @elseif($carrier->bankingDetails)
                                    <x-base.badge variant="warning" class="text-xs">
                                        <x-base.lucide icon="clock" class="w-3 h-3 mr-1" />
                                        In Progress
                                    </x-base.badge>
                                @else
                                    <x-base.badge variant="pending" class="text-xs">
                                        <x-base.lucide icon="clock" class="w-3 h-3 mr-1" />
                                        Pending
                                    </x-base.badge>
                                @endif
                            </div>

                            <!-- Final Approval Step -->
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-slate-600">Final Approval</span>
                                @if($carrier->status === 'active')
                                    <x-base.badge variant="success" class="text-xs">
                                        <x-base.lucide icon="check" class="w-3 h-3 mr-1" />
                                        Approved
                                    </x-base.badge>
                                @else
                                    <x-base.badge variant="pending" class="text-xs">
                                        <x-base.lucide icon="clock" class="w-3 h-3 mr-1" />
                                        Pending
                                    </x-base.badge>
                                @endif
                            </div>
                        </div>
                    </div>
                </x-base.box>
            </div>

            <!-- Right Column - Progress & Support -->
            <div class="space-y-6">
                <!-- Progress Circle -->
                <x-carrier.progress-circle
                    percentage="{{ $progress }}"
                    timer="{{ $estimatedTime['message'] ?? '2-3 days remaining' }}"
                    title="Validation Progress" />

                <!-- Contact Support Panel -->
                <x-carrier.contact-support-panel phone="+14328535493" />


                <!-- Sign Out Button -->
                <x-base.box class="box--stacked">
                    <div class="p-6">
                        <form method="POST" action="{{ route('custom.logout') }}" class="w-full">
                            @csrf
                            <x-base.button
                                type="submit"
                                variant="outline"
                                class="w-full justify-center">
                                <x-base.lucide icon="log-out" class="w-4 h-4 mr-2" />
                                Sign Out
                            </x-base.button>
                        </form>
                    </div>
                </x-base.box>
            </div>
        </div>
    </div>

    <!-- Footer Information -->
    <div class="mt-8 text-center">
        <div class="box box-stacked">
        <p class="text-sm text-slate-500">
            Need immediate assistance? Our support team is available 24/7 at
            <a href="tel:+14328535493" class="text-blue-600 hover:text-blue-800 font-medium">+1 (432) 853-5493</a>
        </p>
        </div>
    </div>
</div>
</div>
@endsection