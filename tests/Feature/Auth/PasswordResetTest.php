<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\CodigoRestablecerContrasena;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $this->get('/forgot-password')
            ->assertSeeVolt('pages.auth.forgot-password')
            ->assertOk();
    }

    public function test_reset_code_is_sent_to_a_verified_user(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        Volt::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('enviarCodigo')
            ->assertHasNoErrors()
            ->assertSet('paso', 'codigo');

        Notification::assertSentTo($user, CodigoRestablecerContrasena::class);
    }

    public function test_unknown_email_gets_the_same_answer_without_sending_anything(): void
    {
        Notification::fake();

        Volt::test('pages.auth.forgot-password')
            ->set('email', 'nadie@ufpso.edu.co')
            ->call('enviarCodigo')
            ->assertHasNoErrors()
            ->assertSet('paso', 'codigo');

        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_with_the_code(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('enviarCodigo');

        $codigo = null;
        Notification::assertSentTo($user, CodigoRestablecerContrasena::class, function ($n) use (&$codigo) {
            $codigo = $n->codigo->password;

            return true;
        });

        $component
            ->set('codigo', $codigo)
            ->set('password', 'NuevaClave123')
            ->set('password_confirmation', 'NuevaClave123')
            ->call('restablecer')
            ->assertHasNoErrors()
            ->assertRedirect('/login');

        $this->assertTrue(Hash::check('NuevaClave123', $user->fresh()->password));
    }

    public function test_wrong_code_does_not_change_the_password(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $anterior = $user->password;

        Volt::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('enviarCodigo')
            ->set('codigo', '000000')
            ->set('password', 'NuevaClave123')
            ->set('password_confirmation', 'NuevaClave123')
            ->call('restablecer')
            ->assertHasErrors('codigo');

        $this->assertSame($anterior, $user->fresh()->password);
    }

    public function test_a_second_code_must_wait_a_minute(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        Volt::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('enviarCodigo')
            ->call('reenviar')
            ->assertHasErrors('codigo');

        Notification::assertSentToTimes($user, CodigoRestablecerContrasena::class, 1);
    }

    public function test_old_reset_links_lead_to_the_new_screen(): void
    {
        $this->get('/reset-password/token-antiguo?email=a@ufpso.edu.co')
            ->assertRedirect('/forgot-password');
    }
}
