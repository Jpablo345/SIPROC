<?php

namespace Tests\Feature;

use App\Livewire\BusquedaGlobal;
use App\Livewire\Dashboard;
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

/**
 * Fichas de investigador, grupo y publicación; búsqueda global; filtros del dashboard.
 */
class InterfazInstitucionalTest extends TestCase
{
    use RefreshDatabase;

    private User $docente;
    private ResearchGroup $grupo;
    private Researcher $investigador;
    private Publication $publicacion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->docente = User::factory()->teacher()->withRole('Docente')->create(['name' => 'Diego Pacheco']);
        $this->actingAs($this->docente);

        $tipo = PublicationType::factory()->create(['type_name' => 'Software registrado']);
        $this->grupo = ResearchGroup::factory()->create(['group_name' => 'Ingeniería de Software']);
        $this->investigador = Researcher::factory()->create([
            'name_1' => 'Yeimy', 'name_2' => null, 'last_name_1' => 'Carrascal', 'last_name_2' => null,
            'cod_minciencias' => $this->grupo->cod_minciencias,
        ]);
        $this->publicacion = Publication::factory()->create([
            'title' => 'Plataforma para el seguimiento de egresados',
            'publication_year' => 2025,
            'type_id' => $tipo->type_id,
        ]);
        ResearcherPublication::factory()->create([
            'publication_id' => $this->publicacion->publication_id,
            'researcher_id' => $this->investigador->researcher_id,
            'author_order' => 1,
        ]);
    }

    // ─── Fichas ──────────────────────────────────────────────────────────────

    public function test_profile_pages_show_their_data(): void
    {
        $this->get(route('publicaciones.show', $this->publicacion))
            ->assertOk()
            ->assertSee('Plataforma para el seguimiento de egresados')
            ->assertSee('Yeimy Carrascal')
            ->assertSee('Ingeniería de Software');

        $this->get(route('investigadores.show', $this->investigador))
            ->assertOk()
            ->assertSee('Yeimy Carrascal')
            ->assertSee('Plataforma para el seguimiento de egresados');

        $this->get(route('estructura.grupos.show', $this->grupo))
            ->assertOk()
            ->assertSee('Ingeniería de Software')
            ->assertSee('Yeimy Carrascal');
    }

    public function test_profile_pages_require_the_consultar_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('publicaciones.show', $this->publicacion))->assertForbidden();
        $this->get(route('investigadores.show', $this->investigador))->assertForbidden();
        $this->get(route('estructura.grupos.show', $this->grupo))->assertForbidden();
    }

    public function test_unknown_records_return_404(): void
    {
        $this->get('/publicaciones/999999')->assertNotFound();
        $this->get('/investigadores/999999')->assertNotFound();
    }

    public function test_the_edit_link_opens_the_form_only_for_who_can_edit(): void
    {
        $propia = Publication::factory()->create(['created_by' => $this->docente->id]);
        $ajena = Publication::factory()->create(['created_by' => User::factory()->create()->id]);

        Livewire::withQueryParams(['editar' => $propia->publication_id])
            ->test(IndexPublicaciones::class)
            ->assertSet('showForm', true)
            ->assertSet('editingId', $propia->publication_id);

        Livewire::withQueryParams(['editar' => $ajena->publication_id])
            ->test(IndexPublicaciones::class)
            ->assertSet('showForm', false);
    }

    // ─── Búsqueda global ─────────────────────────────────────────────────────

    public function test_global_search_finds_publications_researchers_and_groups(): void
    {
        $resultados = Livewire::test(BusquedaGlobal::class)->set('q', 'egresados')->instance()->resultados;
        $this->assertSame(['Publicaciones'], array_keys($resultados));
        $this->assertSame(route('publicaciones.show', $this->publicacion), $resultados['Publicaciones'][0]['url']);

        $resultados = Livewire::test(BusquedaGlobal::class)->set('q', 'carrascal')->instance()->resultados;
        $this->assertSame('Yeimy Carrascal', $resultados['Investigadores'][0]['titulo']);

        $resultados = Livewire::test(BusquedaGlobal::class)->set('q', 'ingeniería de soft')->instance()->resultados;
        $this->assertSame('Ingeniería de Software', $resultados['Grupos de investigación'][0]['titulo']);
    }

    public function test_global_search_needs_two_letters_and_permission(): void
    {
        $this->assertSame([], Livewire::test(BusquedaGlobal::class)->set('q', 'e')->instance()->resultados);

        $this->actingAs(User::factory()->create());
        $this->assertSame([], Livewire::test(BusquedaGlobal::class)->set('q', 'egresados')->instance()->resultados);
    }

    public function test_global_search_escapes_html_in_highlights(): void
    {
        Publication::factory()->create(['title' => '<script>alert(1)</script> egresados']);

        Livewire::test(BusquedaGlobal::class)
            ->set('q', 'egresados')
            ->assertDontSeeHtml('<script>alert(1)</script>')
            ->assertSeeHtml('&lt;script&gt;');
    }

    // ─── Dashboard ───────────────────────────────────────────────────────────

    public function test_dashboard_filters_by_year_and_group(): void
    {
        Publication::factory()->create(['publication_year' => 2024]);

        Livewire::test(Dashboard::class)
            ->assertViewHas('indicadores', fn ($i) => $i[0]['valor'] === 2)
            ->set('year', '2025')
            ->assertViewHas('indicadores', fn ($i) => $i[0]['valor'] === 1 && $i[0]['pie']['delta'] === 0)
            ->set('year', '')
            ->set('group', $this->grupo->cod_minciencias)
            ->assertViewHas('indicadores', fn ($i) => $i[0]['valor'] === 1)
            ->call('limpiarFiltros')
            ->assertSet('year', '')
            ->assertSet('group', '');
    }

    public function test_dashboard_shows_recent_activity_with_links(): void
    {
        Livewire::test(Dashboard::class)
            ->assertViewHas('actividad', fn ($a) => $a->contains(fn ($e) => $e['url'] === route('publicaciones.show', $this->publicacion)))
            ->assertSee('Panel general');
    }
}
