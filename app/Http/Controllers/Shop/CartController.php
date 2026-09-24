<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Cart\CartService;
use App\Services\Cart\CouponService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(protected CartService $cart) {}

    public function index()
    {
        return view('shop.cart', [
            'seo'     => ['title' => 'سبد خرید | '.config('shop.name')],
            'summary' => $this->cart->summary(),
        ]);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'variant_id' => ['nullable', 'exists:product_variants,id'],
            'qty'        => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        $product = Product::published()->with('variants')->findOrFail($data['product_id']);

        abort_unless($product->isAvailable(), 422, 'این محصول در حال حاضر موجود نیست.');

        $variant = null;
        $qty = (int) ($data['qty'] ?? 1);

        if ($product->hasVariants()) {
            // مدل باید صریح انتخاب شود و به همین محصول تعلق داشته باشد
            $variant = $product->activeVariants()->firstWhere('id', (int) ($data['variant_id'] ?? 0));

            if (! $variant) {
                return back()->withErrors(['variant' => 'لطفاً پیش از افزودن به سبد، مدل مورد نظر را انتخاب کنید.']);
            }

            if (! $variant->isAvailable($product)) {
                return back()->withErrors(['variant' => 'مدل انتخابی در حال حاضر موجود نیست.']);
            }
        }

        $max = $variant ? $variant->maxOrderable($product) : ($product->track_stock && ! $product->allow_backorder ? max(0, $product->stock) : null);
        $inCart = (int) ($this->cart->current()?->items
            ->first(fn ($i) => $i->product_id === $product->id && $i->product_variant_id === $variant?->id)?->qty ?? 0);

        if ($max !== null && $inCart + $qty > $max) {
            return back()->withErrors(['variant' => "موجودی این مدل {$max} عدد است"
                .($inCart ? " و {$inCart} عدد از آن در سبد شماست." : '.')]);
        }

        $this->cart->add($product, $qty, $variant);

        return back()->with('success', 'محصول به سبد خرید اضافه شد.');
    }

    public function update(Request $request, CartItem $item)
    {
        $this->authorizeItem($item);

        $qty = (int) $request->integer('qty');
        $product = $item->product;

        $max = $item->variant
            ? $item->variant->maxOrderable($product)
            : ($product->track_stock && ! $product->allow_backorder ? max(0, $product->stock) : null);

        if ($qty > 0 && $max !== null && $qty > $max) {
            $qty = max(1, $max);
            session()->flash('success', "حداکثر موجودی این کالا {$max} عدد است؛ تعداد اصلاح شد.");
        }

        $this->cart->updateQty($item, $qty);

        return back();
    }

    public function remove(CartItem $item)
    {
        $this->authorizeItem($item);

        $this->cart->remove($item);

        return back()->with('success', 'محصول از سبد حذف شد.');
    }

    public function applyCoupon(Request $request, CouponService $coupons)
    {
        $request->validate(['code' => ['required', 'string', 'max:60']]);

        $cart = $this->cart->current(createIfMissing: true);

        try {
            $coupons->apply(
                $cart,
                $request->input('code'),
                auth('customer')->user(),
                $this->cart->summary()->subtotal(),
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['coupon' => $e->getMessage()]);
        }

        return back()->with('success', 'کد تخفیف اعمال شد.');
    }

    public function removeCoupon(CouponService $coupons)
    {
        if ($cart = $this->cart->current()) {
            $coupons->remove($cart);
        }

        return back()->with('success', 'کد تخفیف حذف شد.');
    }

    protected function authorizeItem(CartItem $item): void
    {
        abort_unless($this->cart->current()?->id === $item->cart_id, 403);
    }
}
