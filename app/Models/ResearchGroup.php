<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\HasCreator;


class ResearchGroup extends Model
{
    use HasFactory, HasCreator;

    protected $table = 'research_group';
    protected $primaryKey = 'cod_minciencias';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'cod_minciencias',
        'group_name',
        'group_classification',
        'institution_id',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id', 'institution_id');
    }

    public function researchers(): HasMany
    {
        return $this->hasMany(Researcher::class, 'cod_minciencias', 'cod_minciencias');
    }
}
