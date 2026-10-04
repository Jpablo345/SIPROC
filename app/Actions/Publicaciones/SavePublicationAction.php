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
    ): void {
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
                Publication::where('publication_id', $publicationId)->update($publicationData);
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
