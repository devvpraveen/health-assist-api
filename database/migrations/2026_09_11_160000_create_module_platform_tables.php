<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category')->default('clinical');
            $table->string('version')->default('1.0.0');
            $table->string('status')->default('active');
            $table->json('dependencies')->nullable();
            $table->json('optional_dependencies')->nullable();
            $table->json('conflicts')->nullable();
            $table->json('capabilities')->nullable();
            $table->json('configuration_schema')->nullable();
            $table->json('settings_schema')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('package_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->string('inclusion')->default('included'); // included | addon_eligible
            $table->timestamps();

            $table->unique(['package_id', 'module_id']);
        });

        Schema::create('package_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->string('key');
            $table->boolean('enabled')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['package_id', 'key']);
        });

        Schema::create('package_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->string('key');
            $table->bigInteger('value')->nullable(); // null = unlimited
            $table->string('period')->default('month'); // month | day | lifetime
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['package_id', 'key']);
        });

        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('packages')->restrictOnDelete();
            $table->string('status')->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('renews_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('tenant_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->string('status')->default('active'); // active | disabled
            $table->string('source')->default('package'); // package | addon | manual
            $table->json('configuration')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'module_id']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('tenant_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('key');
            $table->boolean('enabled')->default(true);
            $table->bigInteger('limit_value')->nullable();
            $table->string('period')->nullable();
            $table->string('source')->default('package'); // package | addon | override
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'key']);
        });

        Schema::create('usage_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('key');
            $table->string('period_key'); // e.g. 2026-09
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'key', 'period_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_counters');
        Schema::dropIfExists('tenant_entitlements');
        Schema::dropIfExists('tenant_modules');
        Schema::dropIfExists('tenant_subscriptions');
        Schema::dropIfExists('package_limits');
        Schema::dropIfExists('package_entitlements');
        Schema::dropIfExists('package_modules');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('modules');
    }
};
