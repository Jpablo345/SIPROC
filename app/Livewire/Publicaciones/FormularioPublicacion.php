<?php

namespace App\Livewire\Publicaciones;

use App\Actions\Publicaciones\CreateResearcherAction;
use App\Actions\Publicaciones\SavePublicationAction;
use App\Models\BookType;
use App\Models\Institution;
use App\Models\Journal;
use App\Models\Publication;
use App\Models\PublicationType;
use App\Models\ResearchGroup;
use App\Models\Researcher;
use Illuminate\Validation\Rule;
use Livewire\Component;

class FormularioPublicacion extends Component
{
    public ?int $publicationId = null;

    public string $title = '';
    public ?string $publication_year = null;
    public ?string $scope = null;
    public ?string $country_publication = null;
    public ?string $url = null;
    public ?int $type_id = null;

    public ?string $journal_issn = null;

    public ?string $doi = null;
    public string $journalSearch = '';
    public array $journalResults = [];

    public ?string $book_isbn = null;
    public ?string $means_of_dissemination = null;
    public ?string $editorial = null;
    public ?int $book_type_id = null;

    public string $authorSearch = '';
    public array $authorResults = [];
    public array $selectedAuthors = [];

    public ?int $articleTypeId = null;
    public ?int $bookTypeId = null;

    public ?string $modal_document = null;
    public string $modal_name_1 = '';
    public string $modal_name_2 = '';
    public string $modal_last_name_1 = '';
    public string $modal_last_name_2 = '';
    public ?string $modal_cod_minciencias = null;

    public bool $modal_create_group = false;
    public string $modal_group_code = '';
    public string $modal_group_name = '';
    public ?string $modal_group_classification = null;
    public ?int $modal_institution_id = null;

    public bool $modal_create_institution = false;
    public string $modal_institution_name = '';
    public ?string $modal_institution_type = null;
    public ?string $modal_institution_country = null;
    public ?string $modal_institution_city = null;
    public ?string $modal_institution_website = null;

    public function mount(?int $publicationId = null): void
    {
        $this->publicationId = $publicationId;
        $this->resolveTypeIds();

        if ($publicationId) {
            $this->cargarPublicacion($publicationId);
        }
    }

    public function updatedTypeId(): void
    {
        if ($this->isArticleType()) {
            $this->resetBookFields();
            return;
        }

        if ($this->isBookType()) {
            $this->resetArticleFields();
            return;
        }

        $this->resetArticleFields();
        $this->resetBookFields();
    }

    public function updatedAuthorSearch(): void
    {
        $this->buscarInvestigador();
    }

    public function updatedJournalSearch(): void
    {
        $this->buscarRevista();
    }

    public function buscarInvestigador(): void
    {
        $term = trim($this->authorSearch);

        if ($term === '') {
            $this->authorResults = [];
            return;
        }

        $like = '%' . $term . '%';

        $this->authorResults = Researcher::query()
            ->with('researchGroup')
            ->where(function ($query) use ($like) {
                $query->where('researcher_id', 'ilike', $like)
                    ->orWhere('name_1', 'ilike', $like)
                    ->orWhere('name_2', 'ilike', $like)
                    ->orWhere('last_name_1', 'ilike', $like)
                    ->orWhere('last_name_2', 'ilike', $like);
            })
            ->orderBy('last_name_1')
            ->limit(8)
            ->get()
            ->map(function (Researcher $researcher) {
                return [
                    'researcher_id' => $researcher->researcher_id,
                    'name' => $this->formatResearcherName($researcher),
                    'group' => $researcher->researchGroup?->group_name,
                ];
            })
            ->toArray();
    }

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
            ->map(function (Journal $journal) {
                return [
                    'journal_issn' => $journal->journal_issn,
                    'journal_name' => $journal->journal_name,
                ];
            })
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

    public function agregarAutor(string $researcherId): void
    {
        foreach ($this->selectedAuthors as $author) {
            if ($author['researcher_id'] === $researcherId) {
                return;
            }
        }

        $researcher = Researcher::with('researchGroup')->find($researcherId);

        if (!$researcher) {
            return;
        }

        $this->selectedAuthors[] = [
            'researcher_id' => $researcher->researcher_id,
            'name' => $this->formatResearcherName($researcher),
            'group' => $researcher->researchGroup?->group_name,
        ];

        $this->authorSearch = '';
        $this->authorResults = [];
    }

    public function eliminarAutor(int $index): void
    {
        unset($this->selectedAuthors[$index]);
        $this->selectedAuthors = array_values($this->selectedAuthors);
    }

    public function abrirModal(): void
    {
        $this->resetModalFields();
        $this->dispatch('open-modal', name: 'crear-investigador');
    }

    public function crearYSeleccionar(): void
    {
        $data = $this->validate($this->modalRules(), $this->modalMessages());

        $newResearcher = app(CreateResearcherAction::class)->execute($data);

        $this->agregarAutor((string) $newResearcher->researcher_id);

        $this->dispatch('close-modal', name: 'crear-investigador');
        $this->resetModalFields();
    }

    public function guardar(): void
    {
        $data = $this->validate($this->rules(), $this->messages());

        app(SavePublicationAction::class)->execute(
            publicationData: [
                'title' => $data['title'],
                'publication_year' => $data['publication_year'] ?? null,
                'scope' => $data['scope'] ?? null,
                'country_publication' => $data['country_publication'] ?? null,
                'url' => $data['url'] ?? null,
                'type_id' => $data['type_id'] ?? null,
            ],
            articleData: [
                'journal_issn' => $data['journal_issn'] ?? null,
                'doi' => $data['doi'] ?? null,
            ],
            bookData: [
                'book_isbn' => $data['book_isbn'] ?? null,
                'means_of_dissemination' => $data['means_of_dissemination'] ?? null,
                'editorial' => $data['editorial'] ?? null,
                'book_type_id' => $data['book_type_id'] ?? null,
            ],
            selectedAuthors: $this->selectedAuthors,
            publicationId: $this->publicationId,
            isArticle: $this->isArticleType(),
            isBook: $this->isBookType(),
        );

        $this->resetForm();
        session()->flash('status', 'Publicacion guardada.');
        $this->dispatch('publicacionGuardada');
    }

    public function cargarPublicacion(int $publicationId): void
    {
        $publication = Publication::with(['article.journal', 'book', 'researchers', 'type'])->findOrFail($publicationId);

        $this->publicationId = $publication->publication_id;
        $this->title = $publication->title;
        $this->publication_year = $publication->publication_year;
        $this->scope = $publication->scope;
        $this->country_publication = $publication->country_publication;
        $this->url = $publication->url;
        $this->type_id = $publication->type_id;

        if ($publication->article) {
            $this->journal_issn = $publication->article->journal_issn;
            $this->doi = $publication->article->doi;
            $this->journalSearch = $publication->article->journal
                ? $publication->article->journal->journal_name . ' (' . $publication->article->journal->journal_issn . ')'
                : $publication->article->journal_issn;
        }

        if ($publication->book) {
            $this->book_isbn = $publication->book->book_isbn;
            $this->means_of_dissemination = $publication->book->means_of_dissemination;
            $this->editorial = $publication->book->editorial;
            $this->book_type_id = $publication->book->book_type_id;
        }

        $this->selectedAuthors = $publication->researchers
            ->sortBy(function (Researcher $researcher) {
                return $researcher->pivot->author_order ?? 999;
            })
            ->map(function (Researcher $researcher) {
                return [
                    'researcher_id' => $researcher->researcher_id,
                    'name' => $this->formatResearcherName($researcher),
                    'group' => $researcher->researchGroup?->group_name,
                ];
            })
            ->values()
            ->toArray();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'publication_year' => ['nullable', 'regex:/^\d{4}$/'],
            'scope' => ['nullable', 'in:Nacional,Internacional'],
            'country_publication' => ['nullable', 'string', 'max:100'],
            'url' => ['nullable', 'string', 'max:300', 'url'],
            'type_id' => ['required', 'integer', 'exists:publication_type,type_id'],
            'journal_issn' => [
                Rule::requiredIf(fn() => $this->isArticleType()),
                'nullable',
                'string',
                'exists:journal,journal_issn',
            ],
            'doi' => [ // agregar aquí
                'nullable',
                'string',
                'max:200',
                Rule::unique('article', 'doi')->ignore($this->publicationId, 'publication_id'),
            ],
            'book_isbn' => [
                Rule::requiredIf(fn() => $this->isBookType()),
                'nullable',
                'string',
                'max:20',
            ],
            'means_of_dissemination' => ['nullable', 'string', 'max:100'],
            'editorial' => ['nullable', 'string', 'max:255'],
            'book_type_id' => [
                Rule::requiredIf(fn() => $this->isBookType()),
                'nullable',
                'integer',
                'exists:book_type,book_type_id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El titulo es obligatorio.',
            'publication_year.regex' => 'El ano debe tener 4 digitos.',
            'scope.in' => 'El ambito debe ser Nacional o Internacional.',
            'type_id.required' => 'Selecciona el tipo de publicacion.',
            'type_id.exists' => 'El tipo de publicacion no es valido.',
            'journal_issn.required' => 'Selecciona la revista del articulo.',
            'journal_issn.exists' => 'La revista seleccionada no existe.',
            'doi.unique' => 'Este DOI ya está registrado en otra publicación.',
            'book_isbn.required' => 'El ISBN es obligatorio para libros.',
            'book_type_id.required' => 'Selecciona el tipo de libro.',
            'book_type_id.exists' => 'El tipo de libro no es valido.',
            'url.url' => 'La URL debe ser valida y empezar con http.',
        ];
    }

    public function render()
    {
        return view('livewire.publicaciones.formulario-publicacion', [
            'types' => PublicationType::orderBy('type_name')->get(['type_id', 'type_name']),
            'bookTypes' => BookType::orderBy('type_name')->get(['book_type_id', 'type_name']),
            'groups' => ResearchGroup::orderBy('group_name')->get(['cod_minciencias', 'group_name']),
            'institutions' => Institution::orderBy('institution_name')->get(['institution_id', 'institution_name']),
        ]);
    }

    private function resolveTypeIds(): void
    {
        $types = PublicationType::query()
            ->whereRaw('lower(type_name) in (?, ?)', ['articulo', 'libro'])
            ->get(['type_id', 'type_name']);

        $this->articleTypeId = $types->firstWhere('type_name', 'Articulo')?->type_id
            ?? $types->firstWhere('type_name', 'articulo')?->type_id;
        $this->bookTypeId = $types->firstWhere('type_name', 'Libro')?->type_id
            ?? $types->firstWhere('type_name', 'libro')?->type_id;
    }

    public function isArticleType(): bool
    {
        return $this->articleTypeId && $this->type_id === $this->articleTypeId;
    }

    public function isBookType(): bool
    {
        return $this->bookTypeId && $this->type_id === $this->bookTypeId;
    }

    private function resetArticleFields(): void
    {
        $this->journal_issn = null;
        $this->journalSearch = '';
        $this->journalResults = [];
        $this->doi = null;
    }

    private function resetBookFields(): void
    {
        $this->book_isbn = null;
        $this->means_of_dissemination = null;
        $this->editorial = null;
        $this->book_type_id = null;
    }

    private function resetForm(): void
    {
        $this->publicationId = null;
        $this->title = '';
        $this->publication_year = null;
        $this->scope = null;
        $this->country_publication = null;
        $this->url = null;
        $this->type_id = null;
        $this->resetArticleFields();
        $this->resetBookFields();
        $this->authorSearch = '';
        $this->authorResults = [];
        $this->selectedAuthors = [];
        $this->resetValidation();
    }

    private function formatResearcherName(Researcher $researcher): string
    {
        return trim(implode(' ', array_filter([
            $researcher->name_1,
            $researcher->name_2,
            $researcher->last_name_1,
            $researcher->last_name_2,
        ])));
    }

    private function modalRules(): array
    {
        return [
            'modal_document' => ['nullable', 'string', 'max:20', 'unique:researcher,document'],
            'modal_name_1' => ['required', 'string', 'max:50'],
            'modal_name_2' => ['nullable', 'string', 'max:50'],
            'modal_last_name_1' => ['required', 'string', 'max:50'],
            'modal_last_name_2' => ['nullable', 'string', 'max:50'],
            'modal_cod_minciencias' => [
                Rule::requiredIf(fn() => !$this->modal_create_group),
                'nullable',
                'string',
                'max:50',
            ],
            'modal_group_code' => [
                Rule::requiredIf(fn() => $this->modal_create_group),
                'nullable',
                'string',
                'max:50',
            ],
            'modal_group_name' => [
                Rule::requiredIf(fn() => $this->modal_create_group),
                'nullable',
                'string',
                'max:255',
            ],
            'modal_group_classification' => ['nullable', 'string', 'max:50'],
            'modal_institution_id' => ['nullable', 'integer'],
            'modal_create_group' => ['boolean'],
            'modal_create_institution' => ['boolean'],
            'modal_institution_name' => [
                Rule::requiredIf(fn() => $this->modal_create_institution),
                'nullable',
                'string',
                'max:255',
            ],
            'modal_institution_type' => ['nullable', 'string', 'max:50'],
            'modal_institution_country' => ['nullable', 'string', 'max:50'],
            'modal_institution_city' => ['nullable', 'string', 'max:50'],
            'modal_institution_website' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function modalMessages(): array
    {
        return [
            'modal_document.unique' => 'Este número de documento ya está registrado.',
            'modal_name_1.required' => 'El primer nombre es obligatorio.',
            'modal_name_2.string' => 'El segundo nombre debe ser una cadena de texto.',
            'modal_name_2.max' => 'El segundo nombre debe tener como máximo 50 caracteres.',
            'modal_last_name_1.required' => 'El primer apellido es obligatorio.',
            'modal_last_name_2.string' => 'El segundo apellido debe ser una cadena de texto.',
            'modal_last_name_2.max' => 'El segundo apellido debe tener como máximo 50 caracteres.',
            'modal_cod_minciencias.required' => 'Selecciona un grupo existente.',
            'modal_group_code.required' => 'El codigo del grupo es obligatorio.',
            'modal_group_name.required' => 'El nombre del grupo es obligatorio.',
            'modal_institution_name.required' => 'El nombre de la institucion es obligatorio.',
        ];
    }

    private function resetModalFields(): void
    {
        $this->modal_document = null;
        $this->modal_name_1 = '';
        $this->modal_name_2 = '';
        $this->modal_last_name_1 = '';
        $this->modal_last_name_2 = '';
        $this->modal_cod_minciencias = null;
        $this->modal_create_group = false;
        $this->modal_group_code = '';
        $this->modal_group_name = '';
        $this->modal_group_classification = null;
        $this->modal_institution_id = null;
        $this->modal_create_institution = false;
        $this->modal_institution_name = '';
        $this->modal_institution_type = null;
        $this->modal_institution_country = null;
        $this->modal_institution_city = null;
        $this->modal_institution_website = null;
        $this->resetErrorBag();
    }
}
