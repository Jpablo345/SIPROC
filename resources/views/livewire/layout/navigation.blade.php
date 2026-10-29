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

@php
    $navItem = 'block rounded-lg px-3 py-2 text-sm font-medium transition';
    $navItemActive = 'bg-white/15 text-white shadow-[inset_0_0_0_1px_rgba(255,255,255,0.08)]';
    $navItemInactive = 'text-white/80 hover:bg-white/10 hover:text-white';
    $navSubItem = 'block rounded-lg px-3 py-2 text-[13px] font-medium transition';
@endphp

<nav x-data="{ open: false }" class="relative">
    <!-- Mobile Top Bar -->
    <div class="flex items-center justify-between bg-[var(--siproc-red)] px-4 py-3 text-white lg:hidden">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 p-1">
                <img src="{{ asset('img/logoufps.png') }}" alt="UFPS" class="h-8 w-8 rounded-lg object-contain" />
            </div>
            <div>
                <div class="text-xs uppercase tracking-[0.3em] text-white/60">SIPROC</div>
                <div class="text-sm font-semibold">Produccion Cientifica</div>
            </div>
        </div>
        <button @click="open = ! open" class="rounded-lg border border-white/20 p-2 text-white/80 hover:text-white">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <!-- Mobile Drawer -->
    <div x-show="open" class="fixed inset-0 z-40 lg:hidden" x-cloak>
        <div class="absolute inset-0 bg-black/40" @click="open = false"></div>
        <div class="relative flex h-full w-72 flex-col bg-[var(--siproc-red)] text-white">
            <div class="flex items-center justify-between px-5 py-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 p-1">
                        <img src="{{ asset('img/logoufps.png') }}" alt="UFPS"
                            class="h-8 w-8 rounded-lg object-contain" />
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-[0.3em] text-white/60">SIPROC</div>
                        <div class="text-sm font-semibold">Menu principal</div>
                    </div>
                </div>
                <button @click="open = false"
                    class="rounded-lg border border-white/20 p-2 text-white/80 hover:text-white">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-4 pb-6">
                <p class="px-2 text-[11px] font-semibold uppercase tracking-[0.24em] text-white/60">Principal</p>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('dashboard') }}"
                        class="{{ $navItem }} {{ request()->routeIs('dashboard') ? $navItemActive : $navItemInactive }}"
                        wire:navigate>
                        Dashboard
                    </a>
                    <a href="{{ route('publicaciones.index') }}"
                        class="{{ $navItem }} {{ request()->routeIs('publicaciones.index') ? $navItemActive : $navItemInactive }}"
                        wire:navigate>
                        Publicaciones
                    </a>
                    <a href="{{ route('publicaciones.tipos') }}"
                        class="{{ $navItem }} {{ request()->routeIs('publicaciones.tipos') ? $navItemActive : $navItemInactive }}"
                        wire:navigate>
                        Tipos de publicacion
                    </a>
                    <a href="{{ route('publicaciones.journals') }}"
                        class="{{ $navItem }} {{ request()->routeIs('publicaciones.journals') ? $navItemActive : $navItemInactive }}"
                        wire:navigate>
                        Revistas
                    </a>
                    <a href="{{ route('publicaciones.tipos-libro') }}"
                        class="{{ $navItem }} {{ request()->routeIs('publicaciones.tipos-libro') ? $navItemActive : $navItemInactive }}"
                        wire:navigate>
                        Tipos de libro
                    </a>
                    <a href="{{ route('investigadores') }}"
                        class="{{ $navItem }} {{ request()->routeIs('investigadores') ? $navItemActive : $navItemInactive }}"
                        wire:navigate>
                        Investigadores
                    </a>
                </div>

                <div class="mt-6"
                    x-data="{ openTipologia: true, openGeneracion: true, openDesarrollo: false, openApropiacion: false, openDivulgacion: false, openFormacion: false }">
                    <button type="button"
                        class="flex w-full items-center justify-between px-2 text-[11px] font-semibold uppercase tracking-[0.24em] text-white/60"
                        @click="openTipologia = ! openTipologia">
                        <span>Tipologia del producto</span>
                        <svg class="h-4 w-4 text-white/60 transition" :class="openTipologia ? 'rotate-180' : ''"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 9l6 6 6-6" />
                        </svg>
                    </button>
                    <div class="mt-2 space-y-2" x-show="openTipologia" x-transition>
                        <div class="rounded-xl">
                            <button type="button"
                                class="flex w-full items-center justify-between px-3 py-2 text-xs font-semibold text-white/80"
                                @click="openGeneracion = ! openGeneracion">
                                <span>Generacion de nuevo conocimiento</span>
                                <svg class="h-4 w-4 text-white/60 transition"
                                    :class="openGeneracion ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M6 9l6 6 6-6" />
                                </svg>
                            </button>
                            <div class="space-y-1 px-2 pb-2" x-show="openGeneracion" x-transition>
                                <a href="{{ route('tipologia.generacion.articulos') }}"
                                    class="{{ $navSubItem }} {{ request()->routeIs('tipologia.generacion.articulos') ? $navItemActive : $navItemInactive }}"
                                    wire:navigate>
                                    Articulos cientificos
                                </a>
                                <a href="{{ route('tipologia.generacion.libros') }}"
                                    class="{{ $navSubItem }} {{ request()->routeIs('tipologia.generacion.libros') ? $navItemActive : $navItemInactive }}"
                                    wire:navigate>
                                    Libros de investigacion
                                </a>
                                <a href="{{ route('tipologia.generacion.capitulos') }}"
                                    class="{{ $navSubItem }} {{ request()->routeIs('tipologia.generacion.capitulos') ? $navItemActive : $navItemInactive }}"
                                    wire:navigate>
                                    Capitulos de libro
                                </a>
                                <a href="{{ route('tipologia.generacion.trabajos') }}"
                                    class="{{ $navSubItem }} {{ request()->routeIs('tipologia.generacion.trabajos') ? $navItemActive : $navItemInactive }}"
                                    wire:navigate>
                                    Trabajos en eventos
                                </a>
                                <a href="{{ route('tipologia.generacion.otras') }}"
                                    class="{{ $navSubItem }} {{ request()->routeIs('tipologia.generacion.otras') ? $navItemActive : $navItemInactive }}"
                                    wire:navigate>
                                    Otras publicaciones
                                </a>
                            </div>
                        </div>

                        <div class="rounded-xl">
                            <button type="button"
                                class="flex w-full items-center justify-between px-3 py-2 text-xs font-semibold text-white/80"
                                @click="openDesarrollo = ! openDesarrollo">
                                <span>Desarrollo tecnologico e innovacion</span>
                                <svg class="h-4 w-4 text-white/60 transition"
                                    :class="openDesarrollo ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M6 9l6 6 6-6" />
                                </svg>
                            </button>
                            <div class="space-y-1 px-2 pb-2" x-show="openDesarrollo" x-transition>
                                <a href="{{ route('tipologia.desarrollo') }}"
                                    class="{{ $navSubItem }} {{ request()->routeIs('tipologia.desarrollo') ? $navItemActive : $navItemInactive }}"
                                    wire:navigate>
                                    Ver registros
                                </a>
                            </div>
                        </div>
                        <div class="rounded-xl">
                            <button type="button"
                                class="flex w-full items-center justify-between px-3 py-2 text-xs font-semibold text-white/80"
                                @click="openApropiacion = ! openApropiacion">
                                <span>Apropiacion social del conocimiento</span>
                                <svg class="h-4 w-4 text-white/60 transition"
                                    :class="openApropiacion ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M6 9l6 6 6-6" />
                                </svg>
                            </button>
                            <div class="space-y-1 px-2 pb-2" x-show="openApropiacion" x-transition>
                                <a href="{{ route('tipologia.apropiacion') }}"
                                    class="{{ $navSubItem }} {{ request()->routeIs('tipologia.apropiacion') ? $navItemActive : $navItemInactive }}"
                                    wire:navigate>
                                    Ver registros
                                </a>
                            </div>
                        </div>
                        <div class="rounded-xl">
                            <button type="button"
                                class="flex w-full items-center justify-between px-3 py-2 text-xs font-semibold text-white/80"
                                @click="openDivulgacion = ! openDivulgacion">
                                <span>Divulgacion publica de la ciencia</span>
                                <svg class="h-4 w-4 text-white/60 transition"
                                    :class="openDivulgacion ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M6 9l6 6 6-6" />
                                </svg>
                            </button>
                            <div class="space-y-1 px-2 pb-2" x-show="openDivulgacion" x-transition>
                                <a href="{{ route('tipologia.divulgacion') }}"
                                    class="{{ $navSubItem }} {{ request()->routeIs('tipologia.divulgacion') ? $navItemActive : $navItemInactive }}"
                                    wire:navigate>
                                    Eventos
                                </a>
                            </div>
                        </div>
                        <div class="rounded-xl">
                            <button type="button"
                                class="flex w-full items-center justify-between px-3 py-2 text-xs font-semibold text-white/80"
                                @click="openFormacion = ! openFormacion">
                                <span>Formacion de recurso humano</span>
                                <svg class="h-4 w-4 text-white/60 transition" :class="openFormacion ? 'rotate-180' : ''"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 9l6 6 6-6" />
                                </svg>
                            </button>
                            <div class="space-y-1 px-2 pb-2" x-show="openFormacion" x-transition>
                                <a href="{{ route('tipologia.formacion') }}"
                                    class="{{ $navSubItem }} {{ request()->routeIs('tipologia.formacion') ? $navItemActive : $navItemInactive }}"
                                    wire:navigate>
                                    Ver registros
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <p class="px-2 text-[11px] font-semibold uppercase tracking-[0.24em] text-white/60">Estructura</p>
                    <div class="mt-2 space-y-1">
                        <a href="{{ route('estructura.grupos') }}"
                            class="{{ $navItem }} {{ request()->routeIs('estructura.grupos') ? $navItemActive : $navItemInactive }}"
                            wire:navigate>
                            Grupos de investigacion
                        </a>
                        <a href="{{ route('estructura.instituciones') }}"
                            class="{{ $navItem }} {{ request()->routeIs('estructura.instituciones') ? $navItemActive : $navItemInactive }}"
                            wire:navigate>
                            Instituciones
                        </a>
                    </div>
                </div>
            </div>

            <div class="mt-auto border-t border-white/15 px-5 py-4">
                <div class="text-sm font-semibold" x-data="{{ json_encode(['name' => auth()->user()->name]) }}"
                    x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="text-xs text-white/70">{{ auth()->user()->email }}</div>
                <div class="mt-3 flex gap-2">
                    <a href="{{ route('profile') }}"
                        class="rounded-lg border border-white/30 px-3 py-2 text-xs font-semibold text-white/80 hover:text-white"
                        wire:navigate>
                        Perfil
                    </a>
                    <button wire:click="logout"
                        class="rounded-lg bg-white/15 px-3 py-2 text-xs font-semibold text-white hover:bg-white/25">
                        Salir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Desktop Sidebar -->
    <aside
        class="hidden h-screen w-72 flex-col bg-[var(--siproc-red)] text-white lg:sticky lg:top-0 lg:flex lg:shrink-0">
        <div class="px-6 py-7">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 p-2">
                    <img src="{{ asset('img/logoufps.png') }}" alt="UFPS" class="h-8 w-8 rounded-lg object-contain" />
                </div>
                <div>
                    <div class="text-xs uppercase tracking-[0.3em] text-white/60">SIPROC</div>
                    <div class="text-base font-semibold">Produccion Cientifica</div>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-4 pb-6">
            <p class="px-2 text-[11px] font-semibold uppercase tracking-[0.24em] text-white/60">Principal</p>
            <div class="mt-2 space-y-1">
                <a href="{{ route('dashboard') }}"
                    class="{{ $navItem }} {{ request()->routeIs('dashboard') ? $navItemActive : $navItemInactive }}"
                    wire:navigate>
                    Dashboard
                </a>
                <a href="{{ route('publicaciones.index') }}"
                    class="{{ $navItem }} {{ request()->routeIs('publicaciones.index') ? $navItemActive : $navItemInactive }}"
                    wire:navigate>
                    Publicaciones
                </a>
                <a href="{{ route('publicaciones.tipos') }}"
                    class="{{ $navItem }} {{ request()->routeIs('publicaciones.tipos') ? $navItemActive : $navItemInactive }}"
                    wire:navigate>
                    Tipos de publicacion
                </a>
                <a href="{{ route('publicaciones.journals') }}"
                    class="{{ $navItem }} {{ request()->routeIs('publicaciones.journals') ? $navItemActive : $navItemInactive }}"
                    wire:navigate>
                    Revistas
                </a>
                <a href="{{ route('publicaciones.tipos-libro') }}"
                    class="{{ $navItem }} {{ request()->routeIs('publicaciones.tipos-libro') ? $navItemActive : $navItemInactive }}"
                    wire:navigate>
                    Tipos de libro
                </a>
                <a href="{{ route('investigadores') }}"
                    class="{{ $navItem }} {{ request()->routeIs('investigadores') ? $navItemActive : $navItemInactive }}"
                    wire:navigate>
                    Investigadores
                </a>
            </div>

            <div class="mt-6"
                x-data="{ openTipologia: true, openGeneracion: true, openDesarrollo: false, openApropiacion: false, openDivulgacion: false, openFormacion: false }">
                <button type="button"
                    class="flex w-full items-center justify-between px-2 text-[11px] font-semibold uppercase tracking-[0.24em] text-white/60"
                    @click="openTipologia = ! openTipologia">
                    <span>Tipologia del producto</span>
                    <svg class="h-4 w-4 text-white/60 transition" :class="openTipologia ? 'rotate-180' : ''"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M6 9l6 6 6-6" />
                    </svg>
                </button>
                <div class="mt-2 space-y-2" x-show="openTipologia" x-transition>
                    <div class="rounded-xl">
                        <button type="button"
                            class="flex w-full items-center justify-between px-3 py-2 text-xs font-semibold text-white/80"
                            @click="openGeneracion = ! openGeneracion">
                            <span>Generacion de nuevo conocimiento</span>
                            <svg class="h-4 w-4 text-white/60 transition" :class="openGeneracion ? 'rotate-180' : ''"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 9l6 6 6-6" />
                            </svg>
                        </button>
                        <div class="space-y-1 px-2 pb-2" x-show="openGeneracion" x-transition>
                            <a href="{{ route('tipologia.generacion.articulos') }}"
                                class="{{ $navSubItem }} {{ request()->routeIs('tipologia.generacion.articulos') ? $navItemActive : $navItemInactive }}"
                                wire:navigate>
                                Articulos cientificos
                            </a>
                            <a href="{{ route('tipologia.generacion.libros') }}"
                                class="{{ $navSubItem }} {{ request()->routeIs('tipologia.generacion.libros') ? $navItemActive : $navItemInactive }}"
                                wire:navigate>
                                Libros de investigacion
                            </a>
                            <a href="{{ route('tipologia.generacion.capitulos') }}"
                                class="{{ $navSubItem }} {{ request()->routeIs('tipologia.generacion.capitulos') ? $navItemActive : $navItemInactive }}"
                                wire:navigate>
                                Capitulos de libro
                            </a>
                            <a href="{{ route('tipologia.generacion.trabajos') }}"
                                class="{{ $navSubItem }} {{ request()->routeIs('tipologia.generacion.trabajos') ? $navItemActive : $navItemInactive }}"
                                wire:navigate>
                                Trabajos en eventos
                            </a>
                            <a href="{{ route('tipologia.generacion.otras') }}"
                                class="{{ $navSubItem }} {{ request()->routeIs('tipologia.generacion.otras') ? $navItemActive : $navItemInactive }}"
                                wire:navigate>
                                Otras publicaciones
                            </a>
                        </div>
                    </div>

                    <div class="rounded-xl">
                        <button type="button"
                            class="flex w-full items-center justify-between px-3 py-2 text-xs font-semibold text-white/80"
                            @click="openDesarrollo = ! openDesarrollo">
                            <span>Desarrollo tecnologico e innovacion</span>
                            <svg class="h-4 w-4 text-white/60 transition" :class="openDesarrollo ? 'rotate-180' : ''"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 9l6 6 6-6" />
                            </svg>
                        </button>
                        <div class="space-y-1 px-2 pb-2" x-show="openDesarrollo" x-transition>
                            <a href="{{ route('tipologia.desarrollo') }}"
                                class="{{ $navSubItem }} {{ request()->routeIs('tipologia.desarrollo') ? $navItemActive : $navItemInactive }}"
                                wire:navigate>
                                Ver registros
                            </a>
                        </div>
                    </div>
                    <div class="rounded-xl">
                        <button type="button"
                            class="flex w-full items-center justify-between px-3 py-2 text-xs font-semibold text-white/80"
                            @click="openApropiacion = ! openApropiacion">
                            <span>Apropiacion social del conocimiento</span>
                            <svg class="h-4 w-4 text-white/60 transition" :class="openApropiacion ? 'rotate-180' : ''"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 9l6 6 6-6" />
                            </svg>
                        </button>
                        <div class="space-y-1 px-2 pb-2" x-show="openApropiacion" x-transition>
                            <a href="{{ route('tipologia.apropiacion') }}"
                                class="{{ $navSubItem }} {{ request()->routeIs('tipologia.apropiacion') ? $navItemActive : $navItemInactive }}"
                                wire:navigate>
                                Ver registros
                            </a>
                        </div>
                    </div>
                    <div class="rounded-xl">
                        <button type="button"
                            class="flex w-full items-center justify-between px-3 py-2 text-xs font-semibold text-white/80"
                            @click="openDivulgacion = ! openDivulgacion">
                            <span>Divulgacion publica de la ciencia</span>
                            <svg class="h-4 w-4 text-white/60 transition" :class="openDivulgacion ? 'rotate-180' : ''"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 9l6 6 6-6" />
                            </svg>
                        </button>
                        <div class="space-y-1 px-2 pb-2" x-show="openDivulgacion" x-transition>
                            <a href="{{ route('tipologia.divulgacion') }}"
                                class="{{ $navSubItem }} {{ request()->routeIs('tipologia.divulgacion') ? $navItemActive : $navItemInactive }}"
                                wire:navigate>
                                Eventos
                            </a>
                        </div>
                    </div>
                    <div class="rounded-xl">
                        <button type="button"
                            class="flex w-full items-center justify-between px-3 py-2 text-xs font-semibold text-white/80"
                            @click="openFormacion = ! openFormacion">
                            <span>Formacion de recurso humano</span>
                            <svg class="h-4 w-4 text-white/60 transition" :class="openFormacion ? 'rotate-180' : ''"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 9l6 6 6-6" />
                            </svg>
                        </button>
                        <div class="space-y-1 px-2 pb-2" x-show="openFormacion" x-transition>
                            <a href="{{ route('tipologia.formacion') }}"
                                class="{{ $navSubItem }} {{ request()->routeIs('tipologia.formacion') ? $navItemActive : $navItemInactive }}"
                                wire:navigate>
                                Ver registros
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <p class="px-2 text-[11px] font-semibold uppercase tracking-[0.24em] text-white/60">Estructura</p>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('estructura.grupos') }}"
                        class="{{ $navItem }} {{ request()->routeIs('estructura.grupos') ? $navItemActive : $navItemInactive }}"
                        wire:navigate>
                        Grupos de investigacion
                    </a>
                    <a href="{{ route('estructura.instituciones') }}"
                        class="{{ $navItem }} {{ request()->routeIs('estructura.instituciones') ? $navItemActive : $navItemInactive }}"
                        wire:navigate>
                        Instituciones
                    </a>
                </div>
            </div>
        </div>

        <div class="border-t border-white/15 px-6 py-5">
            <div class="text-sm font-semibold" x-data="{{ json_encode(['name' => auth()->user()->name]) }}"
                x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
            <div class="text-xs text-white/70">{{ auth()->user()->email }}</div>
            <div class="mt-3 flex gap-2">
                <a href="{{ route('profile') }}"
                    class="rounded-lg border border-white/30 px-3 py-2 text-xs font-semibold text-white/80 hover:text-white"
                    wire:navigate>
                    Perfil
                </a>
                <button wire:click="logout"
                    class="rounded-lg bg-white/15 px-3 py-2 text-xs font-semibold text-white hover:bg-white/25">
                    Salir
                </button>
            </div>
        </div>
    </aside>
</nav>