<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PostgreSQL no crea índices para las llaves foráneas. Sin ellos, los joins,
 * los whereHas y los borrados en cascada recorren la tabla completa.
 */
return new class extends Migration
{
    private array $indices = [
        'researcher' => ['cod_minciencias', 'created_by'],
        'researcher_publication' => ['researcher_id'],
        'researcher_event' => ['researcher_id'],
        'publication' => ['type_id', 'created_by'],
        'article' => ['journal_issn'],
        'book' => ['book_type_id'],
        'event' => ['host_institution_id', 'origin_institution_id', 'created_by'],
        'research_group' => ['institution_id', 'created_by'],
        'institution' => ['created_by'],
        'journal' => ['created_by'],
        'publication_type' => ['created_by'],
        'book_type' => ['created_by'],
    ];

    public function up(): void
    {
        foreach ($this->indices as $tabla => $columnas) {
            Schema::table($tabla, function (Blueprint $table) use ($columnas) {
                foreach ($columnas as $columna) {
                    $table->index($columna);
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indices as $tabla => $columnas) {
            Schema::table($tabla, function (Blueprint $table) use ($columnas) {
                foreach ($columnas as $columna) {
                    $table->dropIndex([$columna]);
                }
            });
        }
    }
};
