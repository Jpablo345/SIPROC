<?php

namespace App\Livewire\Publicaciones;

use App\Models\Institution;
use App\Models\ResearchGroup;
use App\Models\Researcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class AutoresSelector extends Component
{
    // #[Modelable] permite que el padre use wire:model="selectedAuthors"
    #[Modelable]
    public array $selectedAuthors = [];

    // Búsqueda
    public string $authorSearch = '';
    public array $authorResults = [];

    // Campos del Modal
    public ?string $modal_document = null;
    public string $modal_name_1 = '';
    public ?string $modal_name_2 = null;
    public string $modal_last_name_1 = '';
    public ?string $modal_last_name_2 = null;
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

    public function updatedAuthorSearch(): void
    {
        $this->buscarInvestigador();
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
            ->where(function ($query) use ($like, $term) {
                // researcher_id es bigint: ilike sobre él falla en PostgreSQL, se compara exacto.
                $query->when(ctype_digit($term), fn($q) => $q->orWhere('researcher_id', (int) $term))
                    ->orWhere('document', 'ilike', $like)
                    ->orWhere('name_1', 'ilike', $like)
                    ->orWhere('name_2', 'ilike', $like)
                    ->orWhere('last_name_1', 'ilike', $like)
                    ->orWhere('last_name_2', 'ilike', $like);
            })
            ->orderBy('last_name_1')
            ->limit(8)
            ->get()
            ->map(fn(Researcher $r) => [
                'researcher_id' => $r->researcher_id,
                'name' => $this->formatResearcherName($r),
                'group' => $r->researchGroup?->group_name,
            ])
            ->toArray();
    }

    public function agregarAutor(string $researcherId): void
    {
        // Evitar duplicados
        foreach ($this->selectedAuthors as $author) {
            if ((string) $author['researcher_id'] === $researcherId) {
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

    public function crearYSeleccionar(): void
    {
        $this->authorize('create', Researcher::class);
        if ($this->modal_create_group) {
            $this->authorize('create', ResearchGroup::class);
        }
        if ($this->modal_create_institution) {
            $this->authorize('create', Institution::class);
        }

        $data = $this->validate($this->modalRules(), $this->modalMessages());

        $name1 = trim($data['modal_name_1']);
        $lastName1 = trim($data['modal_last_name_1']);

        $newResearcherId = DB::transaction(function () use ($data, $name1, $lastName1) {
            $institutionId = $data['modal_institution_id'] ?? null;

            if ($data['modal_create_institution']) {
                $institution = Institution::create([
                    'institution_name' => trim($data['modal_institution_name']),
                    'institution_type' => $data['modal_institution_type'],
                    'country' => $data['modal_institution_country'],
                    'city' => $data['modal_institution_city'],
                    'website' => $data['modal_institution_website'],
                ]);
                $institutionId = $institution->institution_id;
            }

            $groupCode = $data['modal_cod_minciencias'] ?? null;

            if ($data['modal_create_group']) {
                $group = ResearchGroup::create([
                    'cod_minciencias' => trim($data['modal_group_code']),
                    'group_name' => trim($data['modal_group_name']),
                    'group_classification' => $data['modal_group_classification'],
                    'institution_id' => $institutionId,
                ]);
                $groupCode = $group->cod_minciencias;
            }

            $researcher = Researcher::create([
                'document' => !empty($data['modal_document']) ? trim($data['modal_document']) : null,
                'name_1' => $name1,
                'name_2' => !empty($data['modal_name_2']) ? trim($data['modal_name_2']) : null,
                'last_name_1' => $lastName1,
                'last_name_2' => !empty($data['modal_last_name_2']) ? trim($data['modal_last_name_2']) : null,
                'cod_minciencias' => $groupCode,
            ]);

            return $researcher->researcher_id;
        });

        $this->agregarAutor((string) $newResearcherId);

        $this->dispatch('close-modal', 'crear-investigador-modal');
        $this->resetModalFields();
    }

    #[Computed]
    public function groups()
    {
        return ResearchGroup::orderBy('group_name')->get(['cod_minciencias', 'group_name']);
    }

    #[Computed]
    public function institutions()
    {
        return Institution::orderBy('institution_name')->get(['institution_id', 'institution_name']);
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
            'modal_institution_website' => ['nullable', 'string', 'max:255', 'url:http,https'],
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

    public function render()
    {
        return view('livewire.publicaciones.autores-selector');
    }
}