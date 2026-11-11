<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\Concerns\HasCreator;


class PublicationType extends Model
{
    use HasFactory, HasCreator;

    protected $table = 'publication_type';
    protected $primaryKey = 'type_id';

    // is_system no es asignable: solo la migración marca Articulo y Libro.
    protected $fillable = [
        'type_name',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class, 'type_id', 'type_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(PublicationTypeField::class, 'type_id', 'type_id')->orderBy('sort_order');
    }

    public function activeFields(): HasMany
    {
        return $this->fields()->where('active', true);
    }

    /**
     * Nombre comparable sin mayúsculas ni tildes ("Artículo" y "articulo" son el mismo tipo).
     */
    public static function normalizeName(string $name): string
    {
        return Str::lower(Str::ascii(trim($name)));
    }

    /**
     * Id de un tipo por su nombre, sin distinguir mayúsculas ("articulo", "libro").
     * Lo usan las pantallas de Tipología para asignar el tipo del sistema que les corresponde.
     */
    public static function idByName(string $name): ?int
    {
        return static::query()
            ->whereRaw('lower(type_name) = ?', [Str::lower($name)])
            ->value('type_id');
    }
}
