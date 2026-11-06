<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Tipos de publicación configurables: cada tipo define sus campos (publication_type_field)
 * y cada publicación guarda los valores en publication.extra_attributes (jsonb).
 * Articulo y Libro quedan marcados como tipos del sistema y no se configuran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('publication_type', function (Blueprint $table) {
            $table->boolean('is_system')->default(false);
        });

        DB::table('publication_type')
            ->whereRaw('lower(type_name) in (?, ?)', ['articulo', 'libro'])
            ->update(['is_system' => true]);

        Schema::create('publication_type_field', function (Blueprint $table) {
            $table->id('field_id');
            $table->foreignId('type_id')->constrained('publication_type', 'type_id')->cascadeOnDelete();
            $table->string('key', 50);
            $table->string('label', 100);
            $table->string('help_text', 255)->nullable();
            $table->string('field_type', 20);
            $table->boolean('required')->default(false);
            $table->boolean('multiple')->default(false);
            $table->jsonb('options')->nullable();
            $table->jsonb('rules')->nullable();
            $table->string('error_message', 255)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['type_id', 'key']);
        });

        Schema::table('publication', function (Blueprint $table) {
            $table->jsonb('extra_attributes')->nullable();
        });

        $this->givePermissionToManageTypes();

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE publication_type_field ADD CONSTRAINT ck_field_type CHECK (field_type IN ('texto', 'texto_largo', 'numero', 'fecha', 'url', 'lista', 'si_no'))");
        DB::statement('CREATE INDEX publication_extra_attributes_gin ON publication USING gin (extra_attributes)');

        DB::unprepared(<<<'SQL'
            ALTER TABLE public.audi_publication ADD COLUMN extra_attributes jsonb NULL;

            CREATE OR REPLACE FUNCTION public.audi_publication_func() RETURNS TRIGGER AS $TRG_GRABAR_AUDI_PUBLICATION$
            BEGIN
                IF (TG_OP = 'UPDATE') THEN
                    INSERT INTO public.audi_publication (consecutivo, publication_id, title, publication_year, fecha_registro, usuario, accion, app_user_id, extra_attributes)
                    VALUES (DEFAULT, OLD.publication_id, OLD.title, OLD.publication_year, CURRENT_TIMESTAMP(0), CURRENT_USER, 'U',
                            NULLIF(current_setting('app.user_id', true), '')::bigint, OLD.extra_attributes);
                    RETURN NEW;
                ELSIF (TG_OP = 'DELETE') THEN
                    INSERT INTO public.audi_publication (consecutivo, publication_id, title, publication_year, fecha_registro, usuario, accion, app_user_id, extra_attributes)
                    VALUES (DEFAULT, OLD.publication_id, OLD.title, OLD.publication_year, CURRENT_TIMESTAMP(0), CURRENT_USER, 'D',
                            NULLIF(current_setting('app.user_id', true), '')::bigint, OLD.extra_attributes);
                    RETURN OLD;
                END IF;
            END;
            $TRG_GRABAR_AUDI_PUBLICATION$ LANGUAGE PLPGSQL;

            CREATE TABLE public.audi_publication_type_field (
                consecutivo     bigserial PRIMARY KEY,
                field_id        bigint NULL,
                type_id         bigint NULL,
                datos_antes     jsonb NULL,
                datos_despues   jsonb NULL,
                fecha_registro  timestamp NULL,
                usuario         varchar(20) NULL,
                accion          char(1) NULL,
                app_user_id     bigint NULL
            );

            CREATE OR REPLACE FUNCTION public.audi_publication_type_field_func() RETURNS TRIGGER AS $TRG_GRABAR_AUDI_TYPE_FIELD$
            BEGIN
                IF (TG_OP = 'INSERT') THEN
                    INSERT INTO public.audi_publication_type_field (field_id, type_id, datos_antes, datos_despues, fecha_registro, usuario, accion, app_user_id)
                    VALUES (NEW.field_id, NEW.type_id, NULL, to_jsonb(NEW), CURRENT_TIMESTAMP(0), CURRENT_USER, 'I',
                            NULLIF(current_setting('app.user_id', true), '')::bigint);
                    RETURN NEW;
                ELSIF (TG_OP = 'UPDATE') THEN
                    INSERT INTO public.audi_publication_type_field (field_id, type_id, datos_antes, datos_despues, fecha_registro, usuario, accion, app_user_id)
                    VALUES (OLD.field_id, OLD.type_id, to_jsonb(OLD), to_jsonb(NEW), CURRENT_TIMESTAMP(0), CURRENT_USER, 'U',
                            NULLIF(current_setting('app.user_id', true), '')::bigint);
                    RETURN NEW;
                ELSIF (TG_OP = 'DELETE') THEN
                    INSERT INTO public.audi_publication_type_field (field_id, type_id, datos_antes, datos_despues, fecha_registro, usuario, accion, app_user_id)
                    VALUES (OLD.field_id, OLD.type_id, to_jsonb(OLD), NULL, CURRENT_TIMESTAMP(0), CURRENT_USER, 'D',
                            NULLIF(current_setting('app.user_id', true), '')::bigint);
                    RETURN OLD;
                END IF;
            END;
            $TRG_GRABAR_AUDI_TYPE_FIELD$ LANGUAGE PLPGSQL;

            CREATE TRIGGER TRG_GRABAR_AUDI_TYPE_FIELD
            AFTER INSERT OR UPDATE OR DELETE ON public.publication_type_field
            FOR EACH ROW EXECUTE FUNCTION public.audi_publication_type_field_func();
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                DROP TRIGGER IF EXISTS TRG_GRABAR_AUDI_TYPE_FIELD ON public.publication_type_field;
                DROP FUNCTION IF EXISTS public.audi_publication_type_field_func();
                DROP TABLE IF EXISTS public.audi_publication_type_field;

                CREATE OR REPLACE FUNCTION public.audi_publication_func() RETURNS TRIGGER AS $TRG_GRABAR_AUDI_PUBLICATION$
                BEGIN
                    IF (TG_OP = 'UPDATE') THEN
                        INSERT INTO public.audi_publication (consecutivo, publication_id, title, publication_year, fecha_registro, usuario, accion, app_user_id)
                        VALUES (DEFAULT, OLD.publication_id, OLD.title, OLD.publication_year, CURRENT_TIMESTAMP(0), CURRENT_USER, 'U',
                                NULLIF(current_setting('app.user_id', true), '')::bigint);
                        RETURN NEW;
                    ELSIF (TG_OP = 'DELETE') THEN
                        INSERT INTO public.audi_publication (consecutivo, publication_id, title, publication_year, fecha_registro, usuario, accion, app_user_id)
                        VALUES (DEFAULT, OLD.publication_id, OLD.title, OLD.publication_year, CURRENT_TIMESTAMP(0), CURRENT_USER, 'D',
                                NULLIF(current_setting('app.user_id', true), '')::bigint);
                        RETURN OLD;
                    END IF;
                END;
                $TRG_GRABAR_AUDI_PUBLICATION$ LANGUAGE PLPGSQL;

                ALTER TABLE public.audi_publication DROP COLUMN IF EXISTS extra_attributes;
                DROP INDEX IF EXISTS publication_extra_attributes_gin;
            SQL);
        }

        Schema::table('publication', function (Blueprint $table) {
            $table->dropColumn('extra_attributes');
        });

        Schema::dropIfExists('publication_type_field');

        Schema::table('publication_type', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });

        Permission::where('name', 'gestionar-tipos')->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Permiso para crear y configurar tipos: Docente y Administrador.
     * También está en RoleSeeder para instalaciones nuevas.
     */
    private function givePermissionToManageTypes(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'gestionar-tipos', 'guard_name' => 'web']);

        foreach (['Docente', 'Administrador'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()?->givePermissionTo('gestionar-tipos');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
