{{--
    Widget de accesibilidad: contraste, tamaño de texto, saturación, enlaces, espaciado, fuente para dislexia,
    altura de línea y ocultar imágenes. Guarda las preferencias en localStorage y se abre con Ctrl+U.
    Los estilos de cada opción están en resources/css/app.css (clases .siproc-*).
    Alpine llama a init() solo una vez por página; el atajo de teclado se registra con x-on y se limpia solo al navegar.
--}}
<div x-data="accesibilidad()" x-on:keydown.window.ctrl.u.prevent="panel = ! panel">

    {{-- Botón flotante --}}
    <button @click="panel = !panel"
        class="fixed bottom-6 right-6 z-50 w-12 h-12 rounded-full bg-siproc-red text-white shadow-xl flex items-center justify-center hover:bg-siproc-red-dark transition-all"
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
        <div class="flex items-center justify-between px-4 py-3 bg-siproc-red sticky top-0 z-10">
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
                    <svg class="w-3.5 h-3.5 text-siproc-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 3v18" />
                    </svg>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-siproc-red">Contraste</p>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <button @click="setContraste('invertido')"
                        :class="contraste === 'invertido' ? 'ring-2 ring-siproc-red bg-siproc-blush' : 'bg-zinc-50 hover:bg-zinc-100'"
                        class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                        <svg class="w-5 h-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <circle cx="12" cy="12" r="9" />
                            <path d="M12 3v18" />
                        </svg>
                        Invertido
                    </button>
                    <button @click="setContraste('oscuro')"
                        :class="contraste === 'oscuro' ? 'ring-2 ring-siproc-red bg-siproc-blush' : 'bg-zinc-50 hover:bg-zinc-100'"
                        class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                        <svg class="w-5 h-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M21 12.79A9 9 0 1111.21 3a7 7 0 109.79 9.79z" />
                        </svg>
                        Oscuro
                    </button>
                    <button @click="setContraste('claro')"
                        :class="contraste === 'claro' ? 'ring-2 ring-siproc-red bg-siproc-blush' : 'bg-zinc-50 hover:bg-zinc-100'"
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
                    <svg class="w-3.5 h-3.5 text-siproc-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M4 20h16M8 20V4l8 0M8 12h8" />
                    </svg>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-siproc-red">Tamaño de texto</p>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <button @click="setTexto(1)"
                        :class="texto === 1 ? 'ring-2 ring-siproc-red bg-siproc-blush' : 'bg-zinc-50 hover:bg-zinc-100'"
                        class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                        <span class="text-base font-bold text-zinc-600">A</span>
                        110%
                    </button>
                    <button @click="setTexto(2)"
                        :class="texto === 2 ? 'ring-2 ring-siproc-red bg-siproc-blush' : 'bg-zinc-50 hover:bg-zinc-100'"
                        class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                        <span class="text-lg font-bold text-zinc-600">A</span>
                        125%
                    </button>
                    <button @click="setTexto(3)"
                        :class="texto === 3 ? 'ring-2 ring-siproc-red bg-siproc-blush' : 'bg-zinc-50 hover:bg-zinc-100'"
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
                    <svg class="w-3.5 h-3.5 text-siproc-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M12 2v20M2 12h20" />
                    </svg>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-siproc-red">Saturación</p>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <button @click="setSaturacion(1)"
                        :class="saturacion === 1 ? 'ring-2 ring-siproc-red bg-siproc-blush' : 'bg-zinc-50 hover:bg-zinc-100'"
                        class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                        <svg class="w-5 h-5 text-zinc-300" fill="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="9" />
                        </svg>
                        Baja
                    </button>
                    <button @click="setSaturacion(2)"
                        :class="saturacion === 2 ? 'ring-2 ring-siproc-red bg-siproc-blush' : 'bg-zinc-50 hover:bg-zinc-100'"
                        class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                        <svg class="w-5 h-5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <circle cx="12" cy="12" r="9" />
                        </svg>
                        Sin color
                    </button>
                    <button @click="setSaturacion(3)"
                        :class="saturacion === 3 ? 'ring-2 ring-siproc-red bg-siproc-blush' : 'bg-zinc-50 hover:bg-zinc-100'"
                        class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                        <svg class="w-5 h-5 text-siproc-red" fill="currentColor" viewBox="0 0 24 24">
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
                    <svg class="w-3.5 h-3.5 text-siproc-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M4 6h16M4 12h16M4 18h7" />
                    </svg>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-siproc-red">Otras opciones</p>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <button @click="toggleEnlaces()"
                        :class="enlaces ? 'ring-2 ring-siproc-red bg-siproc-blush' : 'bg-zinc-50 hover:bg-zinc-100'"
                        class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                        <svg class="w-5 h-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71" />
                            <path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71" />
                        </svg>
                        Resaltar enlaces
                    </button>
                    <button @click="toggleEspaciado()"
                        :class="espaciado ? 'ring-2 ring-siproc-red bg-siproc-blush' : 'bg-zinc-50 hover:bg-zinc-100'"
                        class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                        <svg class="w-5 h-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        Espaciado
                    </button>
                    <button @click="toggleDislexia()"
                        :class="dislexia ? 'ring-2 ring-siproc-red bg-siproc-blush' : 'bg-zinc-50 hover:bg-zinc-100'"
                        class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                        <svg class="w-5 h-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M4 7V4h16v3M9 20h6M12 4v16" />
                        </svg>
                        Dislexia
                    </button>
                    <button @click="toggleLineHeight()"
                        :class="lineHeight ? 'ring-2 ring-siproc-red bg-siproc-blush' : 'bg-zinc-50 hover:bg-zinc-100'"
                        class="flex flex-col items-center gap-1.5 rounded-xl border border-zinc-200 p-3 text-[11px] text-zinc-700 transition">
                        <svg class="w-5 h-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        Altura línea
                    </button>
                    <button @click="toggleImagenes()"
                        :class="imagenes ? 'ring-2 ring-siproc-red bg-siproc-blush' : 'bg-zinc-50 hover:bg-zinc-100'"
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
                class="w-full py-2.5 bg-siproc-red hover:bg-siproc-red-dark text-white text-xs font-semibold rounded-xl transition">
                Restablecer todo
            </button>

            <p class="text-center text-[10px] text-zinc-300 pb-1">SIPROC — UFPSO</p>

        </div>
    </div>
</div>

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
                const saved = JSON.parse(localStorage.getItem('siproc-accesibilidad') || '{}');
                this.contraste = saved.contraste ?? null;
                this.texto = saved.texto ?? 0;
                this.saturacion = saved.saturacion ?? 0;
                this.enlaces = saved.enlaces ?? false;
                this.espaciado = saved.espaciado ?? false;
                this.dislexia = saved.dislexia ?? false;
                this.lineHeight = saved.lineHeight ?? false;
                this.imagenes = saved.imagenes ?? false;
                this.aplicarTodo();
            },

            guardar() {
                localStorage.setItem('siproc-accesibilidad', JSON.stringify({
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
                ['siproc-contraste-invertido', 'siproc-contraste-oscuro', 'siproc-contraste-claro',
                    'siproc-texto-1', 'siproc-texto-2', 'siproc-texto-3',
                    'siproc-saturacion-1', 'siproc-saturacion-2', 'siproc-saturacion-3',
                    'siproc-enlaces', 'siproc-espaciado', 'siproc-dislexia',
                    'siproc-line-height', 'siproc-sin-imagenes'
                ].forEach(c => document.body.classList.remove(c));
                if (this.contraste) document.body.classList.add('siproc-contraste-' + this.contraste);
                if (this.texto) document.body.classList.add('siproc-texto-' + this.texto);
                if (this.saturacion) document.body.classList.add('siproc-saturacion-' + this.saturacion);
                if (this.enlaces) document.body.classList.add('siproc-enlaces');
                if (this.espaciado) document.body.classList.add('siproc-espaciado');
                if (this.dislexia) document.body.classList.add('siproc-dislexia');
                if (this.lineHeight) document.body.classList.add('siproc-line-height');
                if (this.imagenes) document.body.classList.add('siproc-sin-imagenes');
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
                localStorage.removeItem('siproc-accesibilidad');
            }
        }
    }
</script>
