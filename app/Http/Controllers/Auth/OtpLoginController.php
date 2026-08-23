<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\OtpCode;
use App\Models\PriceTier;
use App\Services\Cart\CartService;
use App\Services\Sms\SmsManager;
use App\Support\Casts\Mobile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * ورود مشتری با موبایل + کد یکبار مصرف.
 * کد به‌صورت هش ذخیره می‌شود و هرگز در لاگ ظاهر نمی‌شود.
 */
class OtpLoginController extends Controller
{
    public function __construct(
        protected SmsManager $sms,
        protected CartService $cart,
    ) {}

    public function form()
    {
        return view('auth.login', [
            'seo' => ['title' => 'ورود یا ثبت‌نام | '.config('shop.name')],
        ]);
    }

    public function sendCode(Request $request)
    {
        $mobile = Mobile::normalize($request->input('mobile'));

        if (! Mobile::isValid($mobile)) {
            throw ValidationException::withMessages(['mobile' => 'شماره موبایل معتبر نیست.']);
        }

        $this->guardRateLimit($mobile, $request->ip());

        $code = (string) random_int(10000, 99999);

        OtpCode::create([
            'mobile'     => $mobile,
            'code_hash'  => Hash::make($code),
            'purpose'    => 'login',
            'ip'         => $request->ip(),
            'expires_at' => now()->addSeconds((int) config('shop.otp.ttl_seconds')),
        ]);

        $this->sms->sendPattern($mobile, 'otp', ['code' => $code]);

        return redirect()->route('auth.verify.form', ['mobile' => $mobile])
            ->with('success', 'کد ورود برای شما پیامک شد.');
    }

    public function verifyForm(Request $request)
    {
        $mobile = Mobile::normalize($request->query('mobile'));

        abort_unless(Mobile::isValid($mobile), 404);

        return view('auth.verify', [
            'seo'    => ['title' => 'تأیید کد ورود | '.config('shop.name')],
            'mobile' => $mobile,
        ]);
    }

    public function verify(Request $request)
    {
        $mobile = Mobile::normalize($request->input('mobile'));
        $code   = \App\Support\Digits::toEnglish((string) $request->input('code'));

        $otp = OtpCode::where('mobile', $mobile)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (! $otp || ! $otp->isUsable()) {
            throw ValidationException::withMessages(['code' => 'کد منقضی شده است. دوباره درخواست دهید.']);
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            throw ValidationException::withMessages(['code' => 'کد وارد شده نادرست است.']);
        }

        $otp->update(['used_at' => now()]);

        // is_active را صریح ست می‌کنیم؛ مقدار پیش‌فرض دیتابیس روی نمونه تازه‌ساخته‌شده
        // بارگذاری نمی‌شود و در نتیجه null (falsy) می‌ماند.
        $customer = Customer::firstOrCreate(
            ['mobile' => $mobile],
            [
                'price_tier_id'      => PriceTier::retail()->id,
                'mobile_verified_at' => now(),
                'is_active'          => true,
                'accepts_sms'        => true,
            ],
        );

        if (! $customer->mobile_verified_at) {
            $customer->update(['mobile_verified_at' => now()]);
        }

        abort_unless($customer->is_active, 403, 'حساب کاربری شما غیرفعال است.');

        auth('customer')->login($customer, remember: true);
        $request->session()->regenerate();

        $this->cart->mergeIntoCustomer($customer);

        return redirect()->intended(route('account.dashboard'))
            ->with('success', 'خوش آمدید!');
    }

    public function logout(Request $request)
    {
        auth('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    protected function guardRateLimit(string $mobile, ?string $ip): void
    {
        $recent = OtpCode::where('mobile', $mobile)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recent >= (int) config('shop.otp.max_per_hour')) {
            throw ValidationException::withMessages([
                'mobile' => 'تعداد درخواست‌های شما زیاد است. یک ساعت دیگر تلاش کنید.',
            ]);
        }

        $last = OtpCode::where('mobile', $mobile)->latest()->first();

        if ($last && $last->created_at->diffInSeconds(now()) < config('shop.otp.resend_seconds')) {
            throw ValidationException::withMessages([
                'mobile' => 'کمی صبر کنید و دوباره درخواست ارسال کد بدهید.',
            ]);
        }
    }
}
