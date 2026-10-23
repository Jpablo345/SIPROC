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
    public $timestamps = false;

    protected $fillable = [
        'title',
        'publication_year',
        'scope',
        'country_publication',
        'url',
        'type_id',
    ];

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
