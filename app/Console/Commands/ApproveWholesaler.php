<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\Sms\SmsManager;
use App\Support\Casts\Mobile;
use Illuminate\Console\Command;

/**
 * تأیید یا رد درخواست همکاری از خط فرمان.
 * پس از راه‌اندازی پنل مدیریت، همین کار از رابط گرافیکی هم انجام می‌شود.
 */
class ApproveWholesaler extends Command
{
    protected $signature = 'shop:wholesale
                            {mobile : شماره موبایل مشتری}
                            {--reject : رد درخواست به‌جای تأیید}';

    protected $description = 'تأیید یا رد درخواست همکاری (قیمت عمده) یک مشتری';

    public function handle(SmsManager $sms): int
    {
        $mobile = Mobile::normalize($this->argument('mobile'));

        $customer = Customer::where('mobile', $mobile)->first();

        if (! $customer) {
            $this->error("مشتری با شماره $mobile یافت نشد.");

            return self::FAILURE;
        }

        if ($this->option('reject')) {
            $customer->update(['wholesale_status' => Customer::WHOLESALE_REJECTED]);
            $this->warn("درخواست {$customer->name} رد شد.");

            return self::SUCCESS;
        }

        $customer->update(['wholesale_status' => Customer::WHOLESALE_APPROVED]);

        $sms->sendPattern($customer->mobile, 'wholesale_ok', [
            'name' => $customer->name ?: 'همکار گرامی',
            'tier' => $customer->effectiveTier()->name,
        ], $customer);

        $this->info("{$customer->name} به‌عنوان «{$customer->effectiveTier()->name}» تأیید شد.");

        return self::SUCCESS;
    }
}
