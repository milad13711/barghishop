<?php

namespace App\Http\Controllers\Push;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\Request;

/**
 * ثبت/حذف اشتراک پوش نوتیفیکیشن مرورگر. هم برای مشتری (guard: customer)
 * و هم مدیر پنل (guard: web) کار می‌کند — subscriber بر اساس گاردی که
 * لاگین است تعیین می‌شود.
 */
class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'endpoint'         => ['required', 'string'],
            'keys.p256dh'      => ['required', 'string'],
            'keys.auth'        => ['required', 'string'],
            'contentEncoding'  => ['nullable', 'string'],
        ]);

        $subscriber = $this->resolveSubscriber($request);

        abort_unless($subscriber, 401);

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $data['endpoint'])],
            [
                'subscriber_type'  => $subscriber->getMorphClass(),
                'subscriber_id'    => $subscriber->getKey(),
                'endpoint'         => $data['endpoint'],
                'public_key'       => $data['keys']['p256dh'],
                'auth_token'       => $data['keys']['auth'],
                'content_encoding' => $data['contentEncoding'] ?? 'aes128gcm',
                'user_agent'       => substr((string) $request->userAgent(), 0, 255),
                'last_used_at'     => now(),
            ],
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request)
    {
        $endpoint = (string) $request->input('endpoint');

        PushSubscription::where('endpoint_hash', hash('sha256', $endpoint))->delete();

        return response()->json(['ok' => true]);
    }

    protected function resolveSubscriber(Request $request)
    {
        return auth('customer')->user() ?? auth('web')->user();
    }
}
