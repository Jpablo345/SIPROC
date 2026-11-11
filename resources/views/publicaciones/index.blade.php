<div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="page-eyebrow">Publicaciones</p>
            <h2 class="page-title">Registro general</h2>
        </div>

        <div class="flex items-center gap-2">
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" type="button" class="btn btn-dark" :aria-expanded="open"
                    wire:loading.attr="disabled" wire:target="exportExcel,exportPdf">
                    <x-icon name="arrow-down-tray" class="h-4 w-4" wire:loading.remove wire:target="exportExcel,exportPdf" />
                    <x-spinner wire:loading wire:target="exportExcel,exportPdf" />
                    <span wire:loading.remove wire:target="exportExcel,exportPdf">Exportar</span>
                    <span wire:loading wire:target="exportExcel,exportPdf">Generando…</span>
                    <x-icon name="chevron-down" class="h-3 w-3 transition-transform" x-bind:class="{ 'rotate-180': open }" />
                </button>

                <div x-show="open" x-cloak @click.away="open = false" x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
                    class="absolute right-0 z-30 mt-2 w-56 origin-top-right rounded-xl border border-gray-100 bg-white p-1 shadow-lg">
                    <p class="px-3 pb-1 pt-2 text-[11px] text-slate-500">Se exportan los resultados con los filtros actuales. El PDF admite hasta {{ \App\Livewire\Publicaciones\IndexPublicaciones::PDF_MAX_ROWS }} publicaciones.</p>
                    <button type="button" wire:click="exportExcel" @click="open = false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-semibold text-gray-700 hover:bg-siproc-blush hover:text-siproc-red">
                        <x-icon name="table" class="h-4 w-4" />
                        Exportar a Excel
                    </button>
                    <button type="button" wire:click="exportPdf" @click="open = false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-semibold text-gray-700 hover:bg-siproc-blush hover:text-siproc-red">
                        <x-icon name="document-arrow-down" class="h-4 w-4" />
                        Exportar a PDF
                    </button>
                </div>
            </div>

            <button type="button" wire:click="abrirFormulario" class="btn btn-primary" wire:loading.attr="disabled" wire:target="abrirFormulario">
                <x-icon name="plus" class="h-4 w-4" wire:loading.remove wire:target="abrirFormulario" />
                <x-spinner wire:loading wire:target="abrirFormulario" />
                Registrar publicación
            </button>
        </div>
    </div>

    {{-- Formulario en panel lateral. No se cierra con clic afuera para no perder lo escrito. --}}
    @if ($showForm)
        <div class="fixed inset-0 z-40 flex justify-end" role="dialog" aria-modal="true" aria-labelledby="publication-form-title"
            x-data="{
                init() { document.documentElement.classList.add('overflow-hidden') },
                destroy() { document.documentElement.classList.remove('overflow-hidden') },
            }">
            <div class="absolute inset-0 bg-siproc-ink/40"></div>
            <div class="relative flex h-full w-full max-w-3xl flex-col bg-siproc-cream shadow-2xl">
                <div class="flex items-center justify-between border-b border-siproc-line bg-white/80 px-6 py-4">
                    <div>
                        <p class="eyebrow">Publicaciones</p>
                        <h3 id="publication-form-title" class="section-title">
                            {{ $editingId ? 'Editar publicación' : 'Nueva publicación' }}
                        </h3>
                    </div>
                    <button type="button" wire:click="cerrarFormulario" class="btn btn-outline" aria-label="Cerrar formulario">
                        <x-icon name="x-mark" class="h-4 w-4" />
                        Cerrar
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-6">
                    <livewire:publicaciones.formulario-publicacion :publication-id="$editingId" :key="'publication-form-' . ($editingId ?? 'nuevo')" />
                </div>
            </div>
        </div>
    @endif

    <div class="mt-6 card">
        @php $total = $this->publications->total(); @endphp

        <div class="flex flex-col gap-4">
            {{-- Cabecera con título y contador --}}
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="section-title">Publicaciones registradas</h3>
                    <p class="eyebrow">{{ $total }} {{ $total === 1 ? 'registro' : 'registros' }}</p>
                </div>

                @if ($this->hasActiveFilters)
                    <button type="button" wire:click="clearFilters" class="btn btn-sm btn-outline self-start sm:self-auto">
                        <x-icon name="x-mark" class="h-3.5 w-3.5" />
                        Limpiar filtros
                    </button>
                @endif
            </div>

            {{-- Filtros --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-6">
                <div class="relative sm:col-span-2">
                    <label for="filter-search" class="sr-only">Buscar</label>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <x-text-input id="filter-search" wire:model.live.debounce.300ms="search" type="search"
                        placeholder="Buscar por título, autor, tipo o año" class="w-full pl-10" />
                </div>

                <div>
                    <label for="filter-year" class="sr-only">Año</label>
                    <select id="filter-year" wire:model.live="filterYear" class="field w-full cursor-pointer text-sm">
                        <option value="">Todos los años</option>
                        @foreach ($this->years as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-type" class="sr-only">Tipo</label>
                    <select id="filter-type" wire:model.live="filterType" class="field w-full cursor-pointer text-sm">
                        <option value="">Todos los tipos</option>
                        @foreach ($this->types as $type)
                            <option value="{{ $type->type_id }}">{{ $type->type_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-group" class="sr-only">Grupo</label>
                    <select id="filter-group" wire:model.live="filterGroup" class="field w-full cursor-pointer text-sm">
                        <option value="">Todos los grupos</option>
                        @foreach ($this->groups as $group)
                            <option value="{{ $group->cod_minciencias }}">{{ $group->group_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="relative">
                    <label for="filter-author" class="sr-only">Autor</label>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <x-text-input id="filter-author" wire:model.live.debounce.300ms="filterAuthor" type="text"
                        placeholder="Autor" class="w-full pl-10" />
                </div>
            </div>

            {{-- Filtros activos --}}
            @if ($this->hasActiveFilters)
                <div class="flex flex-wrap gap-2 pt-1 text-xs text-siproc-red-dark">
                    <span class="font-semibold">Filtros activos:</span>
                    @if ($search)
                        <span class="rounded-full border border-siproc-line bg-siproc-blush px-3 py-1">Buscar: "{{ $search }}"</span>
                    @endif
                    @if ($filterYear)
                        <span class="rounded-full border border-siproc-line bg-siproc-blush px-3 py-1">Año: {{ $filterYear }}</span>
                    @endif
                    @if ($filterType)
                        @php $typeName = $this->types->firstWhere('type_id', (int) $filterType)?->type_name ?? $filterType; @endphp
                        <span class="rounded-full border border-siproc-line bg-siproc-blush px-3 py-1">Tipo: {{ $typeName }}</span>
                    @endif
                    @if ($filterGroup)
                        @php $groupName = $this->groups->firstWhere('cod_minciencias', $filterGroup)?->group_name ?? $filterGroup; @endphp
                        <span class="rounded-full border border-siproc-line bg-siproc-blush px-3 py-1">Grupo: {{ $groupName }}</span>
                    @endif
                    @if ($filterAuthor)
                        <span class="rounded-full border border-siproc-line bg-siproc-blush px-3 py-1">Autor: "{{ $filterAuthor }}"</span>
                    @endif
                </div>
            @endif
        </div>

        {{-- Tabla: columnas principales a la vista y el resto en "Detalles" --}}
        <div class="mt-6 table-wrap" wire:loading.delay.class="opacity-50"
            wire:target="search,filterYear,filterType,filterGroup,filterAuthor,clearFilters,gotoPage,setPage,nextPage,previousPage,eliminar">
            <table class="data-table">
                <thead class="table-head">
                    <tr>
                        <th class="px-4 py-3">Publicación</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Año</th>
                        <th class="hidden px-4 py-3 xl:table-cell">Grupo</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>

                @forelse ($this->publications as $publication)
                    @php
                        $authors = $publication->researchers->sortBy(fn ($r) => $r->pivot->author_order ?? 999);
                        $authorNames = $authors->map(fn ($r) => trim(implode(' ', array_filter([
                            $r->name_1, $r->name_2, $r->last_name_1, $r->last_name_2,
                        ]))))->filter()->values();
                        $groups = $publication->researchers->pluck('researchGroup.group_name')->filter()->unique()->implode(', ') ?: '—';
                        $institutions = $publication->researchers->pluck('researchGroup.institution.institution_name')->filter()->unique()->implode(', ') ?: '—';
                        $article = $publication->article;
                        $book = $publication->book;

                        $details = array_filter([
                            'Ámbito' => $publication->scope,
                            'País' => $publication->country_publication,
                            'Grupo' => $groups !== '—' ? $groups : null,
                            'Institución' => $institutions !== '—' ? $institutions : null,
                            'Revista' => $article?->journal?->journal_name,
                            'ISSN' => $article?->journal_issn,
                            'Categoría de la revista' => $article?->journal?->category,
                            'DOI' => $article?->doi,
                            'ISBN' => $book?->book_isbn,
                            'Tipo de libro' => $book?->bookType?->type_name,
                            'Editorial' => $book?->editorial,
                            'Medio de difusión' => $book?->means_of_dissemination,
                        ], fn ($value) => filled($value));
                    @endphp

                    <tbody class="bg-white" wire:key="publication-{{ $publication->publication_id }}" x-data="{ open: false }">
                        <tr class="align-top hover:bg-siproc-blush">
                            <td class="min-w-[16rem] px-4 py-3">
                                <p class="font-semibold text-siproc-ink">{{ $publication->title }}</p>
                                <p class="mt-1 text-xs text-slate-600">
                                    @if ($authorNames->isEmpty())
                                        Sin autores registrados
                                    @else
                                        {{ $authorNames->take(3)->implode(', ') }}
                                        @if ($authorNames->count() > 3)
                                            <span class="text-slate-400">y {{ $authorNames->count() - 3 }} más</span>
                                        @endif
                                    @endif
                                </p>
                                @if ($publication->scope || $publication->country_publication)
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        {{ collect([$publication->scope, $publication->country_publication])->filter()->implode(' · ') }}
                                    </p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge">{{ $publication->type?->type_name ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $publication->publication_year ?? '—' }}</td>
                            <td class="hidden min-w-[10rem] px-4 py-3 text-slate-600 xl:table-cell">{{ $groups }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex flex-col items-end gap-1.5 whitespace-nowrap xl:flex-row xl:justify-end xl:gap-2">
                                    <button type="button" class="btn btn-sm btn-outline" @click="open = ! open"
                                        :aria-expanded="open" aria-controls="publication-details-{{ $publication->publication_id }}">
                                        Detalles
                                        <x-icon name="chevron-down" class="h-3 w-3 transition-transform" x-bind:class="{ 'rotate-180': open }" />
                                    </button>
                                    <button type="button" wire:click="editar({{ $publication->publication_id }})" class="btn btn-sm btn-outline"
                                        wire:loading.attr="disabled" wire:target="editar({{ $publication->publication_id }})">
                                        <x-spinner wire:loading wire:target="editar({{ $publication->publication_id }})" />
                                        Editar
                                    </button>
                                    @can('delete', $publication)
                                        <button type="button"
                                            x-on:click="$dispatch('confirmar', { message: '¿Seguro que deseas eliminar esta publicación?', action: () => $wire.eliminar({{ $publication->publication_id }}) })"
                                            class="btn btn-sm btn-danger">
                                            Eliminar
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        <tr x-show="open" x-cloak id="publication-details-{{ $publication->publication_id }}" class="bg-siproc-blush/60">
                            <td colspan="5" class="px-4 py-4">
                                <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                                    <div class="sm:col-span-2 lg:col-span-3">
                                        <dt class="form-label">Autores</dt>
                                        <dd class="mt-1 text-slate-700">{{ $authorNames->implode(', ') ?: '—' }}</dd>
                                    </div>
                                    @foreach ($details as $label => $value)
                                        <div>
                                            <dt class="form-label">{{ $label }}</dt>
                                            <dd class="mt-1 break-words text-slate-700">{{ $value }}</dd>
                                        </div>
                                    @endforeach
                                    @if ($publication->url)
                                        <div>
                                            <dt class="form-label">Enlace</dt>
                                            <dd class="mt-1">
                                                <a href="{{ $publication->url }}" target="_blank" rel="noopener noreferrer"
                                                    class="break-all text-siproc-red underline hover:text-siproc-red-dark">{{ $publication->url }}</a>
                                            </dd>
                                        </div>
                                    @endif
                                    @if ($publication->extra_details !== [])
                                        <div class="sm:col-span-2 lg:col-span-3">
                                            <dt class="form-label">Campos del tipo</dt>
                                            <dd class="mt-1"><x-extra-details :publication="$publication" class="text-sm" /></dd>
                                        </div>
                                    @endif
                                </dl>
                            </td>
                        </tr>
                    </tbody>
                @empty
                    <tbody class="bg-white">
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center">
                                @if ($this->hasActiveFilters)
                                    <p class="text-sm font-semibold text-siproc-ink">Ninguna publicación coincide con los filtros.</p>
                                    <p class="mt-1 text-sm text-slate-500">Prueba con otros términos o quita algún filtro.</p>
                                    <button type="button" wire:click="clearFilters" class="btn btn-outline mt-4">Limpiar filtros</button>
                                @else
                                    <p class="text-sm font-semibold text-siproc-ink">Todavía no hay publicaciones registradas.</p>
                                    <p class="mt-1 text-sm text-slate-500">Las publicaciones que registres aparecerán aquí.</p>
                                    <button type="button" wire:click="abrirFormulario" class="btn btn-primary mt-4">
                                        <x-icon name="plus" class="h-4 w-4" />
                                        Registrar la primera
                                    </button>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>

        <div class="mt-4">
            {{ $this->publications->links() }}
        </div>
    </div>
</div>
