<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->default('general');
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string | int | bool | json | text
            $table->boolean('is_secret')->default(false);
            $table->timestamps();
        });

        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('limo');
            $table->string('mobile', 11)->index();
            $table->string('event')->nullable(); // otp | order_placed | shipped ...
            $table->text('body')->nullable();
            $table->string('pattern_code')->nullable();
            $table->string('status')->default('queued'); // queued | sent | failed
            $table->string('message_id')->nullable();
            $table->text('error')->nullable();
            $table->unsignedInteger('cost')->default(0);
            $table->nullableMorphs('source');
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('position')->default('home_hero'); // home_hero | home_strip | sidebar
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('image');
            $table->string('image_mobile')->nullable();
            $table->string('link')->nullable();
            $table->string('cta_label')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['position', 'is_active']);
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mobile', 11)->nullable();
            $table->string('email')->nullable();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->string('status')->default('new');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('settings');
    }
};
