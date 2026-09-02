<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_locale_is_persian(): void
    {
        $this->assertSame('fa', config('app.locale'));
    }

    public function test_pages_do_not_leak_raw_translation_keys(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        foreach (['/', route('shop.index'), route('cart.index'), route('blog.index')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertDoesNotMatchRegularExpression(
                '/\bshop\.[a-z_]+/',
                $html,
                "کلید ترجمه خام در $url رندر شده است.",
            );
        }
    }

    public function test_no_gregorian_dates_are_rendered_to_users(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $admin = \App\Models\User::firstOrFail();

        $urls = [
            '/', route('blog.index'), route('shop.index'),
            route('admin.dashboard'), route('admin.orders.index'), route('admin.coupons.index'),
        ];

        foreach ($urls as $url) {
            $html = $this->actingAs($admin, 'web')->get($url)->assertOk()->getContent();

            // اسکیمای JSON-LD طبق استاندارد باید ISO میلادی بماند و کاربر نمی‌بیندش
            $visible = preg_replace('#<script type="application/ld\+json">.*?</script>#s', '', $html);

            $this->assertDoesNotMatchRegularExpression(
                '#\b20\d{2}[-/]\d{2}[-/]\d{2}\b#',
                $visible,
                "تاریخ میلادی در $url نمایش داده شده است.",
            );
        }
    }

    public function test_validation_messages_are_translated(): void
    {
        $this->post(route('pages.contact.store'), ['name' => '', 'body' => ''])
            ->assertSessionHasErrors('name');

        $message = session('errors')->first('name');

        $this->assertStringNotContainsString('validation.', $message);
        $this->assertStringContainsString('الزامی', $message);
    }
}
