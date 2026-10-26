<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\HasCreator;

class Event extends Model
{
    use HasFactory, HasCreator;

    protected $table = 'event';
    protected $primaryKey = 'event_id';

    protected $fillable = [
        'event_name',
        'event_year',
        'event_month',
        'event_scope',
        'event_url',
        'host_institution_id',
        'origin_institution_id',
    ];

    public function hostInstitution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'host_institution_id', 'institution_id');
    }

    public function originInstitution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'origin_institution_id', 'institution_id');
    }

    public function researcherEvents(): HasMany
    {
        return $this->hasMany(ResearcherEvent::class, 'event_id', 'event_id');
    }

    public function researchers(): BelongsToMany
    {
        return $this->belongsToMany(Researcher::class, 'researcher_event', 'event_id', 'researcher_id')
            ->withPivot('event_part_id', 'presentation_title', 'participation_type');
    }
}
