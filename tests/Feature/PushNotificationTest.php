<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PriceTier;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\Push\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function subscriptionPayload(string $endpoint = 'https://fcm.googleapis.com/fcm/send/test-endpoint'): array
    {
        return [
            'endpoint' => $endpoint,
            'keys' => [
                'p256dh' => 'BKQZBHRc7nIuOhL_SB2ckZqX4NI0dnLT8zLmXMjLqQxneb7lEr_bltlKG67TEGdgx0lYxdKSJE7N6q0pZo9BaqU',
                'auth'   => 'k8JV6-boverify',
            ],
            'contentEncoding' => 'aes128gcm',
        ];
    }

    public function test_guest_cannot_subscribe(): void
    {
        $this->postJson(route('push.subscribe'), $this->subscriptionPayload())
            ->assertStatus(401);
    }

    public function test_authenticated_customer_can_subscribe(): void
    {
        $customer = Customer::create([
            'mobile' => '09121110000', 'price_tier_id' => PriceTier::retail()->id, 'is_active' => true,
        ]);

        $this->actingAs($customer, 'customer')
            ->postJson(route('push.subscribe'), $this->subscriptionPayload())
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame(1, $customer->pushSubscriptions()->count());
        $this->assertTrue($customer->pushSubscriptions()->first()->subscriber->is($customer));
    }

    public function test_authenticated_admin_can_subscribe(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')
            ->postJson(route('push.subscribe'), $this->subscriptionPayload())
            ->assertOk();

        $this->assertSame(1, $admin->pushSubscriptions()->count());
    }

    public function test_resubscribing_the_same_endpoint_updates_instead_of_duplicating(): void
    {
        $customer = Customer::create([
            'mobile' => '09121110001', 'price_tier_id' => PriceTier::retail()->id, 'is_active' => true,
        ]);

        $payload = $this->subscriptionPayload();

        $this->actingAs($customer, 'customer')->postJson(route('push.subscribe'), $payload);
        $this->actingAs($customer, 'customer')->postJson(route('push.subscribe'), $payload);

        $this->assertSame(1, PushSubscription::count());
    }

    public function test_unsubscribe_removes_the_record(): void
    {
        $customer = Customer::create([
            'mobile' => '09121110002', 'price_tier_id' => PriceTier::retail()->id, 'is_active' => true,
        ]);

        $payload = $this->subscriptionPayload();

        $this->actingAs($customer, 'customer')->postJson(route('push.subscribe'), $payload);
        $this->assertSame(1, PushSubscription::count());

        $this->actingAs($customer, 'customer')
            ->postJson(route('push.unsubscribe'), ['endpoint' => $payload['endpoint']])
            ->assertOk();

        $this->assertSame(0, PushSubscription::count());
    }

    public function test_notify_is_a_silent_no_op_when_vapid_is_not_configured(): void
    {
        config(['shop.push.public_key' => null, 'shop.push.private_key' => null]);

        $customer = Customer::create([
            'mobile' => '09121110003', 'price_tier_id' => PriceTier::retail()->id, 'is_active' => true,
        ]);
        $customer->pushSubscriptions()->create([
            'endpoint' => 'https://example.com/x', 'endpoint_hash' => hash('sha256', 'x'),
            'public_key' => 'a', 'auth_token' => 'b',
        ]);

        $sent = app(WebPushService::class)->notify($customer, 'عنوان', 'متن');

        $this->assertSame(0, $sent);
    }

    public function test_notify_returns_zero_when_subscriber_has_no_subscriptions(): void
    {
        config([
            'shop.push.public_key'  => 'BKQZBHRc7nIuOhL_SB2ckZqX4NI0dnLT8zLmXMjLqQxneb7lEr_bltlKG67TEGdgx0lYxdKSJE7N6q0pZo9BaqU',
            'shop.push.private_key' => 'GEtrdcs-jbYkle-nO5rf1cFqRShvACc0R7rikvJMsvc',
        ]);

        $customer = Customer::create([
            'mobile' => '09121110004', 'price_tier_id' => PriceTier::retail()->id, 'is_active' => true,
        ]);

        $this->assertSame(0, app(WebPushService::class)->notify($customer, 'عنوان', 'متن'));
    }
}
