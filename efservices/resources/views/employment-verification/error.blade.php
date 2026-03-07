@extends('layouts.guest')

@section('content')
<div class="min-h-screen bg-gray-100 flex items-center justify-center px-4 py-12">
    <div class="max-w-xl w-full bg-white rounded-md shadow-md border border-gray-200 relative overflow-hidden">
        
        <div class="h-2 bg-red-600 w-full"></div>

        <div class="p-8 md:p-10">
            <div class="flex flex-col items-center justify-center mb-8">
                <div class="h-16 w-16 bg-red-50 rounded-full flex items-center justify-center mb-4 border border-red-200">
                    <svg class="h-8 w-8 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                
                <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Error</h1>
            </div>

            <hr class="border-gray-100 mb-8">

            <div class="text-center space-y-5">
                <h2 class="text-lg font-medium text-gray-800">
                    Verification Error
                </h2>

                <p class="text-gray-600 text-base leading-relaxed">
                    We encountered an error while processing your employment verification request.
                </p>
                
                <div class="bg-red-50 p-4 rounded border border-red-100">
                    <p class="text-red-800 text-sm leading-relaxed">
                        This could be due to invalid data or a system issue. Please contact the company that requested this verification for assistance.
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
            <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">System Alert</p>
        </div>
    </div>
</div>
@endsection
