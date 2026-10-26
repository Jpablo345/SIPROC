<?php

namespace Tests\Feature;

use App\Http\Middleware\SetDatabaseAuditUser;
use App\Livewire\Publicaciones\AutoresSelector;
use App\Livewire\Publicaciones\FormularioPublicacion;
use App\Livewire\Publicaciones\Journals;
use App\Models\Article;
use App\Models\Event;
use App\Models\Publication;
use App\Models\Researcher;
use App\Models\User;
use App\Queries\Publicaciones\PublicationIndexQuery;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class DataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withRole('Estudiante')->create();
        $this->actingAs($this->user);
    }

    // ─── Eventos y ponentes ─────────────────────────────────────────────────

    public function test_event_and_speakers_are_saved_together(): void
    {
        $speakers = Researcher::factory()->count(2)->create();

        Volt::test('tipologia.generacion.eventos')
            ->set('event_name', 'Congreso de prueba')
            ->set('event_year', '2025')
            ->set('participation_type', 'Organizador')
            ->set('selectedSpeakers', $speakers->map(fn ($r) => ['researcher_id' => $r->researcher_id, 'name' => 'x', 'group' => null])->all())
            ->call('save')
            ->assertHasNoErrors();

        $event = Event::where('event_name', 'Congreso de prueba')->firstOrFail();

        $this->assertSame(2025, $event->event_year);
        $this->assertEqualsCanonicalizing(
            $speakers->pluck('researcher_id')->all(),
            $event->researcherEvents()->pluck('researcher_id')->all()
        );
        $this->assertSame(['Organizador'], $event->researcherEvents()->distinct()->pluck('participation_type')->all());
    }

    public function test_event_rejects_participation_types_the_database_does_not_allow(): void
    {
        Volt::test('tipologia.generacion.eventos')
            ->set('event_name', 'Congreso')
            ->set('participation_type', 'Panelista')
            ->call('save')
            ->assertHasErrors('participation_type');

        $this->assertDatabaseCount('event', 0);
    }

    public function test_event_rejects_unknown_speakers(): void
    {
        Volt::test('tipologia.generacion.eventos')
            ->set('event_name', 'Congreso')
            ->set('selectedSpeakers', [['researcher_id' => 999999, 'name' => 'x', 'group' => null]])
            ->call('save')
            ->assertHasErrors('selectedSpeakers.0.researcher_id');

        $this->assertDatabaseCount('event', 0);
    }

    // ─── URLs ────────────────────────────────────────────────────────────────

    public function test_javascript_urls_are_rejected_by_validation(): void
    {
        foreach (['articulos-cientificos', 'libros-investigacion', 'otras-publicaciones'] as $component) {
            Volt::test("tipologia.generacion.{$component}")
                ->set('title', 'Título')
                ->set('url', 'javascript:alert(1)')
                ->call('save')
                ->assertHasErrors('url');
        }

        $this->assertDatabaseCount('publication', 0);
    }

    public function test_javascript_urls_are_rejected_by_the_database(): void
    {
        $this->expectException(QueryException::class);

        DB::table('publication')->insert(['title' => 'x', 'url' => 'javascript:alert(1)']);
    }

    // ─── Autores ────────────────────────────────────────────────────────────

    public function test_author_search_by_number_works_on_postgres(): void
    {
        $researcher = Researcher::factory()->create(['document' => '1094567']);

        Livewire::test(AutoresSelector::class)
            ->set('authorSearch', '1094')
            ->assertSet('authorResults.0.researcher_id', $researcher->researcher_id);

        Livewire::test(AutoresSelector::class)
            ->set('authorSearch', (string) $researcher->researcher_id)
            ->assertCount('authorResults', 1);
    }

    public function test_the_same_author_can_not_be_added_twice(): void
    {
        $researcher = Researcher::factory()->create();

        Livewire::test(FormularioPublicacion::class)
            ->call('agregarAutor', (string) $researcher->researcher_id)
            ->call('agregarAutor', (string) $researcher->researcher_id)
            ->assertCount('selectedAuthors', 1);
    }

    public function test_researchers_page_uses_the_document_column(): void
    {
        Volt::test('investigadores')
            ->set('document', '555666')
            ->set('name_1', 'Ana')
            ->set('last_name_1', 'Paz')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('researcher', ['document' => '555666', 'name_1' => 'Ana']);

        Volt::test('investigadores')
            ->set('document', '555666')
            ->set('name_1', 'Otra')
            ->set('last_name_1', 'Persona')
            ->call('save')
            ->assertHasErrors(['document' => 'unique']);
    }

    // ─── Borrados bloqueados por FK restrict ────────────────────────────────

    public function test_a_journal_in_use_is_not_deleted(): void
    {
        $this->actingAs(User::factory()->withRole('Administrador')->create());
        $article = Article::factory()->create();

        Livewire::test(Journals::class)->call('delete', $article->journal_issn);

        $this->assertDatabaseHas('journal', ['journal_issn' => $article->journal_issn]);
    }

    // ─── Años, timestamps, auditoría y prune (P3) ───────────────────────────

    public function test_publications_can_be_searched_and_filtered_by_year(): void
    {
        Publication::factory()->create(['publication_year' => '2021']);
        Publication::factory()->create(['publication_year' => '2019']);

        $query = app(PublicationIndexQuery::class);

        $this->assertSame(1, $query->build(['year' => '2021'])->count());
        $this->assertSame(1, $query->build(['search' => '2019'])->count());
        $this->assertSame(2021, Publication::where('publication_year', 2021)->value('publication_year'));
    }

    public function test_domain_records_get_timestamps(): void
    {
        $publication = Publication::factory()->create();

        $this->assertNotNull($publication->created_at);
        $this->assertNotNull($publication->updated_at);
    }

    public function test_audit_trigger_records_the_application_user(): void
    {
        $researcher = Researcher::factory()->create();

        $request = Request::create('/investigadores');
        $request->setUserResolver(fn () => $this->user);
        (new SetDatabaseAuditUser())->handle($request, fn () => response('ok'));

        $researcher->update(['name_1' => 'Cambiado']);

        $audit = DB::table('audi_researcher')->orderByDesc('consecutivo')->first();
        $this->assertSame('U', $audit->accion);
        $this->assertSame($researcher->researcher_id, (int) $audit->researcher_id);
        $this->assertSame($this->user->id, (int) $audit->app_user_id);
    }

    public function test_only_old_pending_registrations_are_pruned(): void
    {
        $oldPending = User::factory()->unverified()->create(['created_at' => now()->subDays(2)]);
        $recentPending = User::factory()->unverified()->create();
        $legacy = User::factory()->unverified()->create(['user_type' => null, 'code' => null, 'created_at' => now()->subDays(2)]);
        $verified = User::factory()->create(['created_at' => now()->subDays(2)]);

        $this->artisan('model:prune', ['--model' => [User::class]])->assertSuccessful();

        $this->assertModelMissing($oldPending);
        $this->assertModelExists($recentPending);
        $this->assertModelExists($legacy);
        $this->assertModelExists($verified);
    }
}
