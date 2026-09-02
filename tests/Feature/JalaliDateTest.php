<?php

namespace Tests\Feature;

use App\Support\Jalali;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class JalaliDateTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    /** جفت‌های مرجع میلادی ↔ شمسی */
    public static function pairs(): array
    {
        return [
            'نوروز ۱۴۰۵'        => ['2026-03-21', 1405, 1, 1],
            'روز جاری نمونه'    => ['2026-09-02', 1405, 6, 11],
            'نوروز ۱۴۰۰'        => ['2021-03-21', 1400, 1, 1],
            'پایان اسفند ۱۳۹۹'  => ['2021-03-20', 1399, 12, 30],
            'میلاد ۲۰۰۰'        => ['2000-01-01', 1378, 10, 11],
            'اول مهر ۱۴۰۴'      => ['2025-09-23', 1404, 7, 1],
            'یلدا ۱۴۰۴'         => ['2025-12-21', 1404, 9, 30],
            'کبیسه ۱۴۰۳'        => ['2025-03-20', 1403, 12, 30],
        ];
    }

    /** @dataProvider pairs */
    public function test_gregorian_converts_to_jalali(string $gregorian, int $jy, int $jm, int $jd): void
    {
        $date = CarbonImmutable::parse($gregorian);

        $this->assertSame([$jy, $jm, $jd], Jalali::toJalali($date->year, $date->month, $date->day));
    }

    /** @dataProvider pairs */
    public function test_jalali_converts_back_to_gregorian(string $gregorian, int $jy, int $jm, int $jd): void
    {
        $date = CarbonImmutable::parse($gregorian);

        $this->assertSame(
            [$date->year, $date->month, $date->day],
            Jalali::toGregorian($jy, $jm, $jd),
        );
    }

    public function test_round_trip_holds_for_a_full_decade(): void
    {
        $date = CarbonImmutable::create(2020, 1, 1);

        for ($i = 0; $i < 3653; $i++) {
            [$jy, $jm, $jd] = Jalali::toJalali($date->year, $date->month, $date->day);

            $this->assertSame(
                [$date->year, $date->month, $date->day],
                Jalali::toGregorian($jy, $jm, $jd),
                "تبدیل رفت‌وبرگشت در {$date->toDateString()} شکست خورد",
            );

            $date = $date->addDay();
        }
    }

    public function test_format_uses_persian_digits_and_month_names(): void
    {
        $date = CarbonImmutable::create(2026, 9, 2, 14, 30, 0, 'Asia/Tehran');

        $this->assertSame('۱۴۰۵/۰۶/۱۱', Jalali::format($date));
        $this->assertSame('۱۴۰۵/۰۶/۱۱ ۱۴:۳۰', Jalali::format($date, 'Y/m/d H:i'));
        $this->assertSame('۱۱ شهریور ۱۴۰۵', Jalali::long($date));
    }

    public function test_weekday_name_is_correct(): void
    {
        // ۲۱ مارس ۲۰۲۶ روز شنبه است
        $this->assertSame('شنبه', Jalali::format(CarbonImmutable::create(2026, 3, 21, 12), 'l'));
        $this->assertSame('یکشنبه', Jalali::format(CarbonImmutable::create(2026, 3, 22, 12), 'l'));
        $this->assertSame('جمعه', Jalali::format(CarbonImmutable::create(2026, 3, 27, 12), 'l'));
    }

    public function test_it_parses_user_entered_jalali_dates(): void
    {
        foreach (['۱۴۰۵/۰۶/۱۱', '1405/06/11', '1405-6-11'] as $input) {
            $parsed = Jalali::parse($input);

            $this->assertNotNull($parsed, "«{$input}» پارس نشد");
            $this->assertSame('2026-09-02', $parsed->toDateString());
        }

        $this->assertNull(Jalali::parse('چیز نامعتبر'));
        $this->assertNull(Jalali::parse(''));
    }

    public function test_esfand_length_detects_leap_years(): void
    {
        $this->assertSame(30, Jalali::daysInMonth(1403, 12)); // کبیسه
        $this->assertSame(29, Jalali::daysInMonth(1404, 12));
        $this->assertTrue(Jalali::isLeapYear(1403));
        $this->assertFalse(Jalali::isLeapYear(1404));
    }

    public function test_blank_dates_render_as_dash(): void
    {
        $this->assertSame('—', Jalali::format(null));
    }

    public function test_order_code_uses_the_jalali_date(): void
    {
        $this->assertMatchesRegularExpression('/^BS-\d{6}-\d{4}$/', \App\Models\Order::generateCode());

        // ۱۱ شهریور ۱۴۰۵ → 050611
        $this->travelTo(CarbonImmutable::create(2026, 9, 2, 12, 0, 0, 'Asia/Tehran'));

        $this->assertStringStartsWith('BS-050611-', \App\Models\Order::generateCode());

        $this->travelBack();
    }

    public function test_application_timezone_is_tehran(): void
    {
        $this->assertSame('Asia/Tehran', config('app.timezone'));
    }
}
