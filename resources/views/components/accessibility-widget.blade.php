{{--
    Panel de accesibilidad. Se abre con el botón flotante o con Alt+A y se cierra con Escape.
    Las preferencias se guardan en el navegador y se aplican antes de pintar la página
    (resources/views/components/theme-script.blade.php). Los estilos están al final de resources/css/app.css.
--}}
@php
    $secciones = [
        'Lectura' => [
            ['clave' => 'fuente', 'titulo' => 'Fuente de lectura fácil', 'detalle' => 'Atkinson Hyperlegible, pensada para baja visión.'],
            ['clave' => 'espaciado', 'titulo' => 'Más espacio entre letras y líneas', 'detalle' => 'Según la pauta WCAG 1.4.12.'],
            ['clave' => 'guia', 'titulo' => 'Guía de lectura', 'detalle' => 'Una franja sigue al puntero y oscurece el resto.'],
            ['clave' => 'enlaces', 'titulo' => 'Resaltar enlaces', 'detalle' => 'Subrayados y con fondo.'],
        ],
        'Navegación' => [
            ['clave' => 'foco', 'titulo' => 'Foco del teclado visible', 'detalle' => 'Marco grueso al moverse con Tab.'],
            ['clave' => 'cursor', 'titulo' => 'Cursor grande', 'detalle' => null],
            ['clave' => 'movimiento', 'titulo' => 'Reducir movimiento', 'detalle' => 'Quita animaciones y transiciones.'],
            ['clave' => 'imagenes', 'titulo' => 'Ocultar imágenes', 'detalle' => null],
        ],
    ];
    $segmento = 'flex-1 rounded-md px-2 py-1.5 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-siproc-red';
@endphp

<div x-data="panelAccesibilidad()" x-on:keydown.window.alt.a.prevent="alternarPanel()"
    x-on:keydown.escape.window="if (abierto) cerrar()"
    x-on:siproc-tema.window="tema = $event.detail.preferencia"
    x-on:mousemove.window="if (estado.guia) guiaY = $event.clientY">

    {{-- Botón flotante --}}
    <button type="button" x-ref="boton" @click="alternarPanel()"
        class="fixed bottom-6 right-6 z-50 flex h-12 w-12 items-center justify-center rounded-full bg-siproc-red text-white shadow-xl transition hover:bg-siproc-red-dark focus:outline-none focus-visible:ring-4 focus-visible:ring-siproc-red/40"
        :aria-expanded="abierto" aria-controls="panel-accesibilidad" aria-label="Accesibilidad (Alt+A)" title="Accesibilidad (Alt+A)">
        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="9" />
            <path d="M10 16.5l2-3l2 3m-2-3v-2l3-1m-6 0l3 1" />
            <circle cx="12" cy="7.5" r=".6" fill="currentColor" />
        </svg>
        <span x-cloak x-text="activos" :class="activos > 0 ? 'flex' : 'hidden'"
            class="absolute -right-1 -top-1 h-5 min-w-5 items-center justify-center rounded-full bg-white px-1 text-[11px] font-bold text-siproc-red shadow ring-2 ring-siproc-red"
            :aria-label="activos + ' ajustes activos'"></span>
    </button>

    {{-- Panel --}}
    <div id="panel-accesibilidad" x-ref="panel" x-show="abierto" x-cloak role="dialog" aria-modal="false" aria-labelledby="panel-accesibilidad-titulo"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed bottom-20 right-4 z-50 flex max-h-[min(80vh,44rem)] w-[min(23rem,calc(100vw-2rem))] flex-col overflow-hidden rounded-2xl border border-siproc-line bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900 sm:right-6">

        <div class="flex items-start justify-between gap-3 border-b border-siproc-line px-5 py-4 dark:border-gray-800">
            <div>
                <h2 id="panel-accesibilidad-titulo" class="font-display text-base font-bold">Accesibilidad</h2>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">Se guarda en este navegador. Atajo: <kbd class="rounded border border-gray-300 px-1 font-sans dark:border-gray-700">Alt</kbd> + <kbd class="rounded border border-gray-300 px-1 font-sans dark:border-gray-700">A</kbd></p>
            </div>
            <button type="button" @click="cerrar()" aria-label="Cerrar panel de accesibilidad"
                class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-white">
                <x-icon name="x-mark" class="h-5 w-5" />
            </button>
        </div>

        <div class="scroll-sutil flex-1 space-y-6 overflow-y-auto px-5 py-5 dark:[--scroll:rgb(255_255_255/0.2)]">
            {{-- Visualización --}}
            <section aria-labelledby="a11y-visualizacion">
                <h3 id="a11y-visualizacion" class="form-label">Visualización</h3>

                <div class="mt-3 space-y-4">
                    <div>
                        <p id="a11y-tema" class="text-sm font-semibold">Tema</p>
                        <div class="mt-1.5 flex gap-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-800" role="radiogroup" aria-labelledby="a11y-tema">
                            <template x-for="opcion in [['claro', 'Claro'], ['oscuro', 'Oscuro'], ['sistema', 'Automático']]" :key="opcion[0]">
                                <button type="button" role="radio" :aria-checked="tema === opcion[0]" @click="cambiarTema(opcion[0])"
                                    class="{{ $segmento }}"
                                    :class="tema === opcion[0] ? 'bg-white text-siproc-red-dark shadow dark:bg-gray-700 dark:text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
                                    x-text="opcion[1]"></button>
                            </template>
                        </div>
                    </div>

                    <div>
                        <p id="a11y-texto" class="text-sm font-semibold">Tamaño del texto</p>
                        <div class="mt-1.5 flex items-center gap-2" role="group" aria-labelledby="a11y-texto">
                            <button type="button" @click="cambiarTexto(-1)" :disabled="estado.texto === 0" aria-label="Reducir texto"
                                class="btn btn-outline btn-sm w-11 text-sm">A−</button>
                            <div class="relative h-2 flex-1 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700" aria-hidden="true">
                                <div class="absolute inset-y-0 left-0 rounded-full bg-siproc-red transition-all" :style="`width: ${(estado.texto / 4) * 100}%`"></div>
                            </div>
                            <button type="button" @click="cambiarTexto(1)" :disabled="estado.texto === 4" aria-label="Aumentar texto"
                                class="btn btn-outline btn-sm w-11 text-base">A+</button>
                            <span class="w-12 text-right text-sm font-semibold tabular-nums" aria-live="polite" x-text="porcentajes[estado.texto] + ' %'"></span>
                        </div>
                    </div>

                    <div>
                        <p id="a11y-color" class="text-sm font-semibold">Color</p>
                        <div class="mt-1.5 flex gap-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-800" role="radiogroup" aria-labelledby="a11y-color">
                            <template x-for="opcion in [['normal', 'Normal'], ['gris', 'Grises'], ['baja', 'Suave'], ['alta', 'Intenso']]" :key="opcion[0]">
                                <button type="button" role="radio" :aria-checked="estado.color === opcion[0]" @click="cambiar('color', opcion[0])"
                                    class="{{ $segmento }}"
                                    :class="estado.color === opcion[0] ? 'bg-white text-siproc-red-dark shadow dark:bg-gray-700 dark:text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
                                    x-text="opcion[1]"></button>
                            </template>
                        </div>
                    </div>

                    <label class="flex cursor-pointer items-start justify-between gap-4">
                        <span>
                            <span class="block text-sm font-semibold">Alto contraste</span>
                            <span class="block text-xs text-slate-500 dark:text-gray-400">Textos y bordes más marcados.</span>
                        </span>
                        <x-a11y-switch clave="contraste" />
                    </label>
                </div>
            </section>

            @foreach ($secciones as $titulo => $opciones)
                <section aria-labelledby="a11y-{{ \Illuminate\Support\Str::slug($titulo) }}">
                    <h3 id="a11y-{{ \Illuminate\Support\Str::slug($titulo) }}" class="form-label">{{ $titulo }}</h3>
                    <div class="mt-3 space-y-3">
                        @foreach ($opciones as $opcion)
                            <label class="flex cursor-pointer items-start justify-between gap-4">
                                <span>
                                    <span class="block text-sm font-semibold">{{ $opcion['titulo'] }}</span>
                                    @if ($opcion['detalle'])
                                        <span class="block text-xs text-slate-500 dark:text-gray-400">{{ $opcion['detalle'] }}</span>
                                    @endif
                                </span>
                                <x-a11y-switch :clave="$opcion['clave']" />
                            </label>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-siproc-line px-5 py-3 dark:border-gray-800">
            <span class="text-xs text-slate-500 dark:text-gray-400" x-text="activos === 0 ? 'Sin ajustes activos' : (activos === 1 ? '1 ajuste activo' : activos + ' ajustes activos')"></span>
            <button type="button" @click="restablecer()" :disabled="activos === 0" class="btn btn-outline btn-sm">Restablecer</button>
        </div>
    </div>

    {{-- Guía de lectura: oscurece arriba y abajo de una franja que sigue al puntero --}}
    <template x-if="estado.guia">
        <div aria-hidden="true">
            <div class="pointer-events-none fixed inset-x-0 top-0 z-[45] bg-black/45" :style="`height: ${Math.max(guiaY - 45, 0)}px`"></div>
            <div class="pointer-events-none fixed inset-x-0 bottom-0 z-[45] bg-black/45" :style="`top: ${guiaY + 45}px`"></div>
        </div>
    </template>
</div>

<script>
    function panelAccesibilidad() {
        return {
            abierto: false,
            estado: window.siprocA11y.leer(),
            tema: window.siprocTema.preferencia(),
            guiaY: window.innerHeight / 2,
            porcentajes: [100, 112, 125, 137, 150],

            get activos() {
                const base = window.siprocA11y.predeterminado();
                return Object.keys(base).filter((clave) => this.estado[clave] !== base[clave]).length;
            },

            alternarPanel() { this.abierto ? this.cerrar() : this.abrir(); },
            abrir() {
                this.abierto = true;
                this.$nextTick(() => this.$refs.panel.querySelector('button, [role=switch]')?.focus());
            },
            cerrar() {
                this.abierto = false;
                this.$nextTick(() => this.$refs.boton.focus());
            },

            cambiar(clave, valor) {
                this.estado = { ...this.estado, [clave]: valor };
                window.siprocA11y.guardar(this.estado);
            },
            alternar(clave) { this.cambiar(clave, ! this.estado[clave]); },
            cambiarTexto(paso) { this.cambiar('texto', Math.min(4, Math.max(0, this.estado.texto + paso))); },
            cambiarTema(valor) { this.tema = valor; window.siprocTema.establecer(valor); },
            restablecer() {
                this.estado = window.siprocA11y.predeterminado();
                window.siprocA11y.guardar(this.estado);
            },
        };
    }
</script>
