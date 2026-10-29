<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Publicaciones</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 10px;
            color: #2b2323;
            margin: 15px;
            line-height: 1.4;
        }

        .header {
            color: #9c1c1c;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
            border-bottom: 2px solid #9c1c1c;
            padding-bottom: 8px;
        }

        .subtitle {
            font-size: 9px;
            color: #666;
            margin-bottom: 15px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #9c1c1c;
            margin-top: 20px;
            margin-bottom: 10px;
            padding-left: 5px;
            border-left: 4px solid #9c1c1c;
        }

        .section-stats {
            font-size: 8px;
            color: #666;
            margin-bottom: 8px;
            font-style: italic;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 20px;
            font-size: 8px;
        }

        th {
            background-color: #fff7f7;
            color: #7a1515;
            font-weight: bold;
            border: 1px solid #f0dede;
            padding: 6px 4px;
            text-align: left;
            font-size: 7px;
            text-transform: uppercase;
        }

        td {
            border: 1px solid #f0dede;
            padding: 5px 4px;
            vertical-align: top;
        }

        .extra-info {
            font-size: 7px;
            color: #666;
            margin-top: 3px;
        }

        .footer {
            margin-top: 20px;
            font-size: 7px;
            color: #999;
            text-align: center;
            border-top: 1px solid #f0dede;
            padding-top: 8px;
        }
    </style>
</head>

<body>
    <div class="header">
        SIPROC - Producción Científica
    </div>
    <div class="subtitle">
        Reporte generado el {{ now()->format('d/m/Y \a \l\a\s H:i:s') }} |
        Total de publicaciones: {{ $publications->count() }} |
        Artículos: {{ $publications->filter(fn($p) => $p->article)->count() }} |
        Libros: {{ $publications->filter(fn($p) => $p->book)->count() }}
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
    TABLA DE ARTÍCULOS CIENTÍFICOS
    ═══════════════════════════════════════════════════════════════════ --}}
    @php
        $articles = $publications->filter(fn($p) => $p->article !== null);
        $books = $publications->filter(fn($p) => $p->book !== null);
    @endphp

    @if($articles->count() > 0)
        <div class="section-title">ARTÍCULOS CIENTÍFICOS</div>
        <div class="section-stats">Total: {{ $articles->count() }} artículos</div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 4%">#</th>
                        <th style="width: 30%">Título</th>
                        <th style="width: 6%">Año</th>
                        <th style="width: 15%">Revista</th>
                        <th style="width: 8%">ISSN</th>
                        <th style="width: 8%">Categoría</th>
                        <th style="width: 10%">DOI</th>
                        <th style="width: 8%">Ámbito</th>
                        <th style="width: 15%">Autores</th>
                        <th style="width: 8%">País</th>
                        <th style="width: 10%">Grupo Inv.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($articles as $index => $publication)
                        @php
                            $authors = $publication->researchers->sortBy(fn($r) => $r->pivot->author_order ?? 999);
                            $authorsList = $authors->map(fn($r) => trim(implode(' ', array_filter([
                                $r->name_1,
                                $r->name_2,
                                $r->last_name_1,
                                $r->last_name_2
                            ]))))->filter()->implode(', ');

                            $groups = $authors->map(fn($r) => $r->researchGroup?->group_name)->filter()->unique()->implode(', ');

                            $journalName = $publication->article->journal?->journal_name ?? '—';
                            $issn = $publication->article->journal_issn ?? '—';
                            $category = $publication->article->journal?->category ?? '—';
                            $doi = $publication->article->doi ?? '—';
                        @endphp
                        <tr>
                            <td style="text-align: center">{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ Str::limit($publication->title, 55) }}</strong>
                                @if($publication->url)
                                    <div class="extra-info">🔗 {{ Str::limit($publication->url, 35) }}</div>
                                @endif
                            </td>
                            <td style="text-align: center">{{ $publication->publication_year ?? '—' }}</td>
                            <td>{{ Str::limit($journalName, 30) }}</td>
                            <td>{{ $issn }}</td>
                            <td style="text-align: center">{{ $category }}</td>
                            <td>{{ $doi !== '—' ? $doi : '—' }}</td>
                            <td style="text-align: center">{{ $publication->scope ?? '—' }}</td>
                            <td>{{ Str::limit($authorsList ?: '—', 45) }}</td>
                            <td>{{ $publication->country_publication ?? '—' }}</td>
                            <td>{{ Str::limit($groups ?: '—', 25) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════
    TABLA DE LIBROS DE INVESTIGACIÓN
    ═══════════════════════════════════════════════════════════════════ --}}
    @if($books->count() > 0)
        <div class="section-title">LIBROS DE INVESTIGACIÓN</div>
        <div class="section-stats">Total: {{ $books->count() }} libros</div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 4%">#</th>
                        <th style="width: 28%">Título</th>
                        <th style="width: 6%">Año</th>
                        <th style="width: 10%">ISBN</th>
                        <th style="width: 12%">Editorial</th>
                        <th style="width: 8%">Medio Difusión</th>
                        <th style="width: 10%">Tipo de Libro</th>
                        <th style="width: 8%">Ámbito</th>
                        <th style="width: 14%">Autores</th>
                        <th style="width: 8%">País</th>
                        <th style="width: 10%">Grupo Inv.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($books as $index => $publication)
                        @php
                            $authors = $publication->researchers->sortBy(fn($r) => $r->pivot->author_order ?? 999);
                            $authorsList = $authors->map(fn($r) => trim(implode(' ', array_filter([
                                $r->name_1,
                                $r->name_2,
                                $r->last_name_1,
                                $r->last_name_2
                            ]))))->filter()->implode(', ');

                            $groups = $authors->map(fn($r) => $r->researchGroup?->group_name)->filter()->unique()->implode(', ');

                            $isbn = $publication->book->book_isbn ?? '—';
                            $editorial = $publication->book->editorial ?? '—';
                            $meansOfDissemination = $publication->book->means_of_dissemination ?? '—';
                            $bookType = $publication->book->bookType?->type_name ?? '—';
                        @endphp
                        <tr>
                            <td style="text-align: center">{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ Str::limit($publication->title, 50) }}</strong>
                                @if($publication->url)
                                    <div class="extra-info">🔗 {{ Str::limit($publication->url, 35) }}</div>
                                @endif
                            </td>
                            <td style="text-align: center">{{ $publication->publication_year ?? '—' }}</td>
                            <td>{{ $isbn }}</td>
                            <td>{{ Str::limit($editorial, 25) }}</td>
                            <td style="text-align: center">{{ $meansOfDissemination }}</td>
                            <td>{{ $bookType }}</td>
                            <td style="text-align: center">{{ $publication->scope ?? '—' }}</td>
                            <td>{{ Str::limit($authorsList ?: '—', 45) }}</td>
                            <td>{{ $publication->country_publication ?? '—' }}</td>
                            <td>{{ Str::limit($groups ?: '—', 25) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($publications->isEmpty())
        <div style="text-align: center; padding: 40px; color: #999;">
            No hay publicaciones registradas.
        </div>
    @endif

    <div class="footer">
        Reporte generado automáticamente por SIPROC - Sistema de Visualización de Investigaciones de Sistemas<br>
        Universidad Francisco de Paula Santander - Ocaña
    </div>
</body>

</html>