<div class="space-y-2">
    <x-input-label :value="$label" class="form-label" />

    @if($selectedId)
        <div class="flex items-center justify-between rounded-xl border border-siproc-line bg-white/60 px-4 py-3">
            <div>
                <span class="font-medium text-siproc-ink">{{ $selectedName }}</span>
            </div>
            <button type="button" wire:click="resetear"
                class="rounded-full bg-siproc-rose/60 p-1.5 text-siproc-red hover:bg-siproc-rose transition-colors"
                title="Cambiar institución">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @else
        <div class="relative">
            <div class="flex gap-2">
                <div class="flex-1">
                    <x-text-input wire:model.live.debounce.300ms="search" type="search" class="block w-full"
                        placeholder="Buscar institución por nombre, ciudad o país..." />
                </div>
                <button type="button" wire:click="openModal"
                    class="btn btn-primary whitespace-nowrap transition-colors">
                    + Nueva
                </button>
            </div>

            @if (!empty($results))
                <div
                    class="absolute z-30 mt-2 w-full max-h-60 overflow-y-auto rounded-xl border border-siproc-line bg-white p-2 shadow-lg divide-y divide-slate-100">
                    @foreach ($results as $inst)
                        <button type="button" wire:click="select({{ $inst['id'] }})"
                            class="w-full rounded-lg px-3 py-2 text-left hover:bg-siproc-blush transition-colors">
                            <div class="text-sm font-semibold text-slate-700">{{ $inst['name'] }}</div>
                            <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-500">
                                @if($inst['type'])
                                    <span>{{ $inst['type'] }}</span>
                                @endif
                                @if($inst['city'])
                                    <span>{{ $inst['city'] }}</span>
                                @endif
                                @if($inst['country'])
                                    <span>{{ $inst['country'] }}</span>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════
    MODAL PARA CREAR INSTITUCIÓN
    ═══════════════════════════════════════════════════════════════════ --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <h3 class="section-title">Nueva Institución</h3>
                    <button type="button" wire:click="$set('showModal', false)"
                        class="rounded-full p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <div>
                        <x-input-label value="Nombre de la institución *" class="text-xs font-semibold" />
                        <x-text-input wire:model="modal_institution_name" type="text" class="mt-1 w-full"
                            placeholder="Ej: Universidad de los Andes" />
                        <x-input-error :messages="$errors->get('modal_institution_name')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Tipo de institución" class="text-xs font-semibold" />
                        <select wire:model="modal_institution_type"
                            class="mt-1 w-full field">
                            <option value="">Seleccionar tipo...</option>
                            <option value="Publica">Publica</option>
                            <option value="Privada">Privada</option>

                        </select>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <x-input-label value="País" class="text-xs font-semibold" />

                            @php
                                $paises = config('paises');
                                if ($paises)
                                    ksort($paises);
                            @endphp

                            <x-text-input wire:model="modal_institution_country" list="lista-paises-modal" type="text"
                                class="mt-1 w-full" placeholder="Escribe para buscar un país..." autocomplete="off" />

                            <datalist id="lista-paises-modal">
                                @if($paises)
                                    @foreach($paises as $nombre => $codigo)
                                        <option value="{{ $nombre }}"></option>
                                    @endforeach
                                @endif
                            </datalist>
                        </div>
                        <div>
                            <x-input-label value="Ciudad" class="text-xs font-semibold" />
                            <x-text-input wire:model="modal_institution_city" type="text" class="mt-1 w-full"
                                placeholder="Ej: Bogotá" />
                        </div>
                    </div>

                    <div>
                        <x-input-label value="Sitio web" class="text-xs font-semibold" />
                        <x-text-input wire:model="modal_institution_website" type="url" class="mt-1 w-full"
                            placeholder="https://www.ejemplo.com" />
                        <x-input-error :messages="$errors->get('modal_institution_website')" class="mt-1" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2 border-t border-slate-200 pt-4">
                    <button type="button" wire:click="$set('showModal', false)"
                        class="btn btn-outline transition-colors">
                        Cancelar
                    </button>
                    <button type="button" wire:click="createInstitution"
                        class="btn btn-primary transition-colors">
                        Crear institución
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>