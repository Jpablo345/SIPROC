<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<section class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mt-6">
    <header class="border-b border-gray-100 pb-4">
        <h2 class="text-xl font-bold text-gray-800">
            {{ __('Actualizar Contraseña') }}
        </h2>

        <p class="mt-1 text-sm text-gray-500">
            {{ __('Asegúrate de que tu cuenta utilice una contraseña larga y aleatoria para mantenerla segura.') }}
        </p>
    </header>

    <form wire:submit="updatePassword" class="mt-6 space-y-6">
        <div>
            <label for="update_password_current_password" class="block text-sm font-semibold text-gray-700 mb-1">
                {{ __('Contraseña Actual') }}
            </label>
            <input 
                wire:model="current_password" 
                id="update_password_current_password" 
                name="current_password" 
                type="password" 
                class="mt-1 block w-full field" 
                autocomplete="current-password" 
            />
            <x-input-error :messages="$errors->get('current_password')" class="mt-2 text-siproc-red text-sm" />
        </div>

        <div>
            <label for="update_password_password" class="block text-sm font-semibold text-gray-700 mb-1">
                {{ __('Nueva Contraseña') }}
            </label>
            <input 
                wire:model="password" 
                id="update_password_password" 
                name="password" 
                type="password" 
                class="mt-1 block w-full field" 
                autocomplete="new-password" 
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-siproc-red text-sm" />
        </div>

        <div>
            <label for="update_password_password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1">
                {{ __('Confirmar Contraseña') }}
            </label>
            <input 
                wire:model="password_confirmation" 
                id="update_password_password_confirmation" 
                name="password_confirmation" 
                type="password" 
                class="mt-1 block w-full field" 
                autocomplete="new-password" 
            />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-siproc-red text-sm" />
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button 
                type="submit" 
                class="px-5 py-2.5 bg-siproc-red-dark hover:bg-siproc-red-dark text-white font-medium text-sm rounded-md transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-siproc-red focus:ring-offset-2"
            >
                {{ __('Actualizar Contraseña') }}
            </button>

            <x-action-message class="text-sm text-green-600 font-medium" on="password-updated">
                {{ __('¡Contraseña actualizada!') }}
            </x-action-message>
        </div>
    </form>
</section>