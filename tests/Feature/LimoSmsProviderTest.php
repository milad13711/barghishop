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

    public function test_send_code_hits_the_dedicated_otp_endpoint(): void
    {
        Http::fake([
            'api.limosms.com/*' => Http::response(['success' => true, 'message' => 'کد تایید با موفقیت ارسال شد']),
        ]);

        $result = (new LimoSmsProvider)->sendCode('09908008011', 'برقی\u200cشاپ');

        $this->assertTrue($result->ok);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.limosms.com/api/sendcode'
                && $request['Mobile'] === '09908008011';
        });
    }

    public function test_check_code_reports_the_real_persian_error_for_expired_code(): void
    {
        Http::fake([
            'api.limosms.com/*' => Http::response(['success' => false, 'message' => 'کد تایید منقضی شده است']),
        ]);

        $result = (new LimoSmsProvider)->checkCode('09908008011', '186542');

        $this->assertFalse($result->ok);
        $this->assertSame('کد تایید منقضی شده است', $result->error);
    }

    public function test_check_code_reports_the_real_persian_error_for_wrong_code(): void
    {
        Http::fake([
            'api.limosms.com/*' => Http::response(['success' => false, 'message' => 'کدتایید نادرست می باشد']),
        ]);

        $result = (new LimoSmsProvider)->checkCode('09908008011', '00000');

        $this->assertFalse($result->ok);
        $this->assertSame('کدتایید نادرست می باشد', $result->error);
    }

    public function test_check_code_succeeds_for_a_correct_code(): void
    {
        Http::fake([
            'api.limosms.com/*' => Http::response(['success' => true, 'message' => 'تایید موفق']),
        ]);

        $result = (new LimoSmsProvider)->checkCode('09908008011', '186542');

        $this->assertTrue($result->ok);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.limosms.com/api/checkcode'
                && $request['Mobile'] === '09908008011'
                && $request['Code'] === '186542';
        });
    }
}
