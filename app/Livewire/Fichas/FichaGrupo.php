<?php

namespace App\Livewire\Fichas;

use App\Livewire\Dashboard;
use App\Models\Publication;
use App\Models\ResearchGroup;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

/** Ficha de solo lectura de un grupo de investigación: integrantes, producción y evolución. */
class FichaGrupo extends Component
{
    public ResearchGroup $group;

    public function mount(ResearchGroup $group): void
    {
        $this->group = $group->load([
            'institution',
            'researchers' => fn ($q) => $q->withCount('publications')->orderByDesc('publications_count')->orderBy('last_name_1'),
        ]);
    }

    private function publicaciones(): Builder
    {
        return Publication::query()->whereHas(
            'researchers',
            fn (Builder $r) => $r->where('researcher.cod_minciencias', $this->group->cod_minciencias)
        );
    }

    public function render()
    {
        $porAnio = $this->publicaciones()
            ->whereNotNull('publication_year')
            ->selectRaw('publication_year as anio, count(*) as total')
            ->groupBy('publication_year')
            ->orderBy('publication_year')
            ->pluck('total', 'anio');

        $porTipo = $this->publicaciones()
            ->join('publication_type', 'publication_type.type_id', '=', 'publication.type_id')
            ->selectRaw('publication_type.type_name as tipo, count(*) as total')
            ->groupBy('publication_type.type_name')
            ->orderByDesc('total')
            ->pluck('total', 'tipo');

        return view('livewire.fichas.grupo', [
            'indicadores' => [
                ['titulo' => 'Integrantes', 'valor' => $this->group->researchers->count()],
                ['titulo' => 'Publicaciones', 'valor' => $this->publicaciones()->count()],
                ['titulo' => 'Artículos', 'valor' => $this->publicaciones()->has('article')->count()],
                ['titulo' => 'En revistas Q1 y Q2', 'valor' => $this->publicaciones()->whereHas('article.journal', fn (Builder $j) => $j->whereIn('category', ['Q1', 'Q2']))->count()],
            ],
            'graficaAnios' => $porAnio->isEmpty() ? null : [
                'type' => 'bar',
                'labels' => $porAnio->keys()->map(fn ($a) => (string) $a)->values(),
                'datasets' => [['label' => 'Publicaciones', 'data' => $porAnio->values(), 'backgroundColor' => Dashboard::ROJO_OSCURO]],
            ],
            'graficaTipos' => $porTipo->isEmpty() ? null : [
                'type' => 'doughnut',
                'legend' => true,
                'labels' => $porTipo->keys()->values(),
                'datasets' => [[
                    'data' => $porTipo->values(),
                    'backgroundColor' => $porTipo->keys()->values()->map(fn ($t, $i) => Dashboard::SERIE[$i % count(Dashboard::SERIE)]),
                ]],
            ],
            'recientes' => $this->publicaciones()
                ->with('type:type_id,type_name')
                ->orderByDesc('publication_year')
                ->orderByDesc('publication.publication_id')
                ->limit(8)
                ->get(),
        ])->layout('layouts.app');
    }
}
