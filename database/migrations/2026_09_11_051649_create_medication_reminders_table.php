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
        Schema::create('medication_reminders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medication_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('medication_schedules')->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('channel')->default('database');
            $table->timestamp('scheduled_for');
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('pending');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_for']);
            $table->index(['tenant_id', 'patient_id']);
            $table->unique(
                ['medication_id', 'schedule_id', 'scheduled_for', 'channel'],
                'medication_reminders_dose_channel_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medication_reminders');
    }
};
