<?php

namespace App\Actions\Publicaciones;

use App\Models\Institution;
use App\Models\ResearchGroup;
use App\Models\Researcher;
use Illuminate\Support\Facades\DB;

class CreateResearcherAction
{
    public function execute(array $data): Researcher
    {
        return DB::transaction(function () use ($data): Researcher {
            $institutionId = $data['modal_institution_id'] ?? null;

            if ($data['modal_create_institution']) {
                $institution = Institution::create([
                    'institution_name' => trim($data['modal_institution_name']),
                    'institution_type' => $data['modal_institution_type'],
                    'country' => $data['modal_institution_country'],
                    'city' => $data['modal_institution_city'],
                    'website' => $data['modal_institution_website'],
                ]);

                $institutionId = $institution->institution_id;
            }

            $groupCode = $data['modal_cod_minciencias'] ?? null;

            if ($data['modal_create_group']) {
                $group = ResearchGroup::create([
                    'cod_minciencias' => trim($data['modal_group_code']),
                    'group_name' => trim($data['modal_group_name']),
                    'group_classification' => $data['modal_group_classification'],
                    'institution_id' => $institutionId,
                ]);

                $groupCode = $group->cod_minciencias;
            }

            return Researcher::create([
                'document' => !empty($data['modal_document'])
                    ? trim($data['modal_document'])
                    : null,
                'name_1' => trim($data['modal_name_1']),
                'last_name_1' => trim($data['modal_last_name_1']),
                'cod_minciencias' => $groupCode,
            ]);
        });
    }
}
