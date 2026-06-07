<?php

namespace Database\Seeders;

use App\Models\PartnerOffer;
use Illuminate\Database\Seeder;

class PartnerOffersSeeder extends Seeder
{
    /**
     * Seed the initial partner offers.
     */
    public function run(): void
    {
        $offers = [
            [
                'name' => 'DraftKings',
                'slug' => 'draftkings',
                'logo' => '/images/partners/draftkings.svg',
                'offer_text' => 'Bet $5, Get $200 in Bonus Bets',
                'description' => 'New customers can bet $5 and get $200 in bonus bets instantly. One of the best welcome offers in sports betting.',
                'external_url' => 'https://sportsbook.draftkings.com/acq-bet-and-get',
                'internal_url' => null,
                'discount_code_id' => null,
                'type' => 'sportsbook',
                'badge_text' => 'Featured',
                'show_on_picks' => true,
                'show_on_offers_page' => true,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'FanDuel',
                'slug' => 'fanduel',
                'logo' => '/images/partners/fanduel.svg',
                'offer_text' => 'Bet $5, Get $150 in Bonus Bets',
                'description' => 'Place your first $5 bet and receive $150 in bonus bets. Americas #1 sportsbook.',
                'external_url' => 'https://wewingames.go.metabet.io/bet/fanduel',
                'internal_url' => null,
                'discount_code_id' => null,
                'type' => 'sportsbook',
                'badge_text' => null,
                'show_on_picks' => true,
                'show_on_offers_page' => true,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Fanatics',
                'slug' => 'fanatics',
                'logo' => '/images/partners/fanatics.svg',
                'offer_text' => 'Get Up to $1,000 in No Sweat Bets',
                'description' => 'New users get up to $1,000 back in bonus bets if your first bet loses. Plus earn FanCash rewards.',
                'external_url' => 'https://wewingames.go.metabet.io/bet/fanatics',
                'internal_url' => null,
                'discount_code_id' => null,
                'type' => 'sportsbook',
                'badge_text' => 'New',
                'show_on_picks' => true,
                'show_on_offers_page' => true,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'ReBet',
                'slug' => 'rebet',
                'logo' => '/images/partners/rebet.svg',
                'offer_text' => '100% Deposit Match up to $250',
                'description' => 'Double your first deposit with a 100% match bonus up to $250. Fast payouts and great odds.',
                'external_url' => 'https://wewingames.go.metabet.io/bet/rebet',
                'internal_url' => null,
                'discount_code_id' => null,
                'type' => 'sportsbook',
                'badge_text' => null,
                'show_on_picks' => false,
                'show_on_offers_page' => true,
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Polymarket',
                'slug' => 'polymarket',
                'logo' => '/images/partners/polymarket.svg',
                'offer_text' => 'Bet on Real World Events',
                'description' => 'The worlds largest prediction market. Trade on politics, sports, crypto, and more.',
                'external_url' => 'https://polymarket-app.onelink.me/S8ac/WWG',
                'internal_url' => null,
                'discount_code_id' => null,
                'type' => 'prediction',
                'badge_text' => null,
                'show_on_picks' => false,
                'show_on_offers_page' => true,
                'sort_order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($offers as $offer) {
            PartnerOffer::updateOrCreate(
                ['slug' => $offer['slug']],
                $offer
            );
        }

        $this->command->info('Seeded '.count($offers).' partner offers.');
    }
}
