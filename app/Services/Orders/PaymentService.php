<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\Transaction;
use App\Services\Payment\PaymentManager;
use App\Services\Sms\SmsManager;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/** نهایی‌سازی پرداخت: تأیید تراکنش، علامت‌گذاری سفارش و پیامک. */
class PaymentService
{
    public function __construct(
        protected PaymentManager $gateways,
        protected OrderStatusService $status,
        protected SmsManager $sms,
    ) {}

    public function markPaid(Order $order, Transaction $transaction): Order
    {
        return DB::transaction(function () use ($order, $transaction) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->isPaid()) {
                return $order;
            }

            $order->update([
                'payment_status' => 'paid',
                'paid_at'        => now(),
            ]);

            $order = $this->status->transition(
                $order,
                Order::PAID,
                note: 'پرداخت آنلاین موفق — شماره پیگیری: '.$transaction->ref_id,
                actorType: 'system',
            );

            if ($mobile = $order->customer?->mobile) {
                $this->sms->sendPattern($mobile, 'payment_ok', [
                    'code'   => $order->code,
                    'amount' => Money::format($order->grand_total, false),
                ], $order);
            }

            return $order;
        });
    }
}
