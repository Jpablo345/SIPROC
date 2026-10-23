<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        if ((bool) env('SEED_PER_MODEL', false)) {
            $this->call([
                InstitutionSeeder::class,
                PublicationTypeSeeder::class,
                JournalSeeder::class,
                BookTypeSeeder::class,
                ResearchGroupSeeder::class,
                ResearcherSeeder::class,
                PublicationSeeder::class,
                ArticleSeeder::class,
                BookSeeder::class,
                EventSeeder::class,
                ResearcherPublicationSeeder::class,
                ResearcherEventSeeder::class,
                AudiResearcherSeeder::class,
                AudiPublicationSeeder::class,
            ]);
        }

        if ((bool) env('SEED_TEST_USER', false)) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }
    }
}
