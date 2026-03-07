@extends('layouts.guest')

@section('content')
<div class="min-h-screen bg-gray-100 flex items-center justify-center px-4 py-12">
    <div class="max-w-xl w-full bg-white rounded-md shadow-md border border-gray-200 relative overflow-hidden">
        
        <div class="h-2 bg-green-600 w-full"></div>

        <div class="p-8 md:p-10">
            <div class="flex flex-col items-center justify-center mb-8">
                <div class="h-16 w-16 bg-green-100 rounded-full flex items-center justify-center mb-4 border border-green-200">
                    <svg class="h-8 w-8 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                
                <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Thank You!</h1>
                <p class="text-lg font-medium text-green-700 mt-2">Employment Verification Complete</p>
            </div>

            <hr class="border-gray-100 mb-8">

            <div class="text-center space-y-4">
                <p class="text-gray-700 text-base leading-relaxed">
                    Thank you for verifying the employment information. Your response has been recorded successfully.
                </p>
                
                <p class="text-gray-600 text-sm">
                    This helps us maintain accurate records and ensures compliance with regulatory requirements.
                </p>
            </div>

            <div class="mt-10">
                <a href="{{ url('/') }}" class="block w-full text-center px-6 py-3 bg-blue-700 hover:bg-blue-800 text-white font-semibold rounded transition-colors duration-150 shadow-sm border border-blue-800">
                    Return to Homepage
                </a>
            </div>
        </div>
        
        <div class="bg-gray-50 px-8 py-4 border-t border-gray-100 text-center">
            <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">System Notification</p>
        </div>
    </div>
</div>
@endsection
