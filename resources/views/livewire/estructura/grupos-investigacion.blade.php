<?php

use App\Models\Institution;
use App\Models\ResearchGroup;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public ?string $editingCode = null;

    public string $cod_minciencias = '';
    public string $group_name = '';
    public ?string $group_classification = null;
    public ?int $institution_id = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(string $code): void
    {
        $group = ResearchGroup::findOrFail($code);
        $this->authorize('update', $group);

        $this->editingCode = $group->cod_minciencias;
        $this->cod_minciencias = $group->cod_minciencias;
        $this->group_name = $group->group_name;
        $this->group_classification = $group->group_classification;
        $this->institution_id = $group->institution_id;
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        if ($this->editingCode) {
            $this->authorize('update', ResearchGroup::findOrFail($this->editingCode));
        } else {
            $this->authorize('create', ResearchGroup::class);
        }
        if (trim($this->cod_minciencias) === '' || trim($this->group_name) === '') {
            session()->flash('status', 'Completa el codigo y el nombre del grupo.');
            return;
        }

        $data = [
            'cod_minciencias' => trim($this->cod_minciencias),
            'group_name' => trim($this->group_name),
            'group_classification' => $this->group_classification ? trim($this->group_classification) : null,
            'institution_id' => $this->institution_id,
        ];

        if ($this->editingCode) {
            $originalCode = $this->editingCode;

            ResearchGroup::where('cod_minciencias', $originalCode)->update($data);
            session()->flash('status', 'Grupo actualizado.');
        } else {
            ResearchGroup::create($data);
            session()->flash('status', 'Grupo creado.');
        }

        $this->resetForm();
    }

    public function delete(string $code): void
    {
        //ResearchGroup::where('cod_minciencias', $code)->delete();

        $group = ResearchGroup::findOrFail($code);
        $this->authorize('delete', $group);
        $group->delete();

        session()->flash('status', 'Grupo eliminado.');
        $this->resetPage();
    }

    public function getGroupsProperty()
    {
        $term = trim($this->search);

        return ResearchGroup::query()
            ->with('institution')
            ->when($term !== '', function ($query) use ($term) {
                $like = '%' . $term . '%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('cod_minciencias', 'ilike', $like)
                        ->orWhere('group_name', 'ilike', $like)
                        ->orWhere('group_classification', 'ilike', $like)
                        ->orWhereHas('institution', function ($institution) use ($like) {
                            $institution->where('institution_name', 'ilike', $like);
                        });
                });
            })
            ->orderBy('group_name')
            ->paginate(10);
    }

    public function getInstitutionsProperty()
    {
        return Institution::query()
            ->orderBy('institution_name')
            ->get(['institution_id', 'institution_name']);
    }

    private function resetForm(): void
    {
        $this->editingCode = null;
        $this->cod_minciencias = '';
        $this->group_name = '';
        $this->group_classification = null;
        $this->institution_id = null;
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">
    <div class="rounded-2xl border border-white/60 bg-white/80 p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-[#2b2323]">
                    {{ $editingCode ? 'Editar grupo' : 'Nuevo grupo' }}
                </h3>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#9c1c1c]">
                    Vinculado a una institucion
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($editingCode)
                    <button type="button" wire:click="cancelEdit"
                        class="rounded-full border border-[#9c1c1c]/30 px-4 py-2 text-xs font-semibold text-[#7a1515] hover:bg-[#f2d3d3]">
                        Cancelar
                    </button>
                @endif
                <button type="button" wire:click="save"
                    class="rounded-full bg-[#9c1c1c] px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-[#7a1515]">
                    {{ $editingCode ? 'Guardar cambios' : 'Crear grupo' }}
                </button>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="cod_minciencias" :value="'Codigo Minciencias'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="cod_minciencias" wire:model.defer="cod_minciencias" type="text"
                    class="mt-2 block w-full" />
            </div>
            <div>
                <x-input-label for="group_classification" :value="'Clasificacion'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <select id="group_classification" wire:model="group_classification"
                    class="mt-2 block w-full rounded-md border-red-200 bg-white text-zinc-900 shadow-sm transition-colors focus:border-red-600 focus:ring-red-600">
                    <option value="">Sin clasificacion</option>
                    <option value="Categoría A1">Categoría A1</option>
                    <option value="Categoría A">Categoría A</option>
                    <option value="Categoría B">Categoría B</option>
                    <option value="Categoría C">Categoría C</option>
                    <option value="Categoría D">Categoría D</option>
                    <option value="Institucional">Institucional</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <x-input-label for="group_name" :value="'Nombre del grupo'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input id="group_name" wire:model.defer="group_name" type="text" class="mt-2 block w-full" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="institution_id" :value="'Institucion'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <select id="institution_id" wire:model.defer="institution_id"
                    class="mt-2 block w-full rounded-md border-red-200 bg-white text-zinc-900 shadow-sm transition-colors focus:border-red-600 focus:ring-red-600">
                    <option value="">Sin asignar</option>
                    @foreach ($this->institutions as $institution)
                        <option value="{{ $institution->institution_id }}">{{ $institution->institution_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-white/60 bg-white/80 p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-[#2b2323]">Grupos registrados</h3>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#9c1c1c]">{{ $this->groups->total() }}
                    registros</p>
            </div>
            <div class="w-full sm:w-72">
                <x-text-input wire:model.live="search" type="search"
                    placeholder="Buscar por codigo, nombre o institucion" class="w-full" />
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
                        <th class="px-4 py-3">Codigo</th>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Clasificacion</th>
                        <th class="px-4 py-3">Institucion</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0dede] bg-white">
                    @forelse ($this->groups as $group)
                        <tr wire:key="group-{{ $group->cod_minciencias }}" class="hover:bg-[#fff7f7]">
                            <td class="px-4 py-3 font-semibold text-[#2b2323]">{{ $group->cod_minciencias }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $group->group_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $group->group_classification ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $group->institution?->institution_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <button type="button" wire:click="edit('{{ $group->cod_minciencias }}')"
                                        class="rounded-full border border-[#9c1c1c]/30 px-3 py-1 text-xs font-semibold text-[#7a1515] hover:bg-[#f2d3d3]">
                                        Editar
                                    </button>
                                    <button type="button" wire:click="delete('{{ $group->cod_minciencias }}')"
                                        onclick="confirm('Seguro que deseas eliminar este grupo?') || event.stopImmediatePropagation()"
                                        class="rounded-full border border-[#d77a7a]/40 px-3 py-1 text-xs font-semibold text-[#9c1c1c] hover:bg-[#f9dede]">
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">
                                No hay grupos registrados todavia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->groups->links() }}
        </div>
    </div>
</div>