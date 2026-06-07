<?php

namespace Tests\Feature;

use App\Models\DiscountCode;
use App\Models\PartnerOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartnerOfferTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role and admin user
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    // ============================================
    // Admin Index Tests
    // ============================================

    public function test_admin_can_view_partner_offers_index(): void
    {
        PartnerOffer::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.partner-offers.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/PartnerOffers/Index')
            ->has('offers', 3)
        );
    }

    public function test_non_admin_cannot_access_partner_offers_admin(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.partner-offers.index'));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_partner_offers_admin(): void
    {
        $response = $this->get(route('admin.partner-offers.index'));

        $response->assertRedirect(route('login'));
    }

    // ============================================
    // Admin Create Tests
    // ============================================

    public function test_admin_can_view_create_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.partner-offers.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/PartnerOffers/Create')
            ->has('discountCodes')
            ->has('types')
        );
    }

    public function test_admin_can_create_partner_offer(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.partner-offers.store'), [
            'name' => 'DraftKings',
            'offer_text' => 'Bet $5, Get $200',
            'external_url' => 'https://draftkings.com',
            'type' => 'sportsbook',
            'is_active' => true,
            'show_on_picks' => true,
            'show_on_offers_page' => true,
            'sort_order' => 0,
        ]);

        $response->assertRedirect(route('admin.partner-offers.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('partner_offers', [
            'name' => 'DraftKings',
            'slug' => 'draftkings',
            'type' => 'sportsbook',
        ]);
    }

    public function test_partner_offer_slug_is_auto_generated(): void
    {
        $this->actingAs($this->admin)->post(route('admin.partner-offers.store'), [
            'name' => 'Test Offer Name',
            'offer_text' => 'Great offer',
            'external_url' => 'https://example.com',
            'type' => 'sportsbook',
        ]);

        $this->assertDatabaseHas('partner_offers', [
            'name' => 'Test Offer Name',
            'slug' => 'test-offer-name',
        ]);
    }

    public function test_duplicate_slugs_get_counter_suffix(): void
    {
        PartnerOffer::factory()->create(['name' => 'Test', 'slug' => 'test']);

        $this->actingAs($this->admin)->post(route('admin.partner-offers.store'), [
            'name' => 'Test',
            'offer_text' => 'Another test',
            'external_url' => 'https://example.com',
            'type' => 'sportsbook',
        ]);

        $this->assertDatabaseHas('partner_offers', ['slug' => 'test-1']);
    }

    public function test_create_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.partner-offers.store'), []);

        $response->assertSessionHasErrors(['name', 'offer_text', 'external_url', 'type']);
    }

    public function test_create_validates_url_format(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.partner-offers.store'), [
            'name' => 'Test',
            'offer_text' => 'Test offer',
            'external_url' => 'not-a-valid-url',
            'type' => 'sportsbook',
        ]);

        $response->assertSessionHasErrors('external_url');
    }

    public function test_create_validates_type_enum(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.partner-offers.store'), [
            'name' => 'Test',
            'offer_text' => 'Test offer',
            'external_url' => 'https://example.com',
            'type' => 'invalid_type',
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_create_can_link_discount_code(): void
    {
        $discountCode = DiscountCode::create([
            'code' => 'PARTNER10',
            'description' => 'Partner discount',
            'discount_type' => 'percentage',
            'discount_amount' => 10,
            'apply_to' => 'first_payment',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post(route('admin.partner-offers.store'), [
            'name' => 'Test Partner',
            'offer_text' => 'Get 10% off',
            'external_url' => 'https://example.com',
            'type' => 'sportsbook',
            'discount_code_id' => $discountCode->id,
        ]);

        $offer = PartnerOffer::first();
        $this->assertEquals($discountCode->id, $offer->discount_code_id);
    }

    // ============================================
    // Admin Edit/Update Tests
    // ============================================

    public function test_admin_can_view_edit_form(): void
    {
        $offer = PartnerOffer::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.partner-offers.edit', $offer));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/PartnerOffers/Edit')
            ->has('offer')
            ->has('discountCodes')
        );
    }

    public function test_admin_can_update_partner_offer(): void
    {
        $offer = PartnerOffer::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->admin)->put(route('admin.partner-offers.update', $offer), [
            'name' => 'New Name',
            'offer_text' => $offer->offer_text,
            'external_url' => $offer->external_url,
            'type' => $offer->type,
        ]);

        $response->assertRedirect(route('admin.partner-offers.index'));
        $this->assertDatabaseHas('partner_offers', ['id' => $offer->id, 'name' => 'New Name']);
    }

    public function test_update_generates_unique_slug_when_empty(): void
    {
        $existingOffer = PartnerOffer::factory()->create(['slug' => 'updated-name']);
        $offer = PartnerOffer::factory()->create(['name' => 'Original', 'slug' => 'original']);

        $this->actingAs($this->admin)->put(route('admin.partner-offers.update', $offer), [
            'name' => 'Updated Name',
            'slug' => '', // Empty slug triggers auto-generation
            'offer_text' => $offer->offer_text,
            'external_url' => $offer->external_url,
            'type' => $offer->type,
        ]);

        $offer->refresh();
        $this->assertEquals('updated-name-1', $offer->slug);
    }

    // ============================================
    // Admin Delete Tests
    // ============================================

    public function test_admin_can_delete_partner_offer(): void
    {
        $offer = PartnerOffer::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('admin.partner-offers.destroy', $offer));

        $response->assertRedirect(route('admin.partner-offers.index'));
        $this->assertDatabaseMissing('partner_offers', ['id' => $offer->id]);
    }

    public function test_deleting_offer_cascades_to_clicks(): void
    {
        $offer = PartnerOffer::factory()->create();
        $offer->clicks()->create([
            'ip_address' => '127.0.0.1',
            'source_page' => 'offers_page',
        ]);

        $this->assertDatabaseCount('partner_offer_clicks', 1);

        $this->actingAs($this->admin)->delete(route('admin.partner-offers.destroy', $offer));

        $this->assertDatabaseCount('partner_offer_clicks', 0);
    }

    // ============================================
    // Toggle Active Tests
    // ============================================

    public function test_admin_can_toggle_offer_active_status(): void
    {
        $offer = PartnerOffer::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)->post(route('admin.partner-offers.toggle', $offer));

        $response->assertRedirect();
        $this->assertDatabaseHas('partner_offers', ['id' => $offer->id, 'is_active' => false]);

        // Toggle back
        $this->actingAs($this->admin)->post(route('admin.partner-offers.toggle', $offer));
        $this->assertDatabaseHas('partner_offers', ['id' => $offer->id, 'is_active' => true]);
    }

    // ============================================
    // Update Order Tests
    // ============================================

    public function test_admin_can_update_sort_order(): void
    {
        $offer1 = PartnerOffer::factory()->create(['sort_order' => 0]);
        $offer2 = PartnerOffer::factory()->create(['sort_order' => 1]);

        $response = $this->actingAs($this->admin)->post(route('admin.partner-offers.update-order'), [
            'offers' => [
                ['id' => $offer1->id, 'sort_order' => 1],
                ['id' => $offer2->id, 'sort_order' => 0],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('partner_offers', ['id' => $offer1->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('partner_offers', ['id' => $offer2->id, 'sort_order' => 0]);
    }

    public function test_update_order_validates_offer_ids(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.partner-offers.update-order'), [
            'offers' => [
                ['id' => 99999, 'sort_order' => 0],
            ],
        ]);

        $response->assertSessionHasErrors('offers.0.id');
    }

    // ============================================
    // Click Tracking Tests
    // ============================================

    public function test_click_tracking_increments_count(): void
    {
        $offer = PartnerOffer::factory()->create(['click_count' => 5]);

        $response = $this->postJson(route('partner-offers.click', $offer), ['source' => 'offers_page']);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('partner_offers', ['id' => $offer->id, 'click_count' => 6]);
    }

    public function test_click_tracking_creates_detailed_log(): void
    {
        $offer = PartnerOffer::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('partner-offers.click', $offer), [
            'source' => 'picks_sidebar',
        ]);

        $this->assertDatabaseHas('partner_offer_clicks', [
            'partner_offer_id' => $offer->id,
            'user_id' => $user->id,
            'source_page' => 'picks_sidebar',
        ]);
    }

    public function test_click_tracking_validates_source_page(): void
    {
        $offer = PartnerOffer::factory()->create();

        $this->postJson(route('partner-offers.click', $offer), [
            'source' => 'malicious_source_page_value',
        ]);

        // Invalid source should be saved as 'unknown'
        $this->assertDatabaseHas('partner_offer_clicks', [
            'partner_offer_id' => $offer->id,
            'source_page' => 'unknown',
        ]);
    }

    public function test_click_tracking_works_for_guests(): void
    {
        $offer = PartnerOffer::factory()->create();

        $response = $this->postJson(route('partner-offers.click', $offer), [
            'source' => 'offers_page',
        ]);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('partner_offer_clicks', [
            'partner_offer_id' => $offer->id,
            'user_id' => null,
        ]);
    }

    // ============================================
    // Public Page Tests
    // ============================================

    public function test_partner_offers_page_shows_active_offers(): void
    {
        PartnerOffer::factory()->create([
            'name' => 'Active Offer',
            'is_active' => true,
            'show_on_offers_page' => true,
        ]);
        PartnerOffer::factory()->create([
            'name' => 'Inactive Offer',
            'is_active' => false,
            'show_on_offers_page' => true,
        ]);
        PartnerOffer::factory()->create([
            'name' => 'Hidden Offer',
            'is_active' => true,
            'show_on_offers_page' => false,
        ]);

        $response = $this->get(route('partner-offers'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('PartnerOffers')
            ->has('offers', 1)
            ->where('offers.0.name', 'Active Offer')
        );
    }

    // ============================================
    // Model Scope Tests
    // ============================================

    public function test_active_scope_filters_correctly(): void
    {
        PartnerOffer::factory()->create(['is_active' => true]);
        PartnerOffer::factory()->create(['is_active' => false]);

        $this->assertCount(1, PartnerOffer::active()->get());
    }

    public function test_for_picks_scope_filters_correctly(): void
    {
        PartnerOffer::factory()->create(['is_active' => true, 'show_on_picks' => true]);
        PartnerOffer::factory()->create(['is_active' => true, 'show_on_picks' => false]);
        PartnerOffer::factory()->create(['is_active' => false, 'show_on_picks' => true]);

        $this->assertCount(1, PartnerOffer::forPicks()->get());
    }

    public function test_for_offers_page_scope_filters_correctly(): void
    {
        PartnerOffer::factory()->create(['is_active' => true, 'show_on_offers_page' => true]);
        PartnerOffer::factory()->create(['is_active' => false, 'show_on_offers_page' => true]);

        $this->assertCount(1, PartnerOffer::forOffersPage()->get());
    }

    public function test_of_type_scope_filters_correctly(): void
    {
        PartnerOffer::factory()->create(['type' => 'sportsbook']);
        PartnerOffer::factory()->create(['type' => 'prediction']);
        PartnerOffer::factory()->create(['type' => 'casino']);

        $this->assertCount(1, PartnerOffer::ofType('sportsbook')->get());
        $this->assertCount(1, PartnerOffer::ofType('casino')->get());
    }

    // ============================================
    // Click URL Generation Tests
    // ============================================

    public function test_get_click_url_returns_external_url_without_discount(): void
    {
        $offer = PartnerOffer::factory()->create([
            'external_url' => 'https://external.com',
            'discount_code_id' => null,
        ]);

        $this->assertEquals('https://external.com', $offer->getClickUrl());
    }

    public function test_get_click_url_returns_checkout_url_with_discount(): void
    {
        $discountCode = DiscountCode::create([
            'code' => 'PARTNER20',
            'description' => 'Partner discount',
            'discount_type' => 'percentage',
            'discount_amount' => 20,
            'apply_to' => 'first_payment',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $offer = PartnerOffer::factory()->create([
            'external_url' => 'https://external.com',
            'discount_code_id' => $discountCode->id,
        ]);

        $clickUrl = $offer->getClickUrl();
        $this->assertStringContainsString('quick-checkout', $clickUrl);
        $this->assertStringContainsString('PARTNER20', $clickUrl);
    }
}
