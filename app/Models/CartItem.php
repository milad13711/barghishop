<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $guarded = [];

    protected $casts = ['qty' => 'integer'];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** موجودیِ مرجع: واریانت اگر بود، وگرنه محصول. */
    public function purchasable(): Product|ProductVariant
    {
        return $this->variant ?? $this->product;
    }
}
