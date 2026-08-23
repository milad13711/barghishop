<?php

namespace Tests\Feature;

use App\Support\Casts\Mobile;
use PHPUnit\Framework\TestCase;

class MobileNormalizationTest extends TestCase
{
    public static function mobileProvider(): array
    {
        return [
            ['09121234567', '09121234567'],
            ['9121234567', '09121234567'],
            ['+989121234567', '09121234567'],
            ['00989121234567', '09121234567'],
            ['۰۹۱۲۱۲۳۴۵۶۷', '09121234567'],
            ['0912 123 4567', '09121234567'],
            ['0912-123-4567', '09121234567'],
        ];
    }

    /** @dataProvider mobileProvider */
    public function test_it_normalizes_mobile_numbers(string $input, string $expected): void
    {
        $this->assertSame($expected, Mobile::normalize($input));
        $this->assertTrue(Mobile::isValid($input));
    }

    public function test_it_rejects_invalid_numbers(): void
    {
        $this->assertFalse(Mobile::isValid('021123456'));
        $this->assertFalse(Mobile::isValid('0912123456'));
        $this->assertFalse(Mobile::isValid(''));
    }
}
