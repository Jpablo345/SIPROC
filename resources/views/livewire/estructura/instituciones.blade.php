<?php

use App\Models\Institution;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public ?int $editingId = null;

    public string $institution_name = '';
    public ?string $country = null;
    public ?string $city = null;
    public ?string $institution_type = null;
    public ?string $website = null;

    public function rules(): array
    {
        return [
            'institution_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('institution', 'institution_name')->ignore($this->editingId, 'institution_id'),
            ],
            'country' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:50'],
            'institution_type' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:255', 'url', 'starts_with:http'],
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(int $institutionId): void
    {
        $institution = Institution::findOrFail($institutionId);
        $this->authorize('update', $institution);

        $this->editingId = $institution->institution_id;
        $this->institution_name = $institution->institution_name;
        $this->country = $institution->country;
        $this->city = $institution->city;
        $this->institution_type = $institution->institution_type;
        $this->website = $institution->website;
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        if ($this->editingId) {
            $this->authorize('update', Institution::findOrFail($this->editingId));
        } else {
            $this->authorize('create', Institution::class);
        }
        $data = $this->validate();

        if ($this->editingId) {
            Institution::where('institution_id', $this->editingId)->update($data);
            session()->flash('status', 'Institución actualizada.');
        } else {
            Institution::create($data);
            session()->flash('status', 'Institución creada.');
        }

        $this->resetForm();
    }

    public function delete(int $institutionId): void
    {
        //Institution::where('institution_id', $institutionId)->delete();
        $institution = Institution::findOrFail($institutionId);
        $this->authorize('delete', $institution);
        $institution->delete();

        session()->flash('status', 'Institución eliminada.');
        $this->resetPage();
    }

    public function getInstitutionsProperty()
    {
        $term = trim($this->search);

        return Institution::query()
            ->when($term !== '', function ($query) use ($term) {
                $like = '%' . $term . '%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('institution_name', 'ilike', $like)
                        ->orWhere('country', 'ilike', $like)
                        ->orWhere('city', 'ilike', $like)
                        ->orWhere('institution_type', 'ilike', $like)
                        ->orWhere('website', 'ilike', $like);
                });
            })
            ->orderBy('institution_name')
            ->paginate(10);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->institution_name = '';
        $this->country = null;
        $this->city = null;
        $this->institution_type = null;
        $this->website = null;
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">
    <div class="rounded-2xl border border-white/60 bg-white/80 p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-[#2b2323]">
                    {{ $editingId ? 'Editar institución' : 'Nueva institución' }}
                </h3>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#9c1c1c]">
                    Catálogo base para grupos y eventos
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
                    {{ $editingId ? 'Guardar cambios' : 'Crear institución' }}
                </button>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="institution_name" :value="'Nombre'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="institution_name" wire:model="institution_name" type="text"
                    class="mt-2 block w-full" />
                <x-input-error :messages="$errors->get('institution_name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="institution_type" :value="'Tipo'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="institution_type" wire:model="institution_type" type="text"
                    class="mt-2 block w-full" />
                <x-input-error :messages="$errors->get('institution_type')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="country" :value="'País'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />

                @php
                    $paises = config('paises');
                    if ($paises) {
                        ksort($paises);
                    }
                @endphp

                <x-text-input id="country" list="lista-paises" wire:model="country" type="text"
                    class="mt-2 block w-full" placeholder="Escribe para buscar un país..." autocomplete="off" />

                <datalist id="lista-paises">
                    @if($paises)
                        @foreach($paises as $nombre => $codigo)
                            <option value="{{ $nombre }}"></option>
                        @endforeach
                    @endif
                </datalist>

                <x-input-error :messages="$errors->get('country')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="city" :value="'Ciudad'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="city" wire:model="city" type="text" class="mt-2 block w-full" />
                <x-input-error :messages="$errors->get('city')" class="mt-2" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="website" :value="'Sitio web'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="website" wire:model="website" type="url" placeholder="https://"
                    class="mt-2 block w-full" />
                <x-input-error :messages="$errors->get('website')" class="mt-2" />
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-white/60 bg-white/80 p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-[#2b2323]">Instituciones registradas</h3>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#9c1c1c]">
                    {{ $this->institutions->total() }} registros
                </p>
            </div>
            <div class="w-full sm:w-72">
                <x-text-input wire:model.live="search" type="search" placeholder="Buscar por nombre, ciudad o tipo"
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
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">País</th>
                        <th class="px-4 py-3">Ciudad</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Web</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0dede] bg-white">
                    @forelse ($this->institutions as $institution)
                        <tr wire:key="institution-{{ $institution->institution_id }}" class="hover:bg-[#fff7f7]">
                            <td class="px-4 py-3 font-semibold text-[#2b2323]">{{ $institution->institution_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $institution->country ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $institution->city ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $institution->institution_type ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                @if ($institution->website)
                                    <a href="{{ $institution->website }}" target="_blank" rel="noreferrer"
                                        class="text-[#9c1c1c] hover:underline">
                                        {{ $institution->website }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <button type="button" wire:click="edit({{ $institution->institution_id }})"
                                        class="rounded-full border border-[#9c1c1c]/30 px-3 py-1 text-xs font-semibold text-[#7a1515] hover:bg-[#f2d3d3]">
                                        Editar
                                    </button>
                                    <button type="button" wire:click="delete({{ $institution->institution_id }})"
                                        onclick="confirm('¿Seguro que deseas eliminar esta institución?') || event.stopImmediatePropagation()"
                                        class="rounded-full border border-[#d77a7a]/40 px-3 py-1 text-xs font-semibold text-[#9c1c1c] hover:bg-[#f9dede]">
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">
                                No hay instituciones registradas todavía.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->institutions->links() }}
        </div>
    </div>
</div>