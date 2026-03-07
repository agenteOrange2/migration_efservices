<div
    data-tw-merge
    {{ $attributes->class(['box'])->merge($attributes->whereDoesntStartWith('class')->getAttributes()) }}    
>
    {{ $slot }}
</div>