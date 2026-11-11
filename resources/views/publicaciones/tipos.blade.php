<div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="page-eyebrow">Publicaciones</p>
            <h2 class="page-title">Tipos de publicación</h2>
        </div>
    </div>

    <div class="mt-6 space-y-6">
        @if (! $this->canCreate && ! $editingId)
            <x-flash-status />
            <div class="rounded-xl border border-siproc-line bg-white/80 px-4 py-3 text-sm text-slate-600">
                Solo docentes y administradores pueden crear tipos de publicación.
            </div>
        @else
        <div class="card">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="section-title">
                        {{ $editingId ? 'Editar tipo' : 'Nuevo tipo' }}
                    </h3>
                    <p class="eyebrow">
                        {{ $editingId ? 'Cambia el nombre del tipo' : 'Al crearlo podrás definir los campos de su formulario' }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($editingId)
                        <button type="button" wire:click="cancelEdit"
                            class="btn btn-outline">
                            Cancelar
                        </button>
                    @endif
                    <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                        class="btn btn-primary"><x-spinner wire:loading wire:target="save" />
                        {{ $editingId ? 'Guardar cambios' : 'Crear tipo' }}
                    </button>
                </div>
            </div>

            <x-flash-status />

            <div class="mt-6">
                <x-input-label for="type_name" :value="'Nombre del tipo'" class="form-label" />
                <x-text-input id="type_name" wire:model.defer="type_name" type="text" class="mt-2 block w-full" />
                <x-input-error :messages="$errors->get('type_name')" class="mt-2" />
            </div>
        </div>
        @endif

        <div class="card">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="section-title">Tipos registrados</h3>
                    <p class="eyebrow">{{ $this->types->total() }} registros</p>
                </div>
                <div class="w-full sm:w-72">
                    <x-text-input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar tipo" class="w-full" />
                </div>
            </div>

            <div class="mt-6 table-wrap" wire:loading.delay.class="opacity-50">
                <table class="data-table">
                    <thead class="table-head">
                        <tr>
                            <th class="px-4 py-3">Nombre</th>
                            <th class="px-4 py-3">Campos propios</th>
                            <th class="px-4 py-3">Publicaciones</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="table-body">
                        @forelse ($this->types as $type)
                            <tr wire:key="type-{{ $type->type_id }}" class="hover:bg-siproc-blush">
                                <td class="px-4 py-3 font-semibold text-siproc-ink">
                                    {{ $type->type_name }}
                                    @if ($type->is_system)
                                        <span class="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Sistema</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $type->is_system ? 'Fijos' : $type->fields_count }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $type->publications_count }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        @can('update', $type)
                                            <a href="{{ route('publicaciones.tipos.campos', $type->type_id) }}" wire:navigate
                                                class="rounded-full bg-siproc-red px-3 py-1 text-xs font-semibold text-white hover:bg-siproc-red-dark">
                                                Campos
                                            </a>
                                            <button type="button" wire:click="edit({{ $type->type_id }})"
                                                class="btn btn-sm btn-outline">
                                                Editar
                                            </button>
                                        @endcan
                                        @can('delete', $type)
                                            <button type="button" x-on:click="$dispatch('confirmar', { message: '¿Seguro que deseas eliminar este tipo?', action: () => $wire.delete({{ $type->type_id }}) })"
                                                class="btn btn-sm btn-danger">
                                                Eliminar
                                            </button>
                                        @endcan
                                        @if ($type->is_system)
                                            <span class="text-xs text-slate-400">Tipo del sistema</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="table-empty">
                                    No hay tipos registrados todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $this->types->links() }}
            </div>
        </div>
    </div>
</div>
