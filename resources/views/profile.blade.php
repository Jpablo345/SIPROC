<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="page-eyebrow">Cuenta</p>
            <h2 class="page-title">{{ __('Mi perfil') }}</h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto space-y-6">
            
            <div class="p-6 bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <div class="p-6 bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            <div class="p-6 bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="max-w-xl">
                    <livewire:profile.delete-user-form />
                </div>
            </div>

        </div>
    </div>
</x-app-layout>