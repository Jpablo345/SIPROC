<?php

namespace Tests\Feature;

use App\Livewire\Eventos\ConferencistaSelector;
use App\Livewire\Eventos\InstitucionSelector;
use App\Livewire\Publicaciones\AutoresSelector;
use App\Livewire\Publicaciones\IndexPublicaciones;
use App\Models\Institution;
use App\Models\Publication;
use App\Models\ResearchGroup;
use App\Models\Researcher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    // ─── Rutas ───────────────────────────────────────────────────────────────

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_users_without_role_get_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertForbidden();
        $this->actingAs($user)->get('/publicaciones')->assertForbidden();
    }

    public function test_users_with_role_can_open_every_page(): void
    {
        $user = User::factory()->withRole('Estudiante')->create();

        foreach ([
            '/dashboard',
            '/investigadores',
            '/publicaciones',
            '/publicaciones/tipos',
            '/publicaciones/revistas',
            '/publicaciones/tipos-libro',
            '/estructura/grupos-investigacion',
            '/estructura/instituciones',
            '/tipologia/generacion/articulos-cientificos',
            '/tipologia/generacion/libros-investigacion',
            '/tipologia/generacion/otras-publicaciones',
            '/tipologia/divulgacion-publica',
            '/tipologia/desarrollo-tecnologico',
        ] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_removed_routes_no_longer_exist(): void
    {
        $user = User::factory()->withRole('Estudiante')->create();

        $this->get('/send-email')->assertNotFound();
        $this->actingAs($user)->get('/tipologia/generacion/eventos')->assertNotFound();
    }

    // ─── Policy de registros propios ────────────────────────────────────────

    public function test_student_can_create_and_edit_own_records_but_not_delete(): void
    {
        $student = User::factory()->withRole('Estudiante')->create();
        $own = Publication::factory()->create(['created_by' => $student->id]);

        $this->assertTrue($student->can('create', Publication::class));
        $this->assertTrue($student->can('update', $own));
        $this->assertFalse($student->can('delete', $own));
    }

    public function test_student_and_teacher_can_not_touch_other_users_records(): void
    {
        $owner = User::factory()->create();
        $other = Publication::factory()->create(['created_by' => $owner->id]);

        foreach (['Estudiante', 'Docente'] as $role) {
            $user = User::factory()->withRole($role)->create();

            $this->assertFalse($user->can('update', $other), "$role no debería editar registros ajenos");
            $this->assertFalse($user->can('delete', $other), "$role no debería borrar registros ajenos");
        }
    }

    public function test_teacher_can_delete_own_records(): void
    {
        $teacher = User::factory()->teacher()->withRole('Docente')->create();
        $own = Publication::factory()->create(['created_by' => $teacher->id]);

        $this->assertTrue($teacher->can('delete', $own));
    }

    public function test_records_without_owner_are_only_editable_by_administrators(): void
    {
        $orphan = Publication::factory()->create(['created_by' => null]);

        $this->assertFalse(User::factory()->withRole('Docente')->create()->can('update', $orphan));
        $this->assertTrue(User::factory()->withRole('Administrador')->create()->can('update', $orphan));
        $this->assertTrue(User::factory()->withRole('Administrador')->create()->can('delete', $orphan));
    }

    public function test_records_get_the_creator_automatically(): void
    {
        $user = User::factory()->withRole('Estudiante')->create();
        $this->actingAs($user);

        $publication = Publication::factory()->create();

        $this->assertSame($user->id, $publication->created_by);
    }

    // ─── Autorización dentro de los componentes Livewire ───────────────────

    public function test_deleting_someone_elses_publication_is_denied(): void
    {
        $publication = Publication::factory()->create(['created_by' => User::factory()->create()->id]);
        $this->actingAs(User::factory()->withRole('Docente')->create());

        Livewire::test(IndexPublicaciones::class)
            ->call('eliminar', $publication->publication_id)
            ->assertDispatched('permission-denied');

        $this->assertModelExists($publication);
    }

    public function test_exports_require_permission(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(IndexPublicaciones::class)
            ->call('exportExcel')
            ->assertDispatched('permission-denied');
    }

    public function test_selectors_can_not_create_records_without_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $researcherData = ['researcherForm.name_1' => 'Ana', 'researcherForm.last_name_1' => 'Paz', 'researcherForm.create_group' => true,
            'researcherForm.group_code' => 'COL999', 'researcherForm.group_name' => 'Grupo X'];

        foreach ([AutoresSelector::class, ConferencistaSelector::class] as $component) {
            $test = Livewire::test($component);
            foreach ($researcherData as $field => $value) {
                $test->set($field, $value);
            }
            $test->call('crearYSeleccionar')->assertDispatched('permission-denied');
        }

        Livewire::test(InstitucionSelector::class, ['target' => 'host'])
            ->set('modal_institution_name', 'Universidad X')
            ->call('createInstitution')
            ->assertDispatched('permission-denied');

        $this->assertDatabaseCount('researcher', 0);
        $this->assertDatabaseCount('research_group', 0);
        $this->assertDatabaseCount('institution', 0);
    }

    public function test_selectors_create_records_with_permission(): void
    {
        $this->actingAs(User::factory()->withRole('Estudiante')->create());

        Livewire::test(AutoresSelector::class)
            ->set('researcherForm.name_1', 'Ana')
            ->set('researcherForm.name_2', 'María')
            ->set('researcherForm.last_name_1', 'Paz')
            ->set('researcherForm.last_name_2', 'Gómez')
            ->set('researcherForm.create_group', true)
            ->set('researcherForm.group_code', 'COL999')
            ->set('researcherForm.group_name', 'Grupo X')
            ->call('crearYSeleccionar')
            ->assertHasNoErrors()
            ->assertCount('selectedAuthors', 1);

        $this->assertDatabaseHas('researcher', [
            'name_1' => 'Ana', 'name_2' => 'María', 'last_name_1' => 'Paz', 'last_name_2' => 'Gómez', 'cod_minciencias' => 'COL999',
        ]);
        $this->assertNotNull(ResearchGroup::find('COL999'));
        $this->assertNotNull(Researcher::first()->created_by);
        $this->assertSame(0, Institution::count());
    }
}
