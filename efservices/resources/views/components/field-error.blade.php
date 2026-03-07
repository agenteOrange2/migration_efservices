{{-- 
    Field Error Component
    Requirement 7.1: Mostrar mensaje de error junto al campo específico
    
    Usage: <x-field-error field="email" />
    Or with custom message: <x-field-error field="email" message="Custom error message" />
--}}

@props(['field', 'message' => null])

@error($field)
<p {{ $attributes->merge(['class' => 'mt-1 text-sm text-red-600']) }}>
    {{ $message ?? $message }}
</p>
@enderror
