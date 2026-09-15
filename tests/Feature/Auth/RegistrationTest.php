<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_redirects_to_quick_checkout(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect(route('quick-checkout', ['plan' => 'gold', 'period' => 'monthly']));
    }

    public function test_legacy_registration_post_does_not_create_an_account(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('quick-checkout', ['plan' => 'gold', 'period' => 'monthly']));
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }
}
