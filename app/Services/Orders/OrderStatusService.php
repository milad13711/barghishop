<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Sms\SmsManager;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * تنها مسیر مجاز تغییر وضعیت سفارش.
 * هیچ‌جای دیگری نباید $order->update(['status' => ...]) بنویسد.
 *
 * مسئولیت‌ها: اعتبارسنجی گذار، ثبت لاگ، پیامک، امتیاز باشگاه، برگشت موجودی.
 */
class OrderStatusService
{
    public function __construct(
        protected SmsManager $sms,
        protected LoyaltyService $loyalty,
        protected StockService $stock,
    ) {}

    public function transition(
        Order $order,
        string $to,
        ?string $note = null,
        string $actorType = 'admin',
        ?int $actorId = null,
        array $extra = [],
    ): Order {
        return DB::transaction(function () use ($order, $to, $note, $actorType, $actorId, $extra) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $from = $order->status;

            if ($from === $to) {
                return $order;
            }

            if (! $order->canTransitionTo($to)) {
                throw new RuntimeException("تغییر وضعیت از «$from» به «$to» مجاز نیست.");
            }

            $order->fill($extra);
            $order->status = $to;

            match ($to) {
                Order::SHIPPED   => $order->shipped_at   ??= now(),
                Order::DELIVERED => $order->delivered_at ??= now(),
                default          => null,
            };

            $order->save();

            $order->statusLogs()->create([
                'from_status' => $from,
                'to_status'   => $to,
                'actor_type'  => $actorType,
                'actor_id'    => $actorId,
                'note'        => $note,
            ]);

            $this->afterTransition($order, $from, $to);

            return $order;
        });
    }

    protected function afterTransition(Order $order, string $from, string $to): void
    {
        $mobile = $order->customer?->mobile ?? data_get($order->address_snapshot, 'receiver_mobile');

        match ($to) {
            Order::PROCESSING => $this->stock->commit($order),

            Order::SHIPPED => $mobile ? $this->sms->sendPattern($mobile, 'shipped', [
                'code'     => $order->code,
                'tracking' => $order->tracking_code ?: '—',
            ], $order) : null,

            Order::DELIVERED => $this->loyalty->awardForOrder($order),

            Order::CANCELLED, Order::REFUNDED => $this->stock->release($order),

            default => null,
        };
    }
}
