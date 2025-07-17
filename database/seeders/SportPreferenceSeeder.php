<?php

namespace Database\Seeders;

use App\Models\SportPreference;
use Illuminate\Database\Seeder;

class SportPreferenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing preferences
        SportPreference::truncate();

        // Default sport preferences in order of priority
        $preferences = [
            ['sport_name' => 'Football', 'priority' => 0, 'is_active' => true],
            ['sport_name' => 'Basketball', 'priority' => 1, 'is_active' => true],
            ['sport_name' => 'Baseball', 'priority' => 2, 'is_active' => true],
            ['sport_name' => 'Hockey', 'priority' => 3, 'is_active' => true],
            ['sport_name' => 'Soccer', 'priority' => 4, 'is_active' => true],
            ['sport_name' => 'Golf', 'priority' => 5, 'is_active' => true],
            ['sport_name' => 'UFC', 'priority' => 6, 'is_active' => true],
            ['sport_name' => 'Ultimate Fighting Championship', 'priority' => 7, 'is_active' => true],
            ['sport_name' => 'Tennis', 'priority' => 8, 'is_active' => true],
            ['sport_name' => 'Boxing', 'priority' => 9, 'is_active' => true],
        ];

        foreach ($preferences as $preference) {
            SportPreference::create($preference);
        }

        $this->command->info('Sport preferences seeded successfully!');
    }
}