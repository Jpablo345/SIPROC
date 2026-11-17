<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component {
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative">
    {{-- Barra superior en móvil. En modo oscuro usa el logo blanco (una tinta) del manual. --}}
    <div class="flex items-center justify-between border-b border-siproc-line bg-white px-4 py-3 lg:hidden dark:border-gray-800 dark:bg-gray-900">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3">
            <img src="{{ asset('img/logoufps.png') }}" alt="UFPS" class="h-9 w-9 dark:hidden" />
            <img src="{{ asset('img/logoufps-blanco.png') }}" alt="UFPS" class="hidden h-9 w-9 dark:block" />
            <span class="h-8 w-px bg-gray-300 dark:bg-white/20"></span>
            <span class="leading-tight text-siproc-ink dark:text-white">
                <span class="block font-display text-sm font-bold tracking-wide">SIPROC</span>
                <span class="block text-xs text-gray-500">Producción Científica</span>
            </span>
        </a>
        <div class="flex items-center gap-2">
            <button type="button" @click="$dispatch('abrir-busqueda')" aria-label="Buscar"
                class="rounded-lg border border-gray-300 p-2 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                <x-icon name="magnifying-glass" />
            </button>
            <button type="button" @click="open = true" aria-label="Abrir menú" :aria-expanded="open"
                class="rounded-lg border border-gray-300 p-2 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                <x-icon name="bars-3" />
            </button>
        </div>
    </div>

    {{-- Cajón móvil --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-40 lg:hidden">
        <div class="absolute inset-0 bg-black/40" @click="open = false"></div>
        <div class="relative h-full w-72" x-show="open" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0">
            <x-sidebar-menu mobile />
        </div>
    </div>

    {{-- Barra lateral en escritorio --}}
    <aside class="hidden h-screen w-72 lg:sticky lg:top-0 lg:flex lg:shrink-0 lg:flex-col">
        <x-sidebar-menu />
    </aside>
</div>
