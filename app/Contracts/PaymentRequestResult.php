<?php

namespace App\Contracts;

final class PaymentRequestResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $authority = null,
        public readonly ?string $error = null,
    ) {}

    public static function success(string $redirectUrl, string $authority): self
    {
        return new self(true, $redirectUrl, $authority);
    }

    public static function failure(string $error): self
    {
        return new self(false, error: $error);
    }
}
