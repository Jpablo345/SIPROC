<?php

namespace App\Livewire\Eventos;

use App\Livewire\Forms\ResearcherForm;
use App\Models\Institution;
use App\Models\ResearchGroup;
use App\Models\Researcher;
use Livewire\Attributes\Modelable;
use Livewire\Component;


class ConferencistaSelector extends Component
{
    public string $speakerSearch = '';
    public array $speakerResults = [];

    // #[Modelable] permite que el padre use wire:model="selectedSpeakers"
    #[Modelable]
    public array $selectedSpeakers = [];

    // ─── Modal para crear investigador ─────────────────────────────────────────
    public bool $showResearcherModal = false;

    public ResearcherForm $researcherForm;

    // ─── Búsqueda ─────────────────────────────────────────────────────────────

    public function updatedSpeakerSearch(): void
    {
        $term = trim($this->speakerSearch);

        if ($term === '') {
            $this->speakerResults = [];
            return;
        }

        $this->speakerResults = Researcher::query()
            ->with('researchGroup')
            ->search($term)
            ->orderBy('last_name_1')
            ->limit(8)
            ->get()
            ->map(fn (Researcher $r) => $r->toOption())
            ->toArray();
    }

    public function agregar(int $researcherId): void
    {
        foreach ($this->selectedSpeakers as $s) {
            if ((int) $s['researcher_id'] === $researcherId)
                return;
        }

        $researcher = Researcher::with('researchGroup')->find($researcherId);
        if (!$researcher)
            return;

        $this->selectedSpeakers[] = $researcher->toOption();

        $this->speakerSearch = '';
        $this->speakerResults = [];
    }

    public function quitar(int $index): void
    {
        unset($this->selectedSpeakers[$index]);
        $this->selectedSpeakers = array_values($this->selectedSpeakers);
    }

    // ─── Modal para crear investigador ────────────────────────────────────────

    public function openResearcherModal(): void
    {
        $this->researcherForm->reset();
        $this->researcherForm->resetValidation();
        $this->showResearcherModal = true;
    }

    public function crearYSeleccionar(): void
    {
        $researcher = $this->researcherForm->store();

        $this->agregar($researcher->researcher_id);

        $this->showResearcherModal = false;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function getGroupsProperty()
    {
        return ResearchGroup::orderBy('group_name')->get(['cod_minciencias', 'group_name']);
    }

    public function getInstitutionsProperty()
    {
        return Institution::orderBy('institution_name')->get(['institution_id', 'institution_name']);
    }

    public function render()
    {
        return view('livewire.eventos.conferencista-selector');
    }
}
