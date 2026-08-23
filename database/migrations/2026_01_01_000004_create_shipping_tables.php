<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedTinyInteger('zone')->default(2); // ۱=تهران، ۲=شهرستان، ۳=دورافتاده
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->index(['province_id', 'name']);
        });

        // روش ارسال: پست پیشتاز، تیپاکس، پیک تهران، حضوری
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('logo')->nullable();
            // pricing_mode: flat = نرخ ثابت | weight = بر اساس استان و وزن | pickup = تحویل حضوری (رایگان)
            $table->string('pricing_mode')->default('flat');
            $table->unsignedBigInteger('flat_cost')->default(0);
            $table->unsignedBigInteger('free_over')->nullable();   // بالای این مبلغ رایگان
            $table->unsignedBigInteger('max_weight_grams')->nullable();
            // فهرست استان‌های مجاز؛ null یعنی همه استان‌ها. برای روش‌های flat کاربرد دارد.
            $table->json('allowed_province_ids')->nullable();
            $table->boolean('cod_enabled')->default(false);        // پرداخت در محل
            $table->unsignedBigInteger('cod_fee')->default(0);
            $table->unsignedTinyInteger('min_days')->default(2);
            $table->unsignedTinyInteger('max_days')->default(5);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // نرخ‌های وزنی/استانی. province_id = null یعنی نرخ پیش‌فرض همه استان‌ها.
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_method_id')->constrained()->cascadeOnDelete();
            $table->foreignId('province_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('base_cost')->default(0);        // هزینه پایه
            $table->unsignedInteger('base_weight_grams')->default(1000); // وزن پوشش‌داده‌شده با هزینه پایه
            $table->unsignedBigInteger('per_kg_cost')->default(0);       // به ازای هر کیلو اضافه
            $table->unsignedBigInteger('free_over')->nullable();         // بازنویسی سقف رایگان برای این استان
            $table->unsignedTinyInteger('extra_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['shipping_method_id', 'province_id'], 'shipping_rates_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('provinces');
    }
};
