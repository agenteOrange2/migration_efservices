@extends('layouts.guest')

@section('content')
<div class="min-h-screen bg-gray-100 flex items-center justify-center px-4 py-12">
    <div class="max-w-xl w-full bg-white rounded-md shadow-md border border-gray-200 relative overflow-hidden">
        
        <div class="h-2 bg-amber-500 w-full"></div>

        <div class="p-8 md:p-10">
            <div class="flex flex-col items-center justify-center mb-8">
                <div class="h-16 w-16 bg-amber-50 rounded-full flex items-center justify-center mb-4 border border-amber-200">
                    <svg class="h-8 w-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                
                <h1 class="text-3xl font-bold text-gray-900 tracking-tight text-center">Verification Link Expired</h1>
            </div>

            <hr class="border-gray-100 mb-8">

            <div class="text-center space-y-5">
                <h2 class="text-lg font-medium text-gray-800">
                    Verification Link Has Expired
                </h2>

                <p class="text-gray-600 text-base leading-relaxed">
                    The employment verification link you are trying to access has expired or has already been used.
                </p>
                
                <div class="bg-gray-50 p-4 rounded border border-gray-200">
                    <p class="text-gray-600 text-sm leading-relaxed">
                        Verification links are valid for 7 days from the date they are sent. If you need to verify employment information, please contact the driver or the company that requested this verification to send a new verification request.
                    </p>
                </div>
            </div>

            <div class="mt-10">
                <a href="{{ url('/') }}" class="block w-full text-center px-6 py-3 bg-blue-700 hover:bg-blue-800 text-white font-semibold rounded transition-colors duration-150 shadow-sm border border-blue-800">
                    Return to Homepage
                </a>
            </div>
        </div>
        
        <div class="bg-gray-50 px-8 py-4 border-t border-gray-100 text-center">
            <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">Security Notification</p>
        </div>
    </div>
</div>

@endsection
