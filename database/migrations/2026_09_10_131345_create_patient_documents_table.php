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
        Schema::create('patient_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('health_record_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->nullable();
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('checksum')->nullable();
            $table->string('visibility')->default('private');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('integrity_hash')->nullable();
            $table->string('integrity_status')->default('disabled');
            $table->string('integrity_provider')->nullable();
            $table->string('integrity_proof_ref')->nullable();
            $table->timestamp('integrity_attested_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'patient_id']);
            $table->index('health_record_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_documents');
    }
};
