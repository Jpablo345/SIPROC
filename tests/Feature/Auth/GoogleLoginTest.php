<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_with_role_logs_in(): void
    {
        $user = User::factory()->withRole('Estudiante')->create();
        $this->fakeGoogleUser(strtoupper($user->email));

        $this->get('/auth/google/callback')->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_non_institutional_email_is_rejected(): void
    {
        $this->fakeGoogleUser('alguien@gmail.com');

        $this->get('/auth/google/callback')->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_unknown_email_is_rejected(): void
    {
        $this->fakeGoogleUser('nadie@ufpso.edu.co');

        $this->get('/auth/google/callback')->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_unverified_user_is_rejected(): void
    {
        $user = User::factory()->unverified()->withRole('Estudiante')->create();
        $this->fakeGoogleUser($user->email);

        $this->get('/auth/google/callback')->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_without_role_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->fakeGoogleUser($user->email);

        $this->get('/auth/google/callback')->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_invalid_state_redirects_to_login_instead_of_failing(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andThrow(new InvalidStateException());
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get('/auth/google/callback')->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    private function fakeGoogleUser(string $email): void
    {
        $googleUser = (new GoogleUser())->map(['id' => '123', 'name' => 'Google', 'email' => $email]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }
}
