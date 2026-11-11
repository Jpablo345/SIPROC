<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Book;
use App\Models\Journal;
use App\Models\Publication;
use App\Models\PublicationType;
use App\Models\Researcher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Guardado desde las pantallas de Tipología (artículos y libros): crear, editar y autores.
 */
class TipologiaFormsTest extends TestCase
{
    use RefreshDatabase;

    private PublicationType $articleType;

    private PublicationType $bookType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->teacher()->withRole('Docente')->create());

        $this->articleType = PublicationType::create(['type_name' => 'Articulo']);
        $this->bookType = PublicationType::create(['type_name' => 'Libro']);
    }

    private function authors(Researcher ...$researchers): array
    {
        return array_map(fn (Researcher $r) => $r->toOption(), $researchers);
    }

    public function test_creates_an_article_with_journal_and_ordered_authors(): void
    {
        $journal = Journal::factory()->create();
        [$first, $second] = Researcher::factory()->count(2)->create()->all();

        Volt::test('tipologia.generacion.articulos-cientificos')
            ->set('title', '  Redes neuronales en Ocaña  ')
            ->set('publication_year', '2025')
            ->set('scope', 'Nacional')
            ->set('journal_issn', $journal->journal_issn)
            ->set('doi', ' 10.1234/abc ')
            ->set('selectedAuthors', $this->authors($second, $first))
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('editingId', null)
            ->assertSet('title', '');

        $publication = Publication::firstOrFail();
        $this->assertSame('Redes neuronales en Ocaña', $publication->title);
        $this->assertSame($this->articleType->type_id, $publication->type_id);

        $article = Article::findOrFail($publication->publication_id);
        $this->assertSame($journal->journal_issn, $article->journal_issn);
        $this->assertSame('10.1234/abc', $article->doi);

        $this->assertSame(
            [$second->researcher_id, $first->researcher_id],
            $publication->researchers()->orderBy('author_order')->pluck('researcher.researcher_id')->all(),
        );
    }

    public function test_edits_an_article_and_replaces_its_authors(): void
    {
        $journal = Journal::factory()->create();
        [$first, $second] = Researcher::factory()->count(2)->create()->all();

        $component = Volt::test('tipologia.generacion.articulos-cientificos')
            ->set('title', 'Versión inicial')
            ->set('journal_issn', $journal->journal_issn)
            ->set('selectedAuthors', $this->authors($first))
            ->call('save')
            ->assertHasNoErrors();

        $id = Publication::value('publication_id');

        $component->call('edit', $id)
            ->assertSet('editingId', $id)
            ->set('title', 'Versión corregida')
            ->set('selectedAuthors', $this->authors($second))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Publication::count());
        $this->assertSame(1, Article::count());
        $this->assertSame('Versión corregida', Publication::find($id)->title);
        $this->assertSame([$second->researcher_id], Publication::find($id)->researchers()->pluck('researcher.researcher_id')->all());
    }

    public function test_article_requires_a_journal(): void
    {
        Volt::test('tipologia.generacion.articulos-cientificos')
            ->set('title', 'Sin revista')
            ->call('save')
            ->assertHasErrors('journal_issn');

        $this->assertSame(0, Publication::count());
    }

    public function test_journal_search_finds_by_name_and_selects(): void
    {
        $journal = Journal::factory()->create(['journal_name' => 'Revista Ingenio UFPSO']);

        Volt::test('tipologia.generacion.articulos-cientificos')
            ->set('journalSearch', 'ingenio')
            ->assertSet('journalResults', [['journal_issn' => $journal->journal_issn, 'journal_name' => 'Revista Ingenio UFPSO']])
            ->call('seleccionarRevista', $journal->journal_issn)
            ->assertSet('journal_issn', $journal->journal_issn)
            ->assertSet('journalResults', []);
    }

    public function test_creates_and_edits_a_book(): void
    {
        $author = Researcher::factory()->create();

        $component = Volt::test('tipologia.generacion.libros-investigacion')
            ->set('title', 'Historia de la ingeniería')
            ->set('book_isbn', '978-958-00-0000-1')
            ->set('editorial', 'Editorial UFPSO')
            ->set('selectedAuthors', $this->authors($author))
            ->call('save')
            ->assertHasNoErrors();

        $publication = Publication::firstOrFail();
        $this->assertSame($this->bookType->type_id, $publication->type_id);
        $this->assertSame('Editorial UFPSO', Book::findOrFail($publication->publication_id)->editorial);
        $this->assertSame([$author->researcher_id], $publication->researchers()->pluck('researcher.researcher_id')->all());

        $component->call('edit', $publication->publication_id)
            ->set('editorial', 'Sello Editorial UFPSO')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Book::count());
        $this->assertSame('Sello Editorial UFPSO', Book::find($publication->publication_id)->editorial);
    }

    public function test_book_isbn_must_be_unique(): void
    {
        Volt::test('tipologia.generacion.libros-investigacion')
            ->set('title', 'Primero')
            ->set('book_isbn', '978-1')
            ->call('save')
            ->assertHasNoErrors();

        Volt::test('tipologia.generacion.libros-investigacion')
            ->set('title', 'Segundo')
            ->set('book_isbn', '978-1')
            ->call('save')
            ->assertHasErrors('book_isbn');

        $this->assertSame(1, Book::count());
    }
}
