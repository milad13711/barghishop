<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Services\Payment\Gateways\ZarinpalGateway;
use InvalidArgumentException;

class PaymentManager
{
    protected array $drivers = [
        'zarinpal' => ZarinpalGateway::class,
    ];

    public function driver(?string $code = null): PaymentGateway
    {
        $code ??= config('shop.payment.default');

        if (! isset($this->drivers[$code])) {
            throw new InvalidArgumentException("درگاه پرداخت [$code] تعریف نشده است.");
        }

        return app($this->drivers[$code]);
    }

    public function extend(string $code, string $class): void
    {
        $this->drivers[$code] = $class;
    }

    public function available(): array
    {
        return array_keys($this->drivers);
    }
}
