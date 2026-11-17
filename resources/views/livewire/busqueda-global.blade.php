<div x-data="{
        open: false,
        abrir() { this.open = true; this.$nextTick(() => this.$refs.entrada.focus()); },
        cerrar() { this.open = false; },
        mover(paso) {
            const items = [...this.$el.querySelectorAll('[data-resultado]')];
            if (! items.length) return;
            const actual = items.indexOf(document.activeElement);
            const siguiente = actual === -1 ? (paso > 0 ? 0 : items.length - 1) : (actual + paso + items.length) % items.length;
            items[siguiente].focus();
        },
    }"
    x-on:keydown.window.ctrl.k.prevent="open ? cerrar() : abrir()"
    x-on:keydown.window.meta.k.prevent="open ? cerrar() : abrir()"
    x-on:abrir-busqueda.window="abrir()"
    x-on:keydown.escape.window="cerrar()">

    <div x-show="open" x-cloak class="fixed inset-0 z-[9990] flex items-start justify-center p-4 pt-[10vh]"
        role="dialog" aria-modal="true" aria-label="Búsqueda global">
        <div class="absolute inset-0 bg-siproc-ink/50 backdrop-blur-sm" x-show="open" x-transition.opacity @click="cerrar()"></div>

        <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-gray-900 dark:ring-white/10"
            x-show="open" x-trap.noscroll="open" x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            @keydown.arrow-down.prevent="mover(1)" @keydown.arrow-up.prevent="mover(-1)">

            {{-- Campo de búsqueda --}}
            <div class="flex items-center gap-3 border-b border-siproc-line px-4 dark:border-gray-800">
                <x-icon name="magnifying-glass" class="h-5 w-5 text-gray-400" />
                <input x-ref="entrada" wire:model.live.debounce.250ms="q" type="search" autocomplete="off"
                    placeholder="Buscar publicaciones, investigadores, grupos o revistas…"
                    class="h-14 w-full border-0 bg-transparent text-base text-siproc-ink placeholder-gray-400 focus:ring-0 dark:text-white"
                    aria-label="Buscar en SIPROC">
                <x-spinner wire:loading wire:target="q" class="text-gray-400" />
                <kbd class="hidden rounded border border-gray-300 px-1.5 text-[10px] font-semibold text-gray-400 sm:inline dark:border-gray-700">Esc</kbd>
            </div>

            <div class="max-h-[60vh] overflow-y-auto p-2">
                @if (mb_strlen(trim($q)) < 2)
                    <p class="px-3 pb-1 pt-2 text-xs font-semibold uppercase tracking-wider text-gray-400">Ir a</p>
                    <ul>
                        @foreach ($accesos as $acceso)
                            <li>
                                <a href="{{ $acceso['url'] }}" wire:navigate data-resultado @click="cerrar()"
                                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-gray-700 outline-none hover:bg-gray-50 focus:bg-siproc-rose focus:text-siproc-red-dark dark:text-gray-300 dark:hover:bg-gray-800 dark:focus:bg-red-500/10 dark:focus:text-red-300">
                                    <x-icon :name="$acceso['icono']" class="h-4 w-4 opacity-70" />
                                    {{ $acceso['titulo'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <p class="px-3 pb-2 pt-3 text-xs text-gray-400">Escribe al menos 2 letras. Usa ↑ ↓ para moverte y Enter para abrir.</p>
                @elseif (empty($this->resultados))
                    <x-empty-state icon="magnifying-glass" title="Sin resultados" message="No encontramos nada para “{{ $q }}”. Prueba con otra palabra." />
                @else
                    @foreach ($this->resultados as $seccion => $items)
                        <p class="px-3 pb-1 pt-3 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ $seccion }}</p>
                        <ul>
                            @foreach ($items as $item)
                                <li>
                                    <a href="{{ $item['url'] }}" wire:navigate data-resultado @click="cerrar()"
                                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 outline-none hover:bg-gray-50 focus:bg-siproc-rose dark:hover:bg-gray-800 dark:focus:bg-red-500/10">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                            <x-icon :name="$item['icono']" class="h-4 w-4" />
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-semibold text-siproc-ink dark:text-white">{!! $this->resaltar($item['titulo']) !!}</span>
                                            <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ $item['detalle'] }}</span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
