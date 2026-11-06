<?php

namespace Tests\Feature;

use App\Exports\Sheets\TiposConfigurablesSheet;
use App\Http\Middleware\SetDatabaseAuditUser;
use App\Livewire\Dashboard;
use App\Livewire\Publicaciones\ConfigurarCamposTipo;
use App\Livewire\Publicaciones\FormularioPublicacion;
use App\Livewire\Publicaciones\TiposPublicacion;
use App\Models\Publication;
use App\Models\PublicationType;
use App\Models\PublicationTypeField;
use App\Models\User;
use App\Queries\Publicaciones\PublicationIndexQuery;
use App\Support\DynamicFields\FieldRuleBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Tests\TestCase;

class DynamicPublicationTypesTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->teacher()->withRole('Docente')->create();
        $this->actingAs($this->teacher);
    }

    // ─── Crear tipos ────────────────────────────────────────────────────────

    public function test_teacher_creates_a_type_and_goes_to_configure_its_fields(): void
    {
        Livewire::test(TiposPublicacion::class)
            ->set('type_name', 'Software registrado')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('publicaciones.tipos.campos', PublicationType::where('type_name', 'Software registrado')->value('type_id')));

        $type = PublicationType::where('type_name', 'Software registrado')->firstOrFail();
        $this->assertSame($this->teacher->id, $type->created_by);
        $this->assertFalse($type->is_system);
    }

    public function test_student_can_not_create_types(): void
    {
        $this->actingAs(User::factory()->withRole('Estudiante')->create());

        Livewire::test(TiposPublicacion::class)
            ->set('type_name', 'Software')
            ->call('save')
            ->assertDispatched('permission-denied');

        $this->assertDatabaseMissing('publication_type', ['type_name' => 'Software']);
    }

    public function test_type_names_are_unique_ignoring_case_and_accents(): void
    {
        $this->makeType('Software');

        Livewire::test(TiposPublicacion::class)
            ->set('type_name', 'SÓFTWARE')
            ->call('save')
            ->assertHasErrors('type_name');
    }

    // ─── Quién configura ────────────────────────────────────────────────────

    public function test_owner_can_open_the_configuration_page(): void
    {
        $type = $this->makeType('Software');

        $this->get(route('publicaciones.tipos.campos', $type->type_id))->assertOk();
    }

    public function test_teacher_can_not_configure_another_teachers_type(): void
    {
        $type = $this->makeType('Software');

        $this->actingAs(User::factory()->teacher()->withRole('Docente')->create())
            ->get(route('publicaciones.tipos.campos', $type->type_id))
            ->assertForbidden();
    }

    public function test_system_types_can_not_be_configured_even_by_admin(): void
    {
        $article = PublicationType::create(['type_name' => 'Articulo']);
        DB::table('publication_type')->where('type_id', $article->type_id)->update(['is_system' => true]);

        $this->actingAs(User::factory()->withRole('Administrador')->create())
            ->get(route('publicaciones.tipos.campos', $article->type_id))
            ->assertForbidden();
    }

    public function test_types_without_owner_are_configurable_only_by_admin(): void
    {
        $type = $this->makeType('Tesis');
        $type->forceFill(['created_by' => null])->save();

        $this->get(route('publicaciones.tipos.campos', $type->type_id))->assertForbidden();

        $this->actingAs(User::factory()->withRole('Administrador')->create())
            ->get(route('publicaciones.tipos.campos', $type->type_id))
            ->assertOk();
    }

    // ─── Configurar campos ──────────────────────────────────────────────────

    public function test_adding_a_field_generates_its_key_and_order(): void
    {
        $type = $this->makeType('Software');

        $this->configure($type)
            ->call('newField')
            ->set('form.label', 'Número de registro DNDA')
            ->set('form.field_type', 'texto')
            ->set('form.required', true)
            ->set('form.min_length', 5)
            ->set('form.max_length', 20)
            ->set('form.format', 'solo_numeros')
            ->set('form.unique', true)
            ->set('form.min', 3) // no aplica a texto: no se guarda
            ->call('saveField')
            ->assertHasNoErrors();

        $field = $type->fields()->firstOrFail();
        $this->assertSame('numero_de_registro_dnda', $field->key);
        $this->assertSame(1, $field->sort_order);
        $this->assertSameIgnoringOrder(['min_length' => 5, 'max_length' => 20, 'format' => 'solo_numeros', 'unique' => true], $field->rules);
    }

    public function test_invalid_configurations_are_rejected(): void
    {
        $type = $this->makeType('Software');

        $this->configure($type)->call('newField')
            ->set('form.label', 'Código')->set('form.field_type', 'texto')
            ->set('form.min_length', 50)->set('form.max_length', 20)
            ->call('saveField')
            ->assertHasErrors('form.max_length');

        $this->configure($type)->call('newField')
            ->set('form.label', 'Licencia')->set('form.field_type', 'lista')
            ->set('form.options_text', "  \n ")
            ->call('saveField')
            ->assertHasErrors('form.options_text');

        $this->configure($type)->call('newField')
            ->set('form.label', 'Código')->set('form.field_type', 'texto')
            ->set('form.format', '/(a+)+$/')
            ->call('saveField')
            ->assertHasErrors('form.format');

        $this->configure($type)->call('newField')
            ->set('form.label', 'Horas')->set('form.field_type', 'numero')
            ->set('form.min', 10)->set('form.max', 1)
            ->call('saveField')
            ->assertHasErrors('form.max');

        $this->assertSame(0, $type->fields()->count());
    }

    public function test_a_type_can_not_have_more_than_twenty_fields(): void
    {
        $type = $this->makeType('Software');
        foreach (range(1, PublicationTypeField::MAX_PER_TYPE) as $i) {
            $this->field($type, ['key' => "campo_{$i}", 'label' => "Campo {$i}"]);
        }

        $this->configure($type)->call('newField')->assertSet('showForm', false);
        $this->assertSame(PublicationTypeField::MAX_PER_TYPE, $type->fields()->count());
    }

    public function test_fields_can_be_reordered(): void
    {
        $type = $this->makeType('Software');
        $first = $this->field($type, ['key' => 'a', 'label' => 'A', 'sort_order' => 1]);
        $second = $this->field($type, ['key' => 'b', 'label' => 'B', 'sort_order' => 2]);

        $this->configure($type)->call('move', $second->field_id, 'up');

        $this->assertSame(['b', 'a'], $type->fields()->pluck('key')->all());
        $this->assertSame(2, $first->fresh()->sort_order);
    }

    // ─── Registrar publicaciones del tipo ───────────────────────────────────

    public function test_dynamic_fields_are_validated_and_saved(): void
    {
        [$type] = $this->softwareType();

        $this->publicationForm($type)
            ->set('extra.numero_registro', '13-89-A')
            ->set('extra.fecha_registro', now()->addDay()->toDateString())
            ->set('extra.licencia', 'Robada')
            ->set('extra.horas', '2.5')
            ->call('guardar')
            ->assertHasErrors([
                'extra.nombre_software' => 'required',
                'extra.numero_registro',
                'extra.fecha_registro',
                'extra.licencia',
                'extra.horas',
            ]);

        $this->assertDatabaseCount('publication', 0);

        $this->publicationForm($type)
            ->set('extra.nombre_software', 'SegEgresados')
            ->set('extra.numero_registro', '1389221')
            ->set('extra.fecha_registro', '2026-10-15')
            ->set('extra.licencia', 'Institucional')
            ->set('extra.plataformas', ['Web', 'Android'])
            ->set('extra.horas', '120')
            ->set('extra.codigo_abierto', true)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSameIgnoringOrder([
            'nombre_software' => 'SegEgresados',
            'numero_registro' => '1389221',
            'fecha_registro' => '2026-10-15',
            'licencia' => 'Institucional',
            'plataformas' => ['Web', 'Android'],
            'horas' => 120,
            'codigo_abierto' => true,
        ], Publication::firstOrFail()->extra_attributes);
    }

    public function test_custom_message_replaces_generated_ones(): void
    {
        [$type] = $this->softwareType();

        $component = $this->publicationForm($type)
            ->set('extra.nombre_software', 'App')
            ->set('extra.numero_registro', 'ABC12')
            ->call('guardar');

        $this->assertSame(
            'El número DNDA solo lleva dígitos y no puede repetirse.',
            $component->errors()->first('extra.numero_registro')
        );
    }

    public function test_unique_values_can_not_repeat_within_the_type(): void
    {
        [$type] = $this->softwareType();
        $existing = Publication::factory()->create(['type_id' => $type->type_id, 'extra_attributes' => ['numero_registro' => '1389221']]);

        $this->publicationForm($type)
            ->set('extra.nombre_software', 'Otra app')
            ->set('extra.numero_registro', '1389221')
            ->call('guardar')
            ->assertHasErrors('extra.numero_registro');

        // Al editar la misma publicación su propio valor no cuenta como repetido.
        Livewire::test(FormularioPublicacion::class, ['publicationId' => $existing->publication_id])
            ->set('title', 'Editada')
            ->set('extra.nombre_software', 'Otra app')
            ->call('guardar')
            ->assertHasNoErrors();
    }

    public function test_editing_loads_values_and_changing_type_clears_them(): void
    {
        [$type] = $this->softwareType();
        $other = $this->makeType('Patente');
        $publication = Publication::factory()->create([
            'type_id' => $type->type_id,
            'extra_attributes' => ['nombre_software' => 'SegEgresados', 'numero_registro' => '1389221'],
        ]);

        Livewire::test(FormularioPublicacion::class, ['publicationId' => $publication->publication_id])
            ->assertSet('extra.nombre_software', 'SegEgresados')
            ->assertSee('Datos de Software registrado')
            ->set('type_id', $other->type_id)
            ->assertSet('extra', []);
    }

    public function test_deactivated_fields_disappear_but_keep_their_values(): void
    {
        [$type, $fields] = $this->softwareType();
        $publication = Publication::factory()->create([
            'type_id' => $type->type_id,
            'extra_attributes' => ['nombre_software' => 'SegEgresados', 'licencia' => 'Libre'],
        ]);
        $fields['licencia']->update(['active' => false]);

        Livewire::test(FormularioPublicacion::class, ['publicationId' => $publication->publication_id])
            ->assertDontSee('id="pubextra_licencia"', false)
            ->set('extra.nombre_software', 'SegEgresados 2')
            ->call('guardar')
            ->assertHasNoErrors();

        // codigo_abierto (sí/no) siempre tiene respuesta: se guarda false aunque no se marque.
        $this->assertSameIgnoringOrder(
            ['nombre_software' => 'SegEgresados 2', 'licencia' => 'Libre', 'codigo_abierto' => false],
            $publication->fresh()->extra_attributes
        );
    }

    public function test_unknown_rule_keys_are_ignored(): void
    {
        $field = new PublicationTypeField([
            'key' => 'nombre', 'label' => 'Nombre', 'field_type' => 'texto',
            'rules' => ['max_length' => 5, 'exists' => 'users,email', 'regex' => '/(a+)+$/'],
        ]);

        $rules = app(FieldRuleBuilder::class)->build([$field])['rules']['extra.nombre'];

        $this->assertSame(['nullable', 'string', 'max:5'], $rules);
    }

    // ─── Cambios sobre campos en uso ────────────────────────────────────────

    public function test_deleting_a_field_in_use_only_deactivates_it(): void
    {
        [$type, $fields] = $this->softwareType();
        Publication::factory()->create(['type_id' => $type->type_id, 'extra_attributes' => ['licencia' => 'Libre']]);

        $this->configure($type)
            ->call('deleteField', $fields['licencia']->field_id)
            ->call('deleteField', $fields['horas']->field_id);

        $this->assertFalse($fields['licencia']->fresh()->active);
        $this->assertNull($fields['horas']->fresh());
    }

    public function test_stricter_rules_warn_about_affected_publications(): void
    {
        [$type, $fields] = $this->softwareType();
        Publication::factory()->create(['type_id' => $type->type_id, 'extra_attributes' => ['numero_registro' => '12345']]);
        Publication::factory()->create(['type_id' => $type->type_id, 'extra_attributes' => ['numero_registro' => '67890']]);

        $component = $this->configure($type)
            ->call('editField', $fields['numero_registro']->field_id)
            ->set('form.min_length', 8)
            ->call('saveField')
            ->assertSet('affectedCount', 2);

        $this->assertSame(5, $fields['numero_registro']->fresh()->rules['min_length']);

        $component->call('confirmSave')->assertSet('affectedCount', null);
        $this->assertSame(8, $fields['numero_registro']->fresh()->rules['min_length']);
    }

    public function test_field_type_can_not_change_while_in_use(): void
    {
        [$type, $fields] = $this->softwareType();
        Publication::factory()->create(['type_id' => $type->type_id, 'extra_attributes' => ['nombre_software' => 'App']]);

        $this->configure($type)
            ->call('editField', $fields['nombre_software']->field_id)
            ->set('form.field_type', 'numero')
            ->call('saveField')
            ->assertHasErrors('form.field_type');

        $this->assertSame('texto', $fields['nombre_software']->fresh()->field_type);
    }

    public function test_unique_can_not_be_enabled_with_repeated_values(): void
    {
        [$type, $fields] = $this->softwareType();
        Publication::factory()->count(2)->create(['type_id' => $type->type_id, 'extra_attributes' => ['nombre_software' => 'App']]);

        $this->configure($type)
            ->call('editField', $fields['nombre_software']->field_id)
            ->set('form.unique', true)
            ->call('saveField')
            ->assertHasErrors('form.unique');
    }

    // ─── Listados, búsqueda, exportación, dashboard y auditoría ─────────────

    public function test_dynamic_values_are_searchable(): void
    {
        [$type] = $this->softwareType();
        Publication::factory()->create(['type_id' => $type->type_id, 'extra_attributes' => ['nombre_software' => 'SegEgresados']]);
        Publication::factory()->create(['type_id' => $type->type_id, 'extra_attributes' => ['nombre_software' => 'Otro']]);

        $this->assertSame(1, app(PublicationIndexQuery::class)->build(['search' => 'segegres'])->count());
        // Las claves no cuentan como texto buscable.
        $this->assertSame(0, app(PublicationIndexQuery::class)->build(['search' => 'nombre_software'])->count());
    }

    public function test_excel_uses_one_column_per_field_for_a_single_type(): void
    {
        [$type] = $this->softwareType();
        Publication::factory()->create([
            'type_id' => $type->type_id,
            'extra_attributes' => ['nombre_software' => 'SegEgresados', 'codigo_abierto' => true],
        ]);
        $publications = app(PublicationIndexQuery::class)->build([])->get();

        $single = new TiposConfigurablesSheet($publications, $type->load('fields'));
        $this->assertContains('NOMBRE DEL SOFTWARE', $single->headings());
        $this->assertSame('Software registrado', $single->title());
        $row = $single->map($publications->first());
        $this->assertContains('SegEgresados', $row);
        $this->assertContains('Sí', $row);

        $mixed = new TiposConfigurablesSheet($publications);
        $this->assertSame('DETALLES', last($mixed->headings()));
        $this->assertStringContainsString('Nombre del software: SegEgresados', last($mixed->map($publications->first())));
    }

    public function test_dashboard_counts_production_by_type(): void
    {
        [$type] = $this->softwareType();
        $this->makeType('Patente');
        Publication::factory()->count(2)->create(['type_id' => $type->type_id]);

        $production = collect(Livewire::test(Dashboard::class)->get('productionByType'));

        $this->assertSame(2, $production->firstWhere('name', 'Software registrado')['count']);
        $this->assertSame(0, $production->firstWhere('name', 'Patente')['count']);
    }

    public function test_changes_are_audited_with_the_application_user(): void
    {
        $this->setAuditUser();
        [$type, $fields] = $this->softwareType();
        $publication = Publication::factory()->create(['type_id' => $type->type_id, 'extra_attributes' => ['nombre_software' => 'Antes']]);

        $publication->update(['extra_attributes' => ['nombre_software' => 'Después']]);
        $fields['horas']->update(['label' => 'Horas de desarrollo']);
        $fields['horas']->delete();

        $publicationAudit = DB::table('audi_publication')->latest('consecutivo')->first();
        $this->assertSame('Antes', json_decode($publicationAudit->extra_attributes, true)['nombre_software']);
        $this->assertSame($this->teacher->id, (int) $publicationAudit->app_user_id);

        $fieldAudit = DB::table('audi_publication_type_field')->where('field_id', $fields['horas']->field_id)->orderBy('consecutivo')->get();
        $this->assertSame(['I', 'U', 'D'], $fieldAudit->pluck('accion')->map(fn ($a) => trim($a))->all());
        $this->assertSame('Horas de desarrollo', json_decode($fieldAudit[1]->datos_despues, true)['label']);
        $this->assertSame($this->teacher->id, (int) $fieldAudit[2]->app_user_id);
    }

    public function test_editing_a_unique_field_with_old_duplicates_is_not_blocked(): void
    {
        [$type, $fields] = $this->softwareType();
        // Valores repetidos guardados antes de que el campo fuera único.
        Publication::factory()->count(2)->create(['type_id' => $type->type_id, 'extra_attributes' => ['numero_registro' => '12345']]);

        $this->configure($type)
            ->call('editField', $fields['numero_registro']->field_id)
            ->set('form.label', 'Registro DNDA')
            ->call('saveField')
            ->assertHasNoErrors();

        $this->assertSame('Registro DNDA', $fields['numero_registro']->fresh()->label);
    }

    // ─── Ayudas ─────────────────────────────────────────────────────────────

    /**
     * jsonb no conserva el orden de las claves: se comparan ordenadas.
     */
    private function assertSameIgnoringOrder(array $expected, ?array $actual): void
    {
        ksort($expected);
        ksort($actual);
        $this->assertSame($expected, $actual);
    }

    private function makeType(string $name): PublicationType
    {
        return PublicationType::create(['type_name' => $name]);
    }

    private function field(PublicationType $type, array $attributes): PublicationTypeField
    {
        return $type->fields()->create($attributes + ['field_type' => 'texto', 'sort_order' => 0]);
    }

    /**
     * El tipo "Software registrado" del ejemplo, con una muestra de cada clase de campo.
     *
     * @return array{0: PublicationType, 1: array<string, PublicationTypeField>}
     */
    private function softwareType(): array
    {
        $type = $this->makeType('Software registrado');

        $fields = collect([
            ['key' => 'nombre_software', 'label' => 'Nombre del software', 'field_type' => 'texto', 'required' => true, 'rules' => ['min_length' => 3, 'max_length' => 100]],
            ['key' => 'numero_registro', 'label' => 'Número de registro DNDA', 'field_type' => 'texto', 'rules' => ['min_length' => 5, 'max_length' => 20, 'format' => 'solo_numeros', 'unique' => true],
                'error_message' => 'El número DNDA solo lleva dígitos y no puede repetirse.'],
            ['key' => 'fecha_registro', 'label' => 'Fecha de registro', 'field_type' => 'fecha', 'rules' => ['min_date' => '2000-01-01', 'no_future' => true]],
            ['key' => 'licencia', 'label' => 'Licencia', 'field_type' => 'lista', 'options' => ['Libre', 'Propietaria', 'Institucional']],
            ['key' => 'plataformas', 'label' => 'Plataformas', 'field_type' => 'lista', 'multiple' => true, 'options' => ['Web', 'Android', 'iOS']],
            ['key' => 'horas', 'label' => 'Horas', 'field_type' => 'numero', 'rules' => ['integer' => true, 'min' => 1, 'max' => 10000]],
            ['key' => 'codigo_abierto', 'label' => 'Código abierto', 'field_type' => 'si_no'],
        ])->mapWithKeys(fn (array $data, int $i) => [$data['key'] => $this->field($type, $data + ['sort_order' => $i + 1])]);

        return [$type, $fields->all()];
    }

    private function publicationForm(PublicationType $type)
    {
        return Livewire::test(FormularioPublicacion::class)
            ->set('title', 'Plataforma de seguimiento de egresados')
            ->set('type_id', $type->type_id);
    }

    private function configure(PublicationType $type)
    {
        return Livewire::test(ConfigurarCamposTipo::class, ['type' => $type]);
    }

    private function setAuditUser(): void
    {
        $request = Request::create('/publicaciones');
        $request->setUserResolver(fn () => $this->teacher);
        (new SetDatabaseAuditUser())->handle($request, fn () => response('ok'));
    }
}
