<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Los triggers de auditoría guardaban CURRENT_USER, que es siempre el usuario de
 * PostgreSQL de la aplicación. Ahora también guardan el usuario de la app, que el
 * middleware SetDatabaseAuditUser publica en la variable de sesión app.user_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            ALTER TABLE public.audi_researcher ADD COLUMN app_user_id bigint NULL;
            ALTER TABLE public.audi_publication ADD COLUMN app_user_id bigint NULL;
            ALTER TABLE public.audi_researcher ALTER COLUMN researcher_id TYPE bigint USING researcher_id::bigint;

            CREATE OR REPLACE FUNCTION public.audi_researcher_func() RETURNS TRIGGER AS $TRG_GRABAR_AUDI_RESEARCHER$
            BEGIN
                IF (TG_OP = 'UPDATE') THEN
                    INSERT INTO public.audi_researcher (consecutivo, researcher_id, name_1, last_name_1, cod_minciencias, fecha_registro, usuario, accion, app_user_id)
                    VALUES (DEFAULT, OLD.researcher_id, OLD.name_1, OLD.last_name_1, OLD.cod_minciencias, CURRENT_TIMESTAMP(0), CURRENT_USER, 'U',
                            NULLIF(current_setting('app.user_id', true), '')::bigint);
                    RETURN NEW;
                ELSIF (TG_OP = 'DELETE') THEN
                    INSERT INTO public.audi_researcher (consecutivo, researcher_id, name_1, last_name_1, cod_minciencias, fecha_registro, usuario, accion, app_user_id)
                    VALUES (DEFAULT, OLD.researcher_id, OLD.name_1, OLD.last_name_1, OLD.cod_minciencias, CURRENT_TIMESTAMP(0), CURRENT_USER, 'D',
                            NULLIF(current_setting('app.user_id', true), '')::bigint);
                    RETURN OLD;
                END IF;
            END;
            $TRG_GRABAR_AUDI_RESEARCHER$ LANGUAGE PLPGSQL;

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
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Restaura las funciones originales (sin app_user_id) antes de quitar la columna.
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.audi_researcher_func() RETURNS TRIGGER AS $TRG_GRABAR_AUDI_RESEARCHER$
            BEGIN
                IF (TG_OP = 'UPDATE') THEN
                    INSERT INTO public.audi_researcher (consecutivo, researcher_id, name_1, last_name_1, cod_minciencias, fecha_registro, usuario, accion)
                    VALUES (DEFAULT, OLD.researcher_id, OLD.name_1, OLD.last_name_1, OLD.cod_minciencias, CURRENT_TIMESTAMP(0), CURRENT_USER, 'U');
                    RETURN NEW;
                ELSIF (TG_OP = 'DELETE') THEN
                    INSERT INTO public.audi_researcher (consecutivo, researcher_id, name_1, last_name_1, cod_minciencias, fecha_registro, usuario, accion)
                    VALUES (DEFAULT, OLD.researcher_id, OLD.name_1, OLD.last_name_1, OLD.cod_minciencias, CURRENT_TIMESTAMP(0), CURRENT_USER, 'D');
                    RETURN OLD;
                END IF;
            END;
            $TRG_GRABAR_AUDI_RESEARCHER$ LANGUAGE PLPGSQL;

            CREATE OR REPLACE FUNCTION public.audi_publication_func() RETURNS TRIGGER AS $TRG_GRABAR_AUDI_PUBLICATION$
            BEGIN
                IF (TG_OP = 'UPDATE') THEN
                    INSERT INTO public.audi_publication (consecutivo, publication_id, title, publication_year, fecha_registro, usuario, accion)
                    VALUES (DEFAULT, OLD.publication_id, OLD.title, OLD.publication_year, CURRENT_TIMESTAMP(0), CURRENT_USER, 'U');
                    RETURN NEW;
                ELSIF (TG_OP = 'DELETE') THEN
                    INSERT INTO public.audi_publication (consecutivo, publication_id, title, publication_year, fecha_registro, usuario, accion)
                    VALUES (DEFAULT, OLD.publication_id, OLD.title, OLD.publication_year, CURRENT_TIMESTAMP(0), CURRENT_USER, 'D');
                    RETURN OLD;
                END IF;
            END;
            $TRG_GRABAR_AUDI_PUBLICATION$ LANGUAGE PLPGSQL;

            ALTER TABLE public.audi_researcher ALTER COLUMN researcher_id TYPE varchar(10) USING researcher_id::varchar;
            ALTER TABLE public.audi_researcher DROP COLUMN app_user_id;
            ALTER TABLE public.audi_publication DROP COLUMN app_user_id;
        SQL);
    }
};
