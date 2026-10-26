<div>
    {{-- CONTENEDOR DE AUTORES --}}
    <div class="rounded-2xl border border-white/60 bg-white/80 p-6 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h4 class="text-sm font-semibold uppercase tracking-[0.2em] text-[#9c1c1c]">Autores</h4>
                <p class="text-xs text-slate-500">Busca por nombre o documento.</p>
            </div>
            <button type="button" x-on:click="$dispatch('open-modal', 'crear-investigador-modal')"
                class="rounded-full border border-[#9c1c1c]/30 px-4 py-2 text-xs font-semibold text-[#7a1515] hover:bg-[#f2d3d3]">
                + Crear investigador
            </button>
        </div>

        <div class="mt-4">
            <x-text-input wire:model.live="authorSearch" type="search" class="w-full"
                placeholder="Buscar investigador" />
        </div>

        @if (!empty($authorResults))
            <div class="mt-3 space-y-2 rounded-xl border border-[#f0dede] bg-white p-3">
                @foreach ($authorResults as $result)
                    <div class="flex items-center justify-between rounded-lg px-3 py-2 text-sm">
                        <div>
                            <div class="font-semibold text-[#2b2323]">{{ $result['name'] }}</div>
                            <div class="text-xs text-slate-500">{{ $result['group'] ?? 'Sin grupo' }}</div>
                        </div>
                        <button type="button" wire:click="agregarAutor('{{ $result['researcher_id'] }}')"
                            class="rounded-full bg-[#9c1c1c]/10 px-3 py-1 text-xs font-semibold text-[#9c1c1c] hover:bg-[#f2d3d3]">
                            + Agregar
                        </button>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-4 space-y-2">
            @forelse ($selectedAuthors as $index => $author)
                <div
                    class="flex items-center justify-between rounded-xl border border-[#f0dede] bg-white px-4 py-3 text-sm">
                    <div>
                        <div class="font-semibold text-[#2b2323]">{{ $index + 1 }}. {{ $author['name'] }}</div>
                        <div class="text-xs text-slate-500">{{ $author['group'] ?? 'Sin grupo' }}</div>
                    </div>
                    <button type="button" wire:click="eliminarAutor({{ $index }})"
                        class="rounded-full border border-[#d77a7a]/40 px-3 py-1 text-xs font-semibold text-[#9c1c1c] hover:bg-[#f9dede]">
                        Quitar
                    </button>
                </div>
            @empty
                <div
                    class="rounded-xl border border-dashed border-[#f0dede] bg-white/60 px-4 py-6 text-center text-sm text-slate-500">
                    No has agregado autores todavía.
                </div>
            @endforelse
        </div>
    </div>

    {{-- MODAL CREAR INVESTIGADOR --}}
    <x-modal name="crear-investigador-modal" maxWidth="2xl">
        <div class="bg-white p-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-[#2b2323]">Crear investigador</h3>
                <button type="button" x-on:click="$dispatch('close-modal', 'crear-investigador-modal')"
                    class="text-sm text-[#9c1c1c]">Cerrar</button>
            </div>

            <x-researcher-form-fields :form="$researcherForm" :groups="$this->groups"
                :institutions="$this->institutions" :bag="$errors" id-prefix="aut" />

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" x-on:click="$dispatch('close-modal', 'crear-investigador-modal')"
                    class="rounded-full border border-[#9c1c1c]/30 px-4 py-2 text-xs font-semibold text-[#7a1515] hover:bg-[#f2d3d3]">
                    Cancelar
                </button>
                <button type="button" wire:click.prevent="crearYSeleccionar"
                    class="rounded-full bg-[#9c1c1c] px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-[#7a1515]">
                    Guardar investigador
                </button>
            </div>
        </div>
    </x-modal>
</div>