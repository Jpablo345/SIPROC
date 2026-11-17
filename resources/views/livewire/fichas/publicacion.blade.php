@php
    $p = $publication;
    $articulo = $p->article;
    $libro = $p->book;
    $doi = $articulo?->doi;
    $doiUrl = $doi ? (str_starts_with($doi, 'http') ? $doi : 'https://doi.org/'.ltrim($doi, '/')) : null;
@endphp

<div>
    <a href="{{ route('publicaciones.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-siproc-red-dark dark:text-gray-400 dark:hover:text-red-300">
        <x-icon name="arrow-left" class="h-4 w-4" /> Publicaciones
    </a>

    {{-- Encabezado --}}
    <div class="mt-4 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <x-type-badge :name="$p->type?->type_name" />
                @if ($p->publication_year)
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        <x-icon name="calendar" class="h-3.5 w-3.5" /> {{ $p->publication_year }}
                    </span>
                @endif
                @if ($p->scope)
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $p->scope }}</span>
                @endif
            </div>
            <h2 class="page-title mt-3 max-w-4xl leading-snug">{{ $p->title }}</h2>
            <p class="mt-2 text-sm text-slate-500 dark:text-gray-400">
                {{ $autores->pluck('full_name')->implode(', ') ?: 'Sin autores registrados' }}
            </p>
        </div>

        <div class="flex shrink-0 gap-2">
            @if ($p->url)
                <a href="{{ $p->url }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline">
                    <x-icon name="external" class="h-4 w-4" /> Ver en línea
                </a>
            @endif
            @can('update', $p)
                <a href="{{ route('publicaciones.index', ['editar' => $p->publication_id]) }}" wire:navigate class="btn btn-primary">Editar</a>
            @endcan
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            {{-- Datos generales --}}
            <section class="card">
                <h3 class="section-title">Datos de la publicación</h3>
                <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div><dt class="form-label">Tipo</dt><dd class="mt-1">{{ $p->type?->type_name ?? '—' }}</dd></div>
                    <div><dt class="form-label">Año</dt><dd class="mt-1">{{ $p->publication_year ?? '—' }}</dd></div>
                    <div><dt class="form-label">Ámbito</dt><dd class="mt-1">{{ $p->scope ?? '—' }}</dd></div>
                    <div><dt class="form-label">País</dt><dd class="mt-1">{{ $p->country_publication ?? '—' }}</dd></div>
                    @if ($p->url)
                        <div class="sm:col-span-2"><dt class="form-label">Enlace</dt>
                            <dd class="mt-1"><a href="{{ $p->url }}" target="_blank" rel="noopener noreferrer" class="break-all text-siproc-red-dark underline dark:text-red-300">{{ $p->url }}</a></dd></div>
                    @endif
                </dl>
            </section>

            {{-- Artículo --}}
            @if ($articulo)
                <section class="card">
                    <h3 class="section-title">Revista</h3>
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <p class="text-base font-semibold">{{ $articulo->journal?->journal_name ?? 'Revista sin nombre' }}</p>
                        <x-quartile-badge :category="$articulo->journal?->category" />
                    </div>
                    <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div><dt class="form-label">ISSN</dt><dd class="mt-1">{{ $articulo->journal_issn ?? '—' }}</dd></div>
                        <div><dt class="form-label">Categoría</dt><dd class="mt-1">{{ $articulo->journal?->category ?: 'Sin clasificación' }}</dd></div>
                        <div class="sm:col-span-2"><dt class="form-label">DOI</dt>
                            <dd class="mt-1">
                                @if ($doiUrl)
                                    <a href="{{ $doiUrl }}" target="_blank" rel="noopener noreferrer" class="break-all text-siproc-red-dark underline dark:text-red-300">{{ $doi }}</a>
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                    </dl>
                </section>
            @endif

            {{-- Libro --}}
            @if ($libro)
                <section class="card">
                    <h3 class="section-title">Libro</h3>
                    <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div><dt class="form-label">ISBN</dt><dd class="mt-1">{{ $libro->book_isbn ?? '—' }}</dd></div>
                        <div><dt class="form-label">Tipo de libro</dt><dd class="mt-1">{{ $libro->bookType?->type_name ?? '—' }}</dd></div>
                        <div><dt class="form-label">Editorial</dt><dd class="mt-1">{{ $libro->editorial ?? '—' }}</dd></div>
                        <div><dt class="form-label">Medio de difusión</dt><dd class="mt-1">{{ $libro->means_of_dissemination ?? '—' }}</dd></div>
                    </dl>
                </section>
            @endif

            {{-- Campos del tipo configurable --}}
            @if ($p->extra_details !== [])
                <section class="card">
                    <h3 class="section-title">Campos de {{ $p->type?->type_name }}</h3>
                    <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        @foreach ($p->extra_details as $detalle)
                            <div><dt class="form-label">{{ $detalle['label'] }}</dt><dd class="mt-1 break-words">{{ $detalle['value'] }}</dd></div>
                        @endforeach
                    </dl>
                </section>
            @endif
        </div>

        <div class="space-y-6">
            {{-- Autores --}}
            <section class="card">
                <h3 class="section-title">Autores</h3>
                @if ($autores->isEmpty())
                    <x-empty-state icon="users" message="Esta publicación no tiene autores registrados." class="py-6" />
                @else
                    <ol class="mt-4 space-y-3">
                        @foreach ($autores as $autor)
                            <li class="flex items-center gap-3">
                                <x-avatar :name="$autor->full_name" size="sm" />
                                <div class="min-w-0">
                                    <a href="{{ route('investigadores.show', $autor->researcher_id) }}" wire:navigate
                                        class="block truncate font-semibold hover:text-siproc-red-dark dark:hover:text-red-300">{{ $autor->full_name }}</a>
                                    <p class="truncate text-xs text-slate-500 dark:text-gray-400">{{ $autor->researchGroup?->group_name ?? 'Sin grupo' }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>

            {{-- Grupos e instituciones --}}
            @if ($grupos->isNotEmpty())
                <section class="card">
                    <h3 class="section-title">Grupos de investigación</h3>
                    <ul class="mt-4 space-y-3 text-sm">
                        @foreach ($grupos as $grupo)
                            <li>
                                <a href="{{ route('estructura.grupos.show', $grupo->cod_minciencias) }}" wire:navigate
                                    class="font-semibold hover:text-siproc-red-dark dark:hover:text-red-300">{{ $grupo->group_name }}</a>
                                <p class="text-xs text-slate-500 dark:text-gray-400">{{ $grupo->institution?->institution_name ?? 'Sin institución' }}</p>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Registro --}}
            <section class="card text-sm">
                <h3 class="section-title">Registro</h3>
                <dl class="mt-4 space-y-3">
                    <div><dt class="form-label">Registrada por</dt><dd class="mt-1">{{ $p->creator?->name ?? '—' }}</dd></div>
                    <div><dt class="form-label">Fecha de registro</dt><dd class="mt-1">{{ $p->created_at?->translatedFormat('j \d\e F \d\e Y') ?? '—' }}</dd></div>
                    @if ($p->updated_at && $p->created_at && $p->updated_at->gt($p->created_at))
                        <div><dt class="form-label">Última modificación</dt><dd class="mt-1">{{ $p->updated_at->diffForHumans() }}</dd></div>
                    @endif
                </dl>
            </section>
        </div>
    </div>
</div>
