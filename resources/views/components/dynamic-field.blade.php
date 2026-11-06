{{--
    Un campo configurable de un tipo de publicación (App\Models\PublicationTypeField).
    Uso: <x-dynamic-field :field="$field" :bag="$errors" />
    Los límites (maxlength, min, max) se repiten en el HTML para avisar antes de enviar;
    la validación que cuenta es la del servidor.
--}}
@props([
    'field',
    'bag',
    'model' => 'extra',
    'idPrefix' => 'dyn',
])

@php
    $name = "{$model}.{$field->key}";
    $id = "{$idPrefix}_{$field->key}";
    $r = \App\Support\DynamicFields\FieldRuleBuilder::allowedRules($field);
    $labelClass = 'text-xs font-semibold uppercase tracking-[0.2em] text-slate-600';
    $selectClass = 'mt-2 block w-full rounded-md border-red-200 bg-white text-zinc-900 shadow-sm transition-colors focus:border-red-600 focus:ring-red-600';
    $wide = $field->field_type === 'texto_largo' || $field->isMultipleList();
    $maxDate = ! empty($r['no_future']) ? now()->toDateString() : ($r['max_date'] ?? null);
    $messages = array_merge($bag->get($name), $bag->get("{$name}.*"));
@endphp

<div {{ $attributes->merge(['class' => $wide ? 'md:col-span-2' : '']) }}>
    @if ($field->field_type !== 'si_no')
        <x-input-label :for="$id" :class="$labelClass">
            {{ $field->label }}@if ($field->required) <span class="text-[#9c1c1c]">*</span>@endif
        </x-input-label>
    @endif

    @switch($field->field_type)
        @case('texto')
            <x-text-input :id="$id" wire:model="{{ $name }}" type="text" class="mt-2 block w-full"
                maxlength="{{ $r['max_length'] ?? 255 }}" />
            @break

        @case('texto_largo')
            <textarea id="{{ $id }}" wire:model="{{ $name }}" rows="4" class="{{ $selectClass }}"
                maxlength="{{ $r['max_length'] ?? 2000 }}"></textarea>
            @break

        @case('numero')
            <x-text-input :id="$id" wire:model="{{ $name }}" type="number" class="mt-2 block w-full"
                step="{{ ! empty($r['integer']) ? 1 : 'any' }}"
                :min="$r['min'] ?? null" :max="$r['max'] ?? null" />
            @break

        @case('fecha')
            <x-text-input :id="$id" wire:model="{{ $name }}" type="date" class="mt-2 block w-full"
                :min="$r['min_date'] ?? null" :max="$maxDate" />
            @break

        @case('url')
            <x-text-input :id="$id" wire:model="{{ $name }}" type="url" class="mt-2 block w-full" placeholder="https://" />
            @break

        @case('lista')
            @if ($field->multiple)
                <div class="mt-2 flex flex-wrap gap-x-6 gap-y-2">
                    @foreach ($field->options ?? [] as $option)
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" value="{{ $option }}" wire:model="{{ $name }}"
                                class="rounded border-red-200 text-red-600 focus:ring-red-500" />
                            {{ $option }}
                        </label>
                    @endforeach
                </div>
            @else
                <select id="{{ $id }}" wire:model="{{ $name }}" class="{{ $selectClass }}">
                    <option value="">Selecciona una opción</option>
                    @foreach ($field->options ?? [] as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            @endif
            @break

        @case('si_no')
            <label for="{{ $id }}" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                <input id="{{ $id }}" type="checkbox" wire:model="{{ $name }}"
                    class="rounded border-red-200 text-red-600 focus:ring-red-500" />
                {{ $field->label }}
            </label>
            @break
    @endswitch

    @if ($field->help_text)
        <p class="mt-1 text-xs text-slate-500">{{ $field->help_text }}</p>
    @endif

    <x-input-error :messages="$messages" class="mt-2" />
</div>
