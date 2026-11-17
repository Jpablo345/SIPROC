@props(['mobile' => false])

@php
    // Menú lateral. Se usa igual en escritorio y en el cajón móvil, así que un enlace nuevo se agrega solo aquí.
    // "active" acepta los patrones de request()->routeIs(); si se omite, se usa la ruta del enlace.
    $sections = [
        [
            'title' => 'Principal',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Publicaciones', 'route' => 'publicaciones.index', 'icon' => 'document-text', 'active' => ['publicaciones.index', 'publicaciones.show']],
                ['label' => 'Tipos de publicación', 'route' => 'publicaciones.tipos', 'icon' => 'tag', 'active' => ['publicaciones.tipos', 'publicaciones.tipos.*']],
                ['label' => 'Revistas', 'route' => 'publicaciones.journals', 'icon' => 'newspaper'],
                ['label' => 'Tipos de libro', 'route' => 'publicaciones.tipos-libro', 'icon' => 'bookmark'],
                ['label' => 'Investigadores', 'route' => 'investigadores', 'icon' => 'users', 'active' => ['investigadores', 'investigadores.*']],
            ],
        ],
        [
            'title' => 'Tipología del producto',
            'collapsible' => 'tipologia',
            'items' => [
                [
                    'label' => 'Generación de nuevo conocimiento',
                    'icon' => 'light-bulb',
                    'group' => 'generacion',
                    'children' => [
                        ['label' => 'Artículos científicos', 'route' => 'tipologia.generacion.articulos'],
                        ['label' => 'Libros de investigación', 'route' => 'tipologia.generacion.libros'],
                        ['label' => 'Capítulos de libro', 'route' => 'tipologia.generacion.capitulos'],
                        ['label' => 'Trabajos en eventos', 'route' => 'tipologia.generacion.trabajos'],
                        ['label' => 'Otras publicaciones', 'route' => 'tipologia.generacion.otras'],
                    ],
                ],
                ['label' => 'Desarrollo tecnológico e innovación', 'route' => 'tipologia.desarrollo', 'icon' => 'cpu-chip'],
                ['label' => 'Apropiación social del conocimiento', 'route' => 'tipologia.apropiacion', 'icon' => 'user-group'],
                ['label' => 'Divulgación pública de la ciencia', 'route' => 'tipologia.divulgacion', 'icon' => 'megaphone'],
                ['label' => 'Formación de recurso humano', 'route' => 'tipologia.formacion', 'icon' => 'academic-cap'],
            ],
        ],
        [
            'title' => 'Estructura',
            'items' => [
                ['label' => 'Grupos de investigación', 'route' => 'estructura.grupos', 'icon' => 'user-group', 'active' => ['estructura.grupos', 'estructura.grupos.*']],
                ['label' => 'Instituciones', 'route' => 'estructura.instituciones', 'icon' => 'building-library'],
            ],
        ],
    ];

    $isActive = fn (array $item) => request()->routeIs(...(array) ($item['active'] ?? $item['route']));

    // Dos estilos según config('siproc.menu'). Manual UFPSO: logo a color sobre fondo claro y
    // logo blanco (una tinta, public/img/logoufps-blanco.png) sobre fondos oscuros.
    $oscuro = in_array(config('siproc.menu'), ['rojo', 'oscuro'], true);
    $estilo = $oscuro
        ? [
            'contenedor' => 'bg-siproc-red-deep text-white dark:bg-siproc-red-darkest',
            'logoColor' => 'hidden',
            'logoBlanco' => 'block',
            'separador' => 'bg-white/30',
            'activo' => 'bg-white/15 text-white shadow-[inset_3px_0_0_#fff]',
            'inactivo' => 'text-white/80 hover:bg-white/10 hover:text-white',
            'grupoActivo' => 'text-white',
            'seccion' => 'text-white/60 hover:text-white',
            'tenue' => 'text-white/60',
            'borde' => 'border-white/15',
            'boton' => 'border-white/25 text-white/85 hover:bg-white/10 hover:text-white',
            'buscar' => 'border-white/20 bg-white/10 text-white/80 hover:bg-white/15',
            'rol' => 'bg-white/15 text-white',
            'scroll' => '[--scroll:rgb(255_255_255/0.35)]',
        ]
        : [
            'contenedor' => 'border-r border-siproc-line bg-white text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300',
            'logoColor' => 'block dark:hidden',
            'logoBlanco' => 'hidden dark:block',
            'separador' => 'bg-gray-300 dark:bg-white/20',
            'activo' => 'bg-siproc-rose text-siproc-red-dark shadow-[inset_3px_0_0_#D21116] dark:bg-red-500/10 dark:text-red-300',
            'inactivo' => 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white',
            'grupoActivo' => 'text-gray-900 dark:text-white',
            'seccion' => 'text-gray-400 hover:text-gray-700 dark:text-gray-500 dark:hover:text-gray-300',
            'tenue' => 'text-gray-400 dark:text-gray-500',
            'borde' => 'border-siproc-line dark:border-gray-800',
            'boton' => 'border-gray-300 text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800',
            'buscar' => 'border-gray-200 bg-gray-50 text-gray-500 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700',
            'rol' => 'bg-siproc-rose text-siproc-red-dark dark:bg-red-500/15 dark:text-red-300',
            'scroll' => '[--scroll:rgb(0_0_0/0.2)] dark:[--scroll:rgb(255_255_255/0.2)]',
        ];

    $link = 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition';
    $subLink = 'block rounded-lg py-1.5 pl-11 pr-3 text-[13px] font-medium transition';
    $sectionTitle = 'px-3 text-[11px] font-semibold uppercase tracking-[0.2em] ' . $estilo['seccion'];

    $usuario = auth()->user();
    $rol = $usuario->getRoleNames()->first();
@endphp

<div class="flex h-full flex-col {{ $estilo['contenedor'] }}">
    {{-- Marca: logo UFPS, separador vertical y nombre del sistema (arquitectura de marca del manual) --}}
    <div class="flex items-center justify-between gap-2 p-4">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex flex-1 items-center gap-3 rounded-xl p-2">
            <img src="{{ asset('img/logoufps.png') }}" alt="UFPS" class="{{ $estilo['logoColor'] }} h-10 w-10 shrink-0" />
            <img src="{{ asset('img/logoufps-blanco.png') }}" alt="UFPS" class="{{ $estilo['logoBlanco'] }} h-10 w-10 shrink-0" />
            <span class="h-9 w-px {{ $estilo['separador'] }}"></span>
            <span class="leading-tight">
                <span class="block font-display text-base font-bold tracking-wide">SIPROC</span>
                <span class="block text-xs opacity-70">Producción Científica</span>
            </span>
        </a>
        @if ($mobile)
            <button type="button" @click="open = false" aria-label="Cerrar menú" class="rounded-lg border p-2 {{ $estilo['boton'] }}">
                <x-icon name="x-mark" />
            </button>
        @endif
    </div>

    {{-- Búsqueda global --}}
    <div class="px-4 pb-4">
        <button type="button" x-data @click="$dispatch('abrir-busqueda')"
            class="flex w-full items-center gap-2 rounded-lg border px-3 py-2 text-sm transition {{ $estilo['buscar'] }}">
            <x-icon name="magnifying-glass" class="h-4 w-4" />
            <span class="flex-1 text-left">Buscar…</span>
            <kbd class="rounded border border-current px-1.5 text-[10px] font-semibold opacity-60">Ctrl K</kbd>
        </button>
    </div>

    {{-- Enlaces --}}
    <nav class="scroll-sutil flex-1 space-y-6 overflow-y-auto px-3 pb-6 {{ $estilo['scroll'] }}" aria-label="Menú principal"
        x-data="{ tipologia: $persist(true).as('siproc-nav-tipologia'), generacion: $persist(true).as('siproc-nav-generacion') }">
        @foreach ($sections as $section)
            <div>
                @if (isset($section['collapsible']))
                    <button type="button" @click="{{ $section['collapsible'] }} = ! {{ $section['collapsible'] }}"
                        :aria-expanded="{{ $section['collapsible'] }}"
                        class="{{ $sectionTitle }} flex w-full items-center justify-between">
                        <span>{{ $section['title'] }}</span>
                        <x-icon name="chevron-down" class="h-4 w-4 transition"
                            x-bind:class="{{ $section['collapsible'] }} ? 'rotate-180' : ''" />
                    </button>
                @else
                    <p class="{{ $sectionTitle }}">{{ $section['title'] }}</p>
                @endif

                <ul class="mt-2 space-y-0.5"
                    @isset($section['collapsible']) x-show="{{ $section['collapsible'] }}" x-collapse @endisset>
                    @foreach ($section['items'] as $item)
                        <li>
                            @isset($item['children'])
                                @php $groupActive = collect($item['children'])->contains(fn ($child) => $isActive($child)); @endphp
                                <button type="button" @click="{{ $item['group'] }} = ! {{ $item['group'] }}"
                                    :aria-expanded="{{ $item['group'] }}"
                                    class="{{ $link }} w-full text-left {{ $groupActive ? $estilo['grupoActivo'] : $estilo['inactivo'] }}">
                                    <x-icon :name="$item['icon']" />
                                    <span class="flex-1">{{ $item['label'] }}</span>
                                    <x-icon name="chevron-down" class="h-4 w-4 transition {{ $estilo['tenue'] }}"
                                        x-bind:class="{{ $item['group'] }} ? 'rotate-180' : ''" />
                                </button>
                                <ul class="mt-1 space-y-0.5" x-show="{{ $item['group'] }}" x-collapse>
                                    @foreach ($item['children'] as $child)
                                        <li>
                                            <a href="{{ route($child['route']) }}" wire:navigate
                                                @if ($isActive($child)) aria-current="page" @endif
                                                class="{{ $subLink }} {{ $isActive($child) ? $estilo['activo'] : $estilo['inactivo'] }}">
                                                {{ $child['label'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <a href="{{ route($item['route']) }}" wire:navigate
                                    @if ($isActive($item)) aria-current="page" @endif
                                    class="{{ $link }} {{ $isActive($item) ? $estilo['activo'] : $estilo['inactivo'] }}">
                                    <x-icon :name="$item['icon']" />
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endisset
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    {{-- Usuario: nombre, rol y accesos --}}
    <div class="border-t p-4 {{ $estilo['borde'] }}">
        <div class="flex items-center gap-3">
            <x-avatar :name="$usuario->name" />
            <div class="min-w-0 flex-1 leading-tight">
                <p class="truncate text-sm font-semibold" x-data="{{ json_encode(['name' => $usuario->name]) }}"
                    x-text="name" x-on:profile-updated.window="name = $event.detail.name"></p>
                <p class="truncate text-xs {{ $estilo['tenue'] }}">{{ $usuario->email }}</p>
            </div>
        </div>
        @if ($rol)
            <span class="mt-3 inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $estilo['rol'] }}">{{ $rol }}</span>
        @endif
        <div class="mt-3 flex gap-2">
            <a href="{{ route('profile') }}" wire:navigate
                class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-semibold {{ $estilo['boton'] }}">
                <x-icon name="user-circle" class="h-4 w-4" />
                Perfil
            </a>
            <button type="button" x-data="{ oscuro: window.siprocTema ? window.siprocTema.esOscuro() : false }"
                x-on:siproc-tema.window="oscuro = $event.detail.oscuro"
                @click="window.siprocTema.alternar()"
                :aria-label="oscuro ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
                :title="oscuro ? 'Modo claro' : 'Modo oscuro'"
                class="inline-flex items-center justify-center rounded-lg border px-3 py-2 {{ $estilo['boton'] }}">
                <x-icon name="moon" class="h-4 w-4" x-show="! oscuro" />
                <x-icon name="sun" class="h-4 w-4" x-show="oscuro" x-cloak />
            </button>
            <button type="button" wire:click="logout" title="Cerrar sesión"
                class="inline-flex items-center justify-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-semibold {{ $estilo['boton'] }}">
                <x-icon name="logout" class="h-4 w-4" />
                Salir
            </button>
        </div>
    </div>
</div>
