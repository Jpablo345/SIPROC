<?php

namespace App\Livewire\Fichas;

use App\Models\Publication;
use Livewire\Component;

/** Ficha de solo lectura de una publicación: datos, autores, revista o libro y campos del tipo. */
class FichaPublicacion extends Component
{
    public Publication $publication;

    public function mount(Publication $publication): void
    {
        $this->publication = $publication->load([
            'type.fields',
            'researchers.researchGroup.institution',
            'article.journal',
            'book.bookType',
            'creator:id,name',
        ]);
    }

    public function render()
    {
        $autores = $this->publication->researchers->sortBy(fn ($r) => $r->pivot->author_order ?? 999)->values();

        return view('livewire.fichas.publicacion', [
            'autores' => $autores,
            'grupos' => $autores->pluck('researchGroup')->filter()->unique('cod_minciencias')->values(),
        ])->layout('layouts.app');
    }
}
