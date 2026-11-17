@props(['name' => '', 'size' => 'md'])

@php
    // Iniciales y un color fijo por persona, tomado de los colores institucionales y de facultad.
    $palabras = collect(preg_split('/\s+/', trim((string) $name)))->filter();
    $iniciales = mb_strtoupper(
        $palabras->count() >= 3
            ? mb_substr($palabras->first(), 0, 1) . mb_substr($palabras->get(2), 0, 1)
            : $palabras->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('')
    ) ?: '?';
    $colores = ['bg-fac-ing', 'bg-fac-agr-dark', 'bg-fac-adm', 'bg-fac-edu-dark', 'bg-siproc-red-dark', 'bg-fac-ing-500', 'bg-siproc-red-deep'];
    $color = $colores[crc32(mb_strtolower(trim((string) $name))) % count($colores)];
    $tamano = match ($size) {
        'xs' => 'h-6 w-6 text-[10px]',
        'sm' => 'h-8 w-8 text-xs',
        'lg' => 'h-14 w-14 text-lg',
        'xl' => 'h-20 w-20 text-2xl',
        default => 'h-10 w-10 text-sm',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex shrink-0 select-none items-center justify-center rounded-full font-bold text-white $color $tamano"]) }}
    title="{{ $name }}" aria-hidden="true">{{ $iniciales }}</span>
