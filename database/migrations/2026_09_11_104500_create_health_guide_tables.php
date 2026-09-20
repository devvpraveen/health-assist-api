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
        Schema::create('safety_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('severity'); // emergency|urgent|clinician_review|routine
            $table->string('pattern_type'); // keyword|regex
            $table->text('pattern');
            $table->string('action'); // escalate_emergency|escalate_urgent|require_clinician_review|continue
            $table->text('message_template');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'severity']);
        });

        Schema::create('health_guide_conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active'); // active|completed|escalated|abandoned
            $table->string('locale')->nullable();
            $table->json('structured_state')->nullable();
            $table->string('safety_level')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'patient_id']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('health_guide_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('conversation_id')->constrained('health_guide_conversations')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('role'); // user|assistant|system
            $table->text('content');
            $table->json('meta')->nullable();
            $table->foreignId('ai_usage_record_id')->nullable()->constrained('ai_usage_records')->nullOnDelete();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['tenant_id', 'conversation_id']);
        });

        Schema::create('safety_assessments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('health_guide_conversations')->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('input_category')->nullable();
            $table->text('input_redacted')->nullable();
            $table->string('level'); // emergency|urgent|clinician_review|routine|insufficient_information
            $table->json('matched_rule_codes')->nullable();
            $table->string('action')->nullable();
            $table->json('rule_version_snapshot')->nullable();
            $table->string('model_hint')->nullable();
            $table->foreignId('reviewer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'conversation_id']);
            $table->index(['tenant_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('safety_assessments');
        Schema::dropIfExists('health_guide_messages');
        Schema::dropIfExists('health_guide_conversations');
        Schema::dropIfExists('safety_rules');
    }
};
