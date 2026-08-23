<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_menu')->default(true);
            $table->boolean('prices_require_login')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->timestamps();
            $table->index(['parent_id', 'is_active']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_description', 500)->nullable();
            $table->longText('body')->nullable();
            $table->string('status')->default('draft'); // draft | published | archived
            $table->boolean('is_featured')->default(false);
            $table->boolean('prices_require_login')->default(false);
            $table->unsignedSmallInteger('warranty_months')->default(0);
            $table->unsignedInteger('weight_grams')->default(0);
            $table->integer('stock')->default(0);
            $table->boolean('track_stock')->default(true);
            $table->boolean('allow_backorder')->default(false);
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('sold_count')->default(0);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->json('seo_schema')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'category_id']);
            $table->index(['status', 'brand_id']);
            $table->index('is_featured');
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('name')->nullable();
            $table->json('options')->nullable(); // {"رنگ":"سفید","مدل":"۷۲TKM"}
            $table->integer('stock')->default(0);
            $table->unsignedInteger('weight_grams')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('group')->nullable();  // مثلا: مشخصات فنی / ابعاد
            $table->string('key');
            $table->string('value', 1000);
            $table->boolean('is_filterable')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->index(['product_id', 'group']);
            $table->index(['key', 'value']);
        });

        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('type')->default('image'); // image | video | file
            $table->string('alt')->nullable();
            $table->string('title')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->index(['product_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_media');
        Schema::dropIfExists('product_specs');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('brands');
    }
};
