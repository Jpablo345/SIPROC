<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permisos = [
            'consultar', 'agregar',
            'editar-propios', 'eliminar-propios',
            'editar-todos', 'eliminar-todos',
            'gestionar-usuarios',
        ];

        foreach ($permisos as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'Estudiante', 'guard_name' => 'web'])
            ->syncPermissions(['consultar', 'agregar', 'editar-propios']);

        Role::firstOrCreate(['name' => 'Docente', 'guard_name' => 'web'])
            ->syncPermissions(['consultar', 'agregar', 'editar-propios', 'eliminar-propios']);

        Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web'])
            ->syncPermissions(Permission::all());
    }
}