<?php

namespace App\Livewire\Publicaciones;

use App\Models\PublicationType;
use Closure;
use Illuminate\Support\Facades\Gate;
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

    public function save()
    {
        if ($this->editingId) {
            $this->authorize('update', PublicationType::findOrFail($this->editingId));
        } else {
            $this->authorize('create', PublicationType::class);
        }
        $this->type_name = trim($this->type_name);
        $this->validate([
            'type_name' => ['required', 'string', 'max:60', $this->uniqueNameRule()],
        ], [
            'type_name.required' => 'Completa el nombre del tipo.',
            'type_name.max' => 'El nombre no puede superar 60 caracteres.',
        ]);

        $data = ['type_name' => $this->type_name];

        if ($this->editingId) {
            PublicationType::where('type_id', $this->editingId)->update($data);
            session()->flash('status', 'Tipo actualizado.');
            $this->resetForm();
            return null;
        }

        $type = PublicationType::create($data);
        session()->flash('status', 'Tipo creado. Ahora define los campos que pedirá su formulario.');

        // Al crear un tipo se pasa directo a configurar sus campos.
        return $this->redirectRoute('publicaciones.tipos.campos', ['type' => $type->type_id], navigate: true);
    }

    public function delete(int $typeId): void
    {
        $type = PublicationType::findOrFail($typeId);
        $this->authorize('delete', $type);

        // publication.type_id tiene FK restrict: borrar un tipo en uso falla en la BD.
        if ($type->publications()->exists()) {
            session()->flash('status', 'No se puede eliminar: hay publicaciones de este tipo.');
            return;
        }

        $type->delete();

        session()->flash('status', 'Tipo eliminado.');
        $this->resetPage();
    }

    public function getTypesProperty()
    {
        $term = trim($this->search);

        return PublicationType::query()
            ->withCount(['fields', 'publications'])
            ->when($term !== '', function ($query) use ($term) {
                $like = '%' . $term . '%';
                $query->where('type_name', 'ilike', $like);
            })
            ->orderByDesc('is_system')
            ->orderBy('type_name')
            ->paginate(10);
    }

    public function getCanCreateProperty(): bool
    {
        return Gate::allows('create', PublicationType::class);
    }

    public function render()
    {
        return view('publicaciones.tipos')->layout('layouts.app');
    }

    /**
     * El nombre no puede repetirse sin importar mayúsculas ni tildes ("Software" = "software" = "Sóftware").
     */
    private function uniqueNameRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $normalized = PublicationType::normalizeName((string) $value);

            $duplicate = PublicationType::query()
                ->when($this->editingId, fn ($q) => $q->where('type_id', '!=', $this->editingId))
                ->get(['type_id', 'type_name'])
                ->contains(fn (PublicationType $type) => PublicationType::normalizeName($type->type_name) === $normalized);

            if ($duplicate) {
                $fail('Ya existe un tipo con ese nombre.');
            }
        };
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->type_name = '';
        $this->resetValidation();
    }
}
