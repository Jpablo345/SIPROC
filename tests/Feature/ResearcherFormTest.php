<?php

namespace Tests\Feature;

use App\Livewire\Eventos\ConferencistaSelector;
use App\Livewire\Publicaciones\AutoresSelector;
use App\Livewire\Publicaciones\FormularioPublicacion;
use App\Models\Institution;
use App\Models\ResearchGroup;
use App\Models\Researcher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El modal "crear investigador" (ResearcherForm) se comporta igual en los tres componentes que lo usan.
 */
class ResearcherFormTest extends TestCase
{
    use RefreshDatabase;

    private const COMPONENTS = [
        AutoresSelector::class => 'selectedAuthors',
        ConferencistaSelector::class => 'selectedSpeakers',
        FormularioPublicacion::class => 'selectedAuthors',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->withRole('Estudiante')->create());
    }

    public function test_researcher_is_created_in_an_existing_group_and_selected(): void
    {
        $group = ResearchGroup::factory()->create();

        foreach (self::COMPONENTS as $component => $selected) {
            $name = 'Ana' . class_basename($component);

            Livewire::test($component)
                ->set('researcherForm.name_1', $name)
                ->set('researcherForm.last_name_1', 'Paz')
                ->set('researcherForm.cod_minciencias', $group->cod_minciencias)
                ->call('crearYSeleccionar')
                ->assertHasNoErrors()
                ->assertSet("{$selected}.0.name", "{$name} Paz")
                ->assertSet('researcherForm.name_1', '');

            $this->assertDatabaseHas('researcher', ['name_1' => $name, 'cod_minciencias' => $group->cod_minciencias]);
        }
    }

    public function test_new_group_and_institution_are_created_together(): void
    {
        Livewire::test(ConferencistaSelector::class)
            ->set('researcherForm.name_1', 'Luis')
            ->set('researcherForm.last_name_1', 'Mora')
            ->set('researcherForm.create_group', true)
            ->set('researcherForm.group_code', 'COL123')
            ->set('researcherForm.group_name', 'Grupo Nuevo')
            ->set('researcherForm.group_classification', 'Categoría A')
            ->set('researcherForm.create_institution', true)
            ->set('researcherForm.institution_name', 'Universidad Nueva')
            ->set('researcherForm.institution_website', 'https://nueva.edu.co')
            ->call('crearYSeleccionar')
            ->assertHasNoErrors()
            ->assertSet('showResearcherModal', false);

        $institution = Institution::where('institution_name', 'Universidad Nueva')->firstOrFail();
        $group = ResearchGroup::findOrFail('COL123');

        $this->assertSame('Categoría A', $group->group_classification);
        $this->assertSame($institution->institution_id, $group->institution_id);
        $this->assertSame('COL123', Researcher::where('name_1', 'Luis')->value('cod_minciencias'));
    }

    public function test_an_existing_group_is_required_when_not_creating_one(): void
    {
        Livewire::test(AutoresSelector::class)
            ->set('researcherForm.name_1', 'Ana')
            ->set('researcherForm.last_name_1', 'Paz')
            ->call('crearYSeleccionar')
            ->assertHasErrors(['researcherForm.cod_minciencias' => 'required']);

        $this->assertDatabaseCount('researcher', 0);
    }

    public function test_duplicates_are_reported_as_validation_errors(): void
    {
        $group = ResearchGroup::factory()->create();
        Researcher::factory()->create(['document' => '123']);
        Institution::factory()->create(['institution_name' => 'Existente']);

        Livewire::test(AutoresSelector::class)
            ->set('researcherForm.document', '123')
            ->set('researcherForm.name_1', 'Ana')
            ->set('researcherForm.last_name_1', 'Paz')
            ->set('researcherForm.create_group', true)
            ->set('researcherForm.group_code', $group->cod_minciencias)
            ->set('researcherForm.group_name', 'Otro')
            ->set('researcherForm.create_institution', true)
            ->set('researcherForm.institution_name', 'Existente')
            ->call('crearYSeleccionar')
            ->assertHasErrors([
                'researcherForm.document' => 'unique',
                'researcherForm.group_code' => 'unique',
                'researcherForm.institution_name' => 'unique',
            ]);
    }

    public function test_the_modal_fields_are_rendered_in_every_component(): void
    {
        foreach ([AutoresSelector::class, FormularioPublicacion::class] as $component) {
            Livewire::test($component)->assertSeeHtml('wire:model="researcherForm.name_1"');
        }

        Livewire::test(ConferencistaSelector::class)
            ->call('openResearcherModal')
            ->assertSeeHtml('wire:model="researcherForm.name_1"');
    }

    public function test_full_name_skips_missing_parts(): void
    {
        $researcher = Researcher::factory()->make(['name_1' => 'Ana', 'name_2' => null, 'last_name_1' => 'Paz', 'last_name_2' => '']);

        $this->assertSame('Ana Paz', $researcher->full_name);
    }
}
