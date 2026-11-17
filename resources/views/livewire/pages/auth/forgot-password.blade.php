<?php

use App\Models\User;
use App\Notifications\CodigoRestablecerContrasena;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;
use Spatie\OneTimePasswords\Enums\ConsumeOneTimePasswordResult;

/*
 * Restablecer contraseña con un código enviado al correo, igual que el registro.
 * Paso 1: el correo. Paso 2: el código y la nueva contraseña, en el mismo navegador.
 * No se dice si el correo existe: el mensaje es el mismo en ambos casos.
 */
new #[Layout('layouts.guest')] class extends Component
{
    public const MINUTOS = 10;

    public string $email = '';
    public string $codigo = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $aviso = '';

    // 'correo' o 'codigo'
    #[Locked]
    public string $paso = 'correo';

    public function enviarCodigo(): void
    {
        $this->validate(
            ['email' => ['required', 'string', 'email', 'max:255']],
            ['email.required' => 'Escribe tu correo.', 'email.email' => 'Ese correo no es válido.'],
        );

        $this->email = Str::lower(trim($this->email));

        if (! $this->enviar('email')) {
            return;
        }

        $this->paso = 'codigo';
        $this->reset('codigo', 'password', 'password_confirmation', 'aviso');
        $this->resetValidation();
    }

    public function reenviar(): void
    {
        if ($this->enviar('codigo')) {
            $this->codigo = '';
            $this->resetValidation();
            $this->aviso = 'Te enviamos un código nuevo.';
        }
    }

    public function restablecer(): void
    {
        $longitud = (int) config('one-time-passwords.password_length', 6);

        $this->validate([
            'codigo' => ['required', 'digits:'.$longitud],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ], [
            'codigo.required' => 'Escribe el código que te enviamos.',
            'codigo.digits' => "El código tiene $longitud dígitos.",
        ]);

        $usuario = $this->usuario();
        $resultado = $usuario?->consumeOneTimePassword($this->codigo);

        if (! $usuario || ! $resultado->isOk()) {
            $this->addError('codigo', $this->mensajeError($resultado));

            return;
        }

        $usuario->forceFill([
            'password' => Hash::make($this->password),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($usuario));
        RateLimiter::clear($this->claveCorreo());

        Session::flash('status', 'Tu contraseña se cambió. Ya puedes iniciar sesión.');
        $this->redirectRoute('login', navigate: true);
    }

    public function cambiarCorreo(): void
    {
        $this->paso = 'correo';
        $this->reset('codigo', 'password', 'password_confirmation', 'aviso');
        $this->resetValidation();
    }

    /**
     * Envía el código si el correo pertenece a una cuenta verificada.
     * Límites: un código por correo cada minuto y cinco solicitudes por IP cada diez minutos.
     */
    private function enviar(string $campoError): bool
    {
        $claveIp = 'restablecer-ip:'.request()->ip();

        if (RateLimiter::tooManyAttempts($claveIp, 5)) {
            $this->addError($campoError, 'Demasiadas solicitudes. Espera '.ceil(RateLimiter::availableIn($claveIp) / 60).' minutos e inténtalo de nuevo.');

            return false;
        }

        if (RateLimiter::tooManyAttempts($this->claveCorreo(), 1)) {
            $this->addError($campoError, 'Espera '.RateLimiter::availableIn($this->claveCorreo()).' segundos para pedir otro código.');

            return false;
        }

        if ($usuario = $this->usuario()) {
            try {
                $codigo = $usuario->createOneTimePassword(self::MINUTOS);
                $usuario->notify(new CodigoRestablecerContrasena($codigo, self::MINUTOS));
            } catch (\Throwable $e) {
                report($e);
                $this->addError($campoError, 'No pudimos enviar el código. Inténtalo de nuevo en unos minutos.');

                return false;
            }
        }

        RateLimiter::hit($claveIp, 600);
        RateLimiter::hit($this->claveCorreo(), 60);

        return true;
    }

    private function usuario(): ?User
    {
        return User::where('email', $this->email)->whereNotNull('email_verified_at')->first();
    }

    private function claveCorreo(): string
    {
        return 'restablecer-correo:'.sha1($this->email);
    }

    private function mensajeError(?ConsumeOneTimePasswordResult $resultado): string
    {
        return match ($resultado) {
            ConsumeOneTimePasswordResult::OneTimePasswordExpired => 'El código expiró. Pide uno nuevo.',
            ConsumeOneTimePasswordResult::RateLimitExceeded => 'Demasiados intentos. Espera un minuto e inténtalo de nuevo.',
            ConsumeOneTimePasswordResult::DifferentOrigin => 'Usa el código en el mismo navegador donde lo pediste.',
            default => 'El código es incorrecto o ya no es válido.',
        };
    }
}; ?>

<div>
    <h2 class="font-display text-2xl font-bold">Restablecer contraseña</h2>

    @if ($paso === 'correo')
        <p class="mt-1 text-sm text-gray-500">Escribe tu correo institucional y te enviaremos un código para crear una nueva contraseña.</p>

        <form wire:submit="enviarCodigo" class="mt-6 space-y-4">
            <div>
                <x-input-label for="email" :value="__('Correo institucional')" class="font-semibold text-gray-700" />
                <x-text-input wire:model="email" id="email" class="mt-1 block w-full" type="email" name="email"
                    placeholder="nombre@ufpso.edu.co" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <button type="submit" class="btn btn-primary w-full py-3 text-base" wire:loading.attr="disabled" wire:target="enviarCodigo">
                <x-spinner wire:loading wire:target="enviarCodigo" />
                Enviar código
            </button>

            <p class="pt-2 text-center text-sm">
                <a href="{{ route('login') }}" wire:navigate class="font-semibold text-siproc-red-dark hover:underline">Volver a iniciar sesión</a>
            </p>
        </form>
    @else
        <p class="mt-1 text-sm text-gray-500">
            Si <b class="text-gray-900">{{ $email }}</b> tiene cuenta en SIPROC, le llegó un código de 6 dígitos.
            Vence en {{ $this::MINUTOS }} minutos. Revisa también la carpeta de spam.
        </p>

        @if ($aviso)
            <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-3" role="status">
                <p class="text-center text-sm text-green-700">{{ $aviso }}</p>
            </div>
        @endif

        <form wire:submit="restablecer" class="mt-6 space-y-4">
            <div>
                <x-input-label for="codigo" :value="__('Código')" class="font-semibold text-gray-700" />
                <x-text-input wire:model="codigo" id="codigo" class="mt-1 block w-full text-center text-2xl tracking-[0.5em]"
                    type="text" name="codigo" inputmode="numeric" autocomplete="one-time-code" maxlength="6"
                    x-on:input="$el.value = $el.value.replace(/\D/g, '')" required autofocus />
                <x-input-error :messages="$errors->get('codigo')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" :value="__('Nueva contraseña')" class="font-semibold text-gray-700" />
                <x-text-input wire:model="password" id="password" class="mt-1 block w-full" type="password"
                    name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" :value="__('Confirmar nueva contraseña')" class="font-semibold text-gray-700" />
                <x-text-input wire:model="password_confirmation" id="password_confirmation" class="mt-1 block w-full"
                    type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <button type="submit" class="btn btn-primary w-full py-3 text-base" wire:loading.attr="disabled" wire:target="restablecer">
                <x-spinner wire:loading wire:target="restablecer" />
                Cambiar contraseña
            </button>

            <div class="flex items-center justify-between pt-2">
                <button type="button" wire:click="reenviar" class="text-sm font-semibold text-siproc-red-dark hover:underline">
                    Reenviar código
                </button>
                <button type="button" wire:click="cambiarCorreo" class="text-sm font-semibold text-gray-500 hover:text-gray-700">
                    Cambiar correo
                </button>
            </div>
        </form>
    @endif
</div>
