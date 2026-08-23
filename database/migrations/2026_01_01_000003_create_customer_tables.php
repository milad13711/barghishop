<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->default('#0A84FF');
            $table->unsignedInteger('min_points')->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('points_multiplier', 4, 2)->default(1);
            $table->json('benefits')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('mobile', 11)->unique();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->foreignId('price_tier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('loyalty_level_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('wallet_balance')->default(0);
            $table->unsignedInteger('points')->default(0);
            $table->string('company')->nullable();
            $table->string('national_id', 20)->nullable();
            $table->string('economic_code', 30)->nullable();
            $table->string('wholesale_status')->default('none'); // none | pending | approved | rejected
            $table->text('wholesale_note')->nullable();
            $table->timestamp('wholesale_requested_at')->nullable();
            $table->timestamp('mobile_verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('accepts_sms')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            $table->index('wholesale_status');
        });

        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('mobile', 11)->index();
            $table->string('code_hash');
            $table->string('purpose')->default('login');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('ip', 45)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('receiver_name');
            $table->string('receiver_mobile', 11);
            $table->foreignId('province_id')->nullable();
            $table->foreignId('city_id')->nullable();
            $table->string('province_name')->nullable();
            $table->string('city_name')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('line', 500);
            $table->string('plaque', 20)->nullable();
            $table->string('unit', 20)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('loyalty_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->integer('points'); // مثبت = کسب، منفی = مصرف
            $table->string('type');    // earn | spend | adjust | expire
            $table->string('reason')->nullable();
            $table->nullableMorphs('source');
            $table->timestamps();
            $table->index(['customer_id', 'created_at']);
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('amount');  // ریال، مثبت/منفی
            $table->unsignedBigInteger('balance_after');
            $table->string('type');        // deposit | withdraw | order | refund | adjust
            $table->string('reason')->nullable();
            $table->nullableMorphs('source');
            $table->timestamps();
        });

        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['customer_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('loyalty_ledger');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('otp_codes');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('loyalty_levels');
    }
};
