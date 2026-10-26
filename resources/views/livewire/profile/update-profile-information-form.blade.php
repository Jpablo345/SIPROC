<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        // El correo institucional no se puede cambiar: es la identidad verificada por OTP y la del login con Google.
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user->fill($validated);
        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
    <header class="border-b border-gray-100 pb-4">
        <h2 class="text-xl font-bold text-gray-800">
            {{ __('Información del Perfil') }}
        </h2>

        <p class="mt-1 text-sm text-gray-500">
            {{ __("Actualiza la información de tu cuenta. El correo institucional no se puede cambiar.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
        <div>
            <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">
                {{ __('Nombre') }}
            </label>
            <input 
                wire:model="name" 
                id="name" 
                name="name" 
                type="text" 
                class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 text-gray-800 shadow-sm focus:border-red-600 focus:ring focus:ring-red-200 focus:ring-opacity-50 p-2.5" 
                required 
                autofocus 
                autocomplete="name" 
            />
            <x-input-error class="mt-2 text-red-600 text-sm" :messages="$errors->get('name')" />
        </div>

        <div>
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">
                {{ __('Correo Electrónico') }}
            </label>
            <input
                value="{{ $email }}"
                id="email"
                name="email"
                type="email"
                class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 text-gray-500 shadow-sm p-2.5 cursor-not-allowed"
                readonly
                disabled
                autocomplete="username"
            />
            <x-input-error class="mt-2 text-red-600 text-sm" :messages="$errors->get('email')" />

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div class="mt-3 p-3 bg-yellow-50 rounded-md border border-yellow-200">
                    <p class="text-sm text-yellow-800">
                        {{ __('Tu dirección de correo no está verificada.') }}

                        <button wire:click.prevent="sendVerification" class="underline font-medium text-yellow-900 hover:text-black focus:outline-none ml-1">
                            {{ __('Haz clic aquí para reenviar el correo de verificación.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-semibold text-sm text-green-600">
                            {{ __('Se ha enviado un nuevo enlace de verificación a tu correo.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button 
                type="submit" 
                class="px-5 py-2.5 bg-red-700 hover:bg-red-800 text-white font-medium text-sm rounded-md transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
            >
                {{ __('Guardar Cambios') }}
            </button>

            <x-action-message class="text-sm text-green-600 font-medium" on="profile-updated">
                {{ __('¡Guardado con éxito!') }}
            </x-action-message>
        </div>
    </form>
</section>