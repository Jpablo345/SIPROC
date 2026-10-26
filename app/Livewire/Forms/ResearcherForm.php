<?php

namespace App\Livewire\Forms;

use App\Actions\Publicaciones\CreateResearcherAction;
use App\Models\Institution;
use App\Models\ResearchGroup;
use App\Models\Researcher;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Formulario "crear investigador" compartido por los selectores de autores,
 * de conferencistas y el formulario de publicaciones. Los campos se pintan
 * con el componente <x-researcher-form-fields>.
 */
class ResearcherForm extends Form
{
    public ?string $document = null;
    public string $name_1 = '';
    public ?string $name_2 = null;
    public string $last_name_1 = '';
    public ?string $last_name_2 = null;

    // Grupo existente o nuevo
    public ?string $cod_minciencias = null;
    public bool $create_group = false;
    public string $group_code = '';
    public string $group_name = '';
    public ?string $group_classification = null;

    // Institución del grupo nuevo: existente o nueva
    public ?int $institution_id = null;
    public bool $create_institution = false;
    public string $institution_name = '';
    public ?string $institution_type = null;
    public ?string $institution_country = null;
    public ?string $institution_city = null;
    public ?string $institution_website = null;

    /**
     * Valida, crea el investigador (y su grupo e institución si se pidieron) y limpia el formulario.
     */
    public function store(): Researcher
    {
        Gate::authorize('create', Researcher::class);

        if ($this->create_group) {
            Gate::authorize('create', ResearchGroup::class);
        }

        if ($this->create_group && $this->create_institution) {
            Gate::authorize('create', Institution::class);
        }

        $this->validate();

        $researcher = app(CreateResearcherAction::class)->execute($this->all());

        $this->reset();
        $this->resetValidation();

        return $researcher;
    }

    protected function rules(): array
    {
        $creatingInstitution = fn () => $this->create_group && $this->create_institution;

        return [
            'document' => ['nullable', 'string', 'max:20', 'unique:researcher,document'],
            'name_1' => ['required', 'string', 'max:50'],
            'name_2' => ['nullable', 'string', 'max:50'],
            'last_name_1' => ['required', 'string', 'max:50'],
            'last_name_2' => ['nullable', 'string', 'max:50'],

            'cod_minciencias' => [Rule::requiredIf(fn () => ! $this->create_group), 'nullable', 'string', 'exists:research_group,cod_minciencias'],
            'create_group' => ['boolean'],
            'group_code' => [Rule::requiredIf(fn () => $this->create_group), 'nullable', 'string', 'max:50', Rule::unique('research_group', 'cod_minciencias')],
            'group_name' => [Rule::requiredIf(fn () => $this->create_group), 'nullable', 'string', 'max:255'],
            'group_classification' => ['nullable', 'string', 'max:50'],

            'institution_id' => ['nullable', 'integer', 'exists:institution,institution_id'],
            'create_institution' => ['boolean'],
            'institution_name' => [Rule::requiredIf($creatingInstitution), 'nullable', 'string', 'max:255', Rule::unique('institution', 'institution_name')],
            'institution_type' => ['nullable', 'string', 'max:50'],
            'institution_country' => ['nullable', 'string', 'max:50'],
            'institution_city' => ['nullable', 'string', 'max:50'],
            'institution_website' => ['nullable', 'string', 'max:255', 'url:http,https'],
        ];
    }

    protected function messages(): array
    {
        return [
            'document.unique' => 'Este número de documento ya está registrado.',
            'document.max' => 'El documento no puede superar 20 caracteres.',
            'name_1.required' => 'El primer nombre es obligatorio.',
            'name_1.max' => 'El primer nombre no puede superar 50 caracteres.',
            'name_2.max' => 'El segundo nombre no puede superar 50 caracteres.',
            'last_name_1.required' => 'El primer apellido es obligatorio.',
            'last_name_1.max' => 'El primer apellido no puede superar 50 caracteres.',
            'last_name_2.max' => 'El segundo apellido no puede superar 50 caracteres.',
            'cod_minciencias.required' => 'Selecciona un grupo existente.',
            'cod_minciencias.exists' => 'El grupo seleccionado no existe.',
            'group_code.required' => 'El código del grupo es obligatorio.',
            'group_code.unique' => 'Ya existe un grupo con este código.',
            'group_code.max' => 'El código no puede superar 50 caracteres.',
            'group_name.required' => 'El nombre del grupo es obligatorio.',
            'institution_id.exists' => 'La institución seleccionada no existe.',
            'institution_name.required' => 'El nombre de la institución es obligatorio.',
            'institution_name.unique' => 'Ya existe una institución con este nombre.',
            'institution_website.url' => 'El sitio web debe empezar con http:// o https://.',
        ];
    }
}
