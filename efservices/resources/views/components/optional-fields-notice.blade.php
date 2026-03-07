{{-- 
    Optional Fields Notice Component
    Requirement 3.2: Mostrar indicador visual de campos opcionales incompletos
    
    Usage: <x-optional-fields-notice :fields="$incompleteOptionalFields" />
--}}

@props(['fields' => []])

@if (count($fields) > 0)
<div {{ $attributes->merge(['class' => 'mb-4 rounded-lg border border-yellow-200 bg-yellow-50 p-4']) }}>
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3 flex-1">
            <h3 class="text-sm font-medium text-yellow-800">
                {{ __('Optional fields incomplete') }}
            </h3>
            <div class="mt-2 text-sm text-yellow-700">
                <p class="mb-2">{{ __('The following optional fields are not filled. You can complete them later:') }}</p>
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($fields as $field)
                        <li>{{ $field['name'] ?? $field }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="ml-auto pl-3">
            <div class="-mx-1.5 -my-1.5">
                <button type="button" 
                        onclick="this.closest('.mb-4').remove()"
                        class="inline-flex rounded-md bg-yellow-50 p-1.5 text-yellow-500 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:ring-offset-2 focus:ring-offset-yellow-50">
                    <span class="sr-only">{{ __('Dismiss') }}</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
@endif
