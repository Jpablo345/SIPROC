{{-- Insignia del cuartil de una revista (Q1 a Q5). No muestra nada si la revista no tiene cuartil. --}}
@props(['category' => null])

@if ($clase = \App\Support\Quartile::badgeClass($category))
    <span {{ $attributes->merge(['class' => "inline-flex items-center rounded px-1.5 py-0.5 text-[11px] font-bold $clase"]) }}
        title="Cuartil de la revista">{{ strtoupper(trim($category)) }}</span>
@endif
