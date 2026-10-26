<?php

use App\Livewire\Concerns\ValidatesPublication;
use App\Models\Article;
use App\Models\Institution;
use App\Models\Journal;
use App\Models\Publication;
use App\Models\PublicationType;
use App\Models\ResearchGroup;
use App\Models\Researcher;
use App\Models\ResearcherPublication;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination, ValidatesPublication;

    public string $search = '';
    public ?int $editingId = null;

    // Campos publicación
    public string $title = '';
    public ?string $publication_year = null;
    public ?string $scope = null;
    public ?string $country_publication = null;
    public ?string $url = null;

    // Campos artículo
    public ?string $journal_issn = null;
    public ?string $doi = null;
    public string $journalSearch = '';
    public array $journalResults = [];

    // Tipo resuelto automáticamente
    public ?int $articleTypeId = null;

    // Autores
    public array $selectedAuthors = [];


    public function mount(): void
    {
        $this->resolveArticleType();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }


    public function updatedJournalSearch(): void
    {
        $this->buscarRevista();
    }

    // ─── Búsqueda de revistas ─────────────────────────────────────────────────

    public function buscarRevista(): void
    {
        $term = trim($this->journalSearch);

        if ($term === '') {
            $this->journalResults = [];
            return;
        }

        $like = '%' . $term . '%';

        $this->journalResults = Journal::query()
            ->where('journal_issn', 'ilike', $like)
            ->orWhere('journal_name', 'ilike', $like)
            ->orderBy('journal_name')
            ->limit(8)
            ->get(['journal_issn', 'journal_name'])
            ->map(fn(Journal $j) => [
                'journal_issn' => $j->journal_issn,
                'journal_name' => $j->journal_name,
            ])
            ->toArray();
    }

    public function seleccionarRevista(string $journalIssn): void
    {
        $journal = Journal::find($journalIssn);

        if (!$journal) {
            return;
        }

        $this->journal_issn = $journal->journal_issn;
        $this->journalSearch = $journal->journal_name . ' (' . $journal->journal_issn . ')';
        $this->journalResults = [];
    }



    // ─── CRUD artículo ───────────────────────────────────────────────────────

    public function edit(int $publicationId): void
    {
        $article = Article::with(['publication', 'journal'])->findOrFail($publicationId);
        $this->authorize('update', $article->publication);

        $this->editingId = $article->publication_id;
        $this->title = $article->publication?->title ?? '';
        $this->publication_year = $article->publication?->publication_year;
        $this->scope = $article->publication?->scope;
        $this->country_publication = $article->publication?->country_publication;
        $this->url = $article->publication?->url;
        $this->journal_issn = $article->journal_issn;
        $this->doi = $article->doi;


        $this->journalSearch = $article->journal
            ? $article->journal->journal_name . ' (' . $article->journal->journal_issn . ')'
            : $article->journal_issn ?? '';

        // Cargar autores existentes
        $publication = Publication::with(['researchers.researchGroup'])->find($publicationId);
        $this->selectedAuthors = $publication?->researchers
            ->sortBy(fn($r) => $r->pivot->author_order ?? 999)
            ->map(fn(Researcher $r) => [
                'researcher_id' => $r->researcher_id,
                'name' => $this->formatResearcherName($r),
                'group' => $r->researchGroup?->group_name,
            ])
            ->values()
            ->toArray() ?? [];
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        if ($this->editingId) {
            $this->authorize('update', Publication::findOrFail($this->editingId));
        } else {
            $this->authorize('create', Publication::class);
        }
        $this->validate(
            [
                ...$this->publicationRules(),
                'journal_issn' => ['required', 'string', 'exists:journal,journal_issn'],
                'doi' => ['nullable', 'string', 'max:200', Rule::unique('article', 'doi')->ignore($this->editingId, 'publication_id')],
                ...$this->authorRules(),
            ],
            [
                ...$this->publicationMessages(),
                'journal_issn.required' => 'Selecciona la revista del artículo.',
                'journal_issn.exists' => 'La revista seleccionada no existe.',
                'doi.unique' => 'Este DOI ya está registrado en otra publicación.',
                'doi.max' => 'El DOI no puede superar 200 caracteres.',
            ]
        );

        $publicationData = [
            'title' => trim($this->title),
            'publication_year' => $this->publication_year ? trim($this->publication_year) : null,
            'scope' => $this->scope,
            'country_publication' => $this->country_publication ? trim($this->country_publication) : null,
            'url' => $this->url ? trim($this->url) : null,
            'type_id' => $this->articleTypeId,
        ];

        DB::transaction(function () use ($publicationData) {
            if ($this->editingId) {
                Publication::where('publication_id', $this->editingId)->update($publicationData);
                $publicationId = $this->editingId;
            } else {
                $publication = Publication::create($publicationData);
                $publicationId = $publication->publication_id;
            }

            Article::updateOrCreate(
                ['publication_id' => $publicationId],
                [
                    'journal_issn' => $this->journal_issn,
                    'doi' => $this->doi ? trim($this->doi) : null,
                ]
            );

            // Sincronizar autores
            ResearcherPublication::where('publication_id', $publicationId)->delete();
            foreach ($this->selectedAuthors as $index => $author) {
                ResearcherPublication::create([
                    'publication_id' => $publicationId,
                    'researcher_id' => $author['researcher_id'],
                    'author_order' => $index + 1,
                ]);
            }
        });

        session()->flash('status', $this->editingId ? 'Articulo actualizado.' : 'Articulo creado.');
        $this->resetForm();
    }

    public function delete(int $publicationId): void
    {
        //Publication::where('publication_id', $publicationId)->delete();
        $publication = Publication::findOrFail($publicationId);
        $this->authorize('delete', $publication);
        $publication->delete();

        session()->flash('status', 'Articulo eliminado.');
        $this->resetPage();
    }

    // ─── Computed properties ─────────────────────────────────────────────────

   public function getArticlesProperty()
    {
        $term = trim($this->search);

        return Article::query()
            //  Un solo 'with' con la ruta completa 
            ->with(['publication.researchers.researchGroup.institution', 'journal'])
            ->when($term !== '', function ($query) use ($term) {
                $like = '%' . $term . '%';
                $query->whereHas('publication', function ($q) use ($like) {
                    $q->where('title', 'ilike', $like)
                        ->orWhereRaw('CAST(publication_year AS TEXT) LIKE ?', [$like])
                        ->orWhere('scope', 'ilike', $like)
                        ->orWhere('country_publication', 'ilike', $like);
                })
                ->orWhereHas('journal', function ($q) use ($like) {
                    $q->where('journal_name', 'ilike', $like)
                        ->orWhere('journal_issn', 'ilike', $like);
                });
            })
            ->orderByDesc('publication_id')
            ->paginate(10);
    }

    public function getGroupsProperty()
    {
        return ResearchGroup::orderBy('group_name')->get(['cod_minciencias', 'group_name']);
    }

    public function getInstitutionsProperty()
    {
        return Institution::orderBy('institution_name')->get(['institution_id', 'institution_name']);
    }

    // ─── Helpers privados ────────────────────────────────────────────────────

    private function resolveArticleType(): void
    {
        $type = PublicationType::query()
            ->whereRaw('lower(type_name) = ?', ['articulo'])
            ->first(['type_id']);

        $this->articleTypeId = $type?->type_id;
    }

    public function formatResearcherName(Researcher $researcher): string
    {
        return trim(implode(' ', array_filter([
            $researcher->name_1,
            $researcher->name_2,
            $researcher->last_name_1,
            $researcher->last_name_2,
        ])));
    }



    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->publication_year = null;
        $this->scope = null;
        $this->country_publication = null;
        $this->url = null;
        $this->journal_issn = null;
        $this->doi = null;
        $this->journalSearch = '';
        $this->journalResults = [];
        $this->selectedAuthors = [];
        $this->resetValidation();
    }


}; ?>

<div class="space-y-6">

    {{-- ═══════════════════════════════════════════════════════════
    FORMULARIO
    ════════════════════════════════════════════════════════════ --}}
    <div class="rounded-2xl border border-white/60 bg-white/80 p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-[#2b2323]">
                    {{ $editingId ? 'Editar articulo' : 'Nuevo articulo' }}
                </h3>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#9c1c1c]">
                    Datos generales y revista asociada
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($editingId)
                    <button type="button" wire:click="cancelEdit"
                        class="rounded-full border border-[#9c1c1c]/30 px-4 py-2 text-xs font-semibold text-[#7a1515] hover:bg-[#f2d3d3]">
                        Cancelar
                    </button>
                @endif
                <button type="button" wire:click="save"
                    class="rounded-full bg-[#9c1c1c] px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-[#7a1515]">
                    {{ $editingId ? 'Guardar cambios' : 'Crear articulo' }}
                </button>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-xl bg-[#f7e2d2] px-4 py-3 text-xs font-semibold text-[#7a1515]">
                {{ session('status') }}
            </div>
        @endif

        <x-validation-errors :bag="$errors" class="mt-4" />

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-input-label for="title" :value="'Titulo'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="title" wire:model.defer="title" type="text" class="mt-2 block w-full" />
            </div>
            <div>
                <x-input-label for="publication_year" :value="'Ano'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="publication_year" wire:model.defer="publication_year" type="text"
                    class="mt-2 block w-full" />
            </div>
            <div>
                <x-input-label for="scope" :value="'Ambito'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <select id="scope" wire:model.defer="scope"
                    class="mt-2 block w-full rounded-md border-red-200 bg-white text-zinc-900 shadow-sm transition-colors focus:border-red-600 focus:ring-red-600">
                    <option value="">Sin definir</option>
                    <option value="Nacional">Nacional</option>
                    <option value="Internacional">Internacional</option>
                </select>
            </div>
           <div>
                <x-input-label for="country_publication" :value="'Pais'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />

                @php
                    //Llama al archivo config/paises.php
                    $paises = config('paises');
                    if ($paises)
                        ksort($paises); // Ordena alfabéticamente
                @endphp

                <x-text-input id="country_publication" list="lista-paises" wire:model="country_publication" type="text"
                    class="mt-2 block w-full" placeholder="Escribe para buscar un país..." autocomplete="off" />

                <datalist id="lista-paises">
                    @if($paises)
                        @foreach($paises as $nombre => $codigo)
                            <option value="{{ $nombre }}"></option>
                        @endforeach
                    @endif
                </datalist>

                <x-input-error :messages="$errors->get('country_publication')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="url" :value="'URL'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="url" wire:model.defer="url" type="url" class="mt-2 block w-full"
                    placeholder="https://" />
                <x-input-error :messages="$errors->get('url')" class="mt-2" />
            </div>

            {{-- Búsqueda de revista con autocomplete --}}
            <div class="md:col-span-2">
                <x-input-label for="journal_search" :value="'Revista (ISSN o nombre)'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="journal_search" wire:model.live="journalSearch" type="text" class="mt-2 block w-full"
                    placeholder="Buscar por nombre o ISSN..." />

                @if ($journalResults)
                    <div class="mt-3 space-y-1 rounded-xl border border-[#f0dede] bg-white p-3">
                        @foreach ($journalResults as $journal)
                            <button type="button" wire:click="seleccionarRevista('{{ $journal['journal_issn'] }}')"
                                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-[#fff7f7]">
                                <span>{{ $journal['journal_name'] }}</span>
                                <span class="text-xs text-slate-400">{{ $journal['journal_issn'] }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="md:col-span-2">
                <x-input-label for="doi" :value="'DOI'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="doi" wire:model.defer="doi" type="text" class="mt-2 block w-full"
                    placeholder="" />
            </div>
        </div>
    </div>

    <livewire:publicaciones.autores-selector wire:model="selectedAuthors" />

    {{-- ═══════════════════════════════════════════════════════════
    TABLA
    ════════════════════════════════════════════════════════════ --}}
    <div class="rounded-2xl border border-white/60 bg-white/80 p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-[#2b2323]">Articulos registrados</h3>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#9c1c1c]">
                    {{ $this->articles->total() }} registros
                </p>
            </div>
            <div class="w-full sm:w-72">
                <x-text-input wire:model.live="search" type="search" placeholder="Buscar por titulo o revista"
                    class="w-full" />
            </div>
        </div>

        <div class="mt-6 overflow-x-auto rounded-2xl border border-[#f0dede]">
            <table class="min-w-full divide-y divide-[#f0dede] text-sm whitespace-nowrap">
                <thead class="bg-[#fff7f7] text-left text-xs font-semibold uppercase tracking-[0.2em] text-[#7a1515]">
                    <tr>
                        <th class="px-4 py-3">Titulo</th>
                        <th class="px-4 py-3">Ano</th>
                        <th class="px-4 py-3">Revista</th>
                        <th class="px-4 py-3">ISSN</th>
                        <th class="px-4 py-3">Ambito</th>
                        <th class="px-4 py-3">Categoria Revista</th>
                        <th class="px-4 py-3">Autores</th>
                        <th class="px-4 py-3">Grupo de investigacion</th>
                        <th class="px-4 py-3">Filiacion Institucional</th>
                        <th class="px-4 py-3">Pais</th>
                        <th class="px-4 py-3">URL</th>
                        <th class="px-4 py-3">DOI</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0dede] bg-white">
                    @forelse ($this->articles as $article)
                        <tr wire:key="article-{{ $article->publication_id }}" class="hover:bg-[#fff7f7]">
                            
                            {{-- 1. Titulo --}}
                            <td class="px-4 py-3 font-semibold text-[#2b2323]">{{ $article->publication?->title }}</td>
                            
                            {{-- 2. Ano --}}
                            <td class="px-4 py-3 text-slate-600">{{ $article->publication?->publication_year ?? '—' }}</td>
                            
                            {{-- 3. Revista --}}
                            <td class="px-4 py-3 text-slate-600">{{ $article->journal?->journal_name ?? '—' }}</td>
                            
                            {{-- 4. ISSN --}}
                            <td class="px-4 py-3 text-slate-600">{{ $article->journal_issn ?? '—' }}</td>
                            
                            {{-- 5. Ambito --}}
                            <td class="px-4 py-3 text-slate-600">{{ $article->publication?->scope ?? '—' }}</td>
                            
                            {{-- 6. Categoria Revista (Ajusta 'category' si tu BD usa otro nombre de columna) --}}
                            <td class="px-4 py-3 text-slate-600">{{ $article->journal?->category ?? '—' }}</td>
                            
                            {{-- 7. Autores --}}
                            <td class="px-4 py-3 text-slate-600">
                                @if($article->publication && $article->publication->researchers->isNotEmpty())
                                    {{ 
                                        $article->publication->researchers
                                            ->map(fn($author) => $this->formatResearcherName($author))
                                            ->implode(', ') 
                                    }}
                                @else
                                    —
                                @endif
                            </td>
                            
                            
                           {{-- 8. Grupo de investigación --}}
                            <td class="px-4 py-3 text-slate-600">
                                {{ $article->publication?->researchers->pluck('researchGroup.group_name')->filter()->unique()->implode(', ') ?: '—' }}
                            </td>
                            
                            {{-- 9. Filiacion Institucional (Ajusta según tu BD) --}}
                            <td class="px-4 py-3 text-slate-600"> {{ $article->publication?->researchers->pluck('researchGroup.institution.institution_name')->filter()->unique()->implode(', ') ?: '—' }} </td>
                            
                            {{-- 10. Pais --}}
                            <td class="px-4 py-3 text-slate-600">{{ $article->publication?->country_publication ?? '—' }}</td>
                            
                            {{-- 11. URL --}}
                            <td class="px-4 py-3 text-slate-600">
                                @if($article->publication?->url)
                                    <a href="{{ $article->publication->url }}" target="_blank" class="text-blue-600 hover:underline">Ver enlace</a>
                                @else
                                    —
                                @endif
                            </td>
                            
                            {{-- 12. DOI --}}
                            <td class="px-4 py-3 text-slate-600">{{ $article->doi ?? '—' }}</td>
                            
                            {{-- 13. Acciones --}}
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <button type="button" wire:click="edit({{ $article->publication_id }})"
                                        class="rounded-full border border-[#9c1c1c]/30 px-3 py-1 text-xs font-semibold text-[#7a1515] hover:bg-[#f2d3d3]">
                                        Editar
                                    </button>
                                    <button type="button" wire:click="delete({{ $article->publication_id }})"
                                        onclick="confirm('¿Seguro que deseas eliminar este artículo?') || event.stopImmediatePropagation()"
                                        class="rounded-full border border-[#d77a7a]/40 px-3 py-1 text-xs font-semibold text-[#9c1c1c] hover:bg-[#f9dede]">
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                           
                            <td colspan="13" class="px-4 py-6 text-center text-sm text-slate-500">
                                No hay articulos registrados todavia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->articles->links() }}
        </div>
    </div>

</div>