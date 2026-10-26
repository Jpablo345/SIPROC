<?php

namespace Database\Factories;

use App\Models\Researcher;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResearcherFactory extends Factory
{
    protected $model = Researcher::class;

    public function definition(): array
    {
        // researcher_id es autoincremental; el número de identificación va en document.
        $document = str_pad((string) $this->faker->unique()->numberBetween(1, 9999999), 7, '0', STR_PAD_LEFT);

        return [
            'document' => $document,
            'name_1' => $this->faker->firstName(),
            'name_2' => $this->faker->optional()->firstName(),
            'last_name_1' => $this->faker->lastName(),
            'last_name_2' => $this->faker->optional()->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->numerify('3#########'),
            'cod_minciencias' => null,
        ];
    }
}
