<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->unsignedBigInteger('price_cents')->default(0)->after('status');
            $table->string('currency', 8)->default('INR')->after('price_cents');
            $table->unsignedInteger('validity_days')->nullable()->after('currency');
            $table->boolean('is_purchasable')->default(true)->after('validity_days');
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->unsignedBigInteger('price_cents')->default(0)->after('is_active');
            $table->string('currency', 8)->default('INR')->after('price_cents');
            $table->unsignedInteger('validity_days')->nullable()->after('currency');
        });

        Schema::table('tenant_modules', function (Blueprint $table) {
            $table->unsignedBigInteger('paid_cents')->nullable()->after('source');
            $table->string('currency', 8)->nullable()->after('paid_cents');
            $table->timestamp('starts_at')->nullable()->after('activated_at');
            $table->timestamp('expires_at')->nullable()->after('starts_at');
            $table->json('purchase_meta')->nullable()->after('configuration');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_modules', function (Blueprint $table) {
            $table->dropColumn(['paid_cents', 'currency', 'starts_at', 'expires_at', 'purchase_meta']);
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['price_cents', 'currency', 'validity_days']);
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn(['price_cents', 'currency', 'validity_days', 'is_purchasable']);
        });
    }
};
