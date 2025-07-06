<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobPosition;

class JobPositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            [
                'title' => 'Sales Representative',
                'description' => 'Join our sales team to help grow our customer base',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Regional Manager',
                'description' => 'Lead sales efforts in your region',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Business Development',
                'description' => 'Identify and develop new business opportunities',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'title' => 'Marketing',
                'description' => 'Help promote our brand and services',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'title' => 'Customer Service',
                'description' => 'Provide excellent support to our customers',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'title' => 'Other',
                'description' => 'Other positions not listed above',
                'is_active' => true,
                'sort_order' => 99,
            ],
        ];

        foreach ($positions as $position) {
            JobPosition::create($position);
        }
    }
}