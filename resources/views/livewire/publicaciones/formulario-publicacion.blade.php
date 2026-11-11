<form wire:submit.prevent="guardar" class="space-y-6">
    <div class="card">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="section-title">Formulario de publicación</h3>
                <p class="eyebrow">
                    Campos comunes y especificos por tipo
                </p>
            </div>
            <button type="submit" wire:loading.attr="disabled" wire:target="guardar"
                class="btn btn-primary">
                <x-spinner wire:loading wire:target="guardar" />
                Guardar publicación
            </button>
        </div>

        <x-flash-status />

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-input-label for="title" :value="'Título'"
                    class="form-label" />
                <x-text-input id="title" wire:model.defer="title" type="text" class="mt-2 block w-full" />
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="publication_year" :value="'Año'"
                    class="form-label" />
                <x-text-input id="publication_year" wire:model.defer="publication_year" type="text"
                    class="mt-2 block w-full" />
                <x-input-error :messages="$errors->get('publication_year')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="scope" :value="'Ámbito'"
                    class="form-label" />
                <select id="scope" wire:model.defer="scope"
                    class="mt-2 block w-full field">
                    <option value="">Sin definir</option>
                    <option value="Nacional">Nacional</option>
                    <option value="Internacional">Internacional</option>
                </select>
                <x-input-error :messages="$errors->get('scope')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="country_publication" :value="'País'"
                    class="form-label" />

                @php
                    //Llama al archivo config/paises.php
                    $paises = config('paises');
                    if ($paises)
                        ksort($paises); // Ordena alfabéticamente
                @endphp

                <x-text-input id="country_publication" list="lista-paises" wire:model="country_publication" type="text"
                    class="mt-2 block w-full" placeholder="Escribe para buscar un país..." autocomplete="off" />

                <datalist id="lista-paises">
                    @if($paises)
                        @foreach($paises as $nombre => $codigo)
                            <option value="{{ $nombre }}"></option>
                        @endforeach
                    @endif
                </datalist>

                <x-input-error :messages="$errors->get('country_publication')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="type_id" :value="'Tipo'"
                    class="form-label" />
                <select id="type_id" wire:model.live="type_id"
                    class="mt-2 block w-full field">
                    <option value="">Selecciona un tipo</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->type_id }}">{{ $type->type_name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('type_id')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="url" :value="'URL'"
                    class="form-label" />
                <x-text-input id="url" wire:model.defer="url" type="url" class="mt-2 block w-full"
                    placeholder="https://" />
                <x-input-error :messages="$errors->get('url')" class="mt-2" />
            </div>
        </div>
    </div>

    @if ($this->isArticleType())
        <div class="card">
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-semibold uppercase tracking-[0.2em] text-siproc-red">Datos de artículo</h4>
            </div>

            <div class="mt-4">
                <x-input-label for="journal_search" :value="'Revista (ISSN o nombre)'"
                    class="form-label" />
                <x-text-input id="journal_search" wire:model.live.debounce.300ms="journalSearch" type="text" class="mt-2 block w-full" />
                <x-input-error :messages="$errors->get('journal_issn')" class="mt-2" />

                @if ($journalResults)
                    <div class="mt-3 space-y-2 rounded-xl border border-siproc-line bg-white p-3">
                        @foreach ($journalResults as $journal)
                            <button type="button" wire:click="seleccionarRevista('{{ $journal['journal_issn'] }}')"
                                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-siproc-blush">
                                <span>{{ $journal['journal_name'] }}</span>
                                <span class="text-xs text-slate-400">{{ $journal['journal_issn'] }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if ($this->isBookType())
        <div class="card">
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-semibold uppercase tracking-[0.2em] text-siproc-red">Datos de libro</h4>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="book_isbn" :value="'ISBN'"
                        class="form-label" />
                    <x-text-input id="book_isbn" wire:model.defer="book_isbn" type="text" class="mt-2 block w-full" />
                    <x-input-error :messages="$errors->get('book_isbn')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="book_type_id" :value="'Tipo de libro'"
                        class="form-label" />
                    <select id="book_type_id" wire:model.defer="book_type_id"
                        class="mt-2 block w-full field">
                        <option value="">Selecciona un tipo</option>
                        @foreach ($bookTypes as $bookType)
                            <option value="{{ $bookType->book_type_id }}">{{ $bookType->type_name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('book_type_id')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="means_of_dissemination" :value="'Medio de difusión'"
                        class="form-label" />
                    <select id="means_of_dissemination" wire:model="means_of_dissemination"
                        class="mt-2 block w-full field">
                        <option value="">Selecciona un medio</option>
                        <option value="Electronico">Electrónico</option>
                        <option value="Papel">Papel</option>
                    </select>
                    <x-input-error :messages="$errors->get('means_of_dissemination')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="editorial" :value="'Editorial'"
                        class="form-label" />
                    <x-text-input id="editorial" wire:model.defer="editorial" type="text" class="mt-2 block w-full" />
                    <x-input-error :messages="$errors->get('editorial')" class="mt-2" />
                </div>
            </div>
        </div>
    @endif

    @if ($this->dynamicFields->isNotEmpty())
        <div class="card">
            <h4 class="text-sm font-semibold uppercase tracking-[0.2em] text-siproc-red">Datos de {{ $this->dynamicTypeName }}</h4>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                @foreach ($this->dynamicFields as $field)
                    <x-dynamic-field :field="$field" :bag="$errors" id-prefix="pubextra" wire:key="pub-field-{{ $field->field_id }}" />
                @endforeach
            </div>
        </div>
    @endif

    <div class="card">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h4 class="text-sm font-semibold uppercase tracking-[0.2em] text-siproc-red">Autores</h4>
                <p class="text-xs text-slate-500">Busca por nombre o documento.</p>
            </div>
            <button type="button" x-on:click="$dispatch('open-modal', 'crear-investigador')"
                class="btn btn-outline">
                + Crear investigador
            </button>
        </div>

        <div class="mt-4">
            <x-text-input wire:model.live.debounce.300ms="authorSearch" type="search" class="w-full"
                placeholder="Buscar investigador" />
        </div>

        @if ($authorResults)
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

    <x-modal name="crear-investigador" maxWidth="2xl">
        <div class="bg-white p-6">
            <div class="flex items-center justify-between">
                <h3 class="section-title">Crear investigador</h3>
                <button type="button" x-on:click="$dispatch('close-modal', 'crear-investigador')"
                    class="text-sm text-siproc-red">Cerrar</button>
            </div>

            <x-researcher-form-fields :form="$researcherForm" :groups="$groups" :institutions="$institutions"
                :bag="$errors" id-prefix="pub" />

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" x-on:click="$dispatch('close-modal', 'crear-investigador')"
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
</form>