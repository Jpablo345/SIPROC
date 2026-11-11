<?php

namespace App\Livewire\Concerns;

use App\Models\Journal;

/**
 * Buscador de revistas por nombre o ISSN para los formularios de artículo.
 *
 * El componente que lo use debe declarar:
 *   public ?string $journal_issn = null;
 *   public string $journalSearch = '';
 *   public array $journalResults = [];
 */
trait SearchesJournals
{
    public function updatedJournalSearch(): void
    {
        $this->buscarRevista();
    }

    public function buscarRevista(): void
    {
        $term = trim($this->journalSearch);

        if ($term === '') {
            $this->journalResults = [];

            return;
        }

        $like = '%'.$term.'%';

        $this->journalResults = Journal::query()
            ->where('journal_issn', 'ilike', $like)
            ->orWhere('journal_name', 'ilike', $like)
            ->orderBy('journal_name')
            ->limit(8)
            ->get(['journal_issn', 'journal_name'])
            ->map(fn (Journal $journal) => [
                'journal_issn' => $journal->journal_issn,
                'journal_name' => $journal->journal_name,
            ])
            ->toArray();
    }

    public function seleccionarRevista(string $journalIssn): void
    {
        $journal = Journal::find($journalIssn);

        if (! $journal) {
            return;
        }

        $this->journal_issn = $journal->journal_issn;
        $this->journalSearch = self::journalLabel($journal);
        $this->journalResults = [];
    }

    /** Texto que se muestra en el buscador para una revista elegida: "Nombre (ISSN)". */
    protected static function journalLabel(?Journal $journal, ?string $fallbackIssn = null): string
    {
        return $journal
            ? $journal->journal_name.' ('.$journal->journal_issn.')'
            : ($fallbackIssn ?? '');
    }
}
