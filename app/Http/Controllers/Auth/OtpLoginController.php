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
use Illuminate\Validation\ValidationException;

/**
 * ورود مشتری با موبایل + کد یکبار مصرف.
 *
 * برخلاف طرح اولیه، خودِ این کنترلر دیگر کد نمی‌سازد و هش نمی‌کند —
 * درایور پیامک (متد اختصاصی sendCode/checkCode لیمو) این کار را انجام
 * می‌دهد. علت: پیامک عمومی حاوی کد از خط اشتراکی توسط اپراتور فیلتر
 * می‌شد (با تست واقعی کشف شد: «ارسال‌شده» گزارش می‌شد ولی هرگز نمی‌رسید)،
 * ولی مسیر اختصاصی احراز هویت تحویل تضمین‌شده دارد.
 *
 * جدول otp_codes فقط برای محدودسازی نرخ درخواست نگه داشته شده؛ ستون
 * code_hash دیگر معنای واقعی ندارد (مقدار جایگزین می‌نویسیم تا NOT NULL
 * نشکند) — خودِ کد و اعتبارسنجی‌اش کاملاً نزد درایور پیامک است.
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

        $this->guardRateLimit($mobile);

        $log = $this->sms->sendCode($mobile);

        OtpCode::create([
            'mobile'     => $mobile,
            'code_hash'  => 'external', // خودِ کد نزد درایور پیامک است، نه اینجا
            'purpose'    => 'login',
            'ip'         => $request->ip(),
            'expires_at' => now()->addSeconds((int) config('shop.otp.ttl_seconds')),
        ]);

        if ($log->status !== 'sent') {
            throw ValidationException::withMessages([
                'mobile' => $log->error ?: 'ارسال کد با خطا مواجه شد. لطفاً دوباره تلاش کنید.',
            ]);
        }

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

        $result = $this->sms->checkCode($mobile, $code);

        if (! $result->ok) {
            throw ValidationException::withMessages([
                'code' => $result->error ?: 'کد وارد شده نادرست است.',
            ]);
        }

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

    protected function guardRateLimit(string $mobile): void
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
