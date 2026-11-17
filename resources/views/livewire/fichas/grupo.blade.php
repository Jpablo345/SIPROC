<div>
    <a href="{{ route('estructura.grupos') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-siproc-red-dark dark:text-gray-400 dark:hover:text-red-300">
        <x-icon name="arrow-left" class="h-4 w-4" /> Grupos de investigación
    </a>

    {{-- Encabezado --}}
    <div class="mt-4">
        <p class="page-eyebrow">Grupo de investigación</p>
        <h2 class="page-title">{{ $group->group_name }}</h2>
        <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-slate-500 dark:text-gray-400">
            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                Código Minciencias {{ $group->cod_minciencias }}
            </span>
            @if ($group->group_classification)
                <span class="badge">{{ $group->group_classification }}</span>
            @endif
            <span class="inline-flex items-center gap-1.5"><x-icon name="building-library" class="h-4 w-4" /> {{ $group->institution?->institution_name ?? 'Sin institución' }}</span>
        </div>
    </div>

    {{-- Indicadores --}}
    <section class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4" aria-label="Indicadores">
        @foreach ($indicadores as $indicador)
            <div class="card p-5">
                <p class="text-sm font-semibold text-slate-500 dark:text-gray-400">{{ $indicador['titulo'] }}</p>
                <p class="mt-2 font-display text-3xl font-bold">{{ $indicador['valor'] }}</p>
            </div>
        @endforeach
    </section>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-2">
            <h3 class="section-title">Producción por año</h3>
            <div class="mt-4">
                @if ($graficaAnios)
                    <x-chart :config="$graficaAnios" label="Gráfica de barras con las publicaciones del grupo por año" />
                @else
                    <x-empty-state icon="chart-bar" message="El grupo todavía no tiene publicaciones con año registrado." class="h-64" />
                @endif
            </div>
        </section>

        <section class="card">
            <h3 class="section-title">Por tipo de producto</h3>
            <div class="mt-4">
                @if ($graficaTipos)
                    <x-chart :config="$graficaTipos" label="Gráfica circular con las publicaciones del grupo por tipo" />
                @else
                    <x-empty-state message="Sin publicaciones." class="h-64" />
                @endif
            </div>
        </section>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <section class="card">
            <h3 class="section-title">Integrantes</h3>
            @if ($group->researchers->isEmpty())
                <x-empty-state icon="users" message="El grupo no tiene investigadores registrados." class="py-6" />
            @else
                <ul class="mt-4 space-y-3">
                    @foreach ($group->researchers as $investigador)
                        <li class="flex items-center gap-3">
                            <x-avatar :name="$investigador->full_name" size="sm" />
                            <a href="{{ route('investigadores.show', $investigador->researcher_id) }}" wire:navigate
                                class="min-w-0 flex-1 truncate text-sm font-semibold hover:text-siproc-red-dark dark:hover:text-red-300">{{ $investigador->full_name }}</a>
                            <span class="text-xs text-slate-500 dark:text-gray-400">{{ $investigador->publications_count }} pub.</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="card xl:col-span-2">
            <h3 class="section-title">Publicaciones recientes</h3>
            @if ($recientes->isEmpty())
                <x-empty-state icon="document-text" message="El grupo todavía no tiene publicaciones." />
            @else
                <ul class="mt-4 divide-y divide-siproc-line dark:divide-gray-800">
                    @foreach ($recientes as $p)
                        <li class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <a href="{{ route('publicaciones.show', $p->publication_id) }}" wire:navigate
                                class="min-w-0 font-semibold hover:text-siproc-red-dark dark:hover:text-red-300">{{ $p->title }}</a>
                            <div class="flex shrink-0 items-center gap-2">
                                <x-type-badge :name="$p->type?->type_name" />
                                <span class="w-10 text-right text-sm text-slate-500 dark:text-gray-400">{{ $p->publication_year ?? '—' }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</div>
