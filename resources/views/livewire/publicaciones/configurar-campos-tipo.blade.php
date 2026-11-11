@php
    $label = 'form-label';
    $select = 'mt-2 block w-full field';
    $fields = $this->type->fields;
    $type = $form->field_type;
@endphp

<div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="page-eyebrow">Tipos de publicación</p>
            <h2 class="page-title">Campos de "{{ $this->type->type_name }}"</h2>
        </div>
        <a href="{{ route('publicaciones.tipos') }}" wire:navigate
            class="btn btn-outline">
            ← Volver a tipos
        </a>
    </div>

    <div class="mt-6 space-y-6">
        <x-flash-status />

        {{-- Lista de campos --}}
        <div class="card">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="section-title">Campos del formulario</h3>
                    <p class="eyebrow">
                        {{ $fields->count() }} de {{ \App\Models\PublicationTypeField::MAX_PER_TYPE }} campos
                    </p>
                </div>
                @if (! $showForm && $fields->count() < \App\Models\PublicationTypeField::MAX_PER_TYPE)
                    <button type="button" wire:click="newField"
                        class="btn btn-primary">
                        + Agregar campo
                    </button>
                @endif
            </div>

            <div class="mt-6 table-wrap" wire:loading.delay.class="opacity-50">
                <table class="data-table">
                    <thead class="table-head">
                        <tr>
                            <th class="px-4 py-3">Orden</th>
                            <th class="px-4 py-3">Etiqueta</th>
                            <th class="px-4 py-3">Clase</th>
                            <th class="px-4 py-3">Obligatorio</th>
                            <th class="px-4 py-3">Validaciones</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="table-body">
                        @forelse ($fields as $field)
                            <tr wire:key="field-{{ $field->field_id }}" class="{{ $field->active ? 'hover:bg-siproc-blush' : 'bg-slate-50 text-slate-400' }}">
                                <td class="px-4 py-3">
                                    <div class="flex gap-1">
                                        <button type="button" wire:click="move({{ $field->field_id }}, 'up')" @disabled($loop->first)
                                            class="rounded border border-siproc-line px-2 text-xs disabled:opacity-30" title="Subir">↑</button>
                                        <button type="button" wire:click="move({{ $field->field_id }}, 'down')" @disabled($loop->last)
                                            class="rounded border border-siproc-line px-2 text-xs disabled:opacity-30" title="Bajar">↓</button>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold {{ $field->active ? 'text-siproc-ink' : '' }}">{{ $field->label }}</div>
                                    @if ($field->help_text)
                                        <div class="text-xs text-slate-500">{{ $field->help_text }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ \App\Models\PublicationTypeField::TYPES[$field->field_type] }}</td>
                                <td class="px-4 py-3">{{ $field->required ? 'Sí' : 'No' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $field->rulesSummary() }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $field->active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $field->active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2 whitespace-nowrap">
                                        <button type="button" wire:click="editField({{ $field->field_id }})"
                                            class="btn btn-sm btn-outline">
                                            Editar
                                        </button>
                                        <button type="button" wire:click="toggleActive({{ $field->field_id }})"
                                            class="btn btn-sm btn-outline">
                                            {{ $field->active ? 'Desactivar' : 'Activar' }}
                                        </button>
                                        <button type="button" x-on:click="$dispatch('confirmar', { message: '¿Eliminar este campo? Si ya tiene valores guardados solo se desactivará.', action: () => $wire.deleteField({{ $field->field_id }}) })"
                                            class="btn btn-sm btn-danger">
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="table-empty">
                                    Este tipo todavía no tiene campos propios. Las publicaciones de este tipo solo pedirán los campos comunes.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Formulario del campo --}}
        @if ($showForm)
            <div class="card">
                <h3 class="section-title">{{ $form->fieldId ? 'Editar campo' : 'Nuevo campo' }}</h3>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="field_label" :value="'Etiqueta'" :class="$label" />
                        <x-text-input id="field_label" wire:model="form.label" type="text" class="mt-2 block w-full"
                            placeholder="Ej: Número de registro DNDA" />
                        <x-input-error :messages="$errors->get('form.label')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="field_type" :value="'Clase de campo'" :class="$label" />
                        <select id="field_type" wire:model.live="form.field_type" class="{{ $select }}">
                            @foreach (\App\Models\PublicationTypeField::TYPES as $value => $name)
                                <option value="{{ $value }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('form.field_type')" class="mt-2" />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="field_help" :value="'Texto de ayuda (opcional)'" :class="$label" />
                        <x-text-input id="field_help" wire:model="form.help_text" type="text" class="mt-2 block w-full"
                            placeholder="Ej: Tal como aparece en el certificado de la DNDA" />
                        <x-input-error :messages="$errors->get('form.help_text')" class="mt-2" />
                    </div>
                    @if ($type !== 'si_no')
                        <div class="flex items-center gap-2">
                            <input id="field_required" type="checkbox" wire:model="form.required"
                                class="field-check" />
                            <label for="field_required" class="text-sm text-slate-600">Obligatorio</label>
                        </div>
                    @endif
                </div>

                {{-- Validaciones según la clase --}}
                <div class="mt-6 rounded-xl border border-siproc-line bg-siproc-blush p-4">
                    <h4 class="text-sm font-semibold uppercase tracking-[0.2em] text-siproc-red">Validaciones</h4>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        @if (in_array($type, ['texto', 'texto_largo']))
                            <div>
                                <x-input-label for="field_min_length" :value="'Longitud mínima'" :class="$label" />
                                <x-text-input id="field_min_length" wire:model="form.min_length" type="number" min="1" class="mt-2 block w-full" />
                                <x-input-error :messages="$errors->get('form.min_length')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="field_max_length" :value="'Longitud máxima'" :class="$label" />
                                <x-text-input id="field_max_length" wire:model="form.max_length" type="number" min="1"
                                    class="mt-2 block w-full" :placeholder="$type === 'texto' ? '255' : '2000'" />
                                <x-input-error :messages="$errors->get('form.max_length')" class="mt-2" />
                            </div>
                        @endif

                        @if ($type === 'texto')
                            <div>
                                <x-input-label for="field_format" :value="'Formato'" :class="$label" />
                                <select id="field_format" wire:model="form.format" class="{{ $select }}">
                                    <option value="">Ninguno</option>
                                    @foreach (\App\Support\DynamicFields\FieldFormats::options() as $value => $name)
                                        <option value="{{ $value }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('form.format')" class="mt-2" />
                            </div>
                        @endif

                        @if ($type === 'numero')
                            <div class="flex items-center gap-2 md:col-span-2">
                                <input id="field_integer" type="checkbox" wire:model="form.integer"
                                    class="field-check" />
                                <label for="field_integer" class="text-sm text-slate-600">Solo números enteros</label>
                            </div>
                            <div>
                                <x-input-label for="field_min" :value="'Valor mínimo'" :class="$label" />
                                <x-text-input id="field_min" wire:model="form.min" type="number" step="any" class="mt-2 block w-full" />
                                <x-input-error :messages="$errors->get('form.min')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="field_max" :value="'Valor máximo'" :class="$label" />
                                <x-text-input id="field_max" wire:model="form.max" type="number" step="any" class="mt-2 block w-full" />
                                <x-input-error :messages="$errors->get('form.max')" class="mt-2" />
                            </div>
                        @endif

                        @if ($type === 'fecha')
                            <div>
                                <x-input-label for="field_min_date" :value="'Fecha mínima'" :class="$label" />
                                <x-text-input id="field_min_date" wire:model="form.min_date" type="date" class="mt-2 block w-full" />
                                <x-input-error :messages="$errors->get('form.min_date')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="field_max_date" :value="'Fecha máxima'" :class="$label" />
                                <x-text-input id="field_max_date" wire:model="form.max_date" type="date" class="mt-2 block w-full" />
                                <x-input-error :messages="$errors->get('form.max_date')" class="mt-2" />
                            </div>
                            <div class="flex items-center gap-2">
                                <input id="field_no_future" type="checkbox" wire:model="form.no_future"
                                    class="field-check" />
                                <label for="field_no_future" class="text-sm text-slate-600">No puede ser futura</label>
                            </div>
                        @endif

                        @if ($type === 'lista')
                            <div class="md:col-span-2">
                                <x-input-label for="field_options" :value="'Opciones (una por línea)'" :class="$label" />
                                <textarea id="field_options" wire:model="form.options_text" rows="4"
                                    class="{{ $select }}" placeholder="Libre&#10;Propietaria&#10;Institucional"></textarea>
                                <x-input-error :messages="$errors->get('form.options_text')" class="mt-2" />
                            </div>
                            <div class="flex items-center gap-2">
                                <input id="field_multiple" type="checkbox" wire:model="form.multiple"
                                    class="field-check" />
                                <label for="field_multiple" class="text-sm text-slate-600">Permitir elegir varias opciones</label>
                            </div>
                        @endif

                        @if (in_array($type, ['texto', 'numero']))
                            <div class="flex items-center gap-2">
                                <input id="field_unique" type="checkbox" wire:model="form.unique"
                                    class="field-check" />
                                <label for="field_unique" class="text-sm text-slate-600">Único (no se puede repetir entre publicaciones de este tipo)</label>
                            </div>
                            <x-input-error :messages="$errors->get('form.unique')" class="md:col-span-2" />
                        @endif

                        @if ($type === 'url')
                            <p class="text-sm text-slate-500 md:col-span-2">Siempre se exige que empiece con http:// o https://.</p>
                        @endif

                        @if ($type === 'si_no')
                            <p class="text-sm text-slate-500 md:col-span-2">Una casilla de sí o no siempre tiene valor; no lleva validaciones.</p>
                        @endif

                        @if ($type !== 'si_no')
                            <div class="md:col-span-2">
                                <x-input-label for="field_error_message" :value="'Mensaje de error propio (opcional)'" :class="$label" />
                                <x-text-input id="field_error_message" wire:model="form.error_message" type="text" class="mt-2 block w-full"
                                    placeholder="Reemplaza los mensajes generados (salvo el de obligatorio)" />
                                <x-input-error :messages="$errors->get('form.error_message')" class="mt-2" />
                            </div>
                        @endif
                    </div>
                </div>

                @if ($affectedCount)
                    <div class="mt-6 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        <p class="font-semibold">⚠ {{ $affectedCount }} {{ $affectedCount === 1 ? 'publicación' : 'publicaciones' }} de este tipo no
                            {{ $affectedCount === 1 ? 'cumple' : 'cumplen' }} la nueva configuración.</p>
                        <p class="mt-1">Seguirán guardadas, pero quien las edite tendrá que corregir este campo.</p>
                        <div class="mt-3 flex gap-2">
                            <button type="button" wire:click="$set('affectedCount', null)"
                                class="rounded-full border border-amber-400 px-4 py-2 text-xs font-semibold text-amber-900 hover:bg-amber-100">
                                Revisar
                            </button>
                            <button type="button" wire:click="confirmSave"
                                class="rounded-full bg-amber-600 px-4 py-2 text-xs font-semibold text-white hover:bg-amber-700">
                                Guardar de todos modos
                            </button>
                        </div>
                    </div>
                @endif

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="cancel"
                        class="btn btn-outline">
                        Cancelar
                    </button>
                    <button type="button" wire:click="saveField" wire:loading.attr="disabled" wire:target="saveField"
                        class="btn btn-primary"><x-spinner wire:loading wire:target="saveField" />
                        Guardar campo
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
