<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 'student' o 'teacher'. Nullable para no romper los usuarios que ya existen.
            $table->string('user_type', 20)->nullable()->after('email');
            // Código estudiantil (7 dígitos) o docente (5 dígitos).
            $table->string('code', 7)->nullable()->unique()->after('user_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['user_type', 'code']);
        });
    }
};