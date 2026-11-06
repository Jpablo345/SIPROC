<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Concerns\HasCreator;

class Publication extends Model
{
    use HasFactory, HasCreator;

    protected $table = 'publication';
    protected $primaryKey = 'publication_id';

    protected $fillable = [
        'title',
        'publication_year',
        'scope',
        'country_publication',
        'url',
        'type_id',
        'extra_attributes',
    ];

    protected function casts(): array
    {
        return [
            'extra_attributes' => 'array',
        ];
    }

    /**
     * Campos dinámicos con valor, en el orden del tipo: [['label' => ..., 'value' => ...], ...].
     * Incluye campos desactivados que conservan valor. Requiere cargar type.fields.
     */
    public function getExtraDetailsAttribute(): array
    {
        $values = $this->extra_attributes ?? [];

        if ($values === [] || ! $this->type) {
            return [];
        }

        return $this->type->fields
            ->map(fn (PublicationTypeField $field) => [
                'key' => $field->key,
                'label' => $field->label,
                'value' => $field->display($values[$field->key] ?? null),
            ])
            ->filter(fn (array $detail) => $detail['value'] !== null)
            ->values()
            ->all();
    }

    public function getExtraDetailsTextAttribute(): string
    {
        return collect($this->extra_details)
            ->map(fn (array $detail) => "{$detail['label']}: {$detail['value']}")
            ->implode("\n");
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(PublicationType::class, 'type_id', 'type_id');
    }

    public function article(): HasOne
    {
        return $this->hasOne(Article::class, 'publication_id', 'publication_id');
    }

    public function book(): HasOne
    {
        return $this->hasOne(Book::class, 'publication_id', 'publication_id');
    }

    public function researcherPublications(): HasMany
    {
        return $this->hasMany(ResearcherPublication::class, 'publication_id', 'publication_id');
    }

    public function researchers(): BelongsToMany
    {
        return $this->belongsToMany(Researcher::class, 'researcher_publication', 'publication_id', 'researcher_id')
            ->using(ResearcherPublication::class)
            ->withPivot('author_order');
    }
}
