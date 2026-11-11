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
    {{-- Barra superior en móvil --}}
    <div class="flex items-center justify-between bg-siproc-red px-4 py-3 text-white lg:hidden">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 p-1">
                <img src="{{ asset('img/logoufps.png') }}" alt="UFPSO" class="h-8 w-8 rounded-lg object-contain" />
            </span>
            <span>
                <span class="block text-xs uppercase tracking-[0.3em] text-white/60">SIPROC</span>
                <span class="block text-sm font-semibold">Producción Científica</span>
            </span>
        </a>
        <button type="button" @click="open = true" aria-label="Abrir menú" :aria-expanded="open"
            class="rounded-lg border border-white/20 p-2 text-white/80 hover:text-white">
            <x-icon name="bars-3" />
        </button>
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
