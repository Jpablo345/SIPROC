<?php

use App\Models\ResearchGroup;
use App\Models\Researcher;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public ?int $editingId = null;

    public ?string $document = null;
    public string $name_1 = '';
    public ?string $name_2 = null;
    public string $last_name_1 = '';
    public ?string $last_name_2 = null;
    public ?string $email = null;
    public ?string $phone = null;
    public ?string $cod_minciencias = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(int $researcherId): void
    {
        $researcher = Researcher::findOrFail($researcherId);
        $this->authorize('update', $researcher);

        $this->editingId = $researcher->researcher_id;
        $this->document = $researcher->document;
        $this->name_1 = $researcher->name_1;
        $this->name_2 = $researcher->name_2;
        $this->last_name_1 = $researcher->last_name_1;
        $this->last_name_2 = $researcher->last_name_2;
        $this->email = $researcher->email;
        $this->phone = $researcher->phone;
        $this->cod_minciencias = $researcher->cod_minciencias;
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        if ($this->editingId) {
            $this->authorize('update', Researcher::findOrFail($this->editingId));
        } else {
            $this->authorize('create', Researcher::class);
        }
        $this->validate([
            'document' => ['nullable', 'string', 'max:20', Rule::unique('researcher', 'document')->ignore($this->editingId, 'researcher_id')],
            'name_1' => ['required', 'string', 'max:50'],
            'name_2' => ['nullable', 'string', 'max:50'],
            'last_name_1' => ['required', 'string', 'max:50'],
            'last_name_2' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100', Rule::unique('researcher', 'email')->ignore($this->editingId, 'researcher_id')],
            'phone' => ['nullable', 'string', 'max:30'],
            'cod_minciencias' => ['nullable', 'string', 'exists:research_group,cod_minciencias'],
        ], [
            'document.unique' => 'Este número de documento ya está registrado.',
            'name_1.required' => 'El primer nombre es obligatorio.',
            'last_name_1.required' => 'El primer apellido es obligatorio.',
            'email.unique' => 'Este correo ya está registrado.',
        ]);

        $data = [
            'document' => $this->document ? trim($this->document) : null,
            'name_1' => trim($this->name_1),
            'name_2' => $this->name_2 ? trim($this->name_2) : null,
            'last_name_1' => trim($this->last_name_1),
            'last_name_2' => $this->last_name_2 ? trim($this->last_name_2) : null,
            'email' => $this->email ? trim($this->email) : null,
            'phone' => $this->phone ? trim($this->phone) : null,
            'cod_minciencias' => $this->cod_minciencias ?: null,
        ];

        if ($this->editingId) {
            Researcher::where('researcher_id', $this->editingId)->update($data);
            session()->flash('status', 'Investigador actualizado.');
        } else {
            Researcher::create($data);
            session()->flash('status', 'Investigador creado.');
        }

        $this->resetForm();
    }

    public function delete(int $researcherId): void
    {
        //Researcher::where('researcher_id', $researcherId)->delete();
        $researcher = Researcher::findOrFail($researcherId);
        $this->authorize('delete', $researcher);
        $researcher->delete();

        session()->flash('status', 'Investigador eliminado.');
        $this->resetPage();
    }

    public function getResearchersProperty()
    {
        $term = trim($this->search);

        return Researcher::query()
            ->with('researchGroup')
            ->when($term !== '', function ($query) use ($term) {
                $like = '%' . $term . '%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('document', 'ilike', $like)
                        ->orWhere('name_1', 'ilike', $like)
                        ->orWhere('name_2', 'ilike', $like)
                        ->orWhere('last_name_1', 'ilike', $like)
                        ->orWhere('last_name_2', 'ilike', $like)
                        ->orWhere('email', 'ilike', $like)
                        ->orWhere('phone', 'ilike', $like)
                        ->orWhereHas('researchGroup', function ($group) use ($like) {
                            $group->where('group_name', 'ilike', $like);
                        });
                });
            })
            ->orderBy('last_name_1')
            ->paginate(10);
    }

    public function getGroupsProperty()
    {
        return ResearchGroup::query()
            ->orderBy('group_name')
            ->get(['cod_minciencias', 'group_name']);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->document = null;
        $this->name_1 = '';
        $this->name_2 = null;
        $this->last_name_1 = '';
        $this->last_name_2 = null;
        $this->email = null;
        $this->phone = null;
        $this->cod_minciencias = null;
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">
    <div class="rounded-2xl border border-white/60 bg-white/80 p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-[#2b2323]">
                    {{ $editingId ? 'Editar investigador' : 'Nuevo investigador' }}
                </h3>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#9c1c1c]">
                    Datos personales y grupo asociado
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
                    {{ $editingId ? 'Guardar cambios' : 'Crear investigador' }}
                </button>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="document" :value="'Documento'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="document" wire:model.defer="document" type="text"
                    class="mt-2 block w-full" />
                <x-input-error :messages="$errors->get('document')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="cod_minciencias" :value="'Grupo Minciencias'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <select id="cod_minciencias" wire:model.defer="cod_minciencias"
                    class="mt-2 block w-full rounded-md border-red-200 bg-white text-zinc-900 shadow-sm transition-colors focus:border-red-600 focus:ring-red-600">
                    <option value="">Sin asignar</option>
                    @foreach ($this->groups as $group)
                        <option value="{{ $group->cod_minciencias }}">{{ $group->group_name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('cod_minciencias')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="name_1" :value="'Primer nombre'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="name_1" wire:model.defer="name_1" type="text" class="mt-2 block w-full" />
                <x-input-error :messages="$errors->get('name_1')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="name_2" :value="'Segundo nombre'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="name_2" wire:model.defer="name_2" type="text" class="mt-2 block w-full" />
                <x-input-error :messages="$errors->get('name_2')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="last_name_1" :value="'Primer apellido'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="last_name_1" wire:model.defer="last_name_1" type="text" class="mt-2 block w-full" />
                <x-input-error :messages="$errors->get('last_name_1')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="last_name_2" :value="'Segundo apellido'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="last_name_2" wire:model.defer="last_name_2" type="text" class="mt-2 block w-full" />
                <x-input-error :messages="$errors->get('last_name_2')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="email" :value="'Correo'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="email" wire:model.defer="email" type="email" class="mt-2 block w-full" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="phone" :value="'Telefono'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="phone" wire:model.defer="phone" type="text" class="mt-2 block w-full" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-white/60 bg-white/80 p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-[#2b2323]">Investigadores registrados</h3>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#9c1c1c]">
                    {{ $this->researchers->total() }} registros
                </p>
            </div>
            <div class="w-full sm:w-72">
                <x-text-input wire:model.live="search" type="search" placeholder="Buscar por nombre, correo o grupo"
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
                        <th class="px-4 py-3">Documento</th>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Contacto</th>
                        <th class="px-4 py-3">Grupo</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0dede] bg-white">
                    @forelse ($this->researchers as $researcher)
                        <tr wire:key="researcher-{{ $researcher->researcher_id }}" class="hover:bg-[#fff7f7]">
                            <td class="px-4 py-3 font-semibold text-[#2b2323]">{{ $researcher->document ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $researcher->name_1 }} {{ $researcher->name_2 }} {{ $researcher->last_name_1 }}
                                {{ $researcher->last_name_2 }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                <div>{{ $researcher->email ?? '—' }}</div>
                                <div class="text-xs text-slate-400">{{ $researcher->phone ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $researcher->researchGroup?->group_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <button type="button" wire:click="edit({{ $researcher->researcher_id }})"
                                        class="rounded-full border border-[#9c1c1c]/30 px-3 py-1 text-xs font-semibold text-[#7a1515] hover:bg-[#f2d3d3]">
                                        Editar
                                    </button>
                                    <button type="button" wire:click="delete({{ $researcher->researcher_id }})"
                                        onclick="confirm('Seguro que deseas eliminar este investigador?') || event.stopImmediatePropagation()"
                                        class="rounded-full border border-[#d77a7a]/40 px-3 py-1 text-xs font-semibold text-[#9c1c1c] hover:bg-[#f9dede]">
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">
                                No hay investigadores registrados todavia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->researchers->links() }}
        </div>
    </div>
</div>