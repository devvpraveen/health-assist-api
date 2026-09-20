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
        Schema::create('patient_health_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('medical_history')->nullable();
            $table->json('conditions')->nullable();
            $table->json('allergies')->nullable();
            $table->json('medications')->nullable();
            $table->text('previous_treatments')->nullable();
            $table->json('surgeries')->nullable();
            $table->text('family_history')->nullable();
            $table->text('lifestyle')->nullable();
            $table->text('emergency_information')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_health_profiles');
    }
};
