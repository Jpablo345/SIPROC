<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\HasCreator;


class Researcher extends Model
{
    use HasFactory, HasCreator;

    protected $table = 'researcher';
    protected $primaryKey = 'researcher_id';
    public $incrementing = true;       // ahora autoincremental
    protected $keyType = 'int';

    protected $fillable = [
        'document',       // documento (cédula, pasaporte, etc.) — opcional
        'name_1',
        'name_2',
        'last_name_1',
        'last_name_2',
        'email',
        'phone',
        'cod_minciencias',
    ];

    public function researchGroup(): BelongsTo
    {
        return $this->belongsTo(ResearchGroup::class, 'cod_minciencias', 'cod_minciencias');
    }

    public function publications(): BelongsToMany
    {
        return $this->belongsToMany(Publication::class, 'researcher_publication', 'researcher_id', 'publication_id')
            ->using(ResearcherPublication::class)
            ->withPivot('author_order');
    }

    public function researcherEvents(): HasMany
    {
        return $this->hasMany(ResearcherEvent::class, 'researcher_id', 'researcher_id');
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'researcher_event', 'researcher_id', 'event_id')
            ->withPivot('event_part_id', 'presentation_title', 'participation_type');
    }

    // Nombre completo sin espacios dobles cuando falta el segundo nombre o apellido
    public function getFullNameAttribute(): string
    {
        return implode(' ', array_filter([$this->name_1, $this->name_2, $this->last_name_1, $this->last_name_2]));
    }

    /**
     * Búsqueda de los selectores: por id exacto (si es numérico), documento o nombres.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%' . $term . '%';

        return $query->where(function (Builder $inner) use ($term, $like) {
            // researcher_id es bigint: ilike sobre él falla en PostgreSQL, se compara exacto.
            $inner->when(ctype_digit($term), fn (Builder $q) => $q->orWhere('researcher_id', (int) $term))
                ->orWhere('document', 'ilike', $like)
                ->orWhere('name_1', 'ilike', $like)
                ->orWhere('name_2', 'ilike', $like)
                ->orWhere('last_name_1', 'ilike', $like)
                ->orWhere('last_name_2', 'ilike', $like);
        });
    }

    /**
     * Forma en que los selectores guardan un autor/conferencista elegido.
     *
     * @return array{researcher_id: int, name: string, group: ?string}
     */
    public function toOption(): array
    {
        return [
            'researcher_id' => $this->researcher_id,
            'name' => $this->full_name,
            'group' => $this->researchGroup?->group_name,
        ];
    }
}