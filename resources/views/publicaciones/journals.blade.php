<div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="page-eyebrow">Publicaciones</p>
            <h2 class="page-title">Revistas</h2>
        </div>
    </div>

    <div class="mt-6 space-y-6">
        {{-- Formulario --}}
        <div class="card">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="section-title">
                        {{ $editingId ? 'Editar revista' : 'Nueva revista' }}
                    </h3>
                    <p class="eyebrow">
                        Registra revistas por ISSN y nombre
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
                        {{ $editingId ? 'Guardar cambios' : 'Crear revista' }}
                    </button>
                </div>
            </div>

            <x-flash-status />

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="journal_issn" :value="'ISSN'"
                        class="form-label" />
                    <x-text-input id="journal_issn" wire:model="journal_issn" type="text"
                        class="mt-2 block w-full {{ $editingId ? 'bg-slate-100 cursor-not-allowed' : '' }}"
                        :disabled="(bool) $editingId" />
                    <x-input-error :messages="$errors->get('journal_issn')" class="mt-2" />
                    @if ($editingId)
                        <p class="mt-1 text-xs text-slate-400">El ISSN no se puede modificar.</p>
                    @endif
                </div>
                <div>
                    <x-input-label for="journal_name" :value="'Nombre de la revista'"
                        class="form-label" />
                    <x-text-input id="journal_name" wire:model="journal_name" type="text" class="mt-2 block w-full" />
                    <x-input-error :messages="$errors->get('journal_name')" class="mt-2" />
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="category" :value="'Categoría (Q1, Q2, etc.)'"
                        class="form-label" />
                    <select id="category" wire:model="category"
                        class="mt-2 block w-full field">
                        <option value="">Sin clasificación</option>
                        <option value="Q1">Q1</option>
                        <option value="Q2">Q2</option>
                        <option value="Q3">Q3</option>
                        <option value="Q4">Q4</option>
                        <option value="Q5">Q5</option>
                        <x-input-error :messages="$errors->get('category')" class="mt-2" />
                    </select>
                </div>
                
            </div>
        </div>

        {{-- Tabla --}}
        <div class="card">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="section-title">Revistas registradas</h3>
                    <p class="eyebrow">
                        {{ $this->journals->total() }} registros
                    </p>
                </div>
                <div class="w-full sm:w-72">
                    <x-text-input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar por nombre o ISSN"
                        class="w-full" />
                </div>
            </div>

            <div class="mt-6 table-wrap" wire:loading.delay.class="opacity-50">
                <table class="data-table">
                    <thead
                        class="table-head">
                        <tr>
                            <th class="px-4 py-3">ISSN</th>
                            <th class="px-4 py-3">Nombre</th>
                            <th class="px-4 py-3">Categoría</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="table-body">
                        @forelse ($this->journals as $journal)
                            <tr wire:key="journal-{{ $journal->journal_issn }}" class="hover:bg-siproc-blush">
                                <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $journal->journal_issn }}</td>
                                <td class="px-4 py-3 font-semibold text-siproc-ink">{{ $journal->journal_name }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $journal->category ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" wire:click="edit('{{ $journal->journal_issn }}')"
                                            class="btn btn-sm btn-outline">
                                            Editar
                                        </button>
                                        <button type="button" x-on:click="$dispatch('confirmar', { message: '¿Seguro que deseas eliminar esta revista?', action: () => $wire.delete('{{ $journal->journal_issn }}') })"
                                            class="btn btn-sm btn-danger">
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="table-empty">
                                    No hay revistas registradas todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $this->journals->links() }}
            </div>
        </div>
    </div>
</div>