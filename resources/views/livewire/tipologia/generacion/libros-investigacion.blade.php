<?php

use App\Livewire\Concerns\ValidatesPublication;
use App\Models\Book;
use App\Models\BookType;
use App\Models\Institution;
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

    // Campos libro
    public ?string $book_isbn = null;
    public ?string $means_of_dissemination = null;
    public ?string $editorial = null;
    public ?int $book_type_id = null;

    // Tipo resuelto automáticamente
    public ?int $bookTypeId = null;

    // Autores delegados al componente hijo
    public array $selectedAuthors = [];

    public function mount(): void
    {
        $this->resolveBookType();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    // ─── CRUD libro ──────────────────────────────────────────────────────────

    public function edit(int $publicationId): void
    {
        $book = Book::with(['publication', 'bookType'])->findOrFail($publicationId);
        $this->authorize('update', $book->publication);

        $this->editingId = $book->publication_id;
        $this->title = $book->publication?->title ?? '';
        $this->publication_year = $book->publication?->publication_year;
        $this->scope = $book->publication?->scope;
        $this->country_publication = $book->publication?->country_publication;
        $this->url = $book->publication?->url;
        $this->book_isbn = $book->book_isbn;
        $this->means_of_dissemination = $book->means_of_dissemination;
        $this->editorial = $book->editorial;
        $this->book_type_id = $book->book_type_id;

        // Cargar autores existentes
        $publication = Publication::with(['researchers.researchGroup'])->find($publicationId);
        $this->selectedAuthors = $publication?->researchers
            ->sortBy(fn($r) => $r->pivot->author_order ?? 999)
            ->map(fn(Researcher $r) => $r->toOption())
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
                'book_isbn' => ['required', 'string', 'max:20', Rule::unique('book', 'book_isbn')->ignore($this->editingId, 'publication_id')],
                'means_of_dissemination' => ['nullable', 'string', 'max:100'],
                'editorial' => ['nullable', 'string', 'max:255'],
                'book_type_id' => ['nullable', 'integer', 'exists:book_type,book_type_id'],
                ...$this->authorRules(),
            ],
            [
                ...$this->publicationMessages(),
                'book_isbn.required' => 'El ISBN es obligatorio.',
                'book_isbn.max' => 'El ISBN no puede superar 20 caracteres.',
                'book_isbn.unique' => 'Este ISBN ya está registrado en otro libro.',
                'means_of_dissemination.max' => 'El medio de divulgación no puede superar 100 caracteres.',
                'editorial.max' => 'La editorial no puede superar 255 caracteres.',
                'book_type_id.exists' => 'El tipo de libro no es válido.',
            ]
        );

        $publicationData = [
            'title' => trim($this->title),
            'publication_year' => $this->publication_year ? trim($this->publication_year) : null,
            'scope' => $this->scope,
            'country_publication' => $this->country_publication ? trim($this->country_publication) : null,
            'url' => $this->url ? trim($this->url) : null,
            'type_id' => $this->bookTypeId,
        ];

        DB::transaction(function () use ($publicationData) {
            if ($this->editingId) {
                Publication::where('publication_id', $this->editingId)->update($publicationData);
                $publicationId = $this->editingId;
            } else {
                $publication = Publication::create($publicationData);
                $publicationId = $publication->publication_id;
            }

            Book::updateOrCreate(
                ['publication_id' => $publicationId],
                [
                    'book_isbn' => $this->book_isbn,
                    'means_of_dissemination' => $this->means_of_dissemination,
                    'editorial' => $this->editorial,
                    'book_type_id' => $this->book_type_id,
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

        session()->flash('status', $this->editingId ? 'Libro actualizado.' : 'Libro creado.');
        $this->resetForm();
    }

    public function delete(int $publicationId): void
    {
        //Publication::where('publication_id', $publicationId)->delete();
        $publication = Publication::findOrFail($publicationId);
        $this->authorize('delete', $publication);
        $publication->delete();

        session()->flash('status', 'Libro eliminado.');
        $this->resetPage();
    }

    // ─── Computed properties ─────────────────────────────────────────────────

    public function getBooksProperty()
    {
        $term = trim($this->search);

        return Book::query()
            // 👇 Carga ultra-optimizada con una sola ruta 👇
            ->with(['publication.researchers.researchGroup.institution', 'bookType'])
            ->when($term !== '', function ($query) use ($term) {
                $like = '%' . $term . '%';
                $query->whereHas('publication', function ($q) use ($like) {
                    $q->where('title', 'ilike', $like)
                        ->orWhereRaw('CAST(publication_year AS TEXT) LIKE ?', [$like])
                        ->orWhere('scope', 'ilike', $like)
                        ->orWhere('country_publication', 'ilike', $like);
                })
                    ->orWhere('book_isbn', 'ilike', $like)
                    ->orWhereHas('bookType', function ($q) use ($like) {
                        $q->where('type_name', 'ilike', $like);
                    });
            })
            ->orderByDesc('publication_id')
            ->paginate(10);
    }

    public function getBookTypesProperty()
    {
        return BookType::orderBy('type_name')->get(['book_type_id', 'type_name']);
    }

    // ─── Helpers  ────────────────────────────────────────────────────────────

    private function resolveBookType(): void
    {
        $type = PublicationType::query()
            ->whereRaw('lower(type_name) = ?', ['libro'])
            ->first(['type_id']);

        $this->bookTypeId = $type?->type_id;
    }

    // 👇 Cambiado a public para poder usarlo en Blade 👇
    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->publication_year = null;
        $this->scope = null;
        $this->country_publication = null;
        $this->url = null;
        $this->book_isbn = null;
        $this->means_of_dissemination = null;
        $this->editorial = null;
        $this->book_type_id = null;
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
                    {{ $editingId ? 'Editar libro' : 'Nuevo libro' }}
                </h3>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#9c1c1c]">
                    Datos generales y tipo de libro
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
                    {{ $editingId ? 'Guardar cambios' : 'Crear libro' }}
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
                <x-text-input id="title" wire:model="title" type="text" class="mt-2 block w-full" />
            </div>
            <div>
                <x-input-label for="publication_year" :value="'Ano'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="publication_year" wire:model="publication_year" type="text"
                    class="mt-2 block w-full" />
            </div>
            <div>
                <x-input-label for="scope" :value="'Ambito'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <select id="scope" wire:model="scope"
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
                <x-text-input id="url" wire:model="url" type="url" class="mt-2 block w-full" placeholder="https://" />
                <x-input-error :messages="$errors->get('url')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="book_isbn" :value="'ISBN'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="book_isbn" wire:model="book_isbn" type="text" class="mt-2 block w-full" />
            </div>
            <div>
                <x-input-label for="book_type_id" :value="'Tipo de libro'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <select id="book_type_id" wire:model="book_type_id"
                    class="mt-2 block w-full rounded-md border-red-200 bg-white text-zinc-900 shadow-sm transition-colors focus:border-red-600 focus:ring-red-600">
                    <option value="">Selecciona un tipo</option>
                    @foreach ($this->bookTypes as $bookType)
                        <option value="{{ $bookType->book_type_id }}">{{ $bookType->type_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="means_of_dissemination" :value="'Medio de difusion'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <select id="means_of_dissemination" wire:model="means_of_dissemination"
                    class="mt-2 block w-full rounded-md border-red-200 bg-white text-zinc-900 shadow-sm transition-colors focus:border-red-600 focus:ring-red-600">
                    <option value="">Selecciona un medio</option>
                    <option value="Electronico">Electronico</option>
                    <option value="Papel">Papel</option>
                </select>
            </div>
            <div>
                <x-input-label for="editorial" :value="'Editorial'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="editorial" wire:model="editorial" type="text" class="mt-2 block w-full" />
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
                <h3 class="text-lg font-semibold text-[#2b2323]">Libros registrados</h3>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#9c1c1c]">{{ $this->books->total() }}
                    registros</p>
            </div>
            <div class="w-full sm:w-72">
                <x-text-input wire:model.live="search" type="search" placeholder="Buscar por titulo o ISBN"
                    class="w-full" />
            </div>
        </div>

        <div class="mt-6 overflow-x-auto rounded-2xl border border-[#f0dede]">
            <table class="min-w-full divide-y divide-[#f0dede] text-sm whitespace-nowrap">
                <thead class="bg-[#fff7f7] text-left text-xs font-semibold uppercase tracking-[0.2em] text-[#7a1515]">
                    <tr>
                        <th class="px-4 py-3">Titulo</th>
                        <th class="px-4 py-3">ISBN</th>
                        <th class="px-4 py-3">Medio de Divulgación</th>
                        <th class="px-4 py-3">Año</th>
                        <th class="px-4 py-3">Editorial</th>
                        <th class="px-4 py-3">Autores</th>
                        <th class="px-4 py-3">Grupo de Investigación</th>
                        <th class="px-4 py-3">Filiación Institucional</th>
                        <th class="px-4 py-3">Lugar de publicacion</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Ambito</th>
                        <th class="px-4 py-3">URL</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0dede] bg-white">
                    @forelse ($this->books as $book)
                        <tr wire:key="book-{{ $book->publication_id }}" class="hover:bg-[#fff7f7]">
                            <td class="px-4 py-3 font-semibold text-[#2b2323]">{{ $book->publication?->title }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $book->book_isbn }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $book->means_of_dissemination ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $book->publication?->publication_year ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $book->editorial ?? '—' }}</td>
                            {{-- Autores del Libro --}}
                            <td class="px-4 py-3 text-slate-600">
                                @if($book->publication && $book->publication->researchers->isNotEmpty())
                                                {{ 
                                                                                                                        $book->publication->researchers
                                    ->pluck('full_name')
                                    ->implode(', ') 
                                                                                                                    }}
                                @else
                                    —
                                @endif
                            </td>
                            {{-- Grupo de Investigación --}}
                            <td class="px-4 py-3 text-slate-600">
                                {{ $book->publication?->researchers->pluck('researchGroup.group_name')->filter()->unique()->implode(', ') ?: '—' }}
                            </td>
                            {{-- Filiación Institucional --}}
                            <td class="px-4 py-3 text-slate-600">
                                {{ $book->publication?->researchers->pluck('researchGroup.institution.institution_name')->filter()->unique()->implode(', ') ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $book->publication?->country_publication ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $book->bookType?->type_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $book->publication?->scope ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $book->publication?->url ?? '—' }}</td>




                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <button type="button" wire:click="edit({{ $book->publication_id }})"
                                        class="rounded-full border border-[#9c1c1c]/30 px-3 py-1 text-xs font-semibold text-[#7a1515] hover:bg-[#f2d3d3]">
                                        Editar
                                    </button>
                                    <button type="button" wire:click="delete({{ $book->publication_id }})"
                                        onclick="confirm('¿Seguro que deseas eliminar este libro?') || event.stopImmediatePropagation()"
                                        class="rounded-full border border-[#d77a7a]/40 px-3 py-1 text-xs font-semibold text-[#9c1c1c] hover:bg-[#f9dede]">
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            {{-- Colspan ajustado a 12 columnas --}}
                            <td colspan="12" class="px-4 py-6 text-center text-sm text-slate-500">
                                No hay libros registrados todavia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->books->links() }}
        </div>
    </div>
</div>