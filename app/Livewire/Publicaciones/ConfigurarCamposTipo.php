<?php

namespace App\Livewire\Publicaciones;

use App\Livewire\Forms\TypeFieldForm;
use App\Models\Publication;
use App\Models\PublicationType;
use App\Models\PublicationTypeField;
use App\Support\DynamicFields\FieldRuleBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Configuración de los campos de un tipo de publicación (RF-15).
 */
class ConfigurarCamposTipo extends Component
{
    #[Locked]
    public int $typeId;

    public TypeFieldForm $form;

    public bool $showForm = false;

    // Publicaciones que dejarían de cumplir la configuración nueva; se pide confirmar antes de guardar.
    public ?int $affectedCount = null;

    public function mount(PublicationType $type): void
    {
        abort_unless(Gate::allows('update', $type), 403);

        $this->typeId = $type->type_id;
    }

    #[Computed]
    public function type(): PublicationType
    {
        return PublicationType::with('fields')->findOrFail($this->typeId);
    }

    public function newField(): void
    {
        $this->authorizeType();

        if ($this->type->fields->count() >= PublicationTypeField::MAX_PER_TYPE) {
            session()->flash('status', 'Un tipo puede tener como máximo ' . PublicationTypeField::MAX_PER_TYPE . ' campos.');
            return;
        }

        $this->form->reset();
        $this->form->resetValidation();
        $this->affectedCount = null;
        $this->showForm = true;
    }

    public function editField(int $fieldId): void
    {
        $this->authorizeType();

        $this->form->resetValidation();
        $this->form->fillFromField($this->findField($fieldId));
        $this->affectedCount = null;
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->form->reset();
        $this->form->resetValidation();
        $this->affectedCount = null;
        $this->showForm = false;
    }

    public function saveField(bool $confirmed = false): void
    {
        $this->authorizeType();
        $this->form->validate();

        $attributes = $this->form->toAttributes();

        if ($this->form->fieldId) {
            $field = $this->findField($this->form->fieldId);

            if (! $this->canApplyChanges($field, $attributes, $confirmed)) {
                return;
            }

            $field->update($attributes);
            $message = 'Campo actualizado.';
        } else {
            if ($this->type->fields->count() >= PublicationTypeField::MAX_PER_TYPE) {
                $this->addError('form.label', 'Un tipo puede tener como máximo ' . PublicationTypeField::MAX_PER_TYPE . ' campos.');
                return;
            }

            $this->type->fields()->create($attributes + [
                'key' => $this->uniqueKey($attributes['label']),
                'sort_order' => ($this->type->fields->max('sort_order') ?? 0) + 1,
            ]);
            $message = 'Campo agregado.';
        }

        unset($this->type);
        $this->cancel();
        session()->flash('status', $message);
    }

    public function confirmSave(): void
    {
        $this->saveField(confirmed: true);
    }

    public function toggleActive(int $fieldId): void
    {
        $this->authorizeType();

        $field = $this->findField($fieldId);
        $field->update(['active' => ! $field->active]);

        unset($this->type);
        session()->flash('status', $field->active ? 'Campo activado.' : 'Campo desactivado: ya no aparece en el formulario y sus valores se conservan.');
    }

    public function move(int $fieldId, string $direction): void
    {
        $this->authorizeType();

        $fields = $this->type->fields->values();
        $index = $fields->search(fn ($f) => $f->field_id === $fieldId);
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index === false || ! isset($fields[$target])) {
            return;
        }

        DB::transaction(function () use ($fields, $index, $target) {
            // Renumera todo para que el orden quede consecutivo aunque hubiera huecos.
            $ordered = $fields->all();
            [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];
            foreach (array_values($ordered) as $position => $field) {
                if ($field->sort_order !== $position + 1) {
                    $field->update(['sort_order' => $position + 1]);
                }
            }
        });

        unset($this->type);
    }

    public function deleteField(int $fieldId): void
    {
        $this->authorizeType();

        $field = $this->findField($fieldId);

        // Un campo con valores guardados no se borra: se desactiva para no perder datos.
        if ($field->usageCount() > 0) {
            $field->update(['active' => false]);
            session()->flash('status', 'El campo tiene valores guardados: se desactivó en lugar de eliminarse.');
        } else {
            $field->delete();
            session()->flash('status', 'Campo eliminado.');
        }

        unset($this->type);
    }

    public function render()
    {
        return view('livewire.publicaciones.configurar-campos-tipo')->layout('layouts.app');
    }

    /**
     * Reglas para cambios sobre un campo que ya tiene valores guardados.
     */
    private function canApplyChanges(PublicationTypeField $field, array $attributes, bool $confirmed): bool
    {
        if ($field->usageCount() === 0) {
            return true;
        }

        if ($attributes['field_type'] !== $field->field_type) {
            $this->addError('form.field_type', 'No se puede cambiar la clase de un campo que ya tiene valores guardados.');
            return false;
        }

        $enablingUnique = ! empty($attributes['rules']['unique']) && empty($field->rules['unique']);

        if ($enablingUnique && ($duplicates = $this->duplicatedValues($field)) > 0) {
            $this->addError('form.unique', "No se puede exigir valor único: hay {$duplicates} valores repetidos entre publicaciones de este tipo. Corrígelos primero.");
            return false;
        }

        if (! $confirmed) {
            $affected = $this->countNotMatching($field, $attributes);
            if ($affected > 0) {
                $this->affectedCount = $affected;
                return false;
            }
        }

        return true;
    }

    /**
     * Cuántas publicaciones del tipo no cumplirían la configuración nueva del campo.
     */
    private function countNotMatching(PublicationTypeField $field, array $attributes): int
    {
        $candidate = new PublicationTypeField($attributes + ['key' => $field->key, 'type_id' => $field->type_id]);
        // La unicidad se revisa aparte (duplicatedValues).
        $candidate->rules = array_diff_key($candidate->rules ?? [], ['unique' => true]) ?: null;

        $validation = app(FieldRuleBuilder::class)->build([$candidate]);
        $affected = 0;

        Publication::where('type_id', $field->type_id)
            ->select(['publication_id', 'extra_attributes'])
            ->chunkById(200, function ($publications) use ($field, $validation, &$affected) {
                foreach ($publications as $publication) {
                    $data = ['extra' => [$field->key => $publication->extra_attributes[$field->key] ?? null]];
                    if (Validator::make($data, $validation['rules'])->fails()) {
                        $affected++;
                    }
                }
            }, 'publication_id');

        return $affected;
    }

    private function duplicatedValues(PublicationTypeField $field): int
    {
        return Publication::where('type_id', $field->type_id)
            ->whereNotNull("extra_attributes->{$field->key}")
            ->selectRaw('extra_attributes->>(?::text) as valor', [$field->key])
            ->groupBy('valor')
            ->havingRaw('count(*) > 1')
            ->get()
            ->count();
    }

    /**
     * Clave interna a partir de la etiqueta ("Número de registro DNDA" → numero_de_registro_dnda).
     * No cambia después: los valores guardados dependen de ella.
     */
    private function uniqueKey(string $label): string
    {
        $base = Str::limit(Str::slug($label, '_'), 40, '') ?: 'campo';
        $existing = $this->type->fields->pluck('key')->all();
        $key = $base;

        for ($i = 2; in_array($key, $existing, true); $i++) {
            $key = "{$base}_{$i}";
        }

        return $key;
    }

    private function findField(int $fieldId): PublicationTypeField
    {
        return $this->type->fields->firstWhere('field_id', $fieldId) ?? abort(404);
    }

    private function authorizeType(): void
    {
        $this->authorize('update', $this->type);
    }
}
