<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->string('default_locale', 16)->default('en')->after('meta');
            $table->json('supported_locales')->nullable()->after('default_locale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn(['default_locale', 'supported_locales']);
        });
    }
};
