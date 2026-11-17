{{-- Insignia del tipo de publicación, con un color fijo por tipo tomado de los colores de facultad. --}}
@props(['name' => null])

@php
    $normalizado = \App\Models\PublicationType::normalizeName((string) $name);
    $clase = match ($normalizado) {
        'articulo' => 'bg-fac-ing/10 text-fac-ing dark:bg-sky-500/15 dark:text-sky-300',
        'libro' => 'bg-fac-agr/15 text-fac-agr-dark dark:bg-emerald-500/15 dark:text-emerald-300',
        '' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
        default => [
            'bg-fac-adm/10 text-fac-adm dark:bg-violet-500/15 dark:text-violet-300',
            'bg-fac-edu/15 text-fac-edu-dark dark:bg-orange-500/15 dark:text-orange-300',
            'bg-siproc-rose text-siproc-red-dark dark:bg-red-500/15 dark:text-red-300',
            'bg-fac-ing-300/30 text-fac-ing dark:bg-blue-500/15 dark:text-blue-300',
        ][crc32($normalizado) % 4],
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold $clase"]) }}>{{ $name ?: 'Sin tipo' }}</span>
