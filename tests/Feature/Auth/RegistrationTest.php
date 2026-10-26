<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Volt\Volt;
use Spatie\OneTimePasswords\Notifications\OneTimePasswordNotification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_student_receives_a_code_and_is_created_unverified(): void
    {
        $component = $this->fillForm()->call('register');

        $component
            ->assertHasNoErrors()
            ->assertSet('step', 'otp');

        $user = User::where('email', 'nuevo@ufpso.edu.co')->firstOrFail();

        $this->assertNull($user->email_verified_at);
        $this->assertSame('student', $user->user_type);
        Notification::assertSentTo($user, OneTimePasswordNotification::class);
        $this->assertGuest();
    }

    public function test_student_with_valid_code_is_verified_logged_in_and_gets_role(): void
    {
        $component = $this->fillForm()->call('register');
        $user = User::where('email', 'nuevo@ufpso.edu.co')->firstOrFail();

        $component
            ->set('otp', $this->sentCode($user))
            ->call('verifyOtp')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('Estudiante'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_teacher_gets_the_docente_role(): void
    {
        $component = $this->fillForm(type: 'teacher', code: '12345')->call('register');
        $user = User::where('email', 'nuevo@ufpso.edu.co')->firstOrFail();

        $component->set('otp', $this->sentCode($user))->call('verifyOtp')->assertHasNoErrors();

        $this->assertTrue($user->fresh()->hasRole('Docente'));
    }

    public function test_wrong_code_does_not_verify_the_account(): void
    {
        $component = $this->fillForm()->call('register');
        $user = User::where('email', 'nuevo@ufpso.edu.co')->firstOrFail();
        $wrong = $this->sentCode($user) === '000000' ? '111111' : '000000';

        $component
            ->set('otp', $wrong)
            ->call('verifyOtp')
            ->assertHasErrors('otp');

        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertGuest();
    }

    public function test_only_institutional_emails_are_accepted(): void
    {
        $this->fillForm(email: 'alguien@gmail.com')
            ->call('register')
            ->assertHasErrors(['email' => 'ends_with']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_student_code_must_have_seven_digits(): void
    {
        $this->fillForm(code: '12345')
            ->call('register')
            ->assertHasErrors(['code' => 'digits']);
    }

    public function test_verified_email_can_not_be_registered_again(): void
    {
        User::factory()->create(['email' => 'nuevo@ufpso.edu.co']);

        $this->fillForm()
            ->call('register')
            ->assertHasErrors(['email' => 'unique']);
    }

    public function test_a_pending_registration_is_replaced_by_a_new_one(): void
    {
        $this->fillForm()->call('register');
        $this->travel(2)->minutes();

        $this->fillForm(name: 'Otro Nombre')->call('register')->assertHasNoErrors();

        $this->assertSame(1, User::where('email', 'nuevo@ufpso.edu.co')->count());
        $this->assertSame('Otro Nombre', User::where('email', 'nuevo@ufpso.edu.co')->value('name'));
    }

    public function test_the_same_email_can_only_request_one_code_per_minute(): void
    {
        $this->fillForm()->call('register')->assertHasNoErrors();

        $this->fillForm()->call('register')->assertHasErrors('email');

        Notification::assertSentTimes(OneTimePasswordNotification::class, 1);
    }

    public function test_registration_is_limited_per_ip(): void
    {
        foreach (range(1, 5) as $i) {
            $this->fillForm(email: "persona{$i}@ufpso.edu.co", code: "100000{$i}")->call('register')->assertHasNoErrors();
        }

        $this->fillForm(email: 'persona6@ufpso.edu.co', code: '1000006')
            ->call('register')
            ->assertHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'persona6@ufpso.edu.co']);
    }

    private function fillForm(
        string $type = 'student',
        string $code = '1234567',
        string $email = 'nuevo@ufpso.edu.co',
        string $name = 'Usuario Nuevo',
    ): Testable {
        return Volt::test('pages.auth.register')
            ->call('setType', $type)
            ->set('name', $name)
            ->set('email', $email)
            ->set('code', $code)
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123');
    }

    private function sentCode(User $user): string
    {
        $code = null;

        Notification::assertSentTo($user, OneTimePasswordNotification::class, function ($notification) use (&$code) {
            $code = $notification->oneTimePassword->password;

            return true;
        });

        return $code;
    }
}
