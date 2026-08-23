<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return view('admin.settings.index', [
            'settings' => Setting::orderBy('group')->get()->groupBy('group'),
        ]);
    }

    public function update(Request $request)
    {
        foreach ((array) $request->input('settings', []) as $key => $value) {
            Setting::where('key', $key)->update(['value' => $value]);
        }

        Setting::query()->first()?->touch(); // پاک‌سازی کش

        cache()->forget('settings.all');

        return back()->with('success', 'تنظیمات ذخیره شد.');
    }
}
