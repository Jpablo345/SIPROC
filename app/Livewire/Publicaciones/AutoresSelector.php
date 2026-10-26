<?php

namespace App\Livewire\Publicaciones;

use App\Livewire\Forms\ResearcherForm;
use App\Models\Institution;
use App\Models\ResearchGroup;
use App\Models\Researcher;
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

    // Modal "crear investigador"
    public ResearcherForm $researcherForm;

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

        $this->authorResults = Researcher::query()
            ->with('researchGroup')
            ->search($term)
            ->orderBy('last_name_1')
            ->limit(8)
            ->get()
            ->map(fn (Researcher $r) => $r->toOption())
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

        $this->selectedAuthors[] = $researcher->toOption();

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
        $researcher = $this->researcherForm->store();

        $this->agregarAutor((string) $researcher->researcher_id);

        $this->dispatch('close-modal', 'crear-investigador-modal');
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

    public function render()
    {
        return view('livewire.publicaciones.autores-selector');
    }
}
