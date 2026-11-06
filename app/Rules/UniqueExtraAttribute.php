<?php

namespace App\Rules;

use App\Models\Publication;
use App\Models\PublicationTypeField;
use App\Support\DynamicFields\FieldValues;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Un valor de un campo dinámico no puede repetirse entre publicaciones del mismo tipo.
 */
class UniqueExtraAttribute implements ValidationRule
{
    public function __construct(
        private PublicationTypeField $field,
        private ?int $ignorePublicationId,
        private string $message,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalized = FieldValues::normalize($this->field, $value);

        if ($normalized === null) {
            return;
        }

        $exists = Publication::where('type_id', $this->field->type_id)
            ->when($this->ignorePublicationId, fn ($q) => $q->where('publication_id', '!=', $this->ignorePublicationId))
            ->where("extra_attributes->{$this->field->key}", (string) $normalized)
            ->exists();

        if ($exists) {
            $fail($this->message);
        }
    }
}
