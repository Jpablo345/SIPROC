<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\HasCreator;


class Institution extends Model
{
    use HasFactory, HasCreator;

    protected $table = 'institution';
    protected $primaryKey = 'institution_id';

    protected $fillable = [
        'institution_name',
        'country',
        'city',
        'institution_type',
        'website',
    ];

    public function researchGroups(): HasMany
    {
        return $this->hasMany(ResearchGroup::class, 'institution_id', 'institution_id');
    }

    public function hostEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'host_institution_id', 'institution_id');
    }

    public function originEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'origin_institution_id', 'institution_id');
    }
}
