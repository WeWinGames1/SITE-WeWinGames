<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\TicketCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SupportAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create ticket categories
        TicketCategory::create(['name' => 'General Inquiry', 'description' => 'General questions']);
        TicketCategory::create(['name' => 'Technical Support', 'description' => 'Technical issues']);
    }

    public function test_guest_can_access_support_page()
    {
        $response = $this->get('/support');
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('support/PublicCreate')
            ->has('categories')
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
            ->has('categories')
            ->where('isAuthenticated', true)
        );
    }

    public function test_guest_can_submit_support_ticket()
    {
        $response = $this->post('/support', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'category_id' => 1,
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

    public function test_authenticated_user_is_redirected_to_ticket_after_submission()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->post('/support', [
            'category_id' => 1,
            'subject' => 'Test Support Request',
            'content' => 'This is a test support request from an authenticated user.',
            'priority' => 'medium',
        ]);
        
        $response->assertRedirect();
        $response->assertRedirectContains('/support/tickets/');
    }
}