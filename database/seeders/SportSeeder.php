<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Seeder;

class SportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Sport::create([
            'name' => 'Soccer',
        ]);
        Sport::create([
            'name' => 'Basketball',
        ]);
        Sport::create([
            'name' => 'Baseball',
        ]);
        Sport::create([
            'name' => 'Football',
        ]);
        Sport::create([
            'name' => 'Hockey',
        ]);
        Sport::create([
            'name' => 'Golf',
        ]);
    }
}
