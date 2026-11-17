<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CrearAdministradorTest extends TestCase
{
    use RefreshDatabase;

    private function adminOptions(array $overrides = []): array
    {
        return [
            '--name' => 'Laura Sanjuán',
            '--email' => 'laura.sanjuan@ufpso.edu.co',
            '--code' => '54321',
            '--password' => 'clave-segura-123',
            ...$overrides,
        ];
    }

    public function test_creates_a_verified_administrator_and_the_roles_if_missing(): void
    {
        // Instalación nueva: todavía no corrió RoleSeeder.
        Role::query()->delete();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->artisan('siproc:crear-admin', $this->adminOptions())->assertSuccessful();

        $user = User::where('email', 'laura.sanjuan@ufpso.edu.co')->firstOrFail();
        $this->assertTrue($user->hasRole('Administrador'));
        $this->assertTrue($user->can('gestionar-tipos'));
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('teacher', $user->user_type);
        $this->assertSame('54321', $user->code);
    }

    public function test_the_new_administrator_can_log_in(): void
    {
        $this->artisan('siproc:crear-admin', $this->adminOptions())->assertSuccessful();

        $this->assertTrue(auth()->attempt([
            'email' => 'laura.sanjuan@ufpso.edu.co',
            'code' => '54321',
            'user_type' => 'teacher',
            'password' => 'clave-segura-123',
        ]));
    }

    public function test_rejects_repeated_email_or_invalid_code(): void
    {
        $this->artisan('siproc:crear-admin', $this->adminOptions())->assertSuccessful();

        $this->artisan('siproc:crear-admin', $this->adminOptions(['--code' => '11111']))
            ->expectsOutputToContain('Ya existe un usuario con ese correo.')
            ->assertFailed();

        $this->artisan('siproc:crear-admin', $this->adminOptions(['--email' => 'otra@ufpso.edu.co', '--code' => '123']))
            ->expectsOutputToContain('El código debe tener exactamente 5 dígitos.')
            ->assertFailed();

        $this->assertSame(1, User::count());
    }
}
