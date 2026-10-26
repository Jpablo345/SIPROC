<?php

namespace App\Livewire\Publicaciones;

use App\Models\BookType;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class TiposLibro extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $editingId = null;
    public string $type_name = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(int $bookTypeId): void
    {
        $bookType = BookType::findOrFail($bookTypeId);
        $this->authorize('update', $bookType);

        $this->editingId = $bookType->book_type_id;
        $this->type_name = $bookType->type_name;
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        if ($this->editingId) {
            $this->authorize('update', BookType::findOrFail($this->editingId));
        } else {
            $this->authorize('create', BookType::class);
        }
        $this->type_name = trim($this->type_name);
        $this->validate([
            'type_name' => ['required', 'string', 'max:50', Rule::unique('book_type', 'type_name')->ignore($this->editingId, 'book_type_id')],
        ], [
            'type_name.required' => 'Completa el nombre del tipo de libro.',
            'type_name.max' => 'El nombre no puede superar 50 caracteres.',
            'type_name.unique' => 'Ya existe un tipo de libro con ese nombre.',
        ]);

        $data = ['type_name' => trim($this->type_name)];

        if ($this->editingId) {
            BookType::where('book_type_id', $this->editingId)->update($data);
            session()->flash('status', 'Tipo de libro actualizado.');
        } else {
            BookType::create($data);
            session()->flash('status', 'Tipo de libro creado.');
        }

        $this->resetForm();
    }

    public function delete(int $bookTypeId): void
    {
        $bookType = BookType::findOrFail($bookTypeId);
        $this->authorize('delete', $bookType);

        // book.book_type_id tiene FK restrict: borrar un tipo en uso falla en la BD.
        if ($bookType->books()->exists()) {
            session()->flash('status', 'No se puede eliminar: hay libros de este tipo.');
            return;
        }

        $bookType->delete();

        session()->flash('status', 'Tipo de libro eliminado.');
        $this->resetPage();
    }

    public function getBookTypesProperty()
    {
        $term = trim($this->search);

        return BookType::query()
            ->when($term !== '', function ($query) use ($term) {
                $like = '%' . $term . '%';
                $query->where('type_name', 'ilike', $like);
            })
            ->orderBy('type_name')
            ->paginate(10);
    }

    public function render()
    {
        return view('publicaciones.tipos-libro')->layout('layouts.app');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->type_name = '';
        $this->resetValidation();
    }
}