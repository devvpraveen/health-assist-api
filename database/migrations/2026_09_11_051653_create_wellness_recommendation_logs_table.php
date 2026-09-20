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
        Schema::create('wellness_recommendation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_id')->constrained('wellness_contents')->cascadeOnDelete();
            $table->decimal('score', 8, 2);
            $table->json('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'patient_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wellness_recommendation_logs');
    }
};
