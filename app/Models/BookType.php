<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\HasCreator;


class BookType extends Model
{
    use HasFactory, HasCreator;

    protected $table = 'book_type';
    protected $primaryKey = 'book_type_id';
    public $timestamps = false;

    protected $fillable = [
        'type_name',
    ];

    public function books(): HasMany
    {
        return $this->hasMany(Book::class, 'book_type_id', 'book_type_id');
    }
}
