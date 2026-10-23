<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" href="{{ asset('img/logoufps.png') }}?v=1" type="image/png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased">
    <div class="min-h-screen bg-[var(--sivis-cream)] text-[var(--sivis-ink)]">
        <div class="flex min-h-screen">
            <livewire:layout.navigation />

            <div class="flex min-h-screen flex-1 flex-col min-w-0">
                <!-- Page Heading -->
                @if (isset($header))
                    <header class="sticky top-0 z-30 border-b border-[#e7cfcf] bg-[var(--sivis-cream)]/80 backdrop-blur">
                        <div class="mx-auto w-full max-w-6xl px-6 py-4 lg:px-10">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <main class="flex-1 px-6 py-6 lg:px-10">
                    <div class="mx-auto w-full max-w-6xl">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </div>

    <!-- Widget de Accesibilidad SIVIS -->
    <div x-data="accesibilidad()" x-init="init()">

        {{-- Botón flotante --}}
        <button @click="panel = !panel"
            class="fixed bottom-6 right-6 z-50 w-12 h-12 rounded-full bg-[#8B0000] text-white shadow-xl flex items-center justify-center hover:bg-[#6e0000] transition-all"
            aria-label="Menú de accesibilidad">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 3a1 1 0 100 2 1 1 0 000-2zm0 0v2m0 4v10m-4-7l4-3 4 3M8 17h8" />
            </svg>
        </button>

        {{-- Panel --}}
        <div x-show="panel" x-cloak x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            class="fixed bottom-20 right-6 z-50 w-72 rounded-2xl shadow-2xl overflow-hidden border border-zinc-200"
            style="background:#fff; max-height:82vh; overflow-y:auto;">

            {{-- Header rojo --}}
            <div class="flex items-center justify-between px-4 py-3 bg-[#8B0000] sticky top-0 z-10">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 3a1 1 0 100 2 1 1 0 000-2zm0 0v2m0 4v10m-4-7l4-3 4 3M8 17h8" />
                    </svg>
                    <span class="text-sm font-semibold text-white tracking-wide">Accesibilidad</span>
                    <span class="text-[10px] text-white/50 ml-1">CTRL+U</span>
                </div>
                <button @click="panel = false"
                    class="text-white/60 hover:text-white text-xl leading-none transition">&times;</button>
            </div>

            <div class="p-4 space-y-4">

                {{-- CONTRASTE --}}
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-3.5 h-3.5 text-[#8B0000]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <circle cx="12" cy="12" r="9" />
                            <path d="M12 3v18" />
                        </svg>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-[#8B0000]">Contraste</p>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <button @click="setContraste('invertido')"
                            :class="contraste === 'invertido' ? 'ring-2 ring-[#8B0000] bg-[#f9eded]' : 'bg-zinc-50 hover:bg-zinc-100'"
                            class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                            <svg class="w-5 h-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 3v18" />
                            </svg>
                            Invertido
                        </button>
                        <button @click="setContraste('oscuro')"
                            :class="contraste === 'oscuro' ? 'ring-2 ring-[#8B0000] bg-[#f9eded]' : 'bg-zinc-50 hover:bg-zinc-100'"
                            class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                            <svg class="w-5 h-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path d="M21 12.79A9 9 0 1111.21 3a7 7 0 109.79 9.79z" />
                            </svg>
                            Oscuro
                        </button>
                        <button @click="setContraste('claro')"
                            :class="contraste === 'claro' ? 'ring-2 ring-[#8B0000] bg-[#f9eded]' : 'bg-zinc-50 hover:bg-zinc-100'"
                            class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                            <svg class="w-5 h-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <circle cx="12" cy="12" r="4" />
                                <path
                                    d="M12 2v2M12 20v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M2 12h2M20 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
                            </svg>
                            Claro
                        </button>
                    </div>
                </div>

                <div class="border-t border-zinc-100"></div>

                {{-- TAMAÑO DE TEXTO --}}
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-3.5 h-3.5 text-[#8B0000]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M4 20h16M8 20V4l8 0M8 12h8" />
                        </svg>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-[#8B0000]">Tamaño de texto</p>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <button @click="setTexto(1)"
                            :class="texto === 1 ? 'ring-2 ring-[#8B0000] bg-[#f9eded]' : 'bg-zinc-50 hover:bg-zinc-100'"
                            class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                            <span class="text-base font-bold text-zinc-600">A</span>
                            110%
                        </button>
                        <button @click="setTexto(2)"
                            :class="texto === 2 ? 'ring-2 ring-[#8B0000] bg-[#f9eded]' : 'bg-zinc-50 hover:bg-zinc-100'"
                            class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                            <span class="text-lg font-bold text-zinc-600">A</span>
                            125%
                        </button>
                        <button @click="setTexto(3)"
                            :class="texto === 3 ? 'ring-2 ring-[#8B0000] bg-[#f9eded]' : 'bg-zinc-50 hover:bg-zinc-100'"
                            class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                            <span class="text-xl font-bold text-zinc-600">A</span>
                            150%
                        </button>
                    </div>
                </div>

                <div class="border-t border-zinc-100"></div>

                {{-- SATURACIÓN --}}
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-3.5 h-3.5 text-[#8B0000]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M12 2v20M2 12h20" />
                        </svg>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-[#8B0000]">Saturación</p>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <button @click="setSaturacion(1)"
                            :class="saturacion === 1 ? 'ring-2 ring-[#8B0000] bg-[#f9eded]' : 'bg-zinc-50 hover:bg-zinc-100'"
                            class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                            <svg class="w-5 h-5 text-zinc-300" fill="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="9" />
                            </svg>
                            Baja
                        </button>
                        <button @click="setSaturacion(2)"
                            :class="saturacion === 2 ? 'ring-2 ring-[#8B0000] bg-[#f9eded]' : 'bg-zinc-50 hover:bg-zinc-100'"
                            class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                            <svg class="w-5 h-5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <circle cx="12" cy="12" r="9" />
                            </svg>
                            Sin color
                        </button>
                        <button @click="setSaturacion(3)"
                            :class="saturacion === 3 ? 'ring-2 ring-[#8B0000] bg-[#f9eded]' : 'bg-zinc-50 hover:bg-zinc-100'"
                            class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                            <svg class="w-5 h-5 text-[#8B0000]" fill="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="9" />
                            </svg>
                            Alta
                        </button>
                    </div>
                </div>

                <div class="border-t border-zinc-100"></div>

                {{-- OTRAS OPCIONES --}}
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-3.5 h-3.5 text-[#8B0000]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-[#8B0000]">Otras opciones</p>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <button @click="toggleEnlaces()"
                            :class="enlaces ? 'ring-2 ring-[#8B0000] bg-[#f9eded]' : 'bg-zinc-50 hover:bg-zinc-100'"
                            class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                            <svg class="w-5 h-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71" />
                                <path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71" />
                            </svg>
                            Resaltar enlaces
                        </button>
                        <button @click="toggleEspaciado()"
                            :class="espaciado ? 'ring-2 ring-[#8B0000] bg-[#f9eded]' : 'bg-zinc-50 hover:bg-zinc-100'"
                            class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                            <svg class="w-5 h-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            Espaciado
                        </button>
                        <button @click="toggleDislexia()"
                            :class="dislexia ? 'ring-2 ring-[#8B0000] bg-[#f9eded]' : 'bg-zinc-50 hover:bg-zinc-100'"
                            class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                            <svg class="w-5 h-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path d="M4 7V4h16v3M9 20h6M12 4v16" />
                            </svg>
                            Dislexia
                        </button>
                        <button @click="toggleLineHeight()"
                            :class="lineHeight ? 'ring-2 ring-[#8B0000] bg-[#f9eded]' : 'bg-zinc-50 hover:bg-zinc-100'"
                            class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                            <svg class="w-5 h-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            Altura línea
                        </button>
                        <button @click="toggleImagenes()"
                            :class="imagenes ? 'ring-2 ring-[#8B0000] bg-[#f9eded]' : 'bg-zinc-50 hover:bg-zinc-100'"
                            class="col-span-2 flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                            <svg class="w-5 h-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path d="M3 3l18 18M21 15l-5-5-4 4-2-2-4 4" />
                            </svg>
                            Ocultar imágenes
                        </button>
                    </div>
                </div>

                {{-- RESTABLECER --}}
                <button @click="resetAll()"
                    class="w-full py-2.5 bg-[#8B0000] hover:bg-[#6e0000] text-white text-xs font-semibold rounded-xl transition">
                    Restablecer todo
                </button>

                <p class="text-center text-[10px] text-zinc-300 pb-1">SIVIS — UFPSO</p>

            </div>
        </div>
    </div>

    <style>
        .sivis-contraste-invertido {
            filter: invert(100%) hue-rotate(180deg) !important;
        }

        .sivis-contraste-invertido img {
            filter: invert(100%) hue-rotate(180deg) !important;
        }

        .sivis-contraste-oscuro {
            background: #000 !important;
            color: #fff !important;
        }

        .sivis-contraste-oscuro * {
            background-color: #000 !important;
            color: #fff !important;
            border-color: #555 !important;
        }

        .sivis-contraste-claro {
            background: #fff !important;
            color: #000 !important;
        }

        .sivis-contraste-claro * {
            background-color: #fff !important;
            color: #000 !important;
            border-color: #ccc !important;
        }

        .sivis-texto-1 {
            zoom: 1.1;
        }

        .sivis-texto-2 {
            zoom: 1.25;
        }

        .sivis-texto-3 {
            zoom: 1.5;
        }

        .sivis-saturacion-1 {
            filter: saturate(30%) !important;
        }

        .sivis-saturacion-2 {
            filter: saturate(0%) !important;
        }

        .sivis-saturacion-3 {
            filter: saturate(200%) !important;
        }

        .sivis-enlaces a {
            text-decoration: underline !important;
            font-weight: bold !important;
            color: #1d4ed8 !important;
            background: #dbeafe !important;
            padding: 0 2px !important;
            border-radius: 2px !important;
        }

        .sivis-espaciado * {
            letter-spacing: 0.1em !important;
            word-spacing: 0.2em !important;
        }

        .sivis-dislexia {
            font-family: 'Comic Sans MS', cursive !important;
        }

        .sivis-line-height {
            line-height: 2 !important;
        }

        .sivis-sin-imagenes img {
            visibility: hidden !important;
        }
    </style>

    <script>
        function accesibilidad() {
            return {
                panel: false,
                contraste: null,
                texto: 0,
                saturacion: 0,
                enlaces: false,
                espaciado: false,
                dislexia: false,
                lineHeight: false,
                imagenes: false,

                init() {
                    const saved = JSON.parse(localStorage.getItem('sivis-accesibilidad') || '{}');
                    this.contraste = saved.contraste ?? null;
                    this.texto = saved.texto ?? 0;
                    this.saturacion = saved.saturacion ?? 0;
                    this.enlaces = saved.enlaces ?? false;
                    this.espaciado = saved.espaciado ?? false;
                    this.dislexia = saved.dislexia ?? false;
                    this.lineHeight = saved.lineHeight ?? false;
                    this.imagenes = saved.imagenes ?? false;
                    this.aplicarTodo();
                    document.addEventListener('livewire:navigated', () => this.aplicarTodo());
                    document.addEventListener('keydown', (e) => {
                        if (e.ctrlKey && e.key === 'u') { e.preventDefault(); this.panel = !this.panel; }
                    });
                },

                guardar() {
                    localStorage.setItem('sivis-accesibilidad', JSON.stringify({
                        contraste: this.contraste,
                        texto: this.texto,
                        saturacion: this.saturacion,
                        enlaces: this.enlaces,
                        espaciado: this.espaciado,
                        dislexia: this.dislexia,
                        lineHeight: this.lineHeight,
                        imagenes: this.imagenes,
                    }));
                },

                aplicarTodo() {
                    ['sivis-contraste-invertido', 'sivis-contraste-oscuro', 'sivis-contraste-claro',
                        'sivis-texto-1', 'sivis-texto-2', 'sivis-texto-3',
                        'sivis-saturacion-1', 'sivis-saturacion-2', 'sivis-saturacion-3',
                        'sivis-enlaces', 'sivis-espaciado', 'sivis-dislexia',
                        'sivis-line-height', 'sivis-sin-imagenes'
                    ].forEach(c => document.body.classList.remove(c));
                    if (this.contraste) document.body.classList.add('sivis-contraste-' + this.contraste);
                    if (this.texto) document.body.classList.add('sivis-texto-' + this.texto);
                    if (this.saturacion) document.body.classList.add('sivis-saturacion-' + this.saturacion);
                    if (this.enlaces) document.body.classList.add('sivis-enlaces');
                    if (this.espaciado) document.body.classList.add('sivis-espaciado');
                    if (this.dislexia) document.body.classList.add('sivis-dislexia');
                    if (this.lineHeight) document.body.classList.add('sivis-line-height');
                    if (this.imagenes) document.body.classList.add('sivis-sin-imagenes');
                },

                setContraste(tipo) {
                    this.contraste = this.contraste === tipo ? null : tipo;
                    this.aplicarTodo(); this.guardar();
                },
                setTexto(nivel) {
                    this.texto = this.texto === nivel ? 0 : nivel;
                    this.aplicarTodo(); this.guardar();
                },
                setSaturacion(nivel) {
                    this.saturacion = this.saturacion === nivel ? 0 : nivel;
                    this.aplicarTodo(); this.guardar();
                },
                toggleEnlaces() { this.enlaces = !this.enlaces; this.aplicarTodo(); this.guardar(); },
                toggleEspaciado() { this.espaciado = !this.espaciado; this.aplicarTodo(); this.guardar(); },
                toggleDislexia() { this.dislexia = !this.dislexia; this.aplicarTodo(); this.guardar(); },
                toggleLineHeight() { this.lineHeight = !this.lineHeight; this.aplicarTodo(); this.guardar(); },
                toggleImagenes() { this.imagenes = !this.imagenes; this.aplicarTodo(); this.guardar(); },

                resetAll() {
                    this.contraste = null; this.texto = 0; this.saturacion = 0;
                    this.enlaces = false; this.espaciado = false; this.dislexia = false;
                    this.lineHeight = false; this.imagenes = false;
                    this.aplicarTodo();
                    localStorage.removeItem('sivis-accesibilidad');
                }
            }
        }
    </script>

    
    {{-- Aviso de permisos insuficientes --}}
    <div x-data="{ open: false, message: '', timer: null }" x-on:permission-denied.window="
        message = $event.detail.message;
        open = true;
        clearTimeout(timer);
        timer = setTimeout(() => open = false, 5000)
     " x-show="open" x-transition style="display: none;" role="alert"
        class="fixed right-6 top-6 z-[9999] w-full max-w-sm rounded-xl border border-red-200 bg-white p-4 shadow-2xl">
        <div class="flex items-start gap-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100 text-[#9c1c1c]">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m0 3.75h.008M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-bold text-[#7a1515]">Acceso denegado</p>
                <p class="mt-0.5 text-sm text-slate-600" x-text="message"></p>
            </div>
            <button type="button" x-on:click="open = false" class="text-slate-400 hover:text-slate-600"
                aria-label="Cerrar">✕</button>
        </div>
    </div>
</body>

</html>