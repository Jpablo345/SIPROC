<?php

namespace App\Livewire\Publicaciones;

use App\Models\PublicationType;
use Livewire\Component;
use Livewire\WithPagination;

class TiposPublicacion extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $editingId = null;
    public string $type_name = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(int $typeId): void
    {
        $type = PublicationType::findOrFail($typeId);
        $this->authorize('update', $type);

        $this->editingId = $type->type_id;
        $this->type_name = $type->type_name;
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        if ($this->editingId) {
            $this->authorize('update', PublicationType::findOrFail($this->editingId));
        } else {
            $this->authorize('create', PublicationType::class);
        }
        if (trim($this->type_name) === '') {
            session()->flash('status', 'Completa el nombre del tipo.');
            return;
        }

        $data = ['type_name' => trim($this->type_name)];

        if ($this->editingId) {
            PublicationType::where('type_id', $this->editingId)->update($data);
            session()->flash('status', 'Tipo actualizado.');
        } else {
            PublicationType::create($data);
            session()->flash('status', 'Tipo creado.');
        }

        $this->resetForm();
    }

    public function delete(int $typeId): void
    {
        $type = PublicationType::findOrFail($typeId);
        $this->authorize('delete', $type);
        $type->delete();

        session()->flash('status', 'Tipo eliminado.');
        $this->resetPage();
    }

    public function getTypesProperty()
    {
        $term = trim($this->search);

        return PublicationType::query()
            ->when($term !== '', function ($query) use ($term) {
                $like = '%' . $term . '%';
                $query->where('type_name', 'ilike', $like);
            })
            ->orderBy('type_name')
            ->paginate(10);
    }

    public function render()
    {
        return view('publicaciones.tipos')->layout('layouts.app');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->type_name = '';
        $this->resetValidation();
    }
}
