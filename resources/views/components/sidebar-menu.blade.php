@props(['mobile' => false])

@php
    // Menú lateral. Se usa igual en escritorio y en el cajón móvil, así que un enlace nuevo se agrega solo aquí.
    // "active" acepta los patrones de request()->routeIs(); si se omite, se usa la ruta del enlace.
    $sections = [
        [
            'title' => 'Principal',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Publicaciones', 'route' => 'publicaciones.index', 'icon' => 'document-text'],
                ['label' => 'Tipos de publicación', 'route' => 'publicaciones.tipos', 'icon' => 'tag', 'active' => ['publicaciones.tipos', 'publicaciones.tipos.*']],
                ['label' => 'Revistas', 'route' => 'publicaciones.journals', 'icon' => 'newspaper'],
                ['label' => 'Tipos de libro', 'route' => 'publicaciones.tipos-libro', 'icon' => 'bookmark'],
                ['label' => 'Investigadores', 'route' => 'investigadores', 'icon' => 'users'],
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
                ['label' => 'Grupos de investigación', 'route' => 'estructura.grupos', 'icon' => 'user-group'],
                ['label' => 'Instituciones', 'route' => 'estructura.instituciones', 'icon' => 'building-library'],
            ],
        ],
    ];

    $isActive = fn (array $item) => request()->routeIs(...(array) ($item['active'] ?? $item['route']));

    $link = 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition';
    $subLink = 'block rounded-lg py-1.5 pl-11 pr-3 text-[13px] font-medium transition';
    $active = 'bg-white/15 text-white shadow-[inset_0_0_0_1px_rgba(255,255,255,0.08)]';
    $inactive = 'text-white/80 hover:bg-white/10 hover:text-white';
    $sectionTitle = 'px-3 text-[11px] font-semibold uppercase tracking-[0.24em] text-white/60';
@endphp

<div class="flex h-full flex-col bg-siproc-red text-white">
    {{-- Marca --}}
    <div class="flex items-center justify-between px-5 py-6">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 p-1.5">
                <img src="{{ asset('img/logoufps.png') }}" alt="UFPSO" class="h-8 w-8 rounded-lg object-contain" />
            </span>
            <span>
                <span class="block text-xs uppercase tracking-[0.3em] text-white/60">SIPROC</span>
                <span class="block text-base font-semibold">Producción Científica</span>
            </span>
        </a>
        @if ($mobile)
            <button type="button" @click="open = false" aria-label="Cerrar menú"
                class="rounded-lg border border-white/20 p-2 text-white/80 hover:text-white">
                <x-icon name="x-mark" />
            </button>
        @endif
    </div>

    {{-- Enlaces --}}
    <nav class="flex-1 space-y-6 overflow-y-auto px-3 pb-6" aria-label="Menú principal"
        x-data="{ tipologia: $persist(true).as('siproc-nav-tipologia'), generacion: $persist(true).as('siproc-nav-generacion') }">
        @foreach ($sections as $section)
            <div>
                @if (isset($section['collapsible']))
                    <button type="button" @click="{{ $section['collapsible'] }} = ! {{ $section['collapsible'] }}"
                        :aria-expanded="{{ $section['collapsible'] }}"
                        class="{{ $sectionTitle }} flex w-full items-center justify-between hover:text-white">
                        <span>{{ $section['title'] }}</span>
                        <x-icon name="chevron-down" class="h-4 w-4 transition"
                            x-bind:class="{{ $section['collapsible'] }} ? 'rotate-180' : ''" />
                    </button>
                @else
                    <p class="{{ $sectionTitle }}">{{ $section['title'] }}</p>
                @endif

                <ul class="mt-2 space-y-1"
                    @isset($section['collapsible']) x-show="{{ $section['collapsible'] }}" x-collapse @endisset>
                    @foreach ($section['items'] as $item)
                        <li>
                            @isset($item['children'])
                                @php $groupActive = collect($item['children'])->contains(fn ($child) => $isActive($child)); @endphp
                                <button type="button" @click="{{ $item['group'] }} = ! {{ $item['group'] }}"
                                    :aria-expanded="{{ $item['group'] }}"
                                    class="{{ $link }} w-full text-left {{ $groupActive ? 'text-white' : $inactive }}">
                                    <x-icon :name="$item['icon']" />
                                    <span class="flex-1">{{ $item['label'] }}</span>
                                    <x-icon name="chevron-down" class="h-4 w-4 text-white/60 transition"
                                        x-bind:class="{{ $item['group'] }} ? 'rotate-180' : ''" />
                                </button>
                                <ul class="mt-1 space-y-0.5" x-show="{{ $item['group'] }}" x-collapse>
                                    @foreach ($item['children'] as $child)
                                        <li>
                                            <a href="{{ route($child['route']) }}" wire:navigate
                                                @if ($isActive($child)) aria-current="page" @endif
                                                class="{{ $subLink }} {{ $isActive($child) ? $active : $inactive }}">
                                                {{ $child['label'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <a href="{{ route($item['route']) }}" wire:navigate
                                    @if ($isActive($item)) aria-current="page" @endif
                                    class="{{ $link }} {{ $isActive($item) ? $active : $inactive }}">
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

    {{-- Usuario --}}
    <div class="border-t border-white/15 px-5 py-4">
        <div class="truncate text-sm font-semibold" x-data="{{ json_encode(['name' => auth()->user()->name]) }}"
            x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
        <div class="truncate text-xs text-white/70">{{ auth()->user()->email }}</div>
        <div class="mt-3 flex gap-2">
            <a href="{{ route('profile') }}" wire:navigate
                class="inline-flex items-center gap-1.5 rounded-lg border border-white/30 px-3 py-2 text-xs font-semibold text-white/80 hover:text-white">
                <x-icon name="user-circle" class="h-4 w-4" />
                Perfil
            </a>
            <button type="button" wire:click="logout"
                class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-3 py-2 text-xs font-semibold text-white hover:bg-white/25">
                <x-icon name="logout" class="h-4 w-4" />
                Salir
            </button>
        </div>
    </div>
</div>
