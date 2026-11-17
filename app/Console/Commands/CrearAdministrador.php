<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Crea una cuenta con el rol Administrador, que no se puede elegir al registrarse.
 * Se usa al instalar SIPROC por primera vez (por ejemplo, en el paquete de Docker).
 *
 * Interactivo:      php artisan siproc:crear-admin
 * Sin preguntas:    php artisan siproc:crear-admin --name="..." --email=... --code=12345 --password=...
 */
class CrearAdministrador extends Command
{
    protected $signature = 'siproc:crear-admin
        {--name= : Nombre completo}
        {--email= : Correo con el que inicia sesión}
        {--code= : Código de docente (5 dígitos), se pide al iniciar sesión}
        {--password= : Contraseña (mínimo 8 caracteres)}';

    protected $description = 'Crea un usuario con el rol Administrador';

    public function handle(): int
    {
        // Los roles los crea RoleSeeder; en una instalación nueva puede que todavía no existan.
        if (! Role::where('name', 'Administrador')->where('guard_name', 'web')->exists()) {
            $this->components->info('Creando roles y permisos...');
            $this->callSilently('db:seed', ['--class' => RoleSeeder::class, '--force' => true]);
        }

        $data = [
            'name' => $this->option('name') ?? text('Nombre completo', required: true),
            'email' => $this->option('email') ?? text('Correo', required: true),
            'code' => $this->option('code') ?? text('Código de docente (5 dígitos)', required: true, hint: 'Se pide junto al correo al iniciar sesión.'),
            'password' => $this->option('password') ?? password('Contraseña', required: true, hint: 'Mínimo 8 caracteres.'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'code' => ['required', 'digits:5', 'unique:users,code'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'code.digits' => 'El código debe tener exactamente 5 dígitos.',
            'code.unique' => 'Ese código ya está registrado.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        // Cuenta de tipo docente (código de 5 dígitos), ya verificada para que pueda entrar sin código OTP.
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'user_type' => 'teacher',
            'code' => $data['code'],
            'password' => $data['password'],
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->assignRole('Administrador');

        $this->components->info("Administrador creado: {$user->email}. Inicia sesión con ese correo, el código {$user->code} y la contraseña.");

        return self::SUCCESS;
    }
}
