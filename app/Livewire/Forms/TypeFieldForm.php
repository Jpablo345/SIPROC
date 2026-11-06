<?php

namespace App\Livewire\Forms;

use App\Models\PublicationTypeField;
use App\Support\DynamicFields\FieldFormats;
use App\Support\DynamicFields\FieldRuleBuilder;
use Closure;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Configuración de un campo de un tipo de publicación: etiqueta, clase y validaciones.
 * Valida además que la configuración tenga sentido (mínimo <= máximo, lista con opciones…).
 */
class TypeFieldForm extends Form
{
    public ?int $fieldId = null;

    public string $label = '';
    public ?string $help_text = null;
    public string $field_type = 'texto';
    public bool $required = false;
    public bool $multiple = false;
    public string $options_text = '';
    public ?string $error_message = null;

    // Validaciones (solo se guardan las que aplican a la clase elegida)
    public $min_length = null;
    public $max_length = null;
    public ?string $format = null;
    public bool $unique = false;
    public bool $integer = false;
    public $min = null;
    public $max = null;
    public ?string $min_date = null;
    public ?string $max_date = null;
    public bool $no_future = false;

    public function fillFromField(PublicationTypeField $field): void
    {
        $rules = $field->rules ?? [];

        $this->fieldId = $field->field_id;
        $this->label = $field->label;
        $this->help_text = $field->help_text;
        $this->field_type = $field->field_type;
        $this->required = $field->required;
        $this->multiple = $field->multiple;
        $this->options_text = implode("\n", $field->options ?? []);
        $this->error_message = $field->error_message;

        $this->min_length = $rules['min_length'] ?? null;
        $this->max_length = $rules['max_length'] ?? null;
        $this->format = $rules['format'] ?? null;
        $this->unique = (bool) ($rules['unique'] ?? false);
        $this->integer = (bool) ($rules['integer'] ?? false);
        $this->min = $rules['min'] ?? null;
        $this->max = $rules['max'] ?? null;
        $this->min_date = $rules['min_date'] ?? null;
        $this->max_date = $rules['max_date'] ?? null;
        $this->no_future = (bool) ($rules['no_future'] ?? false);
    }

    /**
     * Opciones de la lista: una por línea, sin vacías ni repetidas.
     */
    public function options(): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $this->options_text))
            ->map(fn ($option) => trim($option))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Atributos para guardar en publication_type_field (sin key, type_id ni sort_order).
     */
    public function toAttributes(): array
    {
        $candidate = [
            'min_length' => $this->intOrNull($this->min_length),
            'max_length' => $this->intOrNull($this->max_length),
            'format' => $this->format ?: null,
            'unique' => $this->unique ?: null,
            'integer' => $this->integer ?: null,
            'min' => $this->numberOrNull($this->min),
            'max' => $this->numberOrNull($this->max),
            'min_date' => $this->min_date ?: null,
            'max_date' => $this->max_date ?: null,
            'no_future' => $this->no_future ?: null,
        ];

        $allowed = array_flip(FieldRuleBuilder::ALLOWED_RULES[$this->field_type]);
        $rules = array_filter(array_intersect_key($candidate, $allowed), fn ($value) => $value !== null);

        return [
            'label' => trim($this->label),
            'help_text' => $this->help_text ? trim($this->help_text) : null,
            'field_type' => $this->field_type,
            'required' => $this->field_type === 'si_no' ? false : $this->required,
            'multiple' => $this->field_type === 'lista' && $this->multiple,
            'options' => $this->field_type === 'lista' ? $this->options() : null,
            'rules' => $rules === [] ? null : $rules,
            'error_message' => $this->error_message ? trim($this->error_message) : null,
        ];
    }

    protected function rules(): array
    {
        $isText = fn () => in_array($this->field_type, ['texto', 'texto_largo'], true);

        return [
            'label' => ['required', 'string', 'max:100'],
            'help_text' => ['nullable', 'string', 'max:255'],
            'field_type' => ['required', Rule::in(array_keys(PublicationTypeField::TYPES))],
            'required' => ['boolean'],
            'multiple' => ['boolean'],
            'error_message' => ['nullable', 'string', 'max:255'],
            'options_text' => [
                Rule::requiredIf(fn () => $this->field_type === 'lista'),
                'nullable',
                'string',
                function (string $attribute, mixed $value, Closure $fail) {
                    if ($this->field_type !== 'lista') {
                        return;
                    }
                    $options = $this->options();
                    if ($options === []) {
                        $fail('La lista necesita al menos una opción.');
                    } elseif (count($options) > 50) {
                        $fail('La lista admite como máximo 50 opciones.');
                    } elseif (collect($options)->contains(fn ($o) => mb_strlen($o) > 100)) {
                        $fail('Cada opción puede tener como máximo 100 caracteres.');
                    }
                },
            ],
            'min_length' => ['nullable', Rule::excludeIf(fn () => ! $isText()), 'integer', 'min:1', 'max:2000'],
            'max_length' => [
                'nullable', Rule::excludeIf(fn () => ! $isText()), 'integer', 'min:1', 'max:2000',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (filled($value) && filled($this->min_length) && (int) $this->min_length > (int) $value) {
                        $fail('La longitud mínima no puede ser mayor que la máxima.');
                    }
                },
            ],
            'format' => ['nullable', Rule::excludeIf(fn () => $this->field_type !== 'texto'), Rule::in(array_keys(FieldFormats::options()))],
            'unique' => ['boolean'],
            'integer' => ['boolean'],
            'min' => ['nullable', Rule::excludeIf(fn () => $this->field_type !== 'numero'), 'numeric'],
            'max' => [
                'nullable', Rule::excludeIf(fn () => $this->field_type !== 'numero'), 'numeric',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (filled($value) && is_numeric($this->min) && $this->min > $value) {
                        $fail('El valor mínimo no puede ser mayor que el máximo.');
                    }
                },
            ],
            'min_date' => ['nullable', Rule::excludeIf(fn () => $this->field_type !== 'fecha'), 'date'],
            'max_date' => [
                'nullable', Rule::excludeIf(fn () => $this->field_type !== 'fecha'), 'date',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (filled($value) && filled($this->min_date) && strtotime($this->min_date) > strtotime($value)) {
                        $fail('La fecha mínima no puede ser posterior a la máxima.');
                    }
                },
            ],
            'no_future' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'label.required' => 'La etiqueta del campo es obligatoria.',
            'label.max' => 'La etiqueta puede tener como máximo 100 caracteres.',
            'help_text.max' => 'El texto de ayuda puede tener como máximo 255 caracteres.',
            'field_type.in' => 'La clase de campo no es válida.',
            'options_text.required' => 'La lista necesita al menos una opción.',
            'min_length.integer' => 'La longitud mínima debe ser un número entero.',
            'min_length.min' => 'La longitud mínima debe ser al menos 1.',
            'min_length.max' => 'La longitud mínima no puede superar 2000.',
            'max_length.integer' => 'La longitud máxima debe ser un número entero.',
            'max_length.min' => 'La longitud máxima debe ser al menos 1.',
            'max_length.max' => 'La longitud máxima no puede superar 2000.',
            'format.in' => 'El formato elegido no existe.',
            'min.numeric' => 'El valor mínimo debe ser un número.',
            'max.numeric' => 'El valor máximo debe ser un número.',
            'min_date.date' => 'La fecha mínima no es válida.',
            'max_date.date' => 'La fecha máxima no es válida.',
            'error_message.max' => 'El mensaje propio puede tener como máximo 255 caracteres.',
        ];
    }

    private function intOrNull(mixed $value): ?int
    {
        return filled($value) && is_numeric($value) ? (int) $value : null;
    }

    private function numberOrNull(mixed $value): int|float|null
    {
        return filled($value) && is_numeric($value) ? $value + 0 : null;
    }
}
