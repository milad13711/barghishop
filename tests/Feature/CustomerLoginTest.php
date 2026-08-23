<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\OtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PriceTierSeeder::class);
        config()->set('shop.sms.default', 'log');
    }

    public function test_it_sends_an_otp_and_logs_the_customer_in(): void
    {
        $this->post(route('auth.send-code'), ['mobile' => '۰۹۱۲۱۱۱۰۰۰۰'])
            ->assertRedirect(route('auth.verify.form', ['mobile' => '09121110000']));

        $otp = OtpCode::where('mobile', '09121110000')->latest()->firstOrFail();

        // کد به‌صورت هش ذخیره شده و متن خام آن در دیتابیس نیست
        $this->assertNotEmpty($otp->code_hash);
        $this->assertNull($otp->used_at);

        // کد واقعی را نمی‌دانیم، پس یک کد شناخته‌شده جایگزین می‌کنیم
        $otp->update(['code_hash' => Hash::make('12345')]);

        $this->post(route('auth.verify'), ['mobile' => '09121110000', 'code' => '12345'])
            ->assertRedirect(route('account.dashboard'));

        $this->assertAuthenticatedAs(Customer::where('mobile', '09121110000')->first(), 'customer');
    }

    public function test_new_customer_is_created_active_and_on_retail_tier(): void
    {
        $this->post(route('auth.send-code'), ['mobile' => '09121110001']);

        $otp = OtpCode::latest()->firstOrFail();
        $otp->update(['code_hash' => Hash::make('12345')]);

        $this->post(route('auth.verify'), ['mobile' => '09121110001', 'code' => '12345']);

        $customer = Customer::where('mobile', '09121110001')->firstOrFail();

        $this->assertTrue((bool) $customer->is_active);
        $this->assertSame('retail', $customer->effectiveTier()->code);
    }

    public function test_wrong_code_is_rejected_and_counted(): void
    {
        $this->post(route('auth.send-code'), ['mobile' => '09121110002']);

        OtpCode::latest()->first()->update(['code_hash' => Hash::make('12345')]);

        $this->post(route('auth.verify'), ['mobile' => '09121110002', 'code' => '99999'])
            ->assertSessionHasErrors('code');

        $this->assertSame(1, OtpCode::latest()->first()->attempts);
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
