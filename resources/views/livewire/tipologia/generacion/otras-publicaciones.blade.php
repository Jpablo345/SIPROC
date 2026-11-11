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
            session()->flash('status', 'Publicación actualizada.');
        } else {
            Publication::create($data);
            session()->flash('status', 'Publicación creada.');
        }

        $this->resetForm();
    }

    public function delete(int $publicationId): void
    {
        //Publication::where('publication_id', $publicationId)->delete();
        $publication = Publication::findOrFail($publicationId);
        $this->authorize('delete', $publication);
        $publication->delete();

        session()->flash('status', 'Publicación eliminada.');
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
    <div class="card">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="section-title">
                    {{ $editingId ? 'Editar publicación' : 'Nueva publicación' }}
                </h3>
                <p class="eyebrow">
                    Registro general de publicaciones
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
                    {{ $editingId ? 'Guardar cambios' : 'Crear publicación' }}
                </button>
            </div>
        </div>

        <x-validation-errors :bag="$errors" class="mt-4" />

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-input-label for="title" :value="'Título'"
                    class="form-label" />
                <x-text-input id="title" wire:model.defer="title" type="text" class="mt-2 block w-full" />
            </div>
            <div>
                <x-input-label for="publication_year" :value="'Año de publicación'"
                    class="form-label" />
                <x-text-input id="publication_year" wire:model.defer="publication_year" type="text"
                    class="mt-2 block w-full" />
            </div>
            <div>
                <x-input-label for="scope" :value="'Alcance'"
                    class="form-label" />
                <select id="scope" wire:model.defer="scope"
                    class="mt-2 block w-full field">
                    <option value="">Sin definir</option>
                    <option value="Nacional">Nacional</option>
                    <option value="Internacional">Internacional</option>
                </select>
            </div>
            <div>
                <x-input-label for="country_publication" :value="'País'"
                    class="form-label" />
                <x-text-input id="country_publication" wire:model.defer="country_publication" type="text"
                    class="mt-2 block w-full" />
            </div>
            <div>
                <x-input-label for="type_id" :value="'Tipo de publicación'"
                    class="form-label" />
                <select id="type_id" wire:model.defer="type_id"
                    class="mt-2 block w-full field">
                    <option value="">Sin asignar</option>
                    @foreach ($this->types as $type)
                        <option value="{{ $type->type_id }}">{{ $type->type_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <x-input-label for="url" :value="'URL'"
                    class="form-label" />
                <x-text-input id="url" wire:model.defer="url" type="url" class="mt-2 block w-full"
                    placeholder="https://" />
                <x-input-error :messages="$errors->get('url')" class="mt-2" />
            </div>
        </div>
    </div>

    <div class="card">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="section-title">Publicaciones registradas</h3>
                <p class="eyebrow">
                    {{ $this->publications->total() }} registros
                </p>
            </div>
            <div class="w-full sm:w-72">
                <x-text-input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar por título, tipo o país"
                    class="w-full" />
            </div>
        </div>

        <x-flash-status />

        <div class="mt-6 table-wrap" wire:loading.delay.class="opacity-50">
            <table class="data-table">
                <thead class="table-head">
                    <tr>
                        <th class="px-4 py-3">Título</th>
                        <th class="px-4 py-3">Año</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Alcance</th>
                        <th class="px-4 py-3">País</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="table-body">
                    @forelse ($this->publications as $publication)
                        <tr wire:key="publication-{{ $publication->publication_id }}" class="hover:bg-siproc-blush">
                            <td class="px-4 py-3 font-semibold text-siproc-ink">
                                <div>{{ $publication->title }}</div>
                                @if ($publication->url)
                                    <a href="{{ $publication->url }}" target="_blank" rel="noreferrer"
                                        class="text-xs text-siproc-red hover:underline">
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
                                        class="btn btn-sm btn-outline">
                                        Editar
                                    </button>
                                    <button type="button" x-on:click="$dispatch('confirmar', { message: '¿Seguro que deseas eliminar esta publicación?', action: () => $wire.delete({{ $publication->publication_id }}) })"
                                        class="btn btn-sm btn-danger">
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="table-empty">
                                No hay publicaciones registradas todavía.
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