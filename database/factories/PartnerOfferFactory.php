<?php

namespace Database\Factories;

use App\Models\PartnerOffer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PartnerOffer>
 */
class PartnerOfferFactory extends Factory
{
    protected $model = PartnerOffer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'logo' => null,
            'offer_text' => fake()->sentence(6),
            'description' => fake()->optional()->paragraph(),
            'external_url' => fake()->url(),
            'internal_url' => null,
            'discount_code_id' => null,
            'type' => fake()->randomElement(['sportsbook', 'prediction', 'casino']),
            'badge_text' => fake()->optional(0.3)->randomElement(['New', 'Featured', 'Hot']),
            'show_on_picks' => fake()->boolean(70),
            'show_on_offers_page' => fake()->boolean(80),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
            'click_count' => fake()->numberBetween(0, 1000),
        ];
    }

    /**
     * Indicate that the offer is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the offer is a sportsbook type.
     */
    public function sportsbook(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'sportsbook',
        ]);
    }

    /**
     * Indicate that the offer is a prediction type.
     */
    public function prediction(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'prediction',
        ]);
    }

    /**
     * Indicate that the offer is a casino type.
     */
    public function casino(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'casino',
        ]);
    }

    /**
     * Indicate that the offer should show on picks page.
     */
    public function forPicks(): static
    {
        return $this->state(fn (array $attributes) => [
            'show_on_picks' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the offer should show on offers page.
     */
    public function forOffersPage(): static
    {
        return $this->state(fn (array $attributes) => [
            'show_on_offers_page' => true,
            'is_active' => true,
        ]);
    }
}
