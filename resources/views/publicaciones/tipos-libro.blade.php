<div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="page-eyebrow">Publicaciones</p>
            <h2 class="page-title">Tipos de libro</h2>
        </div>
    </div>

    <div class="mt-6 space-y-6">
        {{-- Formulario --}}
        <div class="card">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="section-title">
                        {{ $editingId ? 'Editar tipo de libro' : 'Nuevo tipo de libro' }}
                    </h3>
                    <p class="eyebrow">
                        Clasifica los libros (Monografía, Capítulo, etc.)
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
                <x-text-input id="type_name" wire:model="type_name" type="text" class="mt-2 block w-full" />
                <x-input-error :messages="$errors->get('type_name')" class="mt-2" />
            </div>
        </div>

        {{-- Tabla --}}
        <div class="card">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="section-title">Tipos registrados</h3>
                    <p class="eyebrow">{{ $this->bookTypes->total() }} registros</p>
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
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="table-body">
                        @forelse ($this->bookTypes as $bookType)
                            <tr wire:key="book-type-{{ $bookType->book_type_id }}" class="hover:bg-siproc-blush">
                                <td class="px-4 py-3 font-semibold text-siproc-ink">{{ $bookType->type_name }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" wire:click="edit({{ $bookType->book_type_id }})"
                                            class="btn btn-sm btn-outline">
                                            Editar
                                        </button>
                                        <button type="button" x-on:click="$dispatch('confirmar', { message: '¿Seguro que deseas eliminar este tipo de libro?', action: () => $wire.delete({{ $bookType->book_type_id }}) })"
                                            class="btn btn-sm btn-danger">
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="table-empty">
                                    No hay tipos de libro registrados todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $this->bookTypes->links() }}
            </div>
        </div>
    </div>
</div>