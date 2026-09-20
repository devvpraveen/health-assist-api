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
        Schema::create('report_analyses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('patient_documents')->cascadeOnDelete();
            $table->foreignId('health_record_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('queued');
            $table->string('report_type')->nullable();
            $table->string('ocr_provider')->nullable();
            $table->longText('ocr_raw_text')->nullable();
            $table->json('extracted_facts')->nullable();
            $table->json('interpretation')->nullable();
            $table->json('reference_range_findings')->nullable();
            $table->string('safety_level')->nullable();
            $table->foreignId('safety_assessment_id')->nullable()->constrained('safety_assessments')->nullOnDelete();
            $table->text('patient_explanation')->nullable();
            $table->text('clinician_notes')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('ai_usage_record_id')->nullable()->constrained('ai_usage_records')->nullOnDelete();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'patient_id']);
            $table->index(['tenant_id', 'status']);
            $table->index('document_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_analyses');
    }
};
