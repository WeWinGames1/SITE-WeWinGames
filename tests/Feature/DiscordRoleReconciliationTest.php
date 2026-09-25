<?php

namespace Tests\Feature;

use App\Jobs\SyncDiscordRolesJob;
use App\Listeners\SyncDiscordRolesOnSubscriptionChange;
use App\Models\StripeProduct;
use App\Models\User;
use App\Services\DiscordService;
use App\Services\SubscriptionSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Cashier\Events\WebhookReceived;
use Spatie\Permission\Models\Role;
use Stripe\StripeClient;
use Stripe\StripeObject;
use Tests\TestCase;

class DiscordRoleReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private const GUILD = 'guild-1';

    private const FREE = 'role-free';

    private const GOLD = 'role-gold';

    private const PLATINUM = 'role-platinum';

    private const STAFF = 'role-staff';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.discord.bot_token' => 'bot-token',
            'services.discord.guild_id' => self::GUILD,
            'services.discord.roles' => ['free' => self::FREE, 'gold' => self::GOLD, 'platinum' => self::PLATINUM],
            'services.discord.exempt_roles' => [self::STAFF],
        ]);

        StripeProduct::create([
            'name' => 'Gold Monthly',
            'stripe_product_id' => 'prod_gold',
            'stripe_price_id' => 'price_gold_monthly',
            'price' => 65,
            'tier' => 'Gold',
            'billing_period' => 'monthly',
            'is_active' => true,
            'features' => [],
        ]);
    }

    public function test_expired_manual_subscription_is_canceled_and_stops_granting_access(): void
    {
        $user = User::factory()->create();
        $this->subscribe($user, 'manual_abc', 'active', currentPeriodEnd: now()->subDay());
        $user->update(['admin_override' => true, 'override_tier' => 'gold', 'override_expiry' => now()->subDay()->toDateString()]);

        $this->assertTrue($user->fresh()->hasActiveSubscription(), 'precondition: manual sub grants access forever');

        $affected = app(SubscriptionSyncService::class)->expireManualSubscriptions();

        $this->assertSame([$user->id], $affected->all());
        $this->assertSame('canceled', $user->subscriptions()->first()->stripe_status);
        $this->assertFalse($user->fresh()->hasActiveSubscription());
    }

    public function test_current_manual_subscription_is_left_alone(): void
    {
        $user = User::factory()->create();
        $this->subscribe($user, 'manual_abc', 'active', currentPeriodEnd: now()->addWeek());

        $affected = app(SubscriptionSyncService::class)->expireManualSubscriptions();

        $this->assertTrue($affected->isEmpty());
        $this->assertTrue($user->fresh()->hasActiveSubscription());
    }

    public function test_audit_flags_canceled_linked_member_and_unlinked_role_holders(): void
    {
        $canceled = User::factory()->create(['discord_id' => '100', 'discord_username' => 'lapsed']);
        $this->subscribe($canceled, 'sub_lapsed', 'canceled', endsAt: now()->subDay());

        $active = User::factory()->create(['discord_id' => '200', 'discord_username' => 'payer']);
        $this->subscribe($active, 'sub_active', 'active');

        $unlinkedActive = User::factory()->create(['discord_id' => null, 'discord_username' => 'OldTimer']);
        $this->subscribe($unlinkedActive, 'sub_old', 'active');

        $this->fakeGuild([
            $this->member('100', 'lapsed', [self::FREE, self::GOLD]),
            $this->member('200', 'payer', [self::FREE, self::GOLD]),
            $this->member('300', 'stranger', [self::GOLD]),
            $this->member('400', 'moderator', [self::GOLD, self::STAFF]),
            $this->member('500', 'oldtimer', [self::FREE, self::GOLD]),
            $this->member('600', 'lurker', []),
        ]);

        $audit = app(DiscordService::class)->audit();

        $this->assertSame(6, $audit['members_scanned']);

        $this->assertCount(1, $audit['linked']);
        $this->assertSame($canceled->id, $audit['linked'][0]['user_id']);
        $this->assertSame([self::FREE, self::GOLD], $audit['linked'][0]['remove']);
        $this->assertSame([], $audit['linked'][0]['add']);

        $this->assertSame(['300'], array_column($audit['unlinked'], 'discord_id'));
        $this->assertSame([self::GOLD], $audit['unlinked'][0]['remove']);
    }

    public function test_audit_returns_null_when_members_cannot_be_listed(): void
    {
        Http::fake(['discord.com/*' => Http::response(['message' => 'Missing Access'], 403)]);

        $this->assertNull(app(DiscordService::class)->audit());
    }

    public function test_applying_audit_rows_removes_roles_through_the_api(): void
    {
        Http::fake(['discord.com/*' => Http::response(null, 204)]);

        $result = app(DiscordService::class)->applyAuditRows([
            ['discord_id' => '100', 'add' => [], 'remove' => [self::FREE, self::GOLD]],
        ]);

        $this->assertSame(['fixed' => 1, 'failed' => 0], $result);
        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
            && str_ends_with($request->url(), '/guilds/'.self::GUILD.'/members/100/roles/'.self::GOLD));
        Http::assertSentCount(2);
    }

    public function test_sync_from_stripe_overwrites_stale_local_status(): void
    {
        $user = User::factory()->create(['stripe_id' => 'cus_1']);
        $this->subscribe($user, 'sub_1', 'active');
        $this->subscribe($user, 'sub_gone', 'active');

        $this->fakeStripeSubscriptions([
            [
                'id' => 'sub_1',
                'status' => 'canceled',
                'ended_at' => now()->subDay()->timestamp,
                'cancel_at_period_end' => false,
                'current_period_start' => now()->subMonth()->timestamp,
                'current_period_end' => now()->subDay()->timestamp,
                'items' => ['data' => [['price' => ['id' => 'price_gold_monthly']]]],
            ],
        ]);

        $changes = app(SubscriptionSyncService::class)->syncFromStripe($user);

        $this->assertCount(2, $changes);
        $this->assertSame(['canceled'], $user->subscriptions()->pluck('stripe_status')->unique()->values()->all());
        $this->assertFalse($user->fresh()->hasActiveSubscription());
    }

    public function test_webhook_cancellation_date_is_stored_in_app_timezone(): void
    {
        config(['cashier.webhook.secret' => null]);
        $user = User::factory()->create(['stripe_id' => 'cus_1']);
        $this->subscribe($user, 'sub_1', 'active');
        $periodEnd = now()->addDays(3)->startOfSecond();

        $this->postJson('/stripe/webhook', [
            'type' => 'customer.subscription.updated',
            'data' => ['object' => [
                'id' => 'sub_1',
                'customer' => 'cus_1',
                'status' => 'active',
                'cancel_at_period_end' => true,
                'current_period_end' => $periodEnd->timestamp,
                'items' => ['data' => [['id' => 'si_1', 'price' => ['id' => 'price_gold_monthly', 'product' => 'prod_gold'], 'quantity' => 1]]],
            ]],
        ])->assertOk();

        $this->assertSame($periodEnd->timestamp, $user->subscriptions()->first()->ends_at->timestamp);
    }

    public function test_webhook_sync_is_delayed_until_cashier_has_updated_the_database(): void
    {
        Queue::fake();
        $user = User::factory()->create(['stripe_id' => 'cus_1', 'discord_id' => '100']);

        (new SyncDiscordRolesOnSubscriptionChange)->handle(new WebhookReceived([
            'type' => 'customer.subscription.deleted',
            'data' => ['object' => ['id' => 'sub_1', 'customer' => 'cus_1']],
        ]));

        Queue::assertPushed(SyncDiscordRolesJob::class, fn (SyncDiscordRolesJob $job) => $job->user->is($user)
            && $job->delay !== null
            && now()->diffInSeconds($job->delay) >= SyncDiscordRolesOnSubscriptionChange::SYNC_DELAY_SECONDS - 1);
    }

    public function test_admin_can_view_audit_page_and_fix_linked_members(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $canceled = User::factory()->create(['discord_id' => '100']);
        $this->subscribe($canceled, 'sub_lapsed', 'canceled', endsAt: now()->subDay());

        Http::fake([
            'discord.com/api/v10/guilds/'.self::GUILD.'/members?*' => Http::response([$this->member('100', 'lapsed', [self::GOLD])]),
            'discord.com/*' => Http::response(null, 204),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.discord-audit.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/DiscordAudit/Index')
                ->where('audit.linked.0.user_id', $canceled->id)
                ->where('roleLabels.'.self::GOLD, 'gold'));

        $this->actingAs($admin)
            ->post(route('admin.discord-audit.fix'), ['scope' => 'linked'])
            ->assertSessionHas('success');

        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
            && str_ends_with($request->url(), '/members/100/roles/'.self::GOLD));
    }

    public function test_audit_fix_rejects_unknown_scope(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.discord-audit.fix'), ['scope' => 'everyone'])
            ->assertSessionHasErrors('scope');
    }

    public function test_admin_resync_pulls_stripe_then_strips_discord_roles(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create(['stripe_id' => 'cus_1', 'discord_id' => '100']);
        $this->subscribe($customer, 'sub_1', 'active');

        $this->fakeStripeSubscriptions([
            [
                'id' => 'sub_1',
                'status' => 'canceled',
                'ended_at' => now()->subHour()->timestamp,
                'cancel_at_period_end' => false,
                'items' => ['data' => [['price' => ['id' => 'price_gold_monthly']]]],
            ],
        ]);

        Http::fake([
            'discord.com/api/v10/guilds/'.self::GUILD.'/members/100' => Http::response(['roles' => [self::FREE, self::GOLD]]),
            'discord.com/*' => Http::response(null, 204),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.customers.resync-access', $customer))
            ->assertSessionHas('success');

        $this->assertFalse($customer->fresh()->hasActiveSubscription());
        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
            && str_ends_with($request->url(), '/members/100/roles/'.self::GOLD));
        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
            && str_ends_with($request->url(), '/members/100/roles/'.self::FREE));
    }

    private function subscribe(User $user, string $stripeId, string $status, $endsAt = null, $currentPeriodEnd = null): void
    {
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => $stripeId,
            'stripe_status' => $status,
            'stripe_price' => 'price_gold_monthly',
            'quantity' => 1,
            'current_period_start' => now()->subMonth(),
            'current_period_end' => $currentPeriodEnd ?? now()->addMonth(),
            'ends_at' => $endsAt,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $members
     */
    private function fakeGuild(array $members): void
    {
        Http::fake(['discord.com/api/v10/guilds/'.self::GUILD.'/members?*' => Http::response($members)]);
    }

    /**
     * @param  array<int, string>  $roles
     * @return array<string, mixed>
     */
    private function member(string $id, string $username, array $roles): array
    {
        return ['user' => ['id' => $id, 'username' => $username, 'global_name' => null], 'roles' => $roles];
    }

    /**
     * @param  array<int, array<string, mixed>>  $subscriptions
     */
    private function fakeStripeSubscriptions(array $subscriptions): void
    {
        $client = new class($subscriptions) extends StripeClient
        {
            public function __construct(private array $data)
            {
                parent::__construct('sk_test_fake');
            }

            public function __get($name)
            {
                return new class($this->data)
                {
                    public function __construct(private array $data) {}

                    public function all(array $params): StripeObject
                    {
                        return StripeObject::constructFrom(['data' => $this->data]);
                    }
                };
            }
        };

        $this->app->instance(SubscriptionSyncService::class, new class($client) extends SubscriptionSyncService
        {
            public function __construct(private StripeClient $client) {}

            protected function stripe(): StripeClient
            {
                return $this->client;
            }
        });
    }
}
