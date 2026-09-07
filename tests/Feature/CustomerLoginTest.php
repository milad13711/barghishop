<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\OtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * درایور 'log' کد را در Cache نگه می‌دارد (نه در جدول otp_codes — آن جدول
 * از این پس فقط برای محدودسازی نرخ درخواست است، نه ذخیره خودِ کد). تست‌ها
 * کد واقعی را از همان Cache می‌خوانند، دقیقاً مثل چیزی که در پیامک واقعی
 * به کاربر می‌رسد.
 */
class CustomerLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PriceTierSeeder::class);
        config()->set('shop.sms.default', 'log');
    }

    protected function realCodeFor(string $mobile): string
    {
        return Cache::get("otp:log:$mobile");
    }

    public function test_it_sends_an_otp_and_logs_the_customer_in(): void
    {
        $this->post(route('auth.send-code'), ['mobile' => '۰۹۱۲۱۱۱۰۰۰۰'])
            ->assertRedirect(route('auth.verify.form', ['mobile' => '09121110000']));

        $code = $this->realCodeFor('09121110000');
        $this->assertNotEmpty($code);

        $this->post(route('auth.verify'), ['mobile' => '09121110000', 'code' => $code])
            ->assertRedirect(route('account.dashboard'));

        $this->assertAuthenticatedAs(Customer::where('mobile', '09121110000')->first(), 'customer');
    }

    public function test_new_customer_is_created_active_and_on_retail_tier(): void
    {
        $this->post(route('auth.send-code'), ['mobile' => '09121110001']);

        $this->post(route('auth.verify'), [
            'mobile' => '09121110001',
            'code'   => $this->realCodeFor('09121110001'),
        ]);

        $customer = Customer::where('mobile', '09121110001')->firstOrFail();

        $this->assertTrue((bool) $customer->is_active);
        $this->assertSame('retail', $customer->effectiveTier()->code);
    }

    public function test_wrong_code_is_rejected(): void
    {
        $this->post(route('auth.send-code'), ['mobile' => '09121110002']);

        $this->post(route('auth.verify'), ['mobile' => '09121110002', 'code' => '99999'])
            ->assertSessionHasErrors('code');

        $this->assertGuest('customer');
    }

    public function test_expired_or_unknown_code_is_rejected(): void
    {
        // بدون send-code قبلی، هیچ کدی در Cache نیست — دقیقاً یعنی «منقضی».
        $this->post(route('auth.verify'), ['mobile' => '09121119999', 'code' => '12345'])
            ->assertSessionHasErrors('code');

        $this->assertGuest('customer');
    }

    public function test_resend_is_throttled(): void
    {
        $this->post(route('auth.send-code'), ['mobile' => '09121110003']);

        $this->post(route('auth.send-code'), ['mobile' => '09121110003'])
            ->assertSessionHasErrors('mobile');

        $this->assertSame(1, OtpCode::where('mobile', '09121110003')->count());
    }

    public function test_invalid_mobile_is_rejected(): void
    {
        $this->post(route('auth.send-code'), ['mobile' => '12345'])
            ->assertSessionHasErrors('mobile');
    }
}
