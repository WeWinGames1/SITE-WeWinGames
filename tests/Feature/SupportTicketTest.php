<?php

namespace Tests\Feature;

use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed ticket categories
        $this->seed(\Database\Seeders\TicketCategorySeeder::class);
    }

    public function test_guest_can_access_support_page()
    {
        $response = $this->get('/support');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('support/PublicCreate')
            ->has('categories', 6) // We seeded 6 categories
            ->where('isAuthenticated', false)
        );
    }

    public function test_authenticated_user_can_access_support_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/support');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('support/PublicCreate')
            ->has('categories', 6)
            ->where('isAuthenticated', true)
        );
    }

    public function test_guest_can_submit_support_ticket()
    {
        $category = TicketCategory::first();

        $response = $this->post('/support', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'category_id' => $category->id,
            'subject' => 'Test Support Request',
            'content' => 'This is a test support request from a guest.',
            'priority' => 'medium',
        ]);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('support/GuestTicketCreated')
            ->has('ticketNumber')
            ->where('email', 'john@example.com')
        );

        $this->assertDatabaseHas('support_tickets', [
            'guest_email' => 'john@example.com',
            'guest_name' => 'John Doe',
            'subject' => 'Test Support Request',
            'is_guest_submission' => true,
        ]);
    }

    public function test_authenticated_user_can_submit_support_ticket()
    {
        $user = User::factory()->create();
        $category = TicketCategory::first();

        $response = $this->actingAs($user)->post('/support', [
            'category_id' => $category->id,
            'subject' => 'Test Support Request',
            'content' => 'This is a test support request from an authenticated user.',
            'priority' => 'high',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $user->id,
            'subject' => 'Test Support Request',
            'is_guest_submission' => false,
        ]);
    }

    public function test_authenticated_user_can_view_their_tickets()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/support/tickets');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('support/Index')
        );
    }

    public function test_unauthenticated_user_cannot_view_tickets_list()
    {
        $response = $this->get('/support/tickets');

        $response->assertRedirect('/login');
    }
}
