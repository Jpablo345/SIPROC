<?php

use App\Models\Institution;
use App\Models\ResearchGroup;
use Illuminate\Validation\Rule;
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
        $this->validate([
            'cod_minciencias' => ['required', 'string', 'max:50', Rule::unique('research_group', 'cod_minciencias')->ignore($this->editingCode, 'cod_minciencias')],
            'group_name' => ['required', 'string', 'max:255'],
            'group_classification' => ['nullable', 'string', 'max:50'],
            'institution_id' => ['nullable', 'integer', 'exists:institution,institution_id'],
        ], [
            'cod_minciencias.required' => 'El código Minciencias es obligatorio.',
            'cod_minciencias.max' => 'El código no puede superar 50 caracteres.',
            'cod_minciencias.unique' => 'Ya existe un grupo con este código.',
            'group_name.required' => 'El nombre del grupo es obligatorio.',
            'group_name.max' => 'El nombre no puede superar 255 caracteres.',
            'group_classification.max' => 'La clasificación no puede superar 50 caracteres.',
            'institution_id.exists' => 'La institución seleccionada no existe.',
        ]);

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
    <div class="card">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="section-title">
                    {{ $editingCode ? 'Editar grupo' : 'Nuevo grupo' }}
                </h3>
                <p class="eyebrow">
                    Vinculado a una institución
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($editingCode)
                    <button type="button" wire:click="cancelEdit"
                        class="btn btn-outline">
                        Cancelar
                    </button>
                @endif
                <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                    class="btn btn-primary"><x-spinner wire:loading wire:target="save" />
                    {{ $editingCode ? 'Guardar cambios' : 'Crear grupo' }}
                </button>
            </div>
        </div>

        <x-validation-errors :bag="$errors" class="mt-4" />

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="cod_minciencias" :value="'Código Minciencias'"
                    class="form-label" />
                <x-text-input id="cod_minciencias" wire:model.defer="cod_minciencias" type="text"
                    class="mt-2 block w-full" />
            </div>
            <div>
                <x-input-label for="group_classification" :value="'Clasificación'"
                    class="form-label" />
                <select id="group_classification" wire:model="group_classification"
                    class="mt-2 block w-full field">
                    <option value="">Sin clasificación</option>
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
                    class="form-label" />
                <x-text-input id="group_name" wire:model.defer="group_name" type="text" class="mt-2 block w-full" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="institution_id" :value="'Institución'"
                    class="form-label" />
                <select id="institution_id" wire:model.defer="institution_id"
                    class="mt-2 block w-full field">
                    <option value="">Sin asignar</option>
                    @foreach ($this->institutions as $institution)
                        <option value="{{ $institution->institution_id }}">{{ $institution->institution_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="section-title">Grupos registrados</h3>
                <p class="eyebrow">{{ $this->groups->total() }}
                    registros</p>
            </div>
            <div class="w-full sm:w-72">
                <x-text-input wire:model.live.debounce.300ms="search" type="search"
                    placeholder="Buscar por código, nombre o institución" class="w-full" />
            </div>
        </div>

        <x-flash-status />

        <div class="mt-6 table-wrap" wire:loading.delay.class="opacity-50">
            <table class="data-table">
                <thead class="table-head">
                    <tr>
                        <th class="px-4 py-3">Código</th>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Clasificación</th>
                        <th class="px-4 py-3">Institución</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="table-body">
                    @forelse ($this->groups as $group)
                        <tr wire:key="group-{{ $group->cod_minciencias }}" class="hover:bg-siproc-blush">
                            <td class="px-4 py-3 font-semibold text-siproc-ink">{{ $group->cod_minciencias }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $group->group_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $group->group_classification ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $group->institution?->institution_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <button type="button" wire:click="edit('{{ $group->cod_minciencias }}')"
                                        class="btn btn-sm btn-outline">
                                        Editar
                                    </button>
                                    <button type="button" x-on:click="$dispatch('confirmar', { message: '¿Seguro que deseas eliminar este grupo?', action: () => $wire.delete('{{ $group->cod_minciencias }}') })"
                                        class="btn btn-sm btn-danger">
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="table-empty">
                                No hay grupos registrados todavía.
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