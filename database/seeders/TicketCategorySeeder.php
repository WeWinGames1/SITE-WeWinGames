<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TicketCategory;

class TicketCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Billing & Subscriptions', 'description' => 'Issues related to payments, billing, and subscription management'],
            ['name' => 'Technical Support', 'description' => 'Technical issues with the website or app'],
            ['name' => 'Account Access', 'description' => 'Login problems, password resets, and account recovery'],
            ['name' => 'Betting Picks', 'description' => 'Questions about betting picks and predictions'],
            ['name' => 'Feature Request', 'description' => 'Suggestions for new features or improvements'],
            ['name' => 'General Inquiry', 'description' => 'General questions and other issues'],
        ];

        foreach ($categories as $category) {
            TicketCategory::firstOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description']]
            );
        }
    }
}