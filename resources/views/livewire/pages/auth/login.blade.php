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

<div class="w-full">
    <div class="flex flex-col items-center mb-8">
        <div class="w-24 h-24 p-2 shadow-lg">
            <img src="{{ asset('img/logoufps.png') }}" alt="UFPS" class="w-full h-auto" />
        </div>
        <h2 class="mt-4 text-2xl font-black text-white uppercase tracking-tight">
            Inicio de sesión
        </h2>
        <div class="h-1 w-10 bg-red-600 mt-1"></div>
    </div>

    <div class="bg-white p-8 rounded-2xl shadow-2xl border border-red-200">

        <x-auth-session-status class="mb-4 text-center text-red-600" :status="session('status')" />

        @if ($errors->has('email') && !$errors->has('form.email'))
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-sm text-red-600 text-center">{{ $errors->first('email') }}</p>
            </div>
        @endif

        <form wire:submit="login" class="space-y-6">
            <div>
                <x-input-label for="email" :value="__('Correo Institucional')" class="font-bold text-zinc-700" />
                <x-text-input wire:model="form.email" id="email"
                    class="block mt-1 w-full border-red-200 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500"
                    type="email" name="email" required autofocus />
                <x-input-error :messages="$errors->get('form.email')" class="mt-2 text-red-600" />
            </div>

            <div>
                <x-input-label for="code" :value="__('Código')" class="font-bold text-zinc-700" />
                <x-text-input wire:model="form.code" id="code"
                    class="block mt-1 w-full border-red-200 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500"
                    type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="7"
                    x-on:input="$el.value = $el.value.replace(/\D/g, '')" required />
                <x-input-error :messages="$errors->get('form.code')" class="mt-2 text-red-600" />
            </div>

            <div>
                <x-input-label for="password" :value="__('Contraseña')" class="font-bold text-zinc-700" />
                <x-text-input wire:model="form.password" id="password"
                    class="block mt-1 w-full border-red-200 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500"
                    type="password" name="password" required />
                <x-input-error :messages="$errors->get('form.password')" class="mt-2 text-red-600" />
            </div>

            <div class="flex items-center justify-between">
                <label class="inline-flex items-center">
                    <input wire:model="form.remember" type="checkbox"
                        class="rounded border-red-200 bg-white text-red-600 focus:ring-red-500">
                    <span class="ms-2 text-xs text-zinc-600">Recordarme</span>
                </label>
                @if (Route::has('password.request'))
                    <a class="text-xs font-bold text-red-600 hover:text-red-500 transition-colors"
                        href="{{ route('password.request') }}">
                        ¿Olvidaste tu clave?
                    </a>
                @endif
            </div>

            <div class="flex flex-col gap-3 pt-4">
                <button type="submit"
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-black py-3 rounded-lg shadow-lg shadow-red-900/50 transition-all uppercase text-sm tracking-widest">
                    {{ __('Entrar') }}
                </button>

                {{-- Separador --}}
                <div class="relative flex items-center justify-center my-1">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-red-200"></div>
                    </div>
                    <span class="relative bg-white px-3 text-xs text-zinc-400 font-bold uppercase tracking-widest">o</span>
                </div>

                {{-- Botón Google --}}
                <a href="{{ route('google.redirect') }}"
                    class="w-full flex items-center justify-center gap-3 bg-white border border-red-200 text-zinc-700 font-bold py-3 rounded-lg hover:bg-red-50 transition-all text-sm">
                    <svg class="w-5 h-5" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Continuar con Google
                </a>

                <a href="{{ route('register') }}" wire:navigate class="w-full text-center">
                    <span class="text-xs text-zinc-500 block mb-2 font-bold uppercase tracking-widest">¿No tienes cuenta?</span>
                    <button type="button"
                        class="w-full bg-white border border-red-200 text-red-700 font-bold py-3 rounded-lg hover:bg-red-600 hover:text-white transition-all uppercase text-sm tracking-widest">
                        {{ __('Registrarse') }}
                    </button>
                </a>
            </div>
        </form>
    </div>

    <div class="mt-8 text-center">
        <p class="text-[10px] text-white/70 font-bold uppercase tracking-[0.2em]">
            Sistema de Visualización de Investigaciones de Sistemas
        </p>
    </div>
</div>