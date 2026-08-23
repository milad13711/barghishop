<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title')->nullable();
            $table->string('type')->default('percent'); // percent | fixed | free_shipping
            $table->unsignedBigInteger('value')->default(0);
            $table->unsignedBigInteger('max_discount')->nullable();
            $table->unsignedBigInteger('min_total')->default(0);
            $table->json('tier_scope')->nullable();     // null = همه سطوح
            $table->json('category_scope')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_customer')->default(1);
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_token')->nullable()->index();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('abandoned_notified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('qty')->default(1);
            $table->timestamps();
            $table->unique(['cart_id', 'product_id', 'product_variant_id'], 'cart_items_unique');
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('price_tier_id')->nullable()->constrained()->nullOnDelete();
            // pending_payment | paid | processing | shipped | delivered | cancelled | refunded
            $table->string('status')->default('pending_payment');
            $table->string('payment_method')->default('online'); // online | cod | wallet | bank_transfer
            $table->string('payment_status')->default('unpaid'); // unpaid | paid | refunded | partial

            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount_total')->default(0);
            $table->unsignedBigInteger('coupon_discount')->default(0);
            $table->unsignedBigInteger('loyalty_discount')->default(0);
            $table->unsignedBigInteger('wallet_used')->default(0);
            $table->unsignedBigInteger('shipping_cost')->default(0);
            $table->unsignedBigInteger('cod_fee')->default(0);
            $table->unsignedBigInteger('tax_total')->default(0);
            $table->unsignedBigInteger('grand_total')->default(0);
            $table->unsignedInteger('total_weight_grams')->default(0);

            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('coupon_code')->nullable();
            $table->foreignId('shipping_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('shipping_method_name')->nullable();
            $table->json('address_snapshot')->nullable();
            $table->string('tracking_code')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->unsignedInteger('points_earned')->default(0);
            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'created_at']);
            $table->index(['customer_id', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name_snapshot');
            $table->string('sku_snapshot')->nullable();
            $table->json('options_snapshot')->nullable();
            $table->unsignedInteger('qty');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('unit_compare_at')->nullable();
            $table->unsignedBigInteger('line_total');
            $table->unsignedInteger('weight_grams')->default(0);
            $table->timestamps();
        });

        Schema::create('order_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('actor_type')->nullable(); // admin | customer | system
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'created_at']);
        });

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('discount_amount');
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway')->default('zarinpal');
            $table->unsignedBigInteger('amount');
            $table->string('authority')->nullable()->index();
            $table->string('ref_id')->nullable()->index();
            $table->string('card_pan')->nullable();
            $table->string('status')->default('pending'); // pending | success | failed | refunded
            $table->string('message')->nullable();
            $table->json('raw')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('order_status_logs');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('coupons');
    }
};
