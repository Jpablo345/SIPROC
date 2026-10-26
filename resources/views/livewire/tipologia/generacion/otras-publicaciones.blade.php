<?php

use App\Livewire\Concerns\ValidatesPublication;
use App\Models\Publication;
use App\Models\PublicationType;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination, ValidatesPublication;

    public string $search = '';
    public ?int $editingId = null;

    public string $title = '';
    public ?string $publication_year = null;
    public ?string $scope = null;
    public ?string $country_publication = null;
    public ?string $url = null;
    public ?int $type_id = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(int $publicationId): void
    {
        $publication = Publication::findOrFail($publicationId);
        $this->authorize('update', $publication);

        $this->editingId = $publication->publication_id;
        $this->title = $publication->title;
        $this->publication_year = $publication->publication_year;
        $this->scope = $publication->scope;
        $this->country_publication = $publication->country_publication;
        $this->url = $publication->url;
        $this->type_id = $publication->type_id;
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        if ($this->editingId) {
            $this->authorize('update', Publication::findOrFail($this->editingId));
        } else {
            $this->authorize('create', Publication::class);
        }
        $this->validate(
            [
                ...$this->publicationRules(),
                'type_id' => ['nullable', 'integer', 'exists:publication_type,type_id'],
            ],
            [
                ...$this->publicationMessages(),
                'type_id.exists' => 'El tipo de publicación no es válido.',
            ]
        );

        $data = [
            'title' => trim($this->title),
            'publication_year' => $this->publication_year ? trim($this->publication_year) : null,
            'scope' => $this->scope,
            'country_publication' => $this->country_publication ? trim($this->country_publication) : null,
            'url' => $this->url ? trim($this->url) : null,
            'type_id' => $this->type_id,
        ];

        if ($this->editingId) {
            Publication::where('publication_id', $this->editingId)->update($data);
            session()->flash('status', 'Publicacion actualizada.');
        } else {
            Publication::create($data);
            session()->flash('status', 'Publicacion creada.');
        }

        $this->resetForm();
    }

    public function delete(int $publicationId): void
    {
        //Publication::where('publication_id', $publicationId)->delete();
        $publication = Publication::findOrFail($publicationId);
        $this->authorize('delete', $publication);
        $publication->delete();

        session()->flash('status', 'Publicacion eliminada.');
        $this->resetPage();
    }

    public function getPublicationsProperty()
    {
        $term = trim($this->search);

        return Publication::query()
            ->with('type')
            ->when($term !== '', function ($query) use ($term) {
                $like = '%' . $term . '%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('title', 'ilike', $like)
                        ->orWhereRaw('CAST(publication_year AS TEXT) LIKE ?', [$like])
                        ->orWhere('scope', 'ilike', $like)
                        ->orWhere('country_publication', 'ilike', $like)
                        ->orWhere('url', 'ilike', $like)
                        ->orWhereHas('type', function ($type) use ($like) {
                            $type->where('type_name', 'ilike', $like);
                        });
                });
            })
            ->orderByDesc('publication_id')
            ->paginate(10);
    }

    public function getTypesProperty()
    {
        return PublicationType::query()
            ->orderBy('type_name')
            ->get(['type_id', 'type_name']);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->publication_year = null;
        $this->scope = null;
        $this->country_publication = null;
        $this->url = null;
        $this->type_id = null;
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">
    <div class="rounded-2xl border border-white/60 bg-white/80 p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-[#2b2323]">
                    {{ $editingId ? 'Editar publicacion' : 'Nueva publicacion' }}
                </h3>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#9c1c1c]">
                    Registro general de publicaciones
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
                    {{ $editingId ? 'Guardar cambios' : 'Crear publicacion' }}
                </button>
            </div>
        </div>

        <x-validation-errors :bag="$errors" class="mt-4" />

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-input-label for="title" :value="'Titulo'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="title" wire:model.defer="title" type="text" class="mt-2 block w-full" />
            </div>
            <div>
                <x-input-label for="publication_year" :value="'Ano de publicacion'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="publication_year" wire:model.defer="publication_year" type="text"
                    class="mt-2 block w-full" />
            </div>
            <div>
                <x-input-label for="scope" :value="'Alcance'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <select id="scope" wire:model.defer="scope"
                    class="mt-2 block w-full rounded-md border-red-200 bg-white text-zinc-900 shadow-sm transition-colors focus:border-red-600 focus:ring-red-600">
                    <option value="">Sin definir</option>
                    <option value="Nacional">Nacional</option>
                    <option value="Internacional">Internacional</option>
                </select>
            </div>
            <div>
                <x-input-label for="country_publication" :value="'Pais'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="country_publication" wire:model.defer="country_publication" type="text"
                    class="mt-2 block w-full" />
            </div>
            <div>
                <x-input-label for="type_id" :value="'Tipo de publicacion'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <select id="type_id" wire:model.defer="type_id"
                    class="mt-2 block w-full rounded-md border-red-200 bg-white text-zinc-900 shadow-sm transition-colors focus:border-red-600 focus:ring-red-600">
                    <option value="">Sin asignar</option>
                    @foreach ($this->types as $type)
                        <option value="{{ $type->type_id }}">{{ $type->type_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <x-input-label for="url" :value="'URL'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="url" wire:model.defer="url" type="url" class="mt-2 block w-full"
                    placeholder="https://" />
                <x-input-error :messages="$errors->get('url')" class="mt-2" />
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-white/60 bg-white/80 p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-[#2b2323]">Publicaciones registradas</h3>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#9c1c1c]">
                    {{ $this->publications->total() }} registros
                </p>
            </div>
            <div class="w-full sm:w-72">
                <x-text-input wire:model.live="search" type="search" placeholder="Buscar por titulo, tipo o pais"
                    class="w-full" />
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-xl bg-[#f7e2d2] px-4 py-3 text-xs font-semibold text-[#7a1515]">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-6 overflow-hidden rounded-2xl border border-[#f0dede]">
            <table class="min-w-full divide-y divide-[#f0dede] text-sm">
                <thead class="bg-[#fff7f7] text-left text-xs font-semibold uppercase tracking-[0.2em] text-[#7a1515]">
                    <tr>
                        <th class="px-4 py-3">Titulo</th>
                        <th class="px-4 py-3">Ano</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Alcance</th>
                        <th class="px-4 py-3">Pais</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0dede] bg-white">
                    @forelse ($this->publications as $publication)
                        <tr wire:key="publication-{{ $publication->publication_id }}" class="hover:bg-[#fff7f7]">
                            <td class="px-4 py-3 font-semibold text-[#2b2323]">
                                <div>{{ $publication->title }}</div>
                                @if ($publication->url)
                                    <a href="{{ $publication->url }}" target="_blank" rel="noreferrer"
                                        class="text-xs text-[#9c1c1c] hover:underline">
                                        {{ $publication->url }}
                                    </a>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $publication->publication_year ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $publication->type?->type_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $publication->scope ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $publication->country_publication ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <button type="button" wire:click="edit({{ $publication->publication_id }})"
                                        class="rounded-full border border-[#9c1c1c]/30 px-3 py-1 text-xs font-semibold text-[#7a1515] hover:bg-[#f2d3d3]">
                                        Editar
                                    </button>
                                    <button type="button" wire:click="delete({{ $publication->publication_id }})"
                                        onclick="confirm('Seguro que deseas eliminar esta publicacion?') || event.stopImmediatePropagation()"
                                        class="rounded-full border border-[#d77a7a]/40 px-3 py-1 text-xs font-semibold text-[#9c1c1c] hover:bg-[#f9dede]">
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">
                                No hay publicaciones registradas todavia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->publications->links() }}
        </div>
    </div>
</div>