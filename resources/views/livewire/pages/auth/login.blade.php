<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();
        $this->form->authenticate();
        Session::regenerate();
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>
<div>
    <h2 class="font-display text-2xl font-bold">Iniciar sesión</h2>
    <p class="mt-1 text-sm text-gray-500">Usa tu correo institucional, tu código y tu contraseña.</p>

    <x-auth-session-status class="mt-4 text-center" :status="session('status')" />

    @if ($errors->has('email') && !$errors->has('form.email'))
        <div class="mt-4 rounded-lg border border-siproc-red/20 bg-siproc-rose p-3">
            <p class="text-center text-sm text-siproc-red-dark">{{ $errors->first('email') }}</p>
        </div>
    @endif

    <form wire:submit="login" class="mt-6 space-y-4">
        <div>
            <x-input-label for="email" :value="__('Correo institucional')" class="font-semibold text-gray-700" />
            <x-text-input wire:model="form.email" id="email" class="mt-1 block w-full" type="email" name="email"
                placeholder="nombre@ufpso.edu.co" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="code" :value="__('Código')" class="font-semibold text-gray-700" />
                <x-text-input wire:model="form.code" id="code" class="mt-1 block w-full" type="text" name="code"
                    inputmode="numeric" pattern="[0-9]*" maxlength="7" placeholder="5 o 7 dígitos"
                    x-on:input="$el.value = $el.value.replace(/\D/g, '')" required />
                <x-input-error :messages="$errors->get('form.code')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" :value="__('Contraseña')" class="font-semibold text-gray-700" />
                <x-text-input wire:model="form.password" id="password" class="mt-1 block w-full" type="password"
                    name="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
            </div>
        </div>

        <div class="flex items-center justify-between">
            <label class="inline-flex items-center">
                <input wire:model="form.remember" type="checkbox" class="field-check">
                <span class="ms-2 text-sm text-gray-600">Recordarme</span>
            </label>
            @if (Route::has('password.request'))
                <a class="text-sm font-semibold text-siproc-red-dark hover:underline" href="{{ route('password.request') }}" wire:navigate>
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        <button type="submit" class="btn btn-primary w-full py-3 text-base" wire:loading.attr="disabled" wire:target="login">
            <x-spinner wire:loading wire:target="login" />
            {{ __('Entrar') }}
        </button>

        <div class="flex items-center gap-3 text-xs text-gray-400">
            <span class="h-px flex-1 bg-gray-200"></span>o<span class="h-px flex-1 bg-gray-200"></span>
        </div>

        <a href="{{ route('google.redirect') }}" class="btn btn-outline w-full py-3 text-base">
            <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Continuar con Google
        </a>

        <p class="pt-2 text-center text-sm text-gray-500">
            ¿No tienes cuenta?
            <a href="{{ route('register') }}" wire:navigate class="font-semibold text-siproc-red-dark hover:underline">Regístrate</a>
        </p>
    </form>
</div>
