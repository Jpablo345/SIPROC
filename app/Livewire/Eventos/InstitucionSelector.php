<?php

namespace App\Livewire\Eventos;

use App\Models\Institution;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Validation\Rule;

class InstitucionSelector extends Component
{
    public string $target; // 'host' o 'origin'
    public string $label = 'Institución';
    public string $search = '';
    public array $results = [];
    public ?int $selectedId = null;
    public string $selectedName = '';
    
    // Modal para nueva institución
    public bool $showModal = false;
    public string $modal_institution_name = '';
    public ?string $modal_institution_type = null;
    public ?string $modal_institution_country = null;
    public ?string $modal_institution_city = null;
    public ?string $modal_institution_website = null;
    
    public function mount(string $target, string $label = 'Institución'): void
    {
        $this->target = $target;
        $this->label = $label;
    }
    
    // ─── Búsqueda ─────────────────────────────────────────────────────────────
    
    public function updatedSearch(): void
    {
        $term = trim($this->search);
        
        if ($term === '') {
            $this->results = [];
            return;
        }
        
        $like = '%' . $term . '%';
        
        $this->results = Institution::query()
            ->where('institution_name', 'ilike', $like)
            ->orWhere('city', 'ilike', $like)
            ->orWhere('country', 'ilike', $like)
            ->orWhere('institution_type', 'ilike', $like)
            ->limit(8)
            ->get()
            ->map(fn($i) => [
                'id' => $i->institution_id,
                'name' => $i->institution_name,
                'type' => $i->institution_type,
                'city' => $i->city,
                'country' => $i->country,
            ])
            ->toArray();
    }
    
    public function select(int $id): void
    {
        $institution = Institution::find($id);
        if ($institution) {
            $this->selectedId = $id;
            $this->selectedName = $institution->institution_name;
            $this->search = '';
            $this->results = [];
            
            $this->dispatch('institucion-seleccionada-' . $this->target, id: $id);
        }
    }
    
    // ─── Modal para crear institución ─────────────────────────────────────────
    
    public function openModal(): void
    {
        $this->showModal = true;
        $this->resetModalFields();
    }
    
    public function createInstitution(): void
    {
        $this->authorize('create', Institution::class);

        $data = $this->validate($this->modalRules(), $this->modalMessages());
        
        $institution = Institution::create([
            'institution_name' => trim($data['modal_institution_name']),
            'institution_type' => $data['modal_institution_type'],
            'country' => $data['modal_institution_country'],
            'city' => $data['modal_institution_city'],
            'website' => $data['modal_institution_website'],
        ]);
        
        $this->select($institution->institution_id);
        $this->showModal = false;
        $this->resetModalFields();
        
        $this->dispatch('institucion-creada-' . $this->target, id: $institution->institution_id);
    }
    
    // ─── Eventos del padre ────────────────────────────────────────────────────
    
    #[On('cargar-institucion-{target}')]
    public function loadInstitution(int $id, string $name): void
    {
        $this->selectedId = $id;
        $this->selectedName = $name;
    }
    
    #[On('resetear-institucion')]
    public function resetear(): void
    {
        $this->selectedId = null;
        $this->selectedName = '';
        $this->search = '';
        $this->results = [];
    }
    
    // ─── Reglas y mensajes del modal ─────────────────────────────────────────
    
    private function modalRules(): array
    {
        return [
            'modal_institution_name' => ['required', 'string', 'max:255', 'unique:institution,institution_name'],
            'modal_institution_type' => ['nullable', 'string', 'max:50'],
            'modal_institution_country' => ['nullable', 'string', 'max:100'],
            'modal_institution_city' => ['nullable', 'string', 'max:100'],
            'modal_institution_website' => ['nullable', 'url:http,https', 'max:255'],
        ];
    }
    
    private function modalMessages(): array
    {
        return [
            'modal_institution_name.required' => 'El nombre de la institución es obligatorio.',
            'modal_institution_name.unique' => 'Ya existe una institución con este nombre.',
            'modal_institution_website.url' => 'Debe ser una URL válida (ej: https://ejemplo.com).',
        ];
    }
    
    private function resetModalFields(): void
    {
        $this->modal_institution_name = '';
        $this->modal_institution_type = null;
        $this->modal_institution_country = null;
        $this->modal_institution_city = null;
        $this->modal_institution_website = null;
        $this->resetErrorBag();
    }
    
    public function render()
    {
        return view('livewire.eventos.institucion-selector');
    }
}