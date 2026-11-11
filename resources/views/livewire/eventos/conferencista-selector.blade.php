<div class="card">
    <div>
        <h4 class="text-sm font-semibold uppercase tracking-[0.2em] text-siproc-red">Conferencistas</h4>
        <p class="text-xs text-slate-500">Busca por nombre o documento.</p>
    </div>

    <div class="mt-4 flex gap-2">
        <div class="flex-1">
            <x-text-input wire:model.live.debounce.300ms="speakerSearch" type="search" class="w-full"
                placeholder="Buscar investigador..." />
        </div>
        <button type="button" wire:click="openResearcherModal"
            class="btn btn-primary whitespace-nowrap">
            + Nuevo
        </button>
    </div>

    @if ($speakerResults)
        <div class="mt-3 space-y-1 rounded-xl border border-siproc-line bg-white p-3">
            @foreach ($speakerResults as $result)
                <div class="flex items-center justify-between rounded-lg px-3 py-2 text-sm">
                    <div>
                        <div class="font-semibold text-siproc-ink">{{ $result['name'] }}</div>
                        <div class="text-xs text-slate-500">{{ $result['group'] ?? 'Sin grupo' }}</div>
                    </div>
                    <button type="button" wire:click="agregar({{ $result['researcher_id'] }})"
                        class="rounded-full bg-siproc-red/10 px-3 py-1 text-xs font-semibold text-siproc-red hover:bg-siproc-rose">
                        + Agregar
                    </button>
                </div>
            @endforeach
        </div>
    @endif

    <div class="mt-4 space-y-2">
        @forelse ($selectedSpeakers as $index => $speaker)
            <div class="flex items-center justify-between rounded-xl border border-siproc-line bg-white px-4 py-3">
                <div class="min-w-0">
                    <div class="font-semibold text-siproc-ink">{{ $index + 1 }}. {{ $speaker['name'] }}</div>
                    <div class="text-xs text-slate-500">{{ $speaker['group'] ?? 'Sin grupo' }}</div>
                </div>
                <button type="button" wire:click="quitar({{ $index }})"
                    class="shrink-0 btn btn-sm btn-danger">
                    Quitar
                </button>
            </div>
        @empty
            <div
                class="rounded-xl border border-dashed border-siproc-line bg-white/60 table-empty">
                No has agregado conferencistas todavía.
            </div>
        @endforelse
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
    MODAL PARA CREAR INVESTIGADOR (campos en <x-researcher-form-fields>)
    ═══════════════════════════════════════════════════════════════════ --}}
    @if ($showResearcherModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 overflow-y-auto">
            <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <h3 class="section-title">Nuevo Investigador</h3>
                    <button type="button" wire:click="$set('showResearcherModal', false)"
                        class="rounded-full p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <x-researcher-form-fields :form="$researcherForm" :groups="$this->groups"
                        :institutions="$this->institutions" :bag="$errors" id-prefix="conf" />

                    <div class="mt-6 flex justify-end gap-2 border-t border-slate-200 pt-4">
                        <button type="button" wire:click="$set('showResearcherModal', false)"
                            class="btn btn-outline">
                            Cancelar
                        </button>
                        <button type="button" wire:click="crearYSeleccionar"
                            class="btn btn-primary">
                            Crear y agregar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    </div>