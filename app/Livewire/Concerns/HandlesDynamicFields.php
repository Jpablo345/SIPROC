<?php

namespace App\Livewire\Concerns;

use App\Models\PublicationType;
use App\Models\PublicationTypeField;
use App\Support\DynamicFields\FieldRuleBuilder;
use App\Support\DynamicFields\FieldValues;
use Illuminate\Support\Collection;

/**
 * Campos dinámicos del tipo elegido en un formulario de publicación.
 * El componente que lo usa debe tener la propiedad pública $type_id.
 */
trait HandlesDynamicFields
{
    /** Valores de los campos dinámicos, enlazados con wire:model="extra.{key}". */
    public array $extra = [];

    private ?PublicationType $dynamicType = null;

    /**
     * Campos activos del tipo elegido. Vacío si no hay tipo o si es Articulo/Libro.
     *
     * @return Collection<int, PublicationTypeField>
     */
    public function getDynamicFieldsProperty(): Collection
    {
        $type = $this->selectedDynamicType();

        return $type ? $type->fields->where('active', true)->values() : collect();
    }

    public function getDynamicTypeNameProperty(): ?string
    {
        return $this->selectedDynamicType()?->type_name;
    }

    protected function resetExtra(array $existing = []): void
    {
        $this->extra = FieldValues::defaults($this->dynamicFields, $existing);
    }

    /**
     * @return array{rules: array, messages: array, attributes: array}
     */
    protected function dynamicValidation(?int $publicationId): array
    {
        return app(FieldRuleBuilder::class)->build($this->dynamicFields, $publicationId);
    }

    /**
     * Valor de extra_attributes a guardar. Conserva los valores de campos desactivados.
     */
    protected function extraForStorage(?array $existing): ?array
    {
        $type = $this->selectedDynamicType();

        return $type ? FieldValues::forStorage($type->fields, $this->extra, $existing ?? []) : null;
    }

    private function selectedDynamicType(): ?PublicationType
    {
        $typeId = $this->type_id ? (int) $this->type_id : null;

        if (! $typeId) {
            return null;
        }

        if ($this->dynamicType?->type_id !== $typeId) {
            $this->dynamicType = PublicationType::with('fields')->find($typeId);
        }

        return $this->dynamicType && ! $this->dynamicType->is_system ? $this->dynamicType : null;
    }
}
