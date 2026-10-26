<?php

namespace Tests;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Con RefreshDatabase se siembran los roles y permisos antes de cada test.
     */
    protected bool $seed = true;

    protected string $seeder = RoleSeeder::class;

    /**
     * RefreshDatabase ejecuta migrate:fresh: nunca debe correr sobre la BD de desarrollo.
     * Se comprueba aquí porque setUpTraits() corre antes de refrescar la BD.
     */
    protected function setUpTraits()
    {
        if (in_array(RefreshDatabase::class, class_uses_recursive(static::class), true)) {
            $database = DB::connection()->getDatabaseName();

            if (! str_ends_with($database, '_test')) {
                throw new RuntimeException("Los tests solo pueden usar una BD terminada en _test (actual: {$database}).");
            }
        }

        return parent::setUpTraits();
    }
}
