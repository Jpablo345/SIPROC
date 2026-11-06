<?php

namespace App\Actions\Publicaciones;

use App\Models\Article;
use App\Models\Book;
use App\Models\Publication;
use App\Models\ResearcherPublication;
use Illuminate\Support\Facades\DB;

class SavePublicationAction
{
    public function execute(
        array $publicationData,
        array $articleData,
        array $bookData,
        array $selectedAuthors,
        ?int $publicationId,
        bool $isArticle,
        bool $isBook,
        ?array $extraAttributes = null,
    ): void {
        // Los campos dinámicos solo aplican a tipos configurables; Articulo y Libro usan sus tablas.
        $publicationData['extra_attributes'] = ($isArticle || $isBook) ? null : $extraAttributes;

        DB::transaction(function () use (
            $publicationData,
            $articleData,
            $bookData,
            $selectedAuthors,
            $publicationId,
            $isArticle,
            $isBook,
        ): void {
            if ($publicationId) {
                // update() del modelo (no del query builder) para aplicar el cast de extra_attributes.
                Publication::findOrFail($publicationId)->update($publicationData);
            } else {
                $publicationId = Publication::create($publicationData)->publication_id;
            }

            if ($isArticle) {
                Book::where('publication_id', $publicationId)->delete();
                Article::updateOrCreate(
                    ['publication_id' => $publicationId],
                    $articleData,
                );
            } elseif ($isBook) {
                Article::where('publication_id', $publicationId)->delete();
                Book::updateOrCreate(
                    ['publication_id' => $publicationId],
                    $bookData,
                );
            } else {
                Article::where('publication_id', $publicationId)->delete();
                Book::where('publication_id', $publicationId)->delete();
            }

            ResearcherPublication::where('publication_id', $publicationId)->delete();

            foreach ($selectedAuthors as $index => $author) {
                ResearcherPublication::create([
                    'publication_id' => $publicationId,
                    'researcher_id' => $author['researcher_id'],
                    'author_order' => $index + 1,
                ]);
            }
        });
    }
}
