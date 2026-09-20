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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedInteger('duration_minutes');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('booked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('rescheduled_from_appointment_id')
                ->nullable()
                ->constrained('appointments')
                ->nullOnDelete();
            $table->timestamp('checked_in_at')->nullable();
            $table->unsignedInteger('queue_number')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'provider_id', 'starts_at']);
            $table->index(['tenant_id', 'clinic_id', 'starts_at']);
            $table->index(['tenant_id', 'patient_id', 'starts_at']);
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
