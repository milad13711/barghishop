<?php

namespace App\Services\Payment\Gateways;

use App\Contracts\PaymentGateway;
use App\Contracts\PaymentRequestResult;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * درگاه زرین‌پال (نسخه REST v4).
 * زرین‌پال مبلغ را به «ریال» می‌گیرد — همان واحدی که در دیتابیس ذخیره می‌کنیم.
 */
class ZarinpalGateway implements PaymentGateway
{
    public function code(): string
    {
        return 'zarinpal';
    }

    public function name(): string
    {
        return 'زرین‌پال';
    }

    public function request(Order $order, string $callbackUrl): PaymentRequestResult
    {
        $transaction = $order->transactions()->create([
            'gateway' => $this->code(),
            'amount'  => $order->grand_total,
            'status'  => Transaction::PENDING,
        ]);

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->post($this->baseUrl().'/pg/v4/payment/request.json', [
                    'merchant_id'  => $this->config('merchant_id'),
                    'amount'       => $order->grand_total,
                    'callback_url' => $callbackUrl,
                    'description'  => 'پرداخت سفارش '.$order->code.' – '.config('shop.name'),
                    'metadata'     => array_filter([
                        'mobile' => $order->customer?->mobile,
                        'email'  => $order->customer?->email,
                        'order_id' => (string) $order->id,
                    ]),
                ])
                ->json();
        } catch (\Throwable $e) {
            Log::error('zarinpal.request.exception', ['order' => $order->code, 'msg' => $e->getMessage()]);
            $transaction->update(['status' => Transaction::FAILED, 'message' => 'ارتباط با درگاه برقرار نشد.']);

            return PaymentRequestResult::failure('ارتباط با درگاه پرداخت برقرار نشد. لطفاً دوباره تلاش کنید.');
        }

        $code = data_get($response, 'data.code');

        if ((int) $code !== 100) {
            $error = data_get($response, 'errors.message') ?: 'خطای نامشخص درگاه';
            $transaction->update(['status' => Transaction::FAILED, 'message' => $error, 'raw' => $response]);

            return PaymentRequestResult::failure($error);
        }

        $authority = data_get($response, 'data.authority');
        $transaction->update(['authority' => $authority, 'raw' => $response]);

        return PaymentRequestResult::success($this->startPayUrl($authority), $authority);
    }

    public function verify(Request $request): Transaction
    {
        $authority = $request->query('Authority');
        $status    = $request->query('Status');

        $transaction = Transaction::where('authority', $authority)
            ->where('gateway', $this->code())
            ->latest()
            ->firstOrFail();

        // تراکنش قبلاً تأیید شده — از وریفای دوباره جلوگیری می‌کنیم.
        if ($transaction->status === Transaction::SUCCESS) {
            return $transaction;
        }

        if ($status !== 'OK') {
            $transaction->update(['status' => Transaction::FAILED, 'message' => 'پرداخت توسط کاربر لغو شد.']);

            return $transaction;
        }

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->post($this->baseUrl().'/pg/v4/payment/verify.json', [
                    'merchant_id' => $this->config('merchant_id'),
                    'amount'      => $transaction->amount,
                    'authority'   => $authority,
                ])
                ->json();
        } catch (\Throwable $e) {
            Log::error('zarinpal.verify.exception', ['authority' => $authority, 'msg' => $e->getMessage()]);
            $transaction->update(['message' => 'خطا در تأیید تراکنش. در صورت کسر وجه با پشتیبانی تماس بگیرید.']);

            return $transaction;
        }

        $code = (int) data_get($response, 'data.code');

        // ۱۰۰ = موفق، ۱۰۱ = قبلاً وریفای شده (هر دو یعنی پول دریافت شده)
        if (in_array($code, [100, 101], true)) {
            $transaction->update([
                'status'      => Transaction::SUCCESS,
                'ref_id'      => (string) data_get($response, 'data.ref_id'),
                'card_pan'    => data_get($response, 'data.card_pan'),
                'message'     => 'پرداخت موفق',
                'raw'         => $response,
                'verified_at' => now(),
            ]);
        } else {
            $transaction->update([
                'status'  => Transaction::FAILED,
                'message' => data_get($response, 'errors.message') ?: 'تأیید پرداخت ناموفق بود.',
                'raw'     => $response,
            ]);
        }

        return $transaction;
    }

    protected function sandbox(): bool
    {
        return (bool) $this->config('sandbox');
    }

    protected function baseUrl(): string
    {
        return $this->sandbox()
            ? 'https://sandbox.zarinpal.com'
            : 'https://payment.zarinpal.com';
    }

    protected function startPayUrl(string $authority): string
    {
        return $this->baseUrl().'/pg/StartPay/'.$authority;
    }

    protected function config(string $key): mixed
    {
        return config("shop.payment.gateways.zarinpal.$key");
    }
}
