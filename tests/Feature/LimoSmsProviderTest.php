<?php

namespace Tests\Feature;

use App\Services\Sms\Providers\LimoSmsProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * پاسخ واقعی سرور اکسیرپیامک با تست ارسال واقعی به شماره ۰۹۹۰۸۰۰۸۰۱۱ کشف شد:
 * فیلدها camelCase هستند (success/messageId)، نه PascalCase مستندات پنل
 * (Success/MessageId). این تست دقیقاً همان پاسخ واقعی را قفل می‌کند.
 */
class LimoSmsProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_recognizes_the_real_lowercase_response_shape(): void
    {
        Http::fake([
            'api.limosms.com/*' => Http::response([
                'success'   => true,
                'message'   => 'پیام با موفقیت ارسال شد',
                'messageId' => [28267313],
            ]),
        ]);

        $result = (new LimoSmsProvider)->send('09908008011', 'تست');

        $this->assertTrue($result->ok);
        $this->assertSame('28267313', $result->messageId);
    }

    public function test_it_still_understands_pascalcase_as_a_fallback(): void
    {
        Http::fake([
            'api.limosms.com/*' => Http::response([
                'Success'   => true,
                'Message'   => 'OK',
                'MessageId' => ['abc123'],
            ]),
        ]);

        $result = (new LimoSmsProvider)->send('09908008011', 'تست');

        $this->assertTrue($result->ok);
        $this->assertSame('abc123', $result->messageId);
    }

    public function test_failure_response_is_reported_with_server_message(): void
    {
        Http::fake([
            'api.limosms.com/*' => Http::response([
                'success' => false,
                'message' => 'اعتبار کافی نیست',
            ]),
        ]);

        $result = (new LimoSmsProvider)->send('09908008011', 'تست');

        $this->assertFalse($result->ok);
        $this->assertSame('اعتبار کافی نیست', $result->error);
    }

    public function test_request_body_uses_the_verified_field_names(): void
    {
        Http::fake(['api.limosms.com/*' => Http::response(['success' => true, 'messageId' => ['1']])]);

        config(['shop.sms.providers.limo.sender' => 'VIP']);

        (new LimoSmsProvider)->send('09908008011', 'متن پیام');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.limosms.com/api/sendsms'
                && $request['SenderNumber'] === 'VIP'
                && $request['Message'] === 'متن پیام'
                && $request['MobileNumber'] === ['09908008011'];
        });
    }
}
