<?php

namespace App\Livewire;

use App\Models\Article;
use App\Models\Book;
use App\Models\Event;
use App\Models\Publication;
use App\Models\PublicationType;
use App\Models\ResearchGroup;
use App\Models\Researcher;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public int $totalPublications = 0;
    public int $totalResearchers  = 0;
    public int $totalGroups       = 0;
    public int $totalArticles     = 0;
    public int $totalBooks        = 0;
    public int $totalEvents       = 0;

    public array $recentPublications = [];
    public array $productionByGroup  = [];
    public array $productionByType   = [];

    public function mount(): void
    {
        $this->loadStats();
        $this->loadRecentPublications();
        $this->loadProductionByGroup();
        $this->loadProductionByType();
    }

    private function loadProductionByType(): void
    {
        // Todos los tipos, incluidos los configurables y los que aún no tienen publicaciones.
        $this->productionByType = PublicationType::query()
            ->withCount('publications')
            ->orderByDesc('publications_count')
            ->orderBy('type_name')
            ->get()
            ->map(fn (PublicationType $type) => [
                'name'      => $type->type_name,
                'count'     => $type->publications_count,
                'is_system' => $type->is_system,
            ])
            ->toArray();
    }

    private function loadStats(): void
    {
        $this->totalPublications = Publication::count();
        $this->totalResearchers  = Researcher::count();
        $this->totalGroups       = ResearchGroup::count();
        $this->totalArticles     = Article::count();
        $this->totalBooks        = Book::count();
        $this->totalEvents       = Event::count();
    }

    private function loadRecentPublications(): void
    {
        $this->recentPublications = Publication::query()
            ->with([
                'type',
                'researchers',
                'article.journal',
                'book',
            ])
            ->orderByDesc('publication_id')
            ->limit(5)
            ->get()
            ->map(function (Publication $pub) {
                $firstAuthor = $pub->researchers
                    ->sortBy(fn($r) => $r->pivot->author_order ?? 999)
                    ->first();

                $authorLabel = $firstAuthor
                    ? trim("{$firstAuthor->last_name_1}, {$firstAuthor->name_1}")
                    : null;

                if ($pub->article) {
                    $detail = implode(' · ', array_filter([
                        $authorLabel,
                        $pub->article->journal?->journal_name,
                        $pub->publication_year,
                    ]));
                } elseif ($pub->book) {
                    $detail = implode(' · ', array_filter([
                        $authorLabel,
                        $pub->book->editorial,
                        $pub->publication_year,
                    ]));
                } else {
                    $detail = implode(' · ', array_filter([
                        $authorLabel,
                        $pub->publication_year,
                    ]));
                }

                return [
                    'publication_id' => $pub->publication_id,
                    'title'          => $pub->title,
                    'type'           => $pub->type?->type_name ?? 'Otro',
                    'detail'         => $detail ?: '—',
                ];
            })
            ->toArray();
    }

    private function loadProductionByGroup(): void
    {
        // Cuenta publicaciones únicas por grupo a través de:
        // research_group → researcher (cod_minciencias) → researcher_publication → publication
        $this->productionByGroup = ResearchGroup::query()
            ->select('research_group.cod_minciencias', 'research_group.group_name')
            ->selectSub(
                DB::table('researcher_publication')
                    ->join('researcher', 'researcher.researcher_id', '=', 'researcher_publication.researcher_id')
                    ->whereColumn('researcher.cod_minciencias', 'research_group.cod_minciencias')
                    ->selectRaw('count(distinct researcher_publication.publication_id)'),
                'publication_count'
            )
            ->orderByDesc('publication_count')
            ->limit(6)
            ->get()
            ->map(fn($g) => [
                'name'  => $g->group_name,
                'count' => (int) $g->publication_count,
            ])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.dashboard')->layout('layouts.app');
    }
}