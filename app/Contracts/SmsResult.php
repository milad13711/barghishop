<?php

namespace App\Contracts;

final class SmsResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?string $messageId = null,
        public readonly ?string $error = null,
        public readonly array $raw = [],
    ) {}

    public static function success(?string $messageId = null, array $raw = []): self
    {
        return new self(true, $messageId, null, $raw);
    }

    public static function failure(string $error, array $raw = []): self
    {
        return new self(false, null, $error, $raw);
    }
}
