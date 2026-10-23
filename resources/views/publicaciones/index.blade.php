<div>
   <div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-[#9c1c1c]">Publicaciones</p>
            <h2 class="text-2xl font-semibold text-[#2b2323]">Registro general</h2>
        </div>
        
        <div class="flex items-center gap-2">
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" type="button"
                    class="inline-flex items-center gap-2 rounded-full bg-[#2b2323] px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-[#1f1a1a]">
                    <span>Exportar</span>
                    <svg class="h-3 w-3 transition-transform" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                
                <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100" 
                    x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
                    class="absolute right-0 mt-2 w-44 origin-top-right rounded-xl border border-gray-100 bg-white p-1 shadow-lg z-50">
                    <button wire:click="exportExcel" @click="open = false" class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs font-semibold text-gray-700 hover:bg-[#fff7f7] hover:text-[#9c1c1c] rounded-lg">
                        <span>📊</span> Exportar a Excel
                    </button>
                    <button wire:click="exportPdf" @click="open = false" class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs font-semibold text-gray-700 hover:bg-[#fff7f7] hover:text-[#9c1c1c] rounded-lg">
                        <span>📕</span> Exportar a PDF
                    </button>
                </div>
            </div>

            <button wire:click="abrirFormulario"
                class="rounded-full bg-[#9c1c1c] px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-[#7a1515]">
                + Registrar publicacion
            </button>
        </div>
    </div>

    <div class="mt-6 space-y-6">
        @if ($showForm)
            <div class="rounded-2xl border border-white/60 bg-white/80 p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-[#2b2323]">
                        {{ $editingId ? 'Editar publicacion' : 'Nueva publicacion' }}
                    </h3>
                    <button type="button" wire:click="cerrarFormulario" class="text-sm text-[#9c1c1c]">
                        Cerrar
                    </button>
                </div>
                <div class="mt-6">
                    <livewire:publicaciones.formulario-publicacion :publication-id="$editingId" :key="'publication-form-' . ($editingId ?? 'nuevo')" />
                </div>
            </div>
        @endif

        <div class="rounded-2xl border border-white/60 bg-white/80 p-6 shadow-sm">
            <div class="flex flex-col gap-4">
                <!-- Cabecera con título y contador -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-[#2b2323]">Publicaciones registradas</h3>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#9c1c1c]">
                            {{ $this->publications->total() }} registros
                        </p>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        @if($this->hasActiveFilters)
                            <button wire:click="clearFilters" 
                                class="text-xs text-[#9c1c1c] hover:text-[#7a1515] hover:underline">
                                Limpiar filtros
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Filtros desplegables -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <!-- Búsqueda por texto -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <x-text-input wire:model.live="search" type="search" 
                            placeholder="Buscar..."
                            class="w-full pl-10 rounded-lg border-gray-200 focus:border-[#9c1c1c] focus:ring-[#9c1c1c]" />
                    </div>

                    <!-- Filtro por año -->
                    <div class="relative">
                        <select wire:model.live="filterYear" 
                            class="w-full rounded-lg border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-700 focus:border-[#9c1c1c] focus:ring-[#9c1c1c] appearance-none cursor-pointer hover:border-gray-300 transition-colors">
                            <option value="">Año</option>
                            @foreach($this->years as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Filtro por tipo -->
                    <div class="relative">
                        <select wire:model.live="filterType" 
                            class="w-full rounded-lg border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-700 focus:border-[#9c1c1c] focus:ring-[#9c1c1c] appearance-none cursor-pointer hover:border-gray-300 transition-colors">
                            <option value="">Tipo</option>
                            @foreach($this->types as $type)
                                <option value="{{ $type->publication_type_id }}">{{ $type->type_name }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Filtro por grupo -->
                    <div class="relative">
                        <select wire:model.live="filterGroup" 
                            class="w-full rounded-lg border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-700 focus:border-[#9c1c1c] focus:ring-[#9c1c1c] appearance-none cursor-pointer hover:border-gray-300 transition-colors">
                            <option value="">Grupo</option>
                            @foreach($this->groups as $group)
                                <option value="{{ $group->research_group_id }}">{{ $group->group_name }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Filtro por autor -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <x-text-input wire:model.live="filterAuthor" type="text" 
                            placeholder="Autor..."
                            class="w-full pl-10 rounded-lg border-gray-200 focus:border-[#9c1c1c] focus:ring-[#9c1c1c]" />
                    </div>
                </div>

                <!-- Indicador de filtros activos -->
                @if($this->hasActiveFilters)
                    <div class="flex flex-wrap gap-2 text-xs text-[#7a1515] pt-1">
                        <span class="font-semibold">Filtros activos:</span>
                        @if($search)
                            <span class="rounded-full bg-[#fff7f7] px-3 py-1 border border-[#f0dede]">
                                Buscar: "{{ $search }}"
                            </span>
                        @endif
                        @if($filterYear)
                            <span class="rounded-full bg-[#fff7f7] px-3 py-1 border border-[#f0dede]">
                                Año: {{ $filterYear }}
                            </span>
                        @endif
                        @if($filterType)
                            @php
                                $typeName = $this->types->firstWhere('publication_type_id', (int)$filterType)?->type_name ?? $filterType;
                            @endphp
                            <span class="rounded-full bg-[#fff7f7] px-3 py-1 border border-[#f0dede]">
                                Tipo: {{ $typeName }}
                            </span>
                        @endif
                        @if($filterGroup)
                            @php
                                $groupName = $this->groups->firstWhere('research_group_id', (int)$filterGroup)?->group_name ?? $filterGroup;
                            @endphp
                            <span class="rounded-full bg-[#fff7f7] px-3 py-1 border border-[#f0dede]">
                                Grupo: {{ $groupName }}
                            </span>
                        @endif
                        @if($filterAuthor)
                            <span class="rounded-full bg-[#fff7f7] px-3 py-1 border border-[#f0dede]">
                                Autor: "{{ $filterAuthor }}"
                            </span>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Tabla -->
            <div class="mt-6 overflow-x-auto rounded-2xl border border-[#f0dede]">
                <table class="min-w-full divide-y divide-[#f0dede] text-sm whitespace-nowrap">
                    <thead class="bg-[#fff7f7] text-left text-xs font-semibold uppercase tracking-[0.2em] text-[#7a1515]">
                        <tr>
                            <th class="px-4 py-3">Titulo</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Año</th>
                            <th class="px-4 py-3">Ámbito</th>
                            <th class="px-4 py-3">País</th>
                            <th class="px-4 py-3">Autores</th>
                            <th class="px-4 py-3">Grupo</th>
                            <th class="px-4 py-3">Institución</th>
                            <th class="px-4 py-3">URL</th>
                            <th class="px-4 py-3">Revista</th>
                            <th class="px-4 py-3">ISSN</th>
                            <th class="px-4 py-3">Cat. Revista</th>
                            <th class="px-4 py-3">DOI</th>
                            <th class="px-4 py-3">ISBN</th>
                            <th class="px-4 py-3">Tipo libro</th>
                            <th class="px-4 py-3">Editorial</th>
                            <th class="px-4 py-3">Medio difusión</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f0dede] bg-white">
                        @forelse ($this->publications as $publication)
                            @php
                                $authors = $publication->researchers->sortBy(fn($r) => $r->pivot->author_order ?? 999);
                                $authorNames = $authors->map(fn($r) => trim(implode(' ', array_filter([
                                    $r->name_1, $r->name_2, $r->last_name_1, $r->last_name_2,
                                ]))))->filter()->implode(', ') ?: '—';
                                $groups = $publication->researchers->pluck('researchGroup.group_name')->filter()->unique()->implode(', ') ?: '—';
                                $institutions = $publication->researchers->pluck('researchGroup.institution.institution_name')->filter()->unique()->implode(', ') ?: '—';
                                $isArticle = $publication->article !== null;
                                $isBook    = $publication->book !== null;
                            @endphp
                            <tr wire:key="publication-{{ $publication->publication_id }}" class="hover:bg-[#fff7f7]">
                                <td class="px-4 py-3 font-semibold text-[#2b2323]">{{ $publication->title }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $publication->type?->type_name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $publication->publication_year ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $publication->scope ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $publication->country_publication ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $authorNames }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $groups }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $institutions }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    @if ($publication->url)
                                        <a href="{{ $publication->url }}" target="_blank" class="text-blue-600 hover:underline">Ver enlace</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $isArticle ? ($publication->article->journal?->journal_name ?? '—') : '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $isArticle ? ($publication->article->journal_issn ?? '—') : '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $isArticle ? ($publication->article->journal?->category ?? '—') : '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $isArticle ? ($publication->article->doi ?? '—') : '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $isBook ? ($publication->book->book_isbn ?? '—') : '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $isBook ? ($publication->book->bookType?->type_name ?? '—') : '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $isBook ? ($publication->book->editorial ?? '—') : '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $isBook ? ($publication->book->means_of_dissemination ?? '—') : '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        
                                        <button type="button" wire:click="editar({{ $publication->publication_id }})"
                                            class="rounded-full border border-[#9c1c1c]/30 px-3 py-1 text-xs font-semibold text-[#7a1515] hover:bg-[#f2d3d3]">
                                            Editar
                                        </button>
                                        
                                        @can('delete', $publication)
                                        <button type="button" wire:click="eliminar({{ $publication->publication_id }})"
                                            onclick="confirm('¿Seguro que deseas eliminar esta publicación?') || event.stopImmediatePropagation()"
                                            class="rounded-full border border-[#d77a7a]/40 px-3 py-1 text-xs font-semibold text-[#9c1c1c] hover:bg-[#f9dede]">
                                            Eliminar
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="18" class="px-4 py-6 text-center text-sm text-slate-500">
                                    No hay publicaciones registradas todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $this->publications->links() }}
            </div>
        </div>
    </div>
</div>