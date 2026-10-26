<?php

namespace App\Livewire\Concerns;

/**
 * Reglas comunes de publicación y autores, alineadas con las columnas de
 * publication y researcher_publication. Las usan los formularios de
 * artículos, libros, otras publicaciones y FormularioPublicacion.
 */
trait ValidatesPublication
{
    protected function publicationRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'publication_year' => ['nullable', 'regex:/^\d{4}$/'],
            'scope' => ['nullable', 'in:Nacional,Internacional'],
            'country_publication' => ['nullable', 'string', 'max:100'],
            'url' => ['nullable', 'string', 'max:300', 'url:http,https'],
        ];
    }

    protected function authorRules(): array
    {
        return [
            'selectedAuthors' => ['array'],
            'selectedAuthors.*.researcher_id' => ['required', 'integer', 'distinct', 'exists:researcher,researcher_id'],
        ];
    }

    protected function publicationMessages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede superar 255 caracteres.',
            'publication_year.regex' => 'El año debe tener 4 dígitos.',
            'scope.in' => 'El ámbito debe ser Nacional o Internacional.',
            'country_publication.max' => 'El país no puede superar 100 caracteres.',
            'url.url' => 'La URL debe ser válida y empezar con http:// o https://.',
            'url.max' => 'La URL no puede superar 300 caracteres.',
            'selectedAuthors.*.researcher_id.distinct' => 'Hay autores repetidos.',
            'selectedAuthors.*.researcher_id.exists' => 'Uno de los autores ya no existe.',
        ];
    }
}
