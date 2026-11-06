<?php

namespace App\Support\DynamicFields;

use App\Models\PublicationTypeField;
use App\Rules\UniqueExtraAttribute;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Traduce la configuración de los campos de un tipo a reglas de validación de Laravel.
 * Solo reconoce las claves de ALLOWED_RULES: cualquier otra clave guardada en `rules` se ignora.
 */
final class FieldRuleBuilder
{
    public const ALLOWED_RULES = [
        'texto' => ['min_length', 'max_length', 'format', 'unique'],
        'texto_largo' => ['min_length', 'max_length'],
        'numero' => ['integer', 'min', 'max', 'unique'],
        'fecha' => ['min_date', 'max_date', 'no_future'],
        'url' => [],
        'lista' => [],
        'si_no' => [],
    ];

    // Límite por defecto para no guardar textos sin tope en el jsonb.
    private const DEFAULT_MAX_LENGTH = ['texto' => 255, 'texto_largo' => 2000];

    /**
     * @param  iterable<PublicationTypeField>  $fields  Campos activos del tipo.
     * @return array{rules: array, messages: array, attributes: array}
     */
    public function build(iterable $fields, ?int $ignorePublicationId = null, string $prefix = 'extra'): array
    {
        $result = ['rules' => [], 'messages' => [], 'attributes' => []];

        foreach ($fields as $field) {
            $this->addField($result, $field, $ignorePublicationId, "{$prefix}.{$field->key}");
        }

        return $result;
    }

    /**
     * Reglas reconocidas para el campo, sin las claves desconocidas o que no aplican a su clase.
     */
    public static function allowedRules(PublicationTypeField $field): array
    {
        return array_intersect_key($field->rules ?? [], array_flip(self::ALLOWED_RULES[$field->field_type] ?? []));
    }

    private function addField(array &$result, PublicationTypeField $field, ?int $ignorePublicationId, string $attribute): void
    {
        $label = $field->label;
        $r = self::allowedRules($field);
        $rules = [];
        $messages = [];

        // Un sí/no siempre tiene valor (casilla marcada o no): "obligatorio" no aplica.
        if ($field->required && $field->field_type !== 'si_no') {
            $rules[] = 'required';
            $messages['required'] = "El campo {$label} es obligatorio.";
        } else {
            $rules[] = 'nullable';
        }

        switch ($field->field_type) {
            case 'texto':
            case 'texto_largo':
                $rules[] = 'string';
                if (isset($r['min_length'])) {
                    $rules[] = 'min:' . (int) $r['min_length'];
                    $messages['min'] = "{$label} debe tener al menos {$r['min_length']} caracteres.";
                }
                $max = (int) ($r['max_length'] ?? self::DEFAULT_MAX_LENGTH[$field->field_type]);
                $rules[] = "max:{$max}";
                $messages['max'] = "{$label} debe tener como máximo {$max} caracteres.";
                if (isset($r['format']) && FieldFormats::exists($r['format'])) {
                    $rules[] = FieldFormats::rule($r['format']);
                    $messages[FieldFormats::ruleName($r['format'])] = "{$label} " . FieldFormats::description($r['format']) . '.';
                }
                break;

            case 'numero':
                $integer = ! empty($r['integer']);
                $rules[] = $integer ? 'integer' : 'numeric';
                $messages[$integer ? 'integer' : 'numeric'] = $integer
                    ? "{$label} debe ser un número entero."
                    : "{$label} debe ser un número.";
                if (isset($r['min']) && is_numeric($r['min'])) {
                    $rules[] = 'min:' . $r['min'];
                    $messages['min'] = "{$label} debe ser como mínimo {$r['min']}.";
                }
                if (isset($r['max']) && is_numeric($r['max'])) {
                    $rules[] = 'max:' . $r['max'];
                    $messages['max'] = "{$label} debe ser como máximo {$r['max']}.";
                }
                break;

            case 'fecha':
                $rules[] = 'date';
                $messages['date'] = "{$label} debe ser una fecha válida.";
                if ($min = $this->date($r['min_date'] ?? null)) {
                    $rules[] = 'after_or_equal:' . $min->toDateString();
                    $messages['after_or_equal'] = "{$label} no puede ser anterior al {$min->format('d/m/Y')}.";
                }
                // "No puede ser futura" y la fecha máxima se combinan en un solo límite superior.
                $max = $this->date($r['max_date'] ?? null);
                if (! empty($r['no_future']) && (! $max || $max->isFuture())) {
                    $rules[] = 'before_or_equal:today';
                    $messages['before_or_equal'] = "{$label} no puede ser posterior a hoy.";
                } elseif ($max) {
                    $rules[] = 'before_or_equal:' . $max->toDateString();
                    $messages['before_or_equal'] = "{$label} no puede ser posterior al {$max->format('d/m/Y')}.";
                }
                break;

            case 'url':
                $rules[] = 'url:http,https';
                $rules[] = 'max:300';
                $messages['url'] = "{$label} debe ser una URL que empiece con http:// o https://.";
                $messages['max'] = "{$label} debe tener como máximo 300 caracteres.";
                break;

            case 'lista':
                $options = array_values($field->options ?? []);
                if ($field->multiple) {
                    $rules[] = 'array';
                    $result['rules']["{$attribute}.*"] = ['string', Rule::in($options)];
                    $result['messages']["{$attribute}.*.in"] = $field->error_message
                        ?: "{$label} solo admite las opciones de la lista.";
                } else {
                    $rules[] = 'string';
                    $rules[] = Rule::in($options);
                    $messages['in'] = "{$label} debe ser una de las opciones de la lista.";
                }
                break;

            case 'si_no':
                $rules[] = 'boolean';
                break;
        }

        if (! empty($r['unique']) && in_array($field->field_type, ['texto', 'numero'], true)) {
            $rules[] = new UniqueExtraAttribute(
                $field,
                $ignorePublicationId,
                $field->error_message ?: "Ya existe otra publicación de este tipo con ese valor en {$label}."
            );
        }

        // El mensaje propio reemplaza a los generados, salvo el de obligatorio.
        if ($field->error_message) {
            foreach (array_keys($messages) as $rule) {
                if ($rule !== 'required') {
                    $messages[$rule] = $field->error_message;
                }
            }
        }

        $result['rules'][$attribute] = $rules;
        $result['attributes'][$attribute] = $label;
        foreach ($messages as $rule => $message) {
            $result['messages']["{$attribute}.{$rule}"] = $message;
        }
    }

    private function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return rescue(fn () => Carbon::parse($value)->startOfDay(), null, false);
    }
}
