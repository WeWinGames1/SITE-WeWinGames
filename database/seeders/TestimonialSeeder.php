<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $testimonials = [
            [
                'name' => 'Michael Thompson',
                'title' => 'Sports Enthusiast',
                'stars' => 5,
                'review' => 'WeWinGames has completely transformed my betting strategy. Their picks are incredibly accurate and the detailed analysis helps me understand the reasoning behind each recommendation. I\'ve seen consistent profits since subscribing!',
                'review_date' => '2024-11-15',
                'published' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Sarah Chen',
                'title' => 'Professional Bettor',
                'stars' => 5,
                'review' => 'The value you get with WeWinGames is unmatched. The daily picks across multiple sports have helped diversify my betting portfolio. Their customer support is also top-notch - always responsive and helpful.',
                'review_date' => '2024-10-22',
                'published' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'David Rodriguez',
                'title' => null,
                'stars' => 4,
                'review' => 'Great service with consistent results. The platinum picks especially have been money makers. Only reason for 4 stars instead of 5 is I\'d like to see more UFC coverage, but overall very satisfied.',
                'review_date' => '2024-09-08',
                'published' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Jennifer Williams',
                'title' => 'Weekend Bettor',
                'stars' => 5,
                'review' => 'As someone who only bets on weekends, the weekly subscription is perfect for me. The picks are well-researched and the success rate is impressive. Already recommended to several friends!',
                'review_date' => '2024-08-30',
                'published' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Robert Kim',
                'title' => 'Golf Betting Specialist',
                'stars' => 5,
                'review' => 'Their golf picks are absolutely phenomenal! Hit multiple tournament winners this year. The ROI on golf alone has paid for my annual subscription many times over.',
                'review_date' => '2024-07-12',
                'published' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Lisa Anderson',
                'title' => null,
                'stars' => 5,
                'review' => 'Skeptical at first but the free picks convinced me to subscribe. Best decision ever! The transparency with their track record gives me confidence, and the results speak for themselves.',
                'review_date' => '2024-06-18',
                'published' => true,
                'sort_order' => 6,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::create($testimonial);
        }
    }
}
