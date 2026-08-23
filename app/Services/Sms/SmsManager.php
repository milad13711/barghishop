<?php

namespace App\Services\Sms;

use App\Contracts\SmsProvider;
use App\Models\SmsLog;
use App\Services\Sms\Providers\LimoSmsProvider;
use App\Services\Sms\Providers\LogSmsProvider;
use App\Support\Casts\Mobile;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * تنها نقطه ارسال پیامک. هر ارسال در sms_logs ثبت می‌شود
 * تا هم قابل پیگیری باشد و هم هزینه پنل قابل حسابرسی.
 */
class SmsManager
{
    protected array $drivers = [
        'limo' => LimoSmsProvider::class,
        'log'  => LogSmsProvider::class,
    ];

    public function driver(?string $code = null): SmsProvider
    {
        $code ??= config('shop.sms.default');

        if (! isset($this->drivers[$code])) {
            throw new InvalidArgumentException("درایور پیامک [$code] تعریف نشده است.");
        }

        return app($this->drivers[$code]);
    }

    public function extend(string $code, string $class): void
    {
        $this->drivers[$code] = $class;
    }

    public function send(string $mobile, string $text, ?string $event = null, ?Model $source = null): SmsLog
    {
        $mobile = Mobile::normalize($mobile);

        $log = $this->log($mobile, $event, $source, ['body' => $text]);

        if (! Mobile::isValid($mobile)) {
            return tap($log)->update(['status' => 'failed', 'error' => 'شماره موبایل نامعتبر است.']);
        }

        $result = $this->driver()->send($mobile, $text);

        return $this->finish($log, $result);
    }

    public function sendPattern(
        string $mobile,
        string $event,
        array $params,
        ?Model $source = null,
    ): SmsLog {
        $mobile  = Mobile::normalize($mobile);
        $pattern = config("shop.sms.patterns.$event");

        $log = $this->log($mobile, $event, $source, [
            'pattern_code' => $pattern,
            'body'         => json_encode($params, JSON_UNESCAPED_UNICODE),
        ]);

        if (! Mobile::isValid($mobile)) {
            return tap($log)->update(['status' => 'failed', 'error' => 'شماره موبایل نامعتبر است.']);
        }

        // اگر پترن تعریف نشده باشد به پیام متنی ساده برمی‌گردیم تا ارسال از دست نرود.
        $result = $pattern
            ? $this->driver()->sendPattern($mobile, $pattern, $params)
            : $this->driver()->send($mobile, $this->fallbackText($event, $params));

        return $this->finish($log, $result);
    }

    protected function log(string $mobile, ?string $event, ?Model $source, array $extra): SmsLog
    {
        return SmsLog::create(array_merge([
            'provider'    => config('shop.sms.default'),
            'mobile'      => $mobile,
            'event'       => $event,
            'status'      => 'queued',
            'source_type' => $source?->getMorphClass(),
            'source_id'   => $source?->getKey(),
        ], $extra));
    }

    protected function finish(SmsLog $log, \App\Contracts\SmsResult $result): SmsLog
    {
        $log->update([
            'status'     => $result->ok ? 'sent' : 'failed',
            'message_id' => $result->messageId,
            'error'      => $result->error,
        ]);

        return $log;
    }

    protected function fallbackText(string $event, array $params): string
    {
        $shop = config('shop.name');

        return match ($event) {
            'otp'          => "کد ورود شما: {$params['code']}\n$shop",
            'order_placed' => "سفارش {$params['code']} ثبت شد.\n$shop",
            'payment_ok'   => "پرداخت سفارش {$params['code']} با موفقیت انجام شد.\n$shop",
            'shipped'      => "سفارش {$params['code']} ارسال شد. کد رهگیری: {$params['tracking']}\n$shop",
            'wholesale_ok' => "درخواست همکاری شما تأیید شد. اکنون قیمت‌های عمده برای شما فعال است.\n$shop",
            default        => collect($params)->implode(' ')."\n$shop",
        };
    }
}
