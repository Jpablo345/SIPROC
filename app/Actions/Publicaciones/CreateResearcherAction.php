<?php

namespace App\Actions\Publicaciones;

use App\Models\Institution;
use App\Models\ResearchGroup;
use App\Models\Researcher;
use Illuminate\Support\Facades\DB;

class CreateResearcherAction
{
    /**
     * Crea un investigador y, si se pide, su grupo nuevo (con institución nueva o existente).
     *
     * @param  array<string, mixed>  $data  Campos de App\Livewire\Forms\ResearcherForm, ya validados.
     */
    public function execute(array $data): Researcher
    {
        return DB::transaction(function () use ($data): Researcher {
            $groupCode = $data['cod_minciencias'] ?? null;

            if (! empty($data['create_group'])) {
                $institutionId = $data['institution_id'] ?? null;

                if (! empty($data['create_institution'])) {
                    $institutionId = Institution::create([
                        'institution_name' => trim($data['institution_name']),
                        'institution_type' => $this->clean($data['institution_type'] ?? null),
                        'country' => $this->clean($data['institution_country'] ?? null),
                        'city' => $this->clean($data['institution_city'] ?? null),
                        'website' => $this->clean($data['institution_website'] ?? null),
                    ])->institution_id;
                }

                $groupCode = ResearchGroup::create([
                    'cod_minciencias' => trim($data['group_code']),
                    'group_name' => trim($data['group_name']),
                    'group_classification' => $this->clean($data['group_classification'] ?? null),
                    'institution_id' => $institutionId,
                ])->cod_minciencias;
            }

            return Researcher::create([
                'document' => $this->clean($data['document'] ?? null),
                'name_1' => trim($data['name_1']),
                'name_2' => $this->clean($data['name_2'] ?? null),
                'last_name_1' => trim($data['last_name_1']),
                'last_name_2' => $this->clean($data['last_name_2'] ?? null),
                'cod_minciencias' => $groupCode,
            ]);
        });
    }

    private function clean(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
