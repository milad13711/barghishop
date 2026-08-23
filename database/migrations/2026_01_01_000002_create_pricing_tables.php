<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // سطوح قیمت: retail | wholesale_1 | wholesale_2 ...
        Schema::create('price_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_wholesale')->default(false);
            $table->boolean('requires_approval')->default(false);
            $table->decimal('fallback_discount_percent', 5, 2)->default(0);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // قیمت پلی‌مورفیک: محصول یا واریانت، به تفکیک سطح و حداقل تعداد (پلکانی)
        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->morphs('priceable');
            $table->foreignId('price_tier_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('min_qty')->default(1);
            $table->unsignedBigInteger('amount');            // ریال، عدد صحیح
            $table->unsignedBigInteger('compare_at')->nullable(); // قیمت قبل از تخفیف
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['priceable_type', 'priceable_id', 'price_tier_id', 'min_qty'], 'prices_unique_tier_qty');
            $table->index(['price_tier_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prices');
        Schema::dropIfExists('price_tiers');
    }
};
