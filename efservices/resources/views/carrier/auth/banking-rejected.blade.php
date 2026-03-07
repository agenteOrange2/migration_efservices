@extends('themes.base')

@section('title', 'Banking Information Rejected - EFCTS')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="text-center mb-8">
            <div class="flex justify-center mb-4">
                <div class="p-3 bg-red-100 rounded-full">
                    <x-base.lucide icon="credit-card" class="w-8 h-8 text-red-600" />
                </div>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Banking Information Rejected</h1>
            <p class="text-lg text-slate-600">Your payment method needs to be updated to continue</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Carrier Information -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center gap-3 mb-4">
                        <x-base.lucide icon="building" class="w-5 h-5 text-slate-600" />
                        <h2 class="text-lg font-semibold text-slate-900">Carrier Information</h2>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-slate-600">Company Name</span>
                            <span class="text-sm text-slate-900 font-medium">{{ $carrier->name }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-slate-600">Status</span>
                            <x-base.badge variant="warning" size="sm">
                                <x-base.lucide icon="clock" class="w-3 h-3 mr-1" />
                                Banking Rejected
                            </x-base.badge>
                        </div>
                        @if($carrier->dot_number)
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-slate-600">DOT Number</span>
                            <span class="text-sm text-slate-900 font-medium">{{ $carrier->dot_number }}</span>
                        </div>
                        @endif
                        @if($carrier->mc_number)
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-slate-600">MC Number</span>
                            <span class="text-sm text-slate-900 font-medium">{{ $carrier->mc_number }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Banking Rejection Notice -->
                <x-carrier.info-notice type="danger" title="Banking Information Rejected">
                    <p class="mb-4">Your banking information has been reviewed and unfortunately cannot be approved at this time.</p>
                    
                    @if($bankingDetails->rejection_reason)
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                        <h4 class="font-medium text-red-900 mb-2">Rejection Reason:</h4>
                        <p class="text-red-800">{{ $bankingDetails->rejection_reason }}</p>
                    </div>
                    @endif
                    
                    <p class="text-sm text-slate-600">
                        Please update your banking information with valid details to continue using our services.
                    </p>
                </x-carrier.info-notice>

                <!-- Update Banking Process -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <x-base.lucide icon="refresh-cw" class="w-5 h-5 text-blue-600" />
                        <h2 class="text-lg font-semibold text-slate-900">Update Banking Information</h2>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mt-0.5">
                                <span class="text-xs font-medium text-blue-600">1</span>
                            </div>
                            <div>
                                <h3 class="font-medium text-slate-900">Review Rejection Reason</h3>
                                <p class="text-sm text-slate-600 mt-1">Understand why your banking information was rejected</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mt-0.5">
                                <span class="text-xs font-medium text-blue-600">2</span>
                            </div>
                            <div>
                                <h3 class="font-medium text-slate-900">Prepare Valid Documents</h3>
                                <p class="text-sm text-slate-600 mt-1">Gather valid banking documents and account information</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mt-0.5">
                                <span class="text-xs font-medium text-blue-600">3</span>
                            </div>
                            <div>
                                <h3 class="font-medium text-slate-900">Submit Updated Information</h3>
                                <p class="text-sm text-slate-600 mt-1">Complete the banking setup with corrected information</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <x-base.button 
                        as="a" 
                        href="{{ route('carrier.wizard.step4') }}" 
                        variant="primary" 
                        size="lg" 
                        class="flex-1 justify-center"
                    >
                        <x-base.lucide icon="credit-card" class="w-4 h-4 mr-2" />
                        Update Banking Information
                    </x-base.button>
                    
                    <x-base.button 
                        as="a" 
                        href="#" 
                        variant="outline" 
                        size="lg" 
                        class="flex-1 justify-center"
                        onclick="document.getElementById('contact-modal').classList.remove('hidden')"
                    >
                        <x-base.lucide icon="help-circle" class="w-4 h-4 mr-2" />
                        Contact Support
                    </x-base.button>
                    
                    <x-base.button 
                        as="a" 
                        href="{{ route('logout') }}" 
                        variant="ghost" 
                        size="lg" 
                        class="flex-1 justify-center"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                    >
                        <x-base.lucide icon="log-out" class="w-4 h-4 mr-2" />
                        Sign Out
                    </x-base.button>
                </div>
            </div>

            <!-- Contact Support Panel -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 sticky top-8">
                    <div class="flex items-center gap-3 mb-4">
                        <x-base.lucide icon="headphones" class="w-5 h-5 text-blue-600" />
                        <h2 class="text-lg font-semibold text-slate-900">Need Help?</h2>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <x-base.lucide icon="mail" class="w-6 h-6 text-blue-600" />
                            </div>
                            <h3 class="font-medium text-slate-900 mb-1">Email Support</h3>
                            <p class="text-sm text-slate-600 mb-2">Get help via email</p>
                            <a href="mailto:support@efservices.la" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                                support@efservices.la
                            </a>
                        </div>
                        
                        <div class="border-t border-slate-200 pt-4">
                            <div class="text-center">
                                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <x-base.lucide icon="phone" class="w-6 h-6 text-green-600" />
                                </div>
                                <h3 class="font-medium text-slate-900 mb-1">Phone Support</h3>
                                <p class="text-sm text-slate-600 mb-2">Speak with our team</p>
                                <a href="tel:+1-555-0123" class="text-sm text-green-600 hover:text-green-700 font-medium">
                                    +1 (555) 012-3456
                                </a>
                            </div>
                        </div>
                        
                        <div class="border-t border-slate-200 pt-4">
                            <p class="text-xs text-slate-500 text-center">
                                Support hours: Monday - Friday, 9 AM - 6 PM EST
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contact Modal -->
<div id="contact-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-900">Contact Support</h3>
            <button onclick="document.getElementById('contact-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <x-base.lucide icon="x" class="w-5 h-5" />
            </button>
        </div>
        <p class="text-sm text-slate-600 mb-4">
            Our support team is here to help you resolve your banking information issues.
        </p>
        <div class="space-y-3">
            <a href="mailto:support@efservices.la" class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                <x-base.lucide icon="mail" class="w-5 h-5 text-blue-600" />
                <span class="text-sm font-medium text-slate-900">Email Support</span>
            </a>
            <a href="tel:+1-555-0123" class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                <x-base.lucide icon="phone" class="w-5 h-5 text-green-600" />
                <span class="text-sm font-medium text-slate-900">Call Support</span>
            </a>
        </div>
    </div>
</div>

<!-- Logout Form -->
<form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
    @csrf
</form>
@endsection