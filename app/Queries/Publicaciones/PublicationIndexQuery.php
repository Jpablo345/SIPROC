<?php

namespace App\Queries\Publicaciones;

use App\Models\Publication;
use Illuminate\Database\Eloquent\Builder;

class PublicationIndexQuery
{
    /**
     * Busca el texto en los valores (no en las claves) de los campos dinámicos.
     */
    public const EXTRA_VALUES_LIKE = "EXISTS (SELECT 1 FROM jsonb_each_text(COALESCE(publication.extra_attributes, '{}'::jsonb)) AS extra(key, value) WHERE LOWER(extra.value) LIKE ?)";

    public function build(array $filters)
    {
        $term = trim($filters['search'] ?? '');
        $year = trim($filters['year'] ?? '');
        $type = trim($filters['type'] ?? '');
        $group = trim($filters['group'] ?? '');
        $author = trim($filters['author'] ?? '');

        return Publication::query()
            ->with([
                'type.fields',
                'researchers.researchGroup.institution',
                'article.journal',
                'book.bookType',
            ])
            ->when($term !== '', function (Builder $query) use ($term): void {
                $like = '%' . strtolower($term) . '%';

                $query->where(function (Builder $inner) use ($like): void {
                    $inner->whereRaw('LOWER(title) LIKE ?', [$like])
                        ->orWhereRaw('CAST(publication_year AS TEXT) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(scope) LIKE ?', [$like])
                        ->orWhereHas('type', function (Builder $typeQuery) use ($like): void {
                            $typeQuery->whereRaw('LOWER(type_name) LIKE ?', [$like]);
                        })
                        ->orWhereHas('researchers', function (Builder $researcherQuery) use ($like): void {
                            $researcherQuery->whereRaw('LOWER(name_1) LIKE ?', [$like])
                                ->orWhereRaw('LOWER(last_name_1) LIKE ?', [$like]);
                        })
                        ->orWhereRaw(self::EXTRA_VALUES_LIKE, [$like]);
                });
            })
            ->when($year !== '', fn (Builder $query) => $query->where('publication_year', $year))
            ->when($type !== '', fn (Builder $query) => $query->where('type_id', $type))
            ->when($group !== '', function (Builder $query) use ($group): void {
                $query->whereHas('researchers.researchGroup', function (Builder $groupQuery) use ($group): void {
                    $groupQuery->where('cod_minciencias', $group);
                });
            })
            ->when($author !== '', function (Builder $query) use ($author): void {
                $like = '%' . strtolower($author) . '%';

                $query->whereHas('researchers', function (Builder $authorQuery) use ($like): void {
                    $authorQuery->whereRaw('LOWER(name_1) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(name_2) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(last_name_1) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(last_name_2) LIKE ?', [$like]);
                });
            })
            ->orderByDesc('publication_id');
    }
}
