<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerDiscordTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_customer_detail_reports_discord_connection(): void
    {
        $customer = User::factory()->create([
            'discord_id' => '123456789',
            'discord_username' => 'wager_wizard',
            'discord_connected_at' => now()->subDays(3),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.customers.show', $customer));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/CustomerView')
            ->where('customer.discord_connected', true)
            ->where('customer.discord_username', 'wager_wizard')
            ->whereNot('customer.discord_connected_at', null)
        );
    }

    public function test_customer_detail_reports_no_discord_connection(): void
    {
        $customer = User::factory()->create(['discord_id' => null]);

        $response = $this->actingAs($this->admin)->get(route('admin.customers.show', $customer));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('customer.discord_connected', false)
            ->where('customer.discord_connected_at', null)
        );
    }

    public function test_index_can_filter_by_discord_connection(): void
    {
        $connected = User::factory()->create(['discord_id' => '999', 'discord_connected_at' => now()]);
        $notConnected = User::factory()->create(['discord_id' => null]);

        $response = $this->actingAs($this->admin)->get(route('admin.customers.index', ['discord' => 'connected']));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) use ($connected, $notConnected) {
            $ids = collect($page->toArray()['props']['customers']['data'])->pluck('id');
            $this->assertTrue($ids->contains($connected->id));
            $this->assertFalse($ids->contains($notConnected->id));
        });
    }
}
