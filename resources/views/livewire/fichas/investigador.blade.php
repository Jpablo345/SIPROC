@php $r = $researcher; @endphp

<div>
    <a href="{{ route('investigadores') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-siproc-red-dark dark:text-gray-400 dark:hover:text-red-300">
        <x-icon name="arrow-left" class="h-4 w-4" /> Investigadores
    </a>

    {{-- Encabezado --}}
    <div class="mt-4 flex flex-col gap-5 sm:flex-row sm:items-center">
        <x-avatar :name="$r->full_name" size="xl" />
        <div class="min-w-0">
            <p class="page-eyebrow">Investigador</p>
            <h2 class="page-title">{{ $r->full_name }}</h2>
            <div class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-1 text-sm text-slate-500 dark:text-gray-400">
                @if ($r->researchGroup)
                    <a href="{{ route('estructura.grupos.show', $r->researchGroup->cod_minciencias) }}" wire:navigate
                        class="inline-flex items-center gap-1.5 font-semibold text-siproc-red-dark hover:underline dark:text-red-300">
                        <x-icon name="user-group" class="h-4 w-4" /> {{ $r->researchGroup->group_name }}
                    </a>
                @else
                    <span>Sin grupo de investigación</span>
                @endif
                @if ($r->email)
                    <a href="mailto:{{ $r->email }}" class="inline-flex items-center gap-1.5 hover:underline"><x-icon name="envelope" class="h-4 w-4" /> {{ $r->email }}</a>
                @endif
                @if ($r->phone)
                    <span class="inline-flex items-center gap-1.5"><x-icon name="phone" class="h-4 w-4" /> {{ $r->phone }}</span>
                @endif
            </div>
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
        <div class="space-y-6 xl:col-span-2">
            <section class="card">
                <h3 class="section-title">Producción por año</h3>
                <div class="mt-4">
                    @if ($grafica)
                        <x-chart :config="$grafica" label="Gráfica de barras con las publicaciones del investigador por año" height="h-56" />
                    @else
                        <x-empty-state icon="chart-bar" message="Todavía no tiene publicaciones con año registrado." class="h-56" />
                    @endif
                </div>
            </section>

            <section class="card">
                <h3 class="section-title">Publicaciones</h3>
                @if ($publicaciones->isEmpty())
                    <x-empty-state icon="document-text" message="Todavía no tiene publicaciones registradas." />
                @else
                    <ul class="mt-4 divide-y divide-siproc-line dark:divide-gray-800">
                        @foreach ($publicaciones as $p)
                            <li class="flex flex-col gap-2 py-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <a href="{{ route('publicaciones.show', $p->publication_id) }}" wire:navigate
                                        class="font-semibold hover:text-siproc-red-dark dark:hover:text-red-300">{{ $p->title }}</a>
                                    @if ($p->article?->journal)
                                        <p class="mt-0.5 flex items-center gap-2 text-xs text-slate-500 dark:text-gray-400">
                                            {{ $p->article->journal->journal_name }} <x-quartile-badge :category="$p->article->journal->category" />
                                        </p>
                                    @endif
                                </div>
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

        <div class="space-y-6">
            <section class="card">
                <h3 class="section-title">Coautores frecuentes</h3>
                @if ($coautores->isEmpty())
                    <x-empty-state icon="users" message="Aún no comparte publicaciones con otros investigadores." class="py-6" />
                @else
                    <ul class="mt-4 space-y-3">
                        @foreach ($coautores as $c)
                            <li class="flex items-center gap-3">
                                <x-avatar :name="$c['investigador']->full_name" size="sm" />
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('investigadores.show', $c['investigador']->researcher_id) }}" wire:navigate
                                        class="block truncate text-sm font-semibold hover:text-siproc-red-dark dark:hover:text-red-300">{{ $c['investigador']->full_name }}</a>
                                    <p class="truncate text-xs text-slate-500 dark:text-gray-400">{{ $c['investigador']->researchGroup?->group_name ?? 'Sin grupo' }}</p>
                                </div>
                                <span class="badge" title="Publicaciones en común">{{ $c['total'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="card">
                <h3 class="section-title">Eventos</h3>
                @if ($r->events->isEmpty())
                    <x-empty-state icon="megaphone" message="No tiene participación en eventos registrada." class="py-6" />
                @else
                    <ul class="mt-4 space-y-3 text-sm">
                        @foreach ($r->events as $evento)
                            <li>
                                <p class="font-semibold">{{ $evento->event_name }}</p>
                                <p class="text-xs text-slate-500 dark:text-gray-400">
                                    {{ collect([$evento->pivot->participation_type, $evento->event_year])->filter()->implode(' · ') ?: '—' }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</div>
