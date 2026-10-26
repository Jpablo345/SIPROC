<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Los años eran varchar(4). Se pasan a smallint para ordenar y filtrar como número.
 * Solo PostgreSQL (el resto de migraciones del dominio también lo es).
 */
return new class extends Migration
{
    private array $columnas = [
        'publication' => 'publication_year',
        'event' => 'event_year',
        'audi_publication' => 'publication_year',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->columnas as $tabla => $columna) {
            $invalidos = DB::table($tabla)
                ->whereNotNull($columna)
                ->whereRaw("trim({$columna}) !~ '^\\d{4}$'")
                ->count();

            if ($invalidos > 0) {
                throw new RuntimeException("{$tabla}.{$columna} tiene {$invalidos} valores que no son un año de 4 dígitos; corrígelos antes de migrar.");
            }
        }

        foreach ($this->columnas as $tabla => $columna) {
            DB::statement("ALTER TABLE {$tabla} ALTER COLUMN {$columna} TYPE smallint USING NULLIF(trim({$columna}), '')::smallint");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->columnas as $tabla => $columna) {
            DB::statement("ALTER TABLE {$tabla} ALTER COLUMN {$columna} TYPE varchar(4) USING {$columna}::varchar");
        }
    }
};
