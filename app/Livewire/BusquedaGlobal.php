<?php

namespace App\Livewire;

use App\Models\Journal;
use App\Models\Publication;
use App\Models\ResearchGroup;
use App\Models\Researcher;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Búsqueda global (Ctrl+K o botón "Buscar" del menú): publicaciones, investigadores, grupos y revistas.
 * Abrir y cerrar se maneja en el navegador con Alpine; aquí solo se calculan los resultados.
 */
class BusquedaGlobal extends Component
{
    public const POR_SECCION = 5;

    public string $q = '';

    #[Computed]
    public function resultados(): array
    {
        $termino = trim($this->q);

        if (mb_strlen($termino) < 2 || ! auth()->user()?->can('consultar')) {
            return [];
        }

        $like = '%'.$termino.'%';

        $secciones = [
            'Publicaciones' => Publication::query()
                ->with('type:type_id,type_name')
                ->where('title', 'ilike', $like)
                ->orderByDesc('publication_year')
                ->limit(self::POR_SECCION)
                ->get()
                ->map(fn (Publication $p) => [
                    'titulo' => $p->title,
                    'detalle' => collect([$p->type?->type_name, $p->publication_year])->filter()->implode(' · '),
                    'url' => route('publicaciones.show', $p->publication_id),
                    'icono' => 'document-text',
                ]),

            'Investigadores' => Researcher::query()
                ->with('researchGroup:cod_minciencias,group_name')
                ->search($termino)
                ->orderBy('last_name_1')
                ->limit(self::POR_SECCION)
                ->get()
                ->map(fn (Researcher $r) => [
                    'titulo' => $r->full_name,
                    'detalle' => $r->researchGroup?->group_name ?? 'Sin grupo',
                    'url' => route('investigadores.show', $r->researcher_id),
                    'icono' => 'users',
                ]),

            'Grupos de investigación' => ResearchGroup::query()
                ->where(fn ($q) => $q->where('group_name', 'ilike', $like)->orWhere('cod_minciencias', 'ilike', $like))
                ->orderBy('group_name')
                ->limit(self::POR_SECCION)
                ->get()
                ->map(fn (ResearchGroup $g) => [
                    'titulo' => $g->group_name,
                    'detalle' => 'Código '.$g->cod_minciencias,
                    'url' => route('estructura.grupos.show', $g->cod_minciencias),
                    'icono' => 'user-group',
                ]),

            'Revistas' => Journal::query()
                ->where(fn ($q) => $q->where('journal_name', 'ilike', $like)->orWhere('journal_issn', 'ilike', $like))
                ->orderBy('journal_name')
                ->limit(self::POR_SECCION)
                ->get()
                ->map(fn (Journal $j) => [
                    'titulo' => $j->journal_name,
                    'detalle' => collect(['ISSN '.$j->journal_issn, $j->category])->filter()->implode(' · '),
                    'url' => route('publicaciones.journals'),
                    'icono' => 'newspaper',
                ]),
        ];

        return collect($secciones)->filter(fn ($items) => $items->isNotEmpty())->map->all()->all();
    }

    /** Accesos rápidos que se muestran mientras no hay texto. */
    public function accesos(): array
    {
        return [
            ['titulo' => 'Panel general', 'url' => route('dashboard'), 'icono' => 'home'],
            ['titulo' => 'Publicaciones', 'url' => route('publicaciones.index'), 'icono' => 'document-text'],
            ['titulo' => 'Investigadores', 'url' => route('investigadores'), 'icono' => 'users'],
            ['titulo' => 'Grupos de investigación', 'url' => route('estructura.grupos'), 'icono' => 'user-group'],
            ['titulo' => 'Tipos de publicación', 'url' => route('publicaciones.tipos'), 'icono' => 'tag'],
            ['titulo' => 'Revistas', 'url' => route('publicaciones.journals'), 'icono' => 'newspaper'],
        ];
    }

    public function resaltar(string $texto): string
    {
        $termino = trim($this->q);
        $seguro = e($texto);

        if ($termino === '') {
            return $seguro;
        }

        return preg_replace('/('.preg_quote(e($termino), '/').')/iu', '<mark class="rounded bg-siproc-rose px-0.5 text-inherit dark:bg-red-500/30">$1</mark>', $seguro) ?? $seguro;
    }

    public function render()
    {
        return view('livewire.busqueda-global', ['accesos' => $this->accesos()]);
    }
}
