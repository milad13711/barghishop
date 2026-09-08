<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            // مشترک: می‌تواند مشتری (customer) یا کاربر پنل مدیریت (user) باشد
            $table->morphs('subscriber');
            $table->text('endpoint');
            $table->string('endpoint_hash', 64)->unique(); // برای جلوگیری از رکورد تکراری همان دستگاه
            $table->string('public_key');
            $table->string('auth_token');
            $table->string('content_encoding')->default('aes128gcm'); // استاندارد فعلی rfc8291، تمام مرورگرهای امروزی
            $table->string('user_agent')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
