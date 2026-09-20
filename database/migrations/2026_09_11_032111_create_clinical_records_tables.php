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
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tenant_key')->default('system');
            $table->string('name');
            $table->string('slug');
            $table->string('category')->nullable();
            $table->text('instructions')->nullable();
            $table->text('contraindications')->nullable();
            $table->unsignedInteger('default_duration_seconds')->nullable();
            $table->unsignedInteger('default_sets')->nullable();
            $table->unsignedInteger('default_reps')->nullable();
            $table->string('difficulty')->nullable();
            $table->string('media_url')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['tenant_key', 'slug']);
            $table->index('tenant_id');
            $table->index('status');
        });

        Schema::create('clinical_assessments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('template_key')->nullable();
            $table->dateTime('assessed_at');
            $table->text('chief_complaint')->nullable();
            $table->json('findings')->nullable();
            $table->text('summary')->nullable();
            $table->string('status')->default('draft');
            $table->string('source')->default('clinician');
            $table->foreignId('authored_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'patient_id']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('clinical_soap_notes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assessment_id')->nullable()->constrained('clinical_assessments')->nullOnDelete();
            $table->text('subjective')->nullable();
            $table->text('objective')->nullable();
            $table->text('assessment')->nullable();
            $table->text('plan')->nullable();
            $table->dateTime('session_date');
            $table->string('status')->default('draft');
            $table->string('source')->default('clinician');
            $table->foreignId('authored_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'patient_id']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('clinical_treatment_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('diagnosis_summary')->nullable();
            $table->json('goals')->nullable();
            $table->string('frequency')->nullable();
            $table->unsignedInteger('duration_weeks')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('reassessment_date')->nullable();
            $table->text('home_program_notes')->nullable();
            $table->string('status')->default('draft');
            $table->string('source')->default('clinician');
            $table->foreignId('authored_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'patient_id']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('clinical_treatment_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_plan_id')->nullable()->constrained('clinical_treatment_plans')->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('session_at');
            $table->string('modality')->nullable();
            $table->text('interventions')->nullable();
            $table->text('patient_response')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamps();

            $table->index(['tenant_id', 'patient_id']);
            $table->index(['tenant_id', 'treatment_plan_id']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('clinical_progress_notes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('treatment_plan_id')->nullable()->constrained('clinical_treatment_plans')->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('noted_at');
            $table->text('note');
            $table->json('measurements')->nullable();
            $table->string('status')->default('draft');
            $table->string('source')->default('clinician');
            $table->foreignId('authored_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'patient_id']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('clinical_discharge_summaries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('treatment_plan_id')->nullable()->constrained('clinical_treatment_plans')->nullOnDelete();
            $table->dateTime('discharged_at');
            $table->text('reason')->nullable();
            $table->text('initial_condition')->nullable();
            $table->text('treatment_provided')->nullable();
            $table->text('progress_summary')->nullable();
            $table->text('current_status')->nullable();
            $table->text('home_program')->nullable();
            $table->text('follow_up')->nullable();
            $table->text('referral')->nullable();
            $table->string('status')->default('draft');
            $table->string('source')->default('clinician');
            $table->foreignId('authored_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'patient_id']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('clinical_exercise_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_plan_id')->nullable()->constrained('clinical_treatment_plans')->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'patient_id']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('clinical_exercise_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_plan_id')->constrained('clinical_exercise_plans')->cascadeOnDelete();
            $table->foreignId('exercise_id')->nullable()->constrained('exercises')->nullOnDelete();
            $table->string('custom_name')->nullable();
            $table->string('frequency')->nullable();
            $table->unsignedInteger('sets')->nullable();
            $table->unsignedInteger('reps')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('exercise_plan_id');
        });

        Schema::create('clinical_exercise_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_plan_item_id')->constrained('clinical_exercise_plan_items')->cascadeOnDelete();
            $table->dateTime('performed_at');
            $table->string('result');
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('pain_score')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'patient_id']);
            $table->index('exercise_plan_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinical_exercise_logs');
        Schema::dropIfExists('clinical_exercise_plan_items');
        Schema::dropIfExists('clinical_exercise_plans');
        Schema::dropIfExists('clinical_discharge_summaries');
        Schema::dropIfExists('clinical_progress_notes');
        Schema::dropIfExists('clinical_treatment_sessions');
        Schema::dropIfExists('clinical_treatment_plans');
        Schema::dropIfExists('clinical_soap_notes');
        Schema::dropIfExists('clinical_assessments');
        Schema::dropIfExists('exercises');
    }
};
