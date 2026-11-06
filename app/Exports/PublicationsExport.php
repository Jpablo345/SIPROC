<?php

namespace App\Exports;

use App\Exports\Sheets\ArticulosSheet;
use App\Exports\Sheets\LibrosSheet;
use App\Exports\Sheets\TiposConfigurablesSheet;
use App\Models\PublicationType;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PublicationsExport implements WithMultipleSheets
{
    protected $publications;

    /**
     * @param  PublicationType|null  $singleType  Tipo elegido en el filtro, si hay uno (con sus campos cargados).
     */
    public function __construct($publications, protected ?PublicationType $singleType = null)
    {
        $this->publications = $publications;
    }

    public function sheets(): array
    {
        return [
            new ArticulosSheet($this->publications),
            new LibrosSheet($this->publications),
            new TiposConfigurablesSheet($this->publications, $this->singleType),
        ];
    }
}
