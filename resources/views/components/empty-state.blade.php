{{-- Estado vacío con ícono, título opcional y espacio para una acción. --}}
@props(['message' => 'No hay registros todavía.', 'title' => null, 'icon' => 'inbox'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-4 py-10 text-center']) }}>
    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500">
        <x-icon :name="$icon" class="h-6 w-6" />
    </span>
    @if ($title)
        <p class="mt-3 text-sm font-semibold text-siproc-ink dark:text-white">{{ $title }}</p>
    @endif
    <p class="{{ $title ? 'mt-1' : 'mt-3' }} max-w-sm text-sm text-slate-500 dark:text-gray-400">{{ $message }}</p>
    @if (! $slot->isEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
