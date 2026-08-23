<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Cart\CartService;
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

        $product = Product::published()->findOrFail($data['product_id']);

        abort_unless($product->isAvailable(), 422, 'این محصول در حال حاضر موجود نیست.');

        $this->cart->add(
            $product,
            (int) ($data['qty'] ?? 1),
            isset($data['variant_id']) ? ProductVariant::find($data['variant_id']) : null,
        );

        return back()->with('success', 'محصول به سبد خرید اضافه شد.');
    }

    public function update(Request $request, CartItem $item)
    {
        $this->authorizeItem($item);

        $this->cart->updateQty($item, (int) $request->integer('qty'));

        return back();
    }

    public function remove(CartItem $item)
    {
        $this->authorizeItem($item);

        $this->cart->remove($item);

        return back()->with('success', 'محصول از سبد حذف شد.');
    }

    protected function authorizeItem(CartItem $item): void
    {
        abort_unless($this->cart->current()?->id === $item->cart_id, 403);
    }
}
