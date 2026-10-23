<?php

namespace App\Livewire\Forms;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

class LoginForm extends Form
{
    public string $email = '';

    public string $code = '';

    public string $password = '';

    public bool $remember = false;

    protected function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'code' => ['required', 'regex:/^(\d{5}|\d{7})$/'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'code.required' => 'Ingresa tu código.',
            'code.regex' => 'El código no es válido.',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // El tipo de usuario se deduce de la longitud del código:
        // 7 dígitos = estudiante, 5 dígitos = docente/investigador.
        $credentials = [
            'email' => $this->email,
            'password' => $this->password,
            'code' => $this->code,
            'user_type' => strlen($this->code) === 7 ? 'student' : 'teacher',
            // Solo entran cuentas con el correo verificado por OTP.
            fn ($query) => $query->whereNotNull('email_verified_at'),
        ];

        if (! Auth::attempt($credentials, $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}