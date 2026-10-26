<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AudiPublication extends Model
{
    use HasFactory;

    protected $table = 'audi_publication';
    protected $primaryKey = 'consecutivo';
    public $timestamps = false;

    protected $fillable = [
        'publication_id',
        'title',
        'publication_year',
        'fecha_registro',
        'usuario',
        'accion',
        'app_user_id',
    ];
}
