<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Restricciones que la app ya valida, ahora también en la BD:
 * tipo de usuario y URLs con esquema http/https (evita enlaces javascript:).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE users ADD CONSTRAINT ck_users_user_type CHECK (user_type IS NULL OR user_type IN ('student', 'teacher'))");
        DB::statement("ALTER TABLE publication ADD CONSTRAINT ck_publication_url CHECK (url IS NULL OR url ~* '^https?://')");
        DB::statement("ALTER TABLE event ADD CONSTRAINT ck_event_url CHECK (event_url IS NULL OR event_url ~* '^https?://')");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS ck_users_user_type');
        DB::statement('ALTER TABLE publication DROP CONSTRAINT IF EXISTS ck_publication_url');
        DB::statement('ALTER TABLE event DROP CONSTRAINT IF EXISTS ck_event_url');
    }
};
