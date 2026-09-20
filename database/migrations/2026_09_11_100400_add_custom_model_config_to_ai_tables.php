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
        Schema::table('ai_models', function (Blueprint $table) {
            $table->json('config')->nullable()->after('task_types');
            $table->boolean('is_custom')->default(false)->after('config');
            $table->string('external_model_id')->nullable()->after('is_custom');
        });

        Schema::table('ai_model_versions', function (Blueprint $table) {
            $table->json('config')->nullable()->after('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            $table->dropColumn(['config', 'is_custom', 'external_model_id']);
        });

        Schema::table('ai_model_versions', function (Blueprint $table) {
            $table->dropColumn('config');
        });
    }
};
