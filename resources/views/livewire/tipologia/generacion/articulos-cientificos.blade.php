<?php

use App\Actions\Publicaciones\SavePublicationAction;
use App\Livewire\Concerns\SearchesJournals;
use App\Livewire\Concerns\ValidatesPublication;
use App\Models\Article;
use App\Models\Publication;
use App\Models\PublicationType;
use App\Models\Researcher;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination, ValidatesPublication, SearchesJournals;

    public string $search = '';
    public ?int $editingId = null;

    // Campos publicación
    public string $title = '';
    public ?string $publication_year = null;
    public ?string $scope = null;
    public ?string $country_publication = null;
    public ?string $url = null;

    // Campos artículo (el buscador de revistas viene de SearchesJournals)
    public ?string $journal_issn = null;
    public ?string $doi = null;
    public string $journalSearch = '';
    public array $journalResults = [];

    // Tipo "Articulo" del sistema, resuelto al montar
    public ?int $articleTypeId = null;

    // Autores
    public array $selectedAuthors = [];

    public function mount(): void
    {
        $this->articleTypeId = PublicationType::idByName('articulo');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    // ─── CRUD artículo ───────────────────────────────────────────────────────

    public function edit(int $publicationId): void
    {
        $article = Article::with(['publication.researchers.researchGroup', 'journal'])->findOrFail($publicationId);
        $this->authorize('update', $article->publication);

        $this->editingId = $article->publication_id;
        $this->title = $article->publication?->title ?? '';
        $this->publication_year = $article->publication?->publication_year;
        $this->scope = $article->publication?->scope;
        $this->country_publication = $article->publication?->country_publication;
        $this->url = $article->publication?->url;
        $this->journal_issn = $article->journal_issn;
        $this->doi = $article->doi;
        $this->journalSearch = self::journalLabel($article->journal, $article->journal_issn);

        $this->selectedAuthors = $article->publication?->researchers
            ->sortBy(fn (Researcher $r) => $r->pivot->author_order ?? 999)
            ->map(fn (Researcher $r) => $r->toOption())
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

        // Misma acción que usa el formulario general de Publicaciones.
        app(SavePublicationAction::class)->execute(
            publicationData: [
                'title' => trim($this->title),
                'publication_year' => $this->publication_year ? trim($this->publication_year) : null,
                'scope' => $this->scope,
                'country_publication' => $this->country_publication ? trim($this->country_publication) : null,
                'url' => $this->url ? trim($this->url) : null,
                'type_id' => $this->articleTypeId,
            ],
            articleData: [
                'journal_issn' => $this->journal_issn,
                'doi' => $this->doi ? trim($this->doi) : null,
            ],
            bookData: [],
            selectedAuthors: $this->selectedAuthors,
            publicationId: $this->editingId,
            isArticle: true,
            isBook: false,
        );

        session()->flash('status', $this->editingId ? 'Artículo actualizado.' : 'Artículo creado.');
        $this->resetForm();
    }

    public function delete(int $publicationId): void
    {
        $publication = Publication::findOrFail($publicationId);
        $this->authorize('delete', $publication);
        $publication->delete();

        session()->flash('status', 'Artículo eliminado.');
        $this->resetPage();
    }

    // ─── Listado ─────────────────────────────────────────────────────────────

    public function getArticlesProperty()
    {
        $term = trim($this->search);

        return Article::query()
            ->with(['publication.researchers.researchGroup.institution', 'journal'])
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';
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
    <div class="card">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="section-title">
                    {{ $editingId ? 'Editar artículo' : 'Nuevo artículo' }}
                </h3>
                <p class="eyebrow">
                    Datos generales y revista asociada
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($editingId)
                    <button type="button" wire:click="cancelEdit"
                        class="btn btn-outline">
                        Cancelar
                    </button>
                @endif
                <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                    class="btn btn-primary"><x-spinner wire:loading wire:target="save" />
                    {{ $editingId ? 'Guardar cambios' : 'Crear artículo' }}
                </button>
            </div>
        </div>

        <x-flash-status />

        <x-validation-errors :bag="$errors" class="mt-4" />

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-input-label for="title" :value="'Título'"
                    class="form-label" />
                <x-text-input id="title" wire:model.defer="title" type="text" class="mt-2 block w-full" />
            </div>
            <div>
                <x-input-label for="publication_year" :value="'Año'"
                    class="form-label" />
                <x-text-input id="publication_year" wire:model.defer="publication_year" type="text"
                    class="mt-2 block w-full" />
            </div>
            <div>
                <x-input-label for="scope" :value="'Ámbito'"
                    class="form-label" />
                <select id="scope" wire:model.defer="scope"
                    class="mt-2 block w-full field">
                    <option value="">Sin definir</option>
                    <option value="Nacional">Nacional</option>
                    <option value="Internacional">Internacional</option>
                </select>
            </div>
           <div>
                <x-input-label for="country_publication" :value="'País'"
                    class="form-label" />

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
                    class="form-label" />
                <x-text-input id="url" wire:model.defer="url" type="url" class="mt-2 block w-full"
                    placeholder="https://" />
                <x-input-error :messages="$errors->get('url')" class="mt-2" />
            </div>

            {{-- Búsqueda de revista con autocomplete --}}
            <div class="md:col-span-2">
                <x-input-label for="journal_search" :value="'Revista (ISSN o nombre)'"
                    class="form-label" />
                <x-text-input id="journal_search" wire:model.live.debounce.300ms="journalSearch" type="text" class="mt-2 block w-full"
                    placeholder="Buscar por nombre o ISSN..." />

                @if ($journalResults)
                    <div class="mt-3 space-y-1 rounded-xl border border-siproc-line bg-white p-3">
                        @foreach ($journalResults as $journal)
                            <button type="button" wire:click="seleccionarRevista('{{ $journal['journal_issn'] }}')"
                                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-siproc-blush">
                                <span>{{ $journal['journal_name'] }}</span>
                                <span class="text-xs text-slate-400">{{ $journal['journal_issn'] }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="md:col-span-2">
                <x-input-label for="doi" :value="'DOI'"
                    class="form-label" />
                <x-text-input id="doi" wire:model.defer="doi" type="text" class="mt-2 block w-full"
                    placeholder="" />
            </div>
        </div>
    </div>

    <livewire:publicaciones.autores-selector wire:model="selectedAuthors" />

    {{-- ═══════════════════════════════════════════════════════════
    TABLA
    ════════════════════════════════════════════════════════════ --}}
    <div class="card">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="section-title">Artículos registrados</h3>
                <p class="eyebrow">
                    {{ $this->articles->total() }} registros
                </p>
            </div>
            <div class="w-full sm:w-72">
                <x-text-input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar por título o revista"
                    class="w-full" />
            </div>
        </div>

        <div class="mt-6 table-wrap" wire:loading.delay.class="opacity-50">
            <table class="data-table whitespace-nowrap">
                <thead class="table-head">
                    <tr>
                        <th class="px-4 py-3">Título</th>
                        <th class="px-4 py-3">Año</th>
                        <th class="px-4 py-3">Revista</th>
                        <th class="px-4 py-3">ISSN</th>
                        <th class="px-4 py-3">Ámbito</th>
                        <th class="px-4 py-3">Categoría Revista</th>
                        <th class="px-4 py-3">Autores</th>
                        <th class="px-4 py-3">Grupo de investigación</th>
                        <th class="px-4 py-3">Filiacion Institucional</th>
                        <th class="px-4 py-3">País</th>
                        <th class="px-4 py-3">URL</th>
                        <th class="px-4 py-3">DOI</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="table-body">
                    @forelse ($this->articles as $article)
                        <tr wire:key="article-{{ $article->publication_id }}" class="hover:bg-siproc-blush">
                            
                            {{-- 1. Titulo --}}
                            <td class="px-4 py-3 font-semibold text-siproc-ink">{{ $article->publication?->title }}</td>
                            
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
                                            ->pluck('full_name')
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
                                        class="btn btn-sm btn-outline">
                                        Editar
                                    </button>
                                    <button type="button" x-on:click="$dispatch('confirmar', { message: '¿Seguro que deseas eliminar este artículo?', action: () => $wire.delete({{ $article->publication_id }}) })"
                                        class="btn btn-sm btn-danger">
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                           
                            <td colspan="13" class="table-empty">
                                No hay artículos registrados todavía.
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