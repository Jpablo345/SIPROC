<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
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
            Schema::table($tabla, function (Blueprint $t) {
                $t->foreignId('created_by')->nullable()
                    ->constrained('users')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $t) {
                $t->dropConstrainedForeignId('created_by');
            });
        }
    }
};
