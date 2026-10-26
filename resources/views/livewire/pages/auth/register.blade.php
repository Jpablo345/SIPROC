<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;
use Spatie\OneTimePasswords\Enums\ConsumeOneTimePasswordResult;

new #[Layout('layouts.guest')]
    class extends Component {
    public string $user_type = 'student';
    public string $name = '';
    public string $email = '';
    public string $code = '';
    public string $password = '';
    public string $password_confirmation = '';

    // Paso del registro: 'form' (datos) u 'otp' (verificación del correo)
    public string $step = 'form';
    public string $otp = '';
    public string $notice = '';

    #[Locked]
    public ?int $pendingUserId = null;

    /**
     * Cambia entre estudiante y docente/investigador.
     */
    public function setType(string $type): void
    {
        if (!in_array($type, ['student', 'teacher'], true)) {
            return;
        }

        $this->user_type = $type;
        $this->code = '';
        $this->resetValidation('code');
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $isStudent = $this->user_type === 'student';
        $length = $isStudent ? 7 : 5;
        $label = $isStudent ? 'de estudiante' : 'docente';

        $validated = $this->validate([
            'user_type' => ['required', 'in:student,teacher'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'ends_with:@ufpso.edu.co', Rule::unique('users', 'email')->whereNotNull('email_verified_at')],
            'code' => ['required', 'digits:' . $length, Rule::unique('users', 'code')->whereNotNull('email_verified_at')],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ], [
            'email.ends_with' => 'Debes usar tu correo institucional (@ufpso.edu.co).',
            'email.unique' => 'Ese correo ya está registrado.',
            'code.required' => "Ingresa el código $label.",
            'code.digits' => $isStudent
                ? 'El código de estudiante debe tener exactamente 7 dígitos numéricos.'
                : 'El código docente no es válido.',
            'code.unique' => "Ese código $label ya está registrado.",
        ]);

        // Límite de envíos: 5 registros por IP cada 10 minutos y un código por correo cada minuto.
        $ipKey = $this->registerIpKey();

        if (RateLimiter::tooManyAttempts($ipKey, 5)) {
            $this->addError('email', 'Demasiados intentos de registro. Espera ' . ceil(RateLimiter::availableIn($ipKey) / 60) . ' minutos e inténtalo de nuevo.');

            return;
        }

        $emailKey = $this->resendKey($validated['email']);

        if (RateLimiter::tooManyAttempts($emailKey, 1)) {
            $this->addError('email', 'Espera ' . RateLimiter::availableIn($emailKey) . ' segundos para pedir otro código.');

            return;
        }

        // Un registro sin verificar no bloquea el correo ni el código: se reemplaza.
        User::whereNull('email_verified_at')
            ->whereNotNull('user_type')
            ->where(fn($q) => $q->where('email', $validated['email'])->orWhere('code', $validated['code']))
            ->delete();

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        try {
            $user->sendOneTimePassword();
        } catch (\Throwable $e) {
            report($e);
            $user->delete();
            $this->addError('email', 'No pudimos enviar el código de verificación. Inténtalo de nuevo en unos minutos.');

            return;
        }

        RateLimiter::hit($emailKey, 60);
        RateLimiter::hit($ipKey, 600);

        $this->pendingUserId = $user->id;
        $this->otp = '';
        $this->notice = '';
        $this->step = 'otp';
    }

    /**
     * Verifica el código enviado al correo y completa el registro.
     */
    public function verifyOtp(): void
    {
        $length = (int) config('one-time-passwords.password_length', 6);

        $this->validate([
            'otp' => ['required', 'digits:' . $length],
        ], [
            'otp.required' => 'Ingresa el código que te enviamos.',
            'otp.digits' => "El código tiene $length dígitos.",
        ]);

        $user = $this->pendingUser();

        if (!$user) {
            $this->backToForm();

            return;
        }

        $result = $user->consumeOneTimePassword($this->otp);

        if (!$result->isOk()) {
            $this->addError('otp', $this->otpErrorMessage($result));

            return;
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        $user->assignRole($user->user_type === 'student' ? 'Estudiante' : 'Docente');

        event(new Registered($user));

        Auth::login($user);
        session()->regenerate();

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Envía un código nuevo (máximo uno por minuto).
     */
    public function resend(): void
    {
        $user = $this->pendingUser();

        if (!$user) {
            $this->backToForm();

            return;
        }

        $key = $this->resendKey($user->email);

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $this->addError('otp', 'Espera ' . RateLimiter::availableIn($key) . ' segundos para pedir otro código.');

            return;
        }

        try {
            $user->sendOneTimePassword();
        } catch (\Throwable $e) {
            report($e);
            $this->addError('otp', 'No pudimos reenviar el código. Inténtalo de nuevo en unos minutos.');

            return;
        }

        RateLimiter::hit($key, 60);

        $this->otp = '';
        $this->resetValidation();
        $this->notice = 'Te enviamos un código nuevo.';
    }

    /**
     * Vuelve al formulario para corregir el correo u otros datos.
     */
    public function backToForm(): void
    {
        $this->step = 'form';
        $this->pendingUserId = null;
        $this->otp = '';
        $this->notice = '';
        $this->resetValidation();
    }

    protected function pendingUser(): ?User
    {
        $user = $this->pendingUserId ? User::find($this->pendingUserId) : null;

        return ($user && !$user->email_verified_at) ? $user : null;
    }

    // Por correo (no por id): al re-registrarse se crea un usuario nuevo y el límite por id se evadía.
    protected function resendKey(string $email): string
    {
        return 'register-otp-resend:' . sha1(strtolower($email));
    }

    protected function registerIpKey(): string
    {
        return 'register-ip:' . request()->ip();
    }

    protected function otpErrorMessage(ConsumeOneTimePasswordResult $result): string
    {
        return match ($result) {
            ConsumeOneTimePasswordResult::OneTimePasswordExpired => 'El código expiró. Solicita uno nuevo.',
            ConsumeOneTimePasswordResult::NoOneTimePasswordsFound => 'No hay un código activo. Solicita uno nuevo.',
            ConsumeOneTimePasswordResult::RateLimitExceeded => 'Demasiados intentos. Espera un momento e inténtalo de nuevo.',
            ConsumeOneTimePasswordResult::DifferentOrigin => 'Verifica el código desde el mismo navegador en el que te registraste.',
            default => 'El código es incorrecto.',
        };
    }
}; ?>

<div class="w-full">
    <div class="flex flex-col items-center mb-8">
        <div class="w-24 h-24  p-2 shadow-lg ">
            <img src="{{ asset('img/logoufps.png') }}" alt="UFPS" class="w-full h-auto" />
        </div>
        <h2 class="mt-4 text-xl font-black text-white uppercase tracking-tight">
            Registro Institucional
        </h2>
        <div class="h-1 w-10 bg-red-600 mt-1"></div>
    </div>

    <div class="bg-white p-8 rounded-2xl shadow-2xl border border-red-200">
        @if ($step === 'form')
            <form wire:submit="register" class="space-y-5">

                <div class="grid grid-cols-2 gap-1 p-1 bg-red-50 border border-red-200 rounded-xl">
                    <button type="button" wire:click="setType('student')"
                        class="py-2 rounded-lg text-xs font-black uppercase tracking-wider transition-all {{ $user_type === 'student' ? 'bg-red-600 text-white shadow' : 'text-red-700 hover:bg-red-100' }}">
                        Estudiante
                    </button>
                    <button type="button" wire:click="setType('teacher')"
                        class="py-2 rounded-lg text-xs font-black uppercase tracking-wider transition-all {{ $user_type === 'teacher' ? 'bg-red-600 text-white shadow' : 'text-red-700 hover:bg-red-100' }}">
                        Docente / Investigador
                    </button>
                </div>

                <div>
                    <x-input-label for="name" :value="__('Nombre Completo')" class="font-bold text-zinc-700" />
                    <x-text-input wire:model="name" id="name"
                        class="block mt-1 w-full border-red-200 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500"
                        type="text" name="name" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1 text-red-600" />
                </div>

                <div>
                    <x-input-label for="email" :value="__('Correo Electrónico')" class="font-bold text-zinc-700" />
                    <x-text-input wire:model="email" id="email"
                        class="block mt-1 w-full border-red-200 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500"
                        type="email" name="email" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-1 text-red-600" />
                </div>

                <div>
                    <x-input-label for="code" :value="$user_type === 'student' ? __('Código de Estudiante') : __('Código de Docente')" class="font-bold text-zinc-700" />
                    <x-text-input wire:model="code" id="code"
                        class="block mt-1 w-full border-red-200 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500"
                        type="text" name="code" inputmode="numeric" pattern="[0-9]*" :maxlength="$user_type === 'student' ? 7 : null" x-on:input="$el.value = $el.value.replace(/\D/g, '')" required />
                    @if ($user_type === 'student')
                        <p class="mt-1 text-xs text-zinc-500">Mínimo 7 dígitos</p>
                    @endif
                    <x-input-error :messages="$errors->get('code')" class="mt-1 text-red-600" />
                </div>

                <div>
                    <x-input-label for="password" :value="__('Contraseña')" class="font-bold text-zinc-700" />
                    <x-text-input wire:model="password" id="password"
                        class="block mt-1 w-full border-red-200 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500"
                        type="password" name="password" required />
                    <x-input-error :messages="$errors->get('password')" class="mt-1 text-red-600" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirmar Contraseña')"
                        class="font-bold text-zinc-700" />
                    <x-text-input wire:model="password_confirmation" id="password_confirmation"
                        class="block mt-1 w-full border-red-200 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500"
                        type="password" name="password_confirmation" required />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1 text-red-600" />
                </div>

                <div class="flex flex-col gap-3 pt-4">
                    <button type="submit" wire:loading.attr="disabled" wire:target="register"
                        class="w-full bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white font-black py-3 rounded-lg shadow-lg shadow-red-900/50 transition-all uppercase text-sm tracking-widest">
                        <span wire:loading.remove wire:target="register">{{ __('Crear Cuenta') }}</span>
                        <span wire:loading wire:target="register">Enviando código...</span>
                    </button>

                    <div class="text-center pt-2">
                        <a class="text-xs font-bold text-red-700 hover:text-red-600 transition-colors uppercase tracking-widest"
                            href="{{ route('login') }}" wire:navigate>
                            {{ __('¿Ya tienes cuenta? Inicia sesión') }}
                        </a>
                    </div>
                </div>
            </form>
        @else
            <form wire:submit="verifyOtp" class="space-y-5">
                <div class="text-center">
                    <h3 class="text-lg font-black text-zinc-800 uppercase tracking-tight">Verifica tu correo</h3>
                    <p class="mt-2 text-sm text-zinc-600">
                        Enviamos un código de verificación (OTP) a
                        <span class="font-bold text-zinc-800">{{ $email }}</span>.
                        Ingrésalo para confirmar que el correo te pertenece.
                    </p>
                </div>

                @if ($notice)
                    <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm text-green-700 text-center">{{ $notice }}</p>
                    </div>
                @endif

                <div>
                    <x-input-label for="otp" :value="__('Código de verificación')" class="font-bold text-zinc-700" />
                    <x-text-input wire:model="otp" id="otp"
                        class="block mt-1 w-full text-center text-2xl tracking-[0.5em] border-red-200 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500"
                        type="text" name="otp" inputmode="numeric" autocomplete="one-time-code" maxlength="6"
                        x-on:input="$el.value = $el.value.replace(/\D/g, '')" required autofocus />
                    <x-input-error :messages="$errors->get('otp')" class="mt-1 text-red-600" />
                </div>

                <div class="flex flex-col gap-3 pt-2">
                    <button type="submit" wire:loading.attr="disabled" wire:target="verifyOtp"
                        class="w-full bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white font-black py-3 rounded-lg shadow-lg shadow-red-900/50 transition-all uppercase text-sm tracking-widest">
                        <span wire:loading.remove wire:target="verifyOtp">Verificar y crear cuenta</span>
                        <span wire:loading wire:target="verifyOtp">Verificando...</span>
                    </button>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" wire:click="resend"
                            class="text-xs font-bold text-red-700 hover:text-red-600 transition-colors uppercase tracking-widest">
                            Reenviar código
                        </button>
                        <button type="button" wire:click="backToForm"
                            class="text-xs font-bold text-zinc-500 hover:text-zinc-700 transition-colors uppercase tracking-widest">
                            Cambiar correo
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>

    <div class="mt-8 text-center">
        <p class="text-[10px] text-white/70 font-bold uppercase tracking-[0.2em]">
            Universidad Francisco de Paula Santander - Ocaña • 2026
        </p>
    </div>
</div>