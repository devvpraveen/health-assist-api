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
        Schema::create('appointment_reminders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->string('channel')->default('database');
            $table->dateTime('scheduled_for');
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('pending');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_for']);
            $table->index(['tenant_id', 'appointment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_reminders');
    }
};
