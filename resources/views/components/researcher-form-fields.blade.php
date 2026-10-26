{{--
    Campos del formulario "crear investigador" (App\Livewire\Forms\ResearcherForm).
    Uso: <x-researcher-form-fields :form="$researcherForm" :groups="$this->groups"
             :institutions="$this->institutions" :bag="$errors" />
--}}
@props([
    'form',
    'groups',
    'institutions',
    'bag',
    'model' => 'researcherForm',
    'idPrefix' => 'rf',
])

@php
    $field = fn (string $name) => "{$model}.{$name}";
    $id = fn (string $name) => "{$idPrefix}_{$name}";
    $label = 'text-xs font-semibold uppercase tracking-[0.2em] text-slate-600';
    $select = 'mt-2 block w-full rounded-md border-red-200 bg-white text-zinc-900 shadow-sm transition-colors focus:border-red-600 focus:ring-red-600';
@endphp

<div class="mt-4 grid gap-4 md:grid-cols-2">
    <div>
        <x-input-label :for="$id('document')" :value="'Documento (opcional)'" :class="$label" />
        <x-text-input :id="$id('document')" wire:model="{{ $field('document') }}" type="text" class="mt-2 block w-full"
            placeholder="Cédula, pasaporte..." />
        <x-input-error :messages="$bag->get($field('document'))" class="mt-2" />
    </div>
    <div>
        <x-input-label :for="$id('name_1')" :value="'Primer nombre'" :class="$label" />
        <x-text-input :id="$id('name_1')" wire:model="{{ $field('name_1') }}" type="text" class="mt-2 block w-full" />
        <x-input-error :messages="$bag->get($field('name_1'))" class="mt-2" />
    </div>
    <div>
        <x-input-label :for="$id('name_2')" :value="'Segundo nombre'" :class="$label" />
        <x-text-input :id="$id('name_2')" wire:model="{{ $field('name_2') }}" type="text" class="mt-2 block w-full" />
        <x-input-error :messages="$bag->get($field('name_2'))" class="mt-2" />
    </div>
    <div>
        <x-input-label :for="$id('last_name_1')" :value="'Primer apellido'" :class="$label" />
        <x-text-input :id="$id('last_name_1')" wire:model="{{ $field('last_name_1') }}" type="text" class="mt-2 block w-full" />
        <x-input-error :messages="$bag->get($field('last_name_1'))" class="mt-2" />
    </div>
    <div>
        <x-input-label :for="$id('last_name_2')" :value="'Segundo apellido'" :class="$label" />
        <x-text-input :id="$id('last_name_2')" wire:model="{{ $field('last_name_2') }}" type="text" class="mt-2 block w-full" />
        <x-input-error :messages="$bag->get($field('last_name_2'))" class="mt-2" />
    </div>
    <div class="flex items-center gap-2 pt-6">
        <input id="{{ $id('create_group') }}" type="checkbox" wire:model.live="{{ $field('create_group') }}"
            class="rounded border-red-200 text-red-600 focus:ring-red-500" />
        <label for="{{ $id('create_group') }}" class="text-sm text-slate-600">Crear grupo nuevo</label>
    </div>
</div>

@if (! $form->create_group)
    <div class="mt-4">
        <x-input-label :for="$id('cod_minciencias')" :value="'Grupo existente'" :class="$label" />
        <select id="{{ $id('cod_minciencias') }}" wire:model="{{ $field('cod_minciencias') }}" class="{{ $select }}">
            <option value="">Selecciona un grupo</option>
            @foreach ($groups as $group)
                <option value="{{ $group->cod_minciencias }}">{{ $group->group_name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$bag->get($field('cod_minciencias'))" class="mt-2" />
    </div>
@else
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div>
            <x-input-label :for="$id('group_code')" :value="'Código Minciencias'" :class="$label" />
            <x-text-input :id="$id('group_code')" wire:model="{{ $field('group_code') }}" type="text" class="mt-2 block w-full" />
            <x-input-error :messages="$bag->get($field('group_code'))" class="mt-2" />
        </div>
        <div>
            <x-input-label :for="$id('group_classification')" :value="'Clasificación'" :class="$label" />
            <select id="{{ $id('group_classification') }}" wire:model="{{ $field('group_classification') }}" class="{{ $select }}">
                <option value="">Sin clasificación</option>
                @foreach (['Categoría A1', 'Categoría A', 'Categoría B', 'Categoría C', 'Categoría D', 'Institucional'] as $classification)
                    <option value="{{ $classification }}">{{ $classification }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2">
            <x-input-label :for="$id('group_name')" :value="'Nombre del grupo'" :class="$label" />
            <x-text-input :id="$id('group_name')" wire:model="{{ $field('group_name') }}" type="text" class="mt-2 block w-full" />
            <x-input-error :messages="$bag->get($field('group_name'))" class="mt-2" />
        </div>
    </div>

    <div class="mt-4 flex items-center gap-2">
        <input id="{{ $id('create_institution') }}" type="checkbox" wire:model.live="{{ $field('create_institution') }}"
            class="rounded border-red-200 text-red-600 focus:ring-red-500" />
        <label for="{{ $id('create_institution') }}" class="text-sm text-slate-600">Crear institución nueva</label>
    </div>

    @if (! $form->create_institution)
        <div class="mt-4">
            <x-input-label :for="$id('institution_id')" :value="'Institución existente'" :class="$label" />
            <select id="{{ $id('institution_id') }}" wire:model="{{ $field('institution_id') }}" class="{{ $select }}">
                <option value="">Sin asignar</option>
                @foreach ($institutions as $institution)
                    <option value="{{ $institution->institution_id }}">{{ $institution->institution_name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$bag->get($field('institution_id'))" class="mt-2" />
        </div>
    @else
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-input-label :for="$id('institution_name')" :value="'Nombre de institución'" :class="$label" />
                <x-text-input :id="$id('institution_name')" wire:model="{{ $field('institution_name') }}" type="text" class="mt-2 block w-full" />
                <x-input-error :messages="$bag->get($field('institution_name'))" class="mt-2" />
            </div>
            <div>
                <x-input-label :for="$id('institution_type')" :value="'Tipo'" :class="$label" />
                <x-text-input :id="$id('institution_type')" wire:model="{{ $field('institution_type') }}" type="text" class="mt-2 block w-full" />
            </div>
            <div>
                <x-input-label :for="$id('institution_country')" :value="'País'" :class="$label" />
                <x-text-input :id="$id('institution_country')" wire:model="{{ $field('institution_country') }}" type="text" class="mt-2 block w-full" />
            </div>
            <div>
                <x-input-label :for="$id('institution_city')" :value="'Ciudad'" :class="$label" />
                <x-text-input :id="$id('institution_city')" wire:model="{{ $field('institution_city') }}" type="text" class="mt-2 block w-full" />
            </div>
            <div>
                <x-input-label :for="$id('institution_website')" :value="'Sitio web'" :class="$label" />
                <x-text-input :id="$id('institution_website')" wire:model="{{ $field('institution_website') }}" type="url"
                    class="mt-2 block w-full" placeholder="https://" />
                <x-input-error :messages="$bag->get($field('institution_website'))" class="mt-2" />
            </div>
        </div>
    @endif
@endif
