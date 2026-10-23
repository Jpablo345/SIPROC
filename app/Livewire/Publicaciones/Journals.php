<?php

namespace App\Livewire\Publicaciones;

use App\Models\Journal;
use Livewire\Component;
use Livewire\WithPagination;

class Journals extends Component
{
    use WithPagination;

    public string $search = '';
    public ?string $editingId = null; // ISSN es string
    public string $journal_issn = '';
    public string $journal_name = '';
    public string $category = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(string $issn): void
    {
        $journal = Journal::findOrFail($issn);
        $this->authorize('update', $journal);

        $this->editingId = $journal->journal_issn;
        $this->journal_issn = $journal->journal_issn;
        $this->journal_name = $journal->journal_name;
        $this->category = $journal->category ?? '';
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        if ($this->editingId) {
            $this->authorize('update', Journal::findOrFail($this->editingId));
        } else {
            $this->authorize('create', Journal::class);
        }
        $this->validate([
            'journal_issn' => 'required|string|max:20',
            'journal_name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
        ]);

        $data = [
            'journal_issn' => trim($this->journal_issn),
            'journal_name' => trim($this->journal_name),
            'category' => trim($this->category) ?: null,
        ];

        if ($this->editingId) {
            Journal::where('journal_issn', $this->editingId)->update([
                'journal_name' => $data['journal_name'],
                'category' => $data['category'],
            ]);
            session()->flash('status', 'Revista actualizada.');
        } else {
            Journal::create($data);
            session()->flash('status', 'Revista creada.');
        }

        $this->resetForm();
    }

    public function delete(string $issn): void
    {
        $journal = Journal::findOrFail($issn);
        $this->authorize('delete', $journal);
        $journal->delete();

        session()->flash('status', 'Revista eliminada.');
        $this->resetPage();
    }

    public function getJournalsProperty()
    {
        $term = trim($this->search);

        return Journal::query()
            ->when($term !== '', function ($query) use ($term) {
                $like = '%' . $term . '%';
                $query->where('journal_name', 'ilike', $like)
                    ->orWhere('journal_issn', 'ilike', $like);
            })
            ->orderBy('journal_name')
            ->paginate(10);
    }

    public function render()
    {
        return view('publicaciones.journals')->layout('layouts.app');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->journal_issn = '';
        $this->journal_name = '';
        $this->category = '';
        $this->resetValidation();
    }
}