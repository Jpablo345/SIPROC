<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\Publication;
use App\Models\PublicationType;
use App\Models\ResearchGroup;
use App\Models\Researcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Panel general: indicadores, gráficas de producción y actividad reciente.
 * Los filtros de año y grupo quedan en la URL (?anio=2025&grupo=COL...) para poder compartir la vista.
 */
class Dashboard extends Component
{
    // Colores del manual UFPSO: rojos institucionales y colores de facultad (páginas 15 y 16).
    public const ROJO = '#D21116';
    public const ROJO_OSCURO = '#801418';
    public const SERIE = ['#165082', '#44B86C', '#62538A', '#F69144', '#A51F22', '#0B74BB'];
    public const GRIS = '#D1D5DB';

    #[Url(as: 'anio', except: '')]
    public string $year = '';

    #[Url(as: 'grupo', except: '')]
    public string $group = '';

    /** Producción por tipo (incluye tipos sin publicaciones). */
    public array $productionByType = [];

    public function mount(): void
    {
        $this->productionByType = $this->loadProductionByType();
    }

    public function limpiarFiltros(): void
    {
        $this->reset('year', 'group');
    }

    public function render()
    {
        $this->productionByType = $this->loadProductionByType();

        return view('livewire.dashboard', [
            'saludo' => $this->saludo(),
            'resumenMes' => $this->resumenMes(),
            'indicadores' => $this->indicadores(),
            'graficaAnios' => $this->graficaAnios(),
            'graficaTipos' => $this->graficaTipos(),
            'graficaGrupos' => $this->graficaGrupos(),
            'cuartiles' => $this->cuartiles(),
            'actividad' => $this->actividad(),
            'anios' => $this->aniosDisponibles(),
            'grupos' => ResearchGroup::orderBy('group_name')->get(['cod_minciencias', 'group_name']),
        ])->layout('layouts.app');
    }

    // ─── Consultas base ──────────────────────────────────────────────────────

    /** Publicaciones con los filtros activos. $anio permite consultar otro año (para comparar). */
    private function publicaciones(?int $anio = null, bool $conAnio = true): Builder
    {
        $anio ??= $this->year !== '' ? (int) $this->year : null;

        return Publication::query()
            ->when($conAnio && $anio, fn (Builder $q) => $q->where('publication_year', $anio))
            ->when($this->group !== '', fn (Builder $q) => $q->whereHas(
                'researchers',
                fn (Builder $r) => $r->where('researcher.cod_minciencias', $this->group)
            ));
    }

    private function eventos(?int $anio = null): Builder
    {
        $anio ??= $this->year !== '' ? (int) $this->year : null;

        return Event::query()
            ->when($anio, fn (Builder $q) => $q->where('event_year', $anio))
            ->when($this->group !== '', fn (Builder $q) => $q->whereHas(
                'researchers',
                fn (Builder $r) => $r->where('researcher.cod_minciencias', $this->group)
            ));
    }

    // ─── Encabezado ──────────────────────────────────────────────────────────

    private function saludo(): string
    {
        $hora = now()->hour;
        $momento = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
        $nombre = strtok((string) auth()->user()?->name, ' ') ?: '';

        return trim("$momento, $nombre", ', ');
    }

    private function resumenMes(): array
    {
        $inicio = now()->startOfMonth();

        return [
            'publicaciones' => Publication::where('created_at', '>=', $inicio)->count(),
            'investigadores' => Researcher::where('created_at', '>=', $inicio)->count(),
        ];
    }

    private function aniosDisponibles(): Collection
    {
        return Publication::query()
            ->whereNotNull('publication_year')
            ->distinct()
            ->orderByDesc('publication_year')
            ->pluck('publication_year');
    }

    // ─── Indicadores ─────────────────────────────────────────────────────────

    private function indicadores(): array
    {
        $anio = $this->year !== '' ? (int) $this->year : null;
        $actual = (int) now()->year;

        // Con un año elegido se compara con el anterior; sin filtro de año se muestra lo del año en curso.
        $comparar = function (int $valor, int $anterior) use ($anio): array {
            return ['delta' => $valor - $anterior, 'texto' => 'frente a '.($anio - 1)];
        };

        $publicaciones = $this->publicaciones()->count();
        $eventos = $this->eventos()->count();

        $investigadores = Researcher::query()
            ->when($this->group !== '', fn (Builder $q) => $q->where('cod_minciencias', $this->group))
            ->when($anio, fn (Builder $q) => $q->whereHas('publications', fn (Builder $p) => $p->where('publication_year', $anio)))
            ->count();

        return [
            [
                'titulo' => 'Publicaciones',
                'valor' => $publicaciones,
                'icono' => 'book-open',
                'color' => 'bg-siproc-rose text-siproc-red dark:bg-red-500/15 dark:text-red-300',
                'pie' => $anio
                    ? $comparar($publicaciones, $this->publicaciones($anio - 1)->count())
                    : ['valor' => $this->publicaciones($actual)->count(), 'texto' => "en $actual"],
            ],
            [
                'titulo' => 'Artículos científicos',
                'valor' => $this->publicaciones()->has('article')->count(),
                'icono' => 'newspaper',
                'color' => 'bg-fac-ing/10 text-fac-ing dark:bg-sky-500/15 dark:text-sky-300',
                'pie' => [
                    'valor' => $this->publicaciones()->whereHas('article.journal', fn (Builder $j) => $j->whereIn('category', ['Q1', 'Q2']))->count(),
                    'texto' => 'en revistas Q1 y Q2',
                ],
            ],
            [
                'titulo' => 'Investigadores',
                'valor' => $investigadores,
                'icono' => 'users',
                'color' => 'bg-fac-agr/15 text-fac-agr-dark dark:bg-emerald-500/15 dark:text-emerald-300',
                'pie' => $anio
                    ? ['texto' => "con publicaciones en $anio"]
                    : ['valor' => $this->group !== '' ? 1 : ResearchGroup::count(), 'texto' => $this->group !== '' ? 'grupo seleccionado' : 'grupos de investigación'],
            ],
            [
                'titulo' => 'Eventos',
                'valor' => $eventos,
                'icono' => 'megaphone',
                'color' => 'bg-fac-edu/15 text-fac-edu-dark dark:bg-orange-500/15 dark:text-orange-300',
                'pie' => $anio
                    ? $comparar($eventos, $this->eventos($anio - 1)->count())
                    : ['valor' => $this->eventos($actual)->count(), 'texto' => "en $actual"],
            ],
        ];
    }

    // ─── Gráficas ────────────────────────────────────────────────────────────

    private function graficaAnios(): ?array
    {
        $porAnio = $this->publicaciones(conAnio: false)
            ->whereNotNull('publication_year')
            ->selectRaw('publication_year as anio, count(*) as total')
            ->groupBy('publication_year')
            ->orderByDesc('publication_year')
            ->limit(10)
            ->pluck('total', 'anio')
            ->sortKeys();

        if ($porAnio->isEmpty()) {
            return null;
        }

        // El año elegido (o el más reciente) va en el rojo institucional; los demás en rojo oscuro.
        $resaltado = $this->year !== '' ? (int) $this->year : $porAnio->keys()->last();

        return [
            'type' => 'bar',
            'labels' => $porAnio->keys()->map(fn ($a) => (string) $a)->values(),
            'datasets' => [[
                'label' => 'Publicaciones',
                'data' => $porAnio->values(),
                'backgroundColor' => $porAnio->keys()->map(fn ($a) => (int) $a === (int) $resaltado ? self::ROJO : self::ROJO_OSCURO)->values(),
            ]],
        ];
    }

    private function loadProductionByType(): array
    {
        $anio = $this->year !== '' ? (int) $this->year : null;

        return PublicationType::query()
            ->withCount(['publications' => function (Builder $q) use ($anio) {
                $q->when($anio, fn (Builder $p) => $p->where('publication_year', $anio))
                    ->when($this->group !== '', fn (Builder $p) => $p->whereHas(
                        'researchers',
                        fn (Builder $r) => $r->where('researcher.cod_minciencias', $this->group)
                    ));
            }])
            ->orderByDesc('publications_count')
            ->orderBy('type_name')
            ->get()
            ->map(fn (PublicationType $type) => [
                'name' => $type->type_name,
                'count' => $type->publications_count,
                'is_system' => $type->is_system,
            ])
            ->toArray();
    }

    private function graficaTipos(): ?array
    {
        $tipos = collect($this->productionByType)->where('count', '>', 0)->values();

        if ($tipos->isEmpty()) {
            return null;
        }

        // Hasta cinco tipos con color propio; el resto se agrupa en "Otros".
        $principales = $tipos->take(5);
        $otros = $tipos->slice(5)->sum('count');
        $etiquetas = $principales->pluck('name');
        $valores = $principales->pluck('count');
        $colores = collect(self::SERIE)->take($principales->count());

        if ($otros > 0) {
            $etiquetas->push('Otros');
            $valores->push($otros);
            $colores->push(self::GRIS);
        }

        return [
            'type' => 'doughnut',
            'legend' => true,
            'labels' => $etiquetas->values(),
            'datasets' => [['data' => $valores->values(), 'backgroundColor' => $colores->values()]],
        ];
    }

    private function graficaGrupos(): ?array
    {
        $anio = $this->year !== '' ? (int) $this->year : null;

        $grupos = ResearchGroup::query()
            ->select('research_group.cod_minciencias', 'research_group.group_name')
            ->selectSub(
                DB::table('researcher_publication')
                    ->join('researcher', 'researcher.researcher_id', '=', 'researcher_publication.researcher_id')
                    ->join('publication', 'publication.publication_id', '=', 'researcher_publication.publication_id')
                    ->whereColumn('researcher.cod_minciencias', 'research_group.cod_minciencias')
                    ->when($anio, fn ($q) => $q->where('publication.publication_year', $anio))
                    ->selectRaw('count(distinct researcher_publication.publication_id)'),
                'total'
            )
            ->orderByDesc('total')
            ->orderBy('research_group.group_name')
            ->limit(8)
            ->get()
            ->filter(fn ($g) => (int) $g->total > 0)
            ->values();

        if ($grupos->isEmpty()) {
            return null;
        }

        return [
            'type' => 'bar',
            'horizontal' => true,
            'labels' => $grupos->pluck('group_name'),
            'datasets' => [[
                'label' => 'Publicaciones',
                'data' => $grupos->pluck('total')->map(fn ($t) => (int) $t),
                'backgroundColor' => $grupos->map(fn ($g) => $g->cod_minciencias === $this->group ? self::ROJO : self::SERIE[0]),
            ]],
        ];
    }

    /** Artículos por cuartil de la revista (Q1 a Q5 y sin categoría). */
    private function cuartiles(): array
    {
        $conteo = $this->publicaciones()
            ->join('article', 'article.publication_id', '=', 'publication.publication_id')
            ->leftJoin('journal', 'journal.journal_issn', '=', 'article.journal_issn')
            ->selectRaw("coalesce(nullif(journal.category, ''), 'Sin categoría') as categoria, count(*) as total")
            ->groupBy('categoria')
            ->pluck('total', 'categoria');

        $orden = ['Q1', 'Q2', 'Q3', 'Q4', 'Q5', 'Sin categoría'];
        $total = $conteo->sum();

        return [
            'total' => $total,
            'items' => collect($orden)
                ->filter(fn ($c) => ($conteo[$c] ?? 0) > 0)
                ->map(fn ($c) => [
                    'categoria' => $c,
                    'total' => (int) $conteo[$c],
                    'porcentaje' => $total ? round($conteo[$c] * 100 / $total, 1) : 0,
                    'clase' => \App\Support\Quartile::barClass($c),
                ])
                ->values(),
        ];
    }

    // ─── Actividad reciente ─────────────────────────────────────────────────

    private function actividad(): Collection
    {
        $publicaciones = Publication::query()
            ->with(['type:type_id,type_name', 'creator:id,name'])
            ->whereNotNull('created_at')
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(fn (Publication $p) => [
                'quien' => $p->creator?->name,
                'accion' => 'registró '.$this->articuloTipo($p->type?->type_name),
                'que' => $p->title,
                'url' => route('publicaciones.show', $p->publication_id),
                'cuando' => $p->created_at,
            ]);

        $investigadores = Researcher::query()
            ->with('creator:id,name')
            ->whereNotNull('created_at')
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(fn (Researcher $r) => [
                'quien' => $r->creator?->name,
                'accion' => 'agregó al investigador',
                'que' => $r->full_name,
                'url' => route('investigadores.show', $r->researcher_id),
                'cuando' => $r->created_at,
            ]);

        $eventos = Event::query()
            ->with('creator:id,name')
            ->whereNotNull('created_at')
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(fn (Event $e) => [
                'quien' => $e->creator?->name,
                'accion' => 'registró el evento',
                'que' => $e->event_name,
                'url' => null,
                'cuando' => $e->created_at,
            ]);

        return $publicaciones->concat($investigadores)->concat($eventos)
            ->sortByDesc('cuando')
            ->take(6)
            ->values();
    }

    private function articuloTipo(?string $tipo): string
    {
        return match (PublicationType::normalizeName((string) $tipo)) {
            'articulo' => 'el artículo',
            'libro' => 'el libro',
            '' => 'la publicación',
            default => 'la publicación de tipo '.$tipo,
        };
    }
}
