<?php

namespace App\Support\DynamicFields;

use App\Models\PublicationTypeField;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Convierte lo que llega del formulario en los valores que se guardan en extra_attributes.
 */
final class FieldValues
{
    /**
     * Valores iniciales del formulario para los campos activos de un tipo.
     *
     * @param  Collection<int, PublicationTypeField>  $fields
     */
    public static function defaults(Collection $fields, array $existing = []): array
    {
        return $fields->mapWithKeys(fn (PublicationTypeField $field) => [
            $field->key => $existing[$field->key] ?? match (true) {
                $field->isMultipleList() => [],
                $field->field_type === 'si_no' => false,
                default => null,
            },
        ])->all();
    }

    /**
     * Arma extra_attributes: valores normalizados de los campos activos más los valores que ya
     * tenían los campos desactivados (se conservan aunque no aparezcan en el formulario).
     *
     * @param  Collection<int, PublicationTypeField>  $allFields  Todos los campos del tipo.
     */
    public static function forStorage(Collection $allFields, array $input, array $existing = []): ?array
    {
        $values = [];

        foreach ($allFields as $field) {
            $value = $field->active
                ? self::normalize($field, $input[$field->key] ?? null)
                : ($existing[$field->key] ?? null);

            if ($value !== null) {
                $values[$field->key] = $value;
            }
        }

        return $values === [] ? null : $values;
    }

    public static function normalize(PublicationTypeField $field, mixed $value): mixed
    {
        if ($field->isMultipleList()) {
            $items = array_values(array_filter((array) $value, fn ($item) => $item !== null && $item !== ''));

            return $items === [] ? null : $items;
        }

        if ($field->field_type === 'si_no') {
            return (bool) $value;
        }

        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        return match ($field->field_type) {
            'numero' => is_numeric($value) ? $value + 0 : null,
            'fecha' => rescue(fn () => Carbon::parse($value)->toDateString(), null, false),
            default => trim((string) $value),
        };
    }
}
