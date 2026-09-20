<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        Schema::create('platform_integrations', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('category');
            $table->string('status')->default('standby');
            $table->string('driver')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('security_alerts', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('severity');
            $table->string('title');
            $table->text('detail')->nullable();
            $table->string('status')->default('open');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_alerts');
        Schema::dropIfExists('platform_integrations');
        Schema::dropIfExists('platform_settings');
    }
};
