<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Support\DynamicFields\FieldFormats;
use App\Support\DynamicFields\FieldRuleBuilder;
use Illuminate\Support\Carbon;

/**
 * Campo configurable de un tipo de publicación. Los valores se guardan en
 * publication.extra_attributes bajo la clave $key.
 */
class PublicationTypeField extends Model
{
    public const TYPES = [
        'texto' => 'Texto corto',
        'texto_largo' => 'Texto largo',
        'numero' => 'Número',
        'fecha' => 'Fecha',
        'url' => 'URL',
        'lista' => 'Lista de opciones',
        'si_no' => 'Sí / No',
    ];

    public const MAX_PER_TYPE = 20;

    protected $table = 'publication_type_field';
    protected $primaryKey = 'field_id';

    protected $fillable = [
        'type_id',
        'key',
        'label',
        'help_text',
        'field_type',
        'required',
        'multiple',
        'options',
        'rules',
        'error_message',
        'sort_order',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'multiple' => 'boolean',
            'active' => 'boolean',
            'options' => 'array',
            'rules' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(PublicationType::class, 'type_id', 'type_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function isMultipleList(): bool
    {
        return $this->field_type === 'lista' && $this->multiple;
    }

    /**
     * Valor para mostrar en listados y exportaciones.
     */
    public function display(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        return match ($this->field_type) {
            'si_no' => $value ? 'Sí' : 'No',
            'fecha' => rescue(fn () => Carbon::parse($value)->format('d/m/Y'), (string) $value, false),
            'lista' => is_array($value) ? implode(', ', $value) : (string) $value,
            default => (string) $value,
        };
    }

    /**
     * Resumen de las validaciones para la tabla de configuración: "3–20 caracteres · Solo números · Único".
     */
    public function rulesSummary(): string
    {
        $r = FieldRuleBuilder::allowedRules($this);
        $parts = [];

        if (isset($r['min_length']) || isset($r['max_length'])) {
            $parts[] = ($r['min_length'] ?? 0) . '–' . ($r['max_length'] ?? '∞') . ' caracteres';
        }
        if (isset($r['format']) && FieldFormats::exists($r['format'])) {
            $parts[] = FieldFormats::options()[$r['format']];
        }
        if (! empty($r['integer'])) {
            $parts[] = 'Entero';
        }
        if (isset($r['min']) || isset($r['max'])) {
            $parts[] = 'Entre ' . ($r['min'] ?? '−∞') . ' y ' . ($r['max'] ?? '∞');
        }
        if (isset($r['min_date'])) {
            $parts[] = 'Desde ' . Carbon::parse($r['min_date'])->format('d/m/Y');
        }
        if (isset($r['max_date'])) {
            $parts[] = 'Hasta ' . Carbon::parse($r['max_date'])->format('d/m/Y');
        }
        if (! empty($r['no_future'])) {
            $parts[] = 'No futura';
        }
        if ($this->field_type === 'lista') {
            $parts[] = count($this->options ?? []) . ' opciones' . ($this->multiple ? ', varias' : '');
        }
        if (! empty($r['unique'])) {
            $parts[] = 'Único';
        }

        return $parts === [] ? '—' : implode(' · ', $parts);
    }

    /**
     * Cuántas publicaciones de este tipo tienen un valor guardado en este campo.
     */
    public function usageCount(): int
    {
        return Publication::where('type_id', $this->type_id)
            ->whereNotNull("extra_attributes->{$this->key}")
            ->count();
    }
}
