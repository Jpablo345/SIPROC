<?php

namespace App\Livewire\Publicaciones;

use App\Models\Publication;
use App\Models\PublicationType;
use App\Models\ResearchGroup;
use App\Queries\Publicaciones\PublicationIndexQuery;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PublicationsExport;
use Barryvdh\DomPDF\Facade\Pdf;

class IndexPublicaciones extends Component
{
    use WithPagination;

    /**
     * Máximo de publicaciones por PDF. DomPDF tarda unos 6 s y usa unos 85 MB con 300 filas;
     * con 500 ya roza el memory_limit por defecto de PHP (128 MB). Para más, Excel.
     */
    public const PDF_MAX_ROWS = 300;

    // Filtros existentes
    public string $search = '';

    // Nuevos filtros
    public ?string $filterYear = '';
    public ?string $filterType = '';
    public ?string $filterGroup = '';
    public ?string $filterAuthor = '';

    // Estado del formulario
    public bool $showForm = false;
    public ?int $editingId = null;

    protected $listeners = ['publicacionGuardada' => 'handleSaved'];

    // Actualizar página cuando cambia cualquier filtro
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterYear(): void
    {
        $this->resetPage();
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    public function updatedFilterGroup(): void
    {
        $this->resetPage();
    }

    public function updatedFilterAuthor(): void
    {
        $this->resetPage();
    }

    // Limpiar todos los filtros
    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterYear = '';
        $this->filterType = '';
        $this->filterGroup = '';
        $this->filterAuthor = '';
        $this->resetPage();
    }

    public function getHasActiveFiltersProperty(): bool
    {
        return !empty($this->search) ||
            !empty($this->filterYear) ||
            !empty($this->filterType) ||
            !empty($this->filterGroup) ||
            !empty($this->filterAuthor);
    }

    public function abrirFormulario(): void
    {
        $this->editingId = null;
        $this->showForm = true;
    }

    public function editar(int $publicationId): void
    {
        $this->authorize('update', Publication::findOrFail($publicationId));
        $this->editingId = $publicationId;
        $this->showForm = true;
    }

    public function cerrarFormulario(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    public function eliminar(int $publicationId): void
    {
        //Publication::where('publication_id', $publicationId)->delete();
        $publication = Publication::findOrFail($publicationId);
        $this->authorize('delete', $publication);
        $publication->delete();
        $this->dispatch('notify', type: 'success', message: 'Publicación eliminada.');
        $this->resetPage();
    }

    public function handleSaved(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->resetPage();
    }

    /**
     * Propiedad computada base encargada de procesar todos los filtros
     */
    public function getPublicationsQueryProperty()
    {
        return app(PublicationIndexQuery::class)->build([
            'search' => $this->search,
            'year' => $this->filterYear,
            'type' => $this->filterType,
            'group' => $this->filterGroup,
            'author' => $this->filterAuthor,
        ]);
    }

    /**
     * Entrega las publicaciones paginadas para el renderizado
     */
    public function getPublicationsProperty()
    {
        return $this->getPublicationsQueryProperty()->paginate(10);
    }

    // Exportación a Excel
    public function exportExcel()
    {
        $this->authorize('viewAny', Publication::class);

        $publications = $this->getPublicationsQueryProperty()->get();
        $singleType = $this->filterType ? PublicationType::with('fields')->find((int) $this->filterType) : null;

        return Excel::download(new PublicationsExport($publications, $singleType), 'publicaciones_filtradas.xlsx');
    }

    // Exportación a PDF
    public function exportPdf()
    {
        $this->authorize('viewAny', Publication::class);

        $query = $this->getPublicationsQueryProperty();
        $total = (clone $query)->reorder()->count();

        if ($total > self::PDF_MAX_ROWS) {
            $this->dispatch(
                'notify',
                type: 'error',
                message: 'El PDF admite hasta ' . self::PDF_MAX_ROWS . " publicaciones y los filtros actuales dejan {$total}. Filtra un poco más o usa Exportar a Excel.",
            );

            return null;
        }

        $publications = $query->get();

        $pdf = Pdf::loadView('exports.publications-pdf', compact('publications'))
            ->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'publicaciones_filtradas.pdf');
    }

    // Obtener datos para los selects de filtros
    public function getYearsProperty()
    {
        return Publication::select('publication_year')
            ->distinct()
            ->orderBy('publication_year', 'desc')
            ->pluck('publication_year');
    }

    public function getTypesProperty()
    {
        return PublicationType::orderBy('type_name')->get();
    }

    public function getGroupsProperty()
    {
        return ResearchGroup::orderBy('group_name')->get();
    }

    public function render()
    {
        return view('publicaciones.index')->layout('layouts.app');
    }
}