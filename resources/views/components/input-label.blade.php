@props(['value'])

@php
    // Con la clase .form-label (etiqueta en mayúsculas de los formularios internos) no se aplican los estilos por defecto.
    $defaults = str_contains($attributes->get('class', ''), 'form-label') ? '' : 'block font-medium text-sm text-slate-700';
@endphp

<label {{ $attributes->merge(['class' => $defaults]) }}>
    {{ $value ?? $slot }}
</label>
