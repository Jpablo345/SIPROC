<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AudiResearcher extends Model
{
    use HasFactory;

    protected $table = 'audi_researcher';
    protected $primaryKey = 'consecutivo';
    public $timestamps = false;

    protected $fillable = [
        'researcher_id',
        'name_1',
        'last_name_1',
        'cod_minciencias',
        'fecha_registro',
        'usuario',
        'accion',
        'app_user_id',
    ];
}
