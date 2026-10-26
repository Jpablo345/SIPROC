<div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-[#9c1c1c]">Publicaciones</p>
            <h2 class="text-2xl font-semibold text-[#2b2323]">Tipos de publicacion</h2>
        </div>
    </div>

    <div class="mt-6 space-y-6">
        <div class="rounded-2xl border border-white/60 bg-white/80 p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-[#2b2323]">
                        {{ $editingId ? 'Editar tipo' : 'Nuevo tipo' }}
                    </h3>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#9c1c1c]">
                        Clasifica las publicaciones (Articulo, Libro, etc.)
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($editingId)
                        <button type="button" wire:click="cancelEdit"
                            class="rounded-full border border-[#9c1c1c]/30 px-4 py-2 text-xs font-semibold text-[#7a1515] hover:bg-[#f2d3d3]">
                            Cancelar
                        </button>
                    @endif
                    <button type="button" wire:click="save"
                        class="rounded-full bg-[#9c1c1c] px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-[#7a1515]">
                        {{ $editingId ? 'Guardar cambios' : 'Crear tipo' }}
                    </button>
                </div>
            </div>

            @if (session('status'))
                <div class="mt-4 rounded-xl bg-[#f7e2d2] px-4 py-3 text-xs font-semibold text-[#7a1515]">
                    {{ session('status') }}
                </div>
            @endif

            <div class="mt-6">
                <x-input-label for="type_name" :value="'Nombre del tipo'" class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="type_name" wire:model.defer="type_name" type="text" class="mt-2 block w-full" />
                <x-input-error :messages="$errors->get('type_name')" class="mt-2" />
            </div>
        </div>

        <div class="rounded-2xl border border-white/60 bg-white/80 p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-[#2b2323]">Tipos registrados</h3>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#9c1c1c]">{{ $this->types->total() }} registros</p>
                </div>
                <div class="w-full sm:w-72">
                    <x-text-input wire:model.live="search" type="search" placeholder="Buscar tipo" class="w-full" />
                </div>
            </div>

            <div class="mt-6 overflow-hidden rounded-2xl border border-[#f0dede]">
                <table class="min-w-full divide-y divide-[#f0dede] text-sm">
                    <thead class="bg-[#fff7f7] text-left text-xs font-semibold uppercase tracking-[0.2em] text-[#7a1515]">
                        <tr>
                            <th class="px-4 py-3">Nombre</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f0dede] bg-white">
                        @forelse ($this->types as $type)
                            <tr wire:key="type-{{ $type->type_id }}" class="hover:bg-[#fff7f7]">
                                <td class="px-4 py-3 font-semibold text-[#2b2323]">{{ $type->type_name }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" wire:click="edit({{ $type->type_id }})"
                                            class="rounded-full border border-[#9c1c1c]/30 px-3 py-1 text-xs font-semibold text-[#7a1515] hover:bg-[#f2d3d3]">
                                            Editar
                                        </button>
                                        <button type="button" wire:click="delete({{ $type->type_id }})"
                                            onclick="confirm('Seguro que deseas eliminar este tipo?') || event.stopImmediatePropagation()"
                                            class="rounded-full border border-[#d77a7a]/40 px-3 py-1 text-xs font-semibold text-[#9c1c1c] hover:bg-[#f9dede]">
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-6 text-center text-sm text-slate-500">
                                    No hay tipos registrados todavia.
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
