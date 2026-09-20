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
        Schema::create('report_analysis_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_analysis_id')->constrained('report_analyses')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('kind');
            $table->json('payload')->nullable();
            $table->longText('payload_text')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source')->default('system');
            $table->timestamps();

            $table->unique(['report_analysis_id', 'version', 'kind']);
            $table->index(['report_analysis_id', 'kind']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_analysis_versions');
    }
};
