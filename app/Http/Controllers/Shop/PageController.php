<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\PriceTier;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function about()
    {
        return view('pages.about', [
            'seo' => [
                'title'       => 'درباره برقی‌شاپ | نماینده فروش سیماران',
                'description' => 'برقی‌شاپ، عرضه‌کننده تخصصی آیفون تصویری و تجهیزات برق ساختمان سیماران با قیمت نمایندگی.',
            ],
        ]);
    }

    public function contact()
    {
        return view('pages.contact', [
            'seo' => ['title' => 'تماس با ما | '.config('shop.name')],
        ]);
    }

    public function storeContact(Request $request)
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:120'],
            'mobile'  => ['nullable', 'string', 'max:20'],
            'email'   => ['nullable', 'email', 'max:150'],
            'subject' => ['nullable', 'string', 'max:200'],
            'body'    => ['required', 'string', 'max:2000'],
        ]);

        ContactMessage::create($data);

        return back()->with('success', 'پیام شما ثبت شد. به‌زودی با شما تماس می‌گیریم.');
    }

    public function wholesale()
    {
        return view('pages.wholesale', [
            'seo' => [
                'title'       => 'خرید عمده و همکاری | '.config('shop.name'),
                'description' => 'شرایط همکاری، قیمت‌گذاری پلکانی و ثبت‌نام همکاران و نمایندگان فروش تجهیزات سیماران.',
            ],
            'tiers' => PriceTier::where('is_wholesale', true)->orderBy('sort')->get(),
        ]);
    }
}
