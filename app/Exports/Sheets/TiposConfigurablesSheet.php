<?php

namespace App\Exports\Sheets;

use App\Models\PublicationType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Publicaciones de tipos configurables (ni artículo ni libro) con sus campos dinámicos.
 * Si el filtro deja un solo tipo, cada campo va en su propia columna;
 * si hay varios, todos van juntos en la columna "Detalles".
 */
class TiposConfigurablesSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    private const BASE_HEADINGS = ['N°', 'TÍTULO', 'TIPO', 'AÑO', 'ÁMBITO', 'AUTORES', 'GRUPO DE INVESTIGACIÓN', 'PAÍS', 'ENLACE'];

    protected $publications;
    private int $rowNumber = 0;

    /**
     * @param  PublicationType|null  $singleType  Tipo elegido en el filtro, con sus campos cargados.
     */
    public function __construct($publications, private ?PublicationType $singleType = null)
    {
        $this->publications = $publications
            ->filter(fn ($p) => $p->article === null && $p->book === null && $p->type && ! $p->type->is_system)
            ->values();

        if ($this->singleType?->is_system) {
            $this->singleType = null;
        }
    }

    public function title(): string
    {
        // Excel limita el nombre de la hoja a 31 caracteres.
        return $this->singleType ? mb_substr($this->singleType->type_name, 0, 31) : 'Tipos configurables';
    }

    public function collection()
    {
        return $this->publications;
    }

    public function headings(): array
    {
        $extra = $this->singleType
            ? $this->singleType->fields->map(fn ($field) => mb_strtoupper($field->label))->all()
            : ['DETALLES'];

        return [...self::BASE_HEADINGS, ...$extra];
    }

    public function map($publication): array
    {
        $this->rowNumber++;

        $authors = $publication->researchers
            ->sortBy(fn ($r) => $r->pivot->author_order ?? 999)
            ->pluck('full_name')
            ->filter()
            ->implode(' - ');

        $groups = $publication->researchers->pluck('researchGroup.group_name')->filter()->unique()->implode(', ');

        $row = [
            $this->rowNumber,
            $publication->title,
            $publication->type->type_name,
            $publication->publication_year ?? '—',
            $publication->scope ?? '—',
            $authors ?: '—',
            $groups ?: '—',
            $publication->country_publication ?? '—',
            $publication->url ?? '—',
        ];

        if ($this->singleType) {
            $values = $publication->extra_attributes ?? [];
            foreach ($this->singleType->fields as $field) {
                $row[] = $field->display($values[$field->key] ?? null) ?? '—';
            }
        } else {
            $row[] = $publication->extra_details_text ?: '—';
        }

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        $columns = count($this->headings());
        $lastCol = Coordinate::stringFromColumnIndex($columns);
        $totalRows = $this->publications->count() + 1;

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'name' => 'Arial', 'size' => 10],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE6E6E6']],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        foreach (range(1, $columns) as $index) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setWidth(match ($index) {
                1 => 6,
                2 => 45,
                6, 7 => 30,
                default => 22,
            });
        }

        if ($totalRows > 1) {
            $sheet->getStyle("A2:{$lastCol}{$totalRows}")->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 10],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
        }

        $sheet->getStyle("A1:{$lastCol}{$totalRows}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
        ]);
    }
}
