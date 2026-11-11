<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mt-6">
    <header class="border-b border-gray-100 pb-4">
        <h2 class="text-xl font-bold text-gray-800">
            {{ __('Eliminar Cuenta') }}
        </h2>

        <p class="mt-1 text-sm text-gray-500">
            {{ __('Una vez que se elimine tu cuenta, todos sus recursos y datos se borrarán de forma permanente. Antes de proceder, por favor descarga cualquier información que desees conservar.') }}
        </p>
    </header>

    <div class="mt-6">
        <button
            type="button"
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
            class="px-5 py-2.5 bg-siproc-red hover:bg-siproc-red-dark text-white font-medium text-sm rounded-md transition-colors shadow-sm focus:outline-none"
        >
            {{ __('Eliminar Cuenta') }}
        </button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-6 bg-white rounded-lg">

            <h2 class="text-lg font-bold text-gray-900">
                {{ __('¿Estás seguro de que deseas eliminar tu cuenta?') }}
            </h2>

            <p class="mt-2 text-sm text-gray-600">
                {{ __('Esta acción no se puede deshacer. Por favor, introduce tu contraseña para confirmar que deseas eliminar permanentemente tu cuenta.') }}
            </p>

            <div class="mt-4">
                <label for="password" class="sr-only">{{ __('Contraseña') }}</label>

                <input
                    wire:model="password"
                    id="password"
                    name="password"
                    type="password"
                    class="block w-3/4 field"
                    placeholder="{{ __('Introduce tu contraseña') }}"
                />

                <x-input-error :messages="$errors->get('password')" class="mt-2 text-siproc-red text-sm" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button 
                    type="button" 
                    x-on:click="$dispatch('close')" 
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-sm rounded-md transition-colors"
                >
                    {{ __('Cancelar') }}
                </button>

                <button 
                    type="submit" 
                    class="px-4 py-2 bg-siproc-red hover:bg-siproc-red-dark text-white font-medium text-sm rounded-md transition-colors shadow-sm"
                >
                    {{ __('Eliminar Cuenta Permanentemente') }}
                </button>
            </div>
        </form>
    </x-modal>
</section>