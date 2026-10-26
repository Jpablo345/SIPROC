<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * created_at / updated_at en las tablas que gestionan los usuarios.
 * Son nullable: los registros existentes quedan sin fecha.
 */
return new class extends Migration
{
    private array $tablas = [
        'publication',
        'event',
        'researcher',
        'research_group',
        'institution',
        'journal',
        'publication_type',
        'book_type',
    ];

    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropTimestamps();
            });
        }
    }
};
