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
        Schema::create('medication_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medication_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('medication_schedules')->nullOnDelete();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('logged_at');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->foreignId('logged_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'medication_id']);
            $table->index(['tenant_id', 'patient_id']);
            $table->unique(['medication_id', 'scheduled_for', 'status'], 'medication_logs_dose_status_unique');
            $table->unique(['medication_id', 'idempotency_key'], 'medication_logs_idempotency_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medication_logs');
    }
};
