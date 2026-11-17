<?php

namespace App\Livewire\Fichas;

use App\Livewire\Dashboard;
use App\Models\Researcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/** Ficha de solo lectura de un investigador: producción, gráfica por año, coautores y eventos. */
class FichaInvestigador extends Component
{
    public Researcher $researcher;

    public function mount(Researcher $researcher): void
    {
        $this->researcher = $researcher->load([
            'researchGroup.institution',
            'publications' => fn ($q) => $q->with(['type:type_id,type_name', 'article.journal:journal_issn,journal_name,category'])
                ->orderByDesc('publication_year')
                ->orderByDesc('publication.publication_id'),
            'events' => fn ($q) => $q->orderByDesc('event_year'),
        ]);
    }

    public function render()
    {
        $publicaciones = $this->researcher->publications;

        return view('livewire.fichas.investigador', [
            'publicaciones' => $publicaciones,
            'indicadores' => [
                ['titulo' => 'Publicaciones', 'valor' => $publicaciones->count()],
                ['titulo' => 'Artículos', 'valor' => $publicaciones->filter(fn ($p) => $p->article)->count()],
                ['titulo' => 'En revistas Q1 y Q2', 'valor' => $publicaciones->filter(fn ($p) => in_array($p->article?->journal?->category, ['Q1', 'Q2'], true))->count()],
                ['titulo' => 'Eventos', 'valor' => $this->researcher->events->count()],
            ],
            'grafica' => $this->graficaAnios($publicaciones),
            'coautores' => $this->coautores(),
        ])->layout('layouts.app');
    }

    private function graficaAnios(Collection $publicaciones): ?array
    {
        $porAnio = $publicaciones->whereNotNull('publication_year')->countBy('publication_year')->sortKeys();

        if ($porAnio->isEmpty()) {
            return null;
        }

        return [
            'type' => 'bar',
            'labels' => $porAnio->keys()->map(fn ($a) => (string) $a)->values(),
            'datasets' => [[
                'label' => 'Publicaciones',
                'data' => $porAnio->values(),
                'backgroundColor' => Dashboard::ROJO_OSCURO,
            ]],
        ];
    }

    /** Investigadores con los que más ha publicado. */
    private function coautores(): Collection
    {
        $conteo = DB::table('researcher_publication as propia')
            ->join('researcher_publication as otra', 'otra.publication_id', '=', 'propia.publication_id')
            ->where('propia.researcher_id', $this->researcher->researcher_id)
            ->where('otra.researcher_id', '!=', $this->researcher->researcher_id)
            ->selectRaw('otra.researcher_id, count(*) as total')
            ->groupBy('otra.researcher_id')
            ->orderByDesc('total')
            ->limit(6)
            ->pluck('total', 'researcher_id');

        $investigadores = Researcher::with('researchGroup:cod_minciencias,group_name')
            ->whereIn('researcher_id', $conteo->keys())
            ->get()
            ->keyBy('researcher_id');

        return $conteo->map(fn ($total, $id) => ['investigador' => $investigadores[$id] ?? null, 'total' => (int) $total])
            ->filter(fn ($c) => $c['investigador'])
            ->values();
    }
}
