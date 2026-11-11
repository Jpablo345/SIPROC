<div>
    {{-- CONTENEDOR DE AUTORES --}}
    <div class="card">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h4 class="text-sm font-semibold uppercase tracking-[0.2em] text-siproc-red">Autores</h4>
                <p class="text-xs text-slate-500">Busca por nombre o documento.</p>
            </div>
            <button type="button" x-on:click="$dispatch('open-modal', 'crear-investigador-modal')"
                class="btn btn-outline">
                + Crear investigador
            </button>
        </div>

        <div class="mt-4">
            <x-text-input wire:model.live.debounce.300ms="authorSearch" type="search" class="w-full"
                placeholder="Buscar investigador" />
        </div>

        @if (!empty($authorResults))
            <div class="mt-3 space-y-2 rounded-xl border border-siproc-line bg-white p-3">
                @foreach ($authorResults as $result)
                    <div class="flex items-center justify-between rounded-lg px-3 py-2 text-sm">
                        <div>
                            <div class="font-semibold text-siproc-ink">{{ $result['name'] }}</div>
                            <div class="text-xs text-slate-500">{{ $result['group'] ?? 'Sin grupo' }}</div>
                        </div>
                        <button type="button" wire:click="agregarAutor('{{ $result['researcher_id'] }}')"
                            class="rounded-full bg-siproc-red/10 px-3 py-1 text-xs font-semibold text-siproc-red hover:bg-siproc-rose">
                            + Agregar
                        </button>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-4 space-y-2">
            @forelse ($selectedAuthors as $index => $author)
                <div
                    class="flex items-center justify-between rounded-xl border border-siproc-line bg-white px-4 py-3 text-sm">
                    <div>
                        <div class="font-semibold text-siproc-ink">{{ $index + 1 }}. {{ $author['name'] }}</div>
                        <div class="text-xs text-slate-500">{{ $author['group'] ?? 'Sin grupo' }}</div>
                    </div>
                    <button type="button" wire:click="eliminarAutor({{ $index }})"
                        class="btn btn-sm btn-danger">
                        Quitar
                    </button>
                </div>
            @empty
                <div
                    class="rounded-xl border border-dashed border-siproc-line bg-white/60 table-empty">
                    No has agregado autores todavía.
                </div>
            @endforelse
        </div>
    </div>

    {{-- MODAL CREAR INVESTIGADOR --}}
    <x-modal name="crear-investigador-modal" maxWidth="2xl">
        <div class="bg-white p-6">
            <div class="flex items-center justify-between">
                <h3 class="section-title">Crear investigador</h3>
                <button type="button" x-on:click="$dispatch('close-modal', 'crear-investigador-modal')"
                    class="text-sm text-siproc-red">Cerrar</button>
            </div>

            <x-researcher-form-fields :form="$researcherForm" :groups="$this->groups"
                :institutions="$this->institutions" :bag="$errors" id-prefix="aut" />

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" x-on:click="$dispatch('close-modal', 'crear-investigador-modal')"
                    class="btn btn-outline">
                    Cancelar
                </button>
                <button type="button" wire:click.prevent="crearYSeleccionar"
                    class="btn btn-primary">
                    Guardar investigador
                </button>
            </div>
        </div>
    </x-modal>
</div>