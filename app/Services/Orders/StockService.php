<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;

/**
 * کاهش/بازگردانی موجودی. برای جلوگیری از کسر دوباره،
 * وضعیت آن روی خود سفارش با admin_note ثبت نمی‌شود بلکه از
 * لاگ گذارها استنتاج می‌شود: کسر فقط در اولین ورود به processing.
 */
class StockService
{
    public function commit(Order $order): void
    {
        if ($this->alreadyCommitted($order)) {
            return;
        }

        foreach ($order->items as $item) {
            $this->adjust($item->product_id, $item->product_variant_id, -$item->qty);

            if ($item->product) {
                $item->product->increment('sold_count', $item->qty);
            }
        }
    }

    public function release(Order $order): void
    {
        if (! $this->alreadyCommitted($order)) {
            return;
        }

        foreach ($order->items as $item) {
            $this->adjust($item->product_id, $item->product_variant_id, $item->qty);

            if ($item->product) {
                $item->product->decrement('sold_count', min($item->qty, $item->product->sold_count));
            }
        }
    }

    protected function alreadyCommitted(Order $order): bool
    {
        return $order->statusLogs()
            ->where('to_status', Order::PROCESSING)
            ->exists();
    }

    protected function adjust(?int $productId, ?int $variantId, int $delta): void
    {
        if ($variantId && $variant = ProductVariant::find($variantId)) {
            $variant->update(['stock' => max(0, $variant->stock + $delta)]);
        }

        if ($productId) {
            $product = Product::find($productId);

            if ($product && $product->track_stock) {
                $product->update(['stock' => max(0, $product->stock + $delta)]);
            }
        }
    }
}
