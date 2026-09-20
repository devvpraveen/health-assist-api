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
        Schema::create('health_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('recorded_at')->nullable();
            $table->string('status')->default('active');
            $table->string('integrity_hash')->nullable();
            $table->string('integrity_status')->default('disabled');
            $table->string('integrity_provider')->nullable();
            $table->string('integrity_proof_ref')->nullable();
            $table->timestamp('integrity_attested_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'patient_id']);
            $table->index(['tenant_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_records');
    }
};
