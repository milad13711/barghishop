<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;

/**
 * قرارداد درگاه پرداخت. برای افزودن درگاه جدید فقط یک پیاده‌سازی
 * از این اینترفیس بسازید و در config/shop.php ثبت کنید.
 */
interface PaymentGateway
{
    public function code(): string;

    public function name(): string;

    /** ساخت تراکنش و بازگرداندن آدرس انتقال کاربر به درگاه. */
    public function request(Order $order, string $callbackUrl): PaymentRequestResult;

    /** بررسی بازگشت از درگاه و نهایی‌سازی تراکنش. */
    public function verify(Request $request): Transaction;
}
