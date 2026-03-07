@extends('themes.base')

@section('title', 'Account Inactive - ' . $carrier->name)

@section('content')
<div class="min-h-screen bg-slate-100 py-4 px-4">
    <div class="max-w-6xl mx-auto">
        <!-- Header Section -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-4 border-4 border-red-200">
                <x-base.lucide class="w-8 h-8 text-red-600" icon="alert-triangle" />
            </div>
            <h1 class="text-3xl font-bold text-slate-800 mb-2">Account Inactive</h1>
            <p class="text-lg text-slate-600">{{ auth()->user()->name }}, your carrier account is currently inactive</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Carrier Information -->
                <div class="box box--stacked flex flex-col p-6 hover:shadow-lg transition-all duration-200 group">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-slate-100 rounded-xl border-slate-200 border group-hover:bg-slate-200 transition-colors">
                            <x-base.lucide class="w-5 h-5 text-slate-600" icon="building" />
                        </div>
                        <h2 class="text-lg font-semibold text-slate-800">Carrier Information</h2>
                        <x-base.badge variant="danger" class="ml-auto gap-1.5">
                            <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                            Inactive
                        </x-base.badge>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-slate-500">Company Name</label>
                            <p class="text-slate-800 font-semibold">{{ $carrier->name ?? 'N/A' }}</p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-slate-500">Current Status</label>
                            <div class="flex items-center gap-2">
                                <x-base.badge variant="danger" class="gap-1.5">
                                    <x-base.lucide class="w-3 h-3" icon="x-circle" />
                                    {{ $carrier->status_name ?? 'Inactive' }}
                                </x-base.badge>
                            </div>
                        </div>
                        @if($carrier->dot_number)
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-slate-500">DOT Number</label>
                            <p class="text-slate-800 font-semibold">{{ $carrier->dot_number }}</p>
                        </div>
                        @endif
                        @if($carrier->mc_number)
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-slate-500">MC Number</label>
                            <p class="text-slate-800 font-semibold">{{ $carrier->mc_number }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Account Deactivation Notice -->
                <x-carrier.info-notice type="danger" title="Account Deactivated">
                    Your carrier account has been deactivated by our administrators. This may be due to compliance issues, incomplete documentation, or other administrative reasons.
                    <br><br>
                    Please contact our support team to understand the reason for deactivation and the steps needed for reactivation.
                </x-carrier.info-notice>

                <!-- Reactivation Process -->
                <div class="box box--stacked flex flex-col p-6 hover:shadow-lg transition-all duration-200 group">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-orange-100 rounded-xl border-orange-200 border group-hover:bg-orange-200 transition-colors">
                            <x-base.lucide class="w-5 h-5 text-orange-600" icon="refresh-cw" />
                        </div>
                        <h2 class="text-lg font-semibold text-slate-800">Reactivation Process</h2>
                        <x-base.badge variant="warning" class="ml-auto gap-1.5">
                            <span class="w-1.5 h-1.5 bg-orange-500 rounded-full"></span>
                            Required
                        </x-base.badge>
                    </div>

                    <div class="space-y-4">
                        <!-- Step 1 -->
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 hover:bg-slate-50 transition-colors">
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-blue-100 rounded-lg border border-blue-200">
                                    <x-base.lucide icon="phone" class="w-4 h-4 text-blue-600" />
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-slate-800 mb-1">Contact Support</h4>
                                    <p class="text-sm text-slate-600 leading-relaxed">Reach out to our support team to understand the reason for deactivation</p>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 hover:bg-slate-50 transition-colors">
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-green-100 rounded-lg border border-green-200">
                                    <x-base.lucide icon="file-check" class="w-4 h-4 text-green-600" />
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-slate-800 mb-1">Submit Required Documents</h4>
                                    <p class="text-sm text-slate-600 leading-relaxed">Provide any missing or updated documentation as requested</p>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 hover:bg-slate-50 transition-colors">
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-purple-100 rounded-lg border border-purple-200">
                                    <x-base.lucide icon="check-circle" class="w-4 h-4 text-purple-600" />
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-slate-800 mb-1">Account Review</h4>
                                    <p class="text-sm text-slate-600 leading-relaxed">Our team will review your request and reactivate your account</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <form method="POST" action="{{ route('carrier.request.reactivation') }}" class="w-full">
                        @csrf
                        <x-base.button
                            type="submit"
                            variant="primary"
                            class="w-full justify-center h-12">
                            <x-base.lucide icon="refresh-cw" class="w-4 h-4 mr-2" />
                            Request Reactivation
                        </x-base.button>
                    </form>
                    <x-base.button
                        as="a"
                        href="mailto:support@efcts.com"
                        variant="outline"
                        class="w-full justify-center h-12">
                        <x-base.lucide icon="mail" class="w-4 h-4 mr-2" />
                        Contact Support
                    </x-base.button>
                </div>
            </div>

            <!-- Right Column - Support & Actions -->
            <div class="space-y-6">
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
</div>
@endsection