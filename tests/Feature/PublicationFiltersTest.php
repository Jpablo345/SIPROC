<?php

namespace Tests\Feature;

use App\Livewire\Publicaciones\IndexPublicaciones;
use App\Models\Publication;
use App\Models\PublicationType;
use App\Models\ResearchGroup;
use App\Models\Researcher;
use App\Models\ResearcherPublication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicationFiltersTest extends TestCase
{
    use RefreshDatabase;

    private PublicationType $articleType;
    private PublicationType $bookType;
    private ResearchGroup $groupA;
    private ResearchGroup $groupB;
    private Publication $articlePublication;
    private Publication $bookPublication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->teacher()->withRole('Docente')->create());

        $this->articleType = PublicationType::factory()->create(['type_name' => 'Tipo filtro artículo']);
        $this->bookType = PublicationType::factory()->create(['type_name' => 'Tipo filtro libro']);
        $this->groupA = ResearchGroup::factory()->create();
        $this->groupB = ResearchGroup::factory()->create();

        $this->articlePublication = $this->publicationFor($this->articleType, $this->groupA, 'Publicación del grupo A');
        $this->bookPublication = $this->publicationFor($this->bookType, $this->groupB, 'Publicación del grupo B');
    }

    private function publicationFor(PublicationType $type, ResearchGroup $group, string $title): Publication
    {
        $publication = Publication::factory()->create(['type_id' => $type->type_id, 'title' => $title]);
        $researcher = Researcher::factory()->create(['cod_minciencias' => $group->cod_minciencias]);

        ResearcherPublication::factory()->create([
            'publication_id' => $publication->publication_id,
            'researcher_id' => $researcher->researcher_id,
            'author_order' => 1,
        ]);

        return $publication;
    }

    public function test_filter_options_use_the_real_keys(): void
    {
        Livewire::test(IndexPublicaciones::class)
            ->assertSeeHtml('<option value="' . $this->articleType->type_id . '">')
            ->assertSeeHtml('<option value="' . $this->groupA->cod_minciencias . '">');
    }

    public function test_filter_by_type(): void
    {
        Livewire::test(IndexPublicaciones::class)
            ->set('filterType', (string) $this->articleType->type_id)
            ->assertSee($this->articlePublication->title)
            ->assertDontSee($this->bookPublication->title)
            ->assertSee('Tipo: ' . $this->articleType->type_name);
    }

    public function test_pdf_export_downloads_within_the_limit(): void
    {
        Livewire::test(IndexPublicaciones::class)
            ->call('exportPdf')
            ->assertFileDownloaded('publicaciones_filtradas.pdf');
    }

    public function test_pdf_export_refuses_more_rows_than_the_limit(): void
    {
        Publication::factory()->count(IndexPublicaciones::PDF_MAX_ROWS - 1)->create();

        Livewire::test(IndexPublicaciones::class)
            ->call('exportPdf')
            ->assertDispatched('notify', type: 'error')
            ->assertNoFileDownloaded();
    }

    public function test_filter_by_group(): void
    {
        Livewire::test(IndexPublicaciones::class)
            ->set('filterGroup', $this->groupB->cod_minciencias)
            ->assertSee($this->bookPublication->title)
            ->assertDontSee($this->articlePublication->title)
            ->assertSee('Grupo: ' . $this->groupB->group_name);
    }
}
