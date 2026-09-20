<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversation_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('conversation_ref')->index();
            $table->string('agent')->index();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('structured_json');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['tenant_id', 'conversation_ref', 'agent'], 'ai_conv_mem_unique');
        });

        Schema::create('ai_workflow_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('agent')->index();
            $table->string('intent')->index();
            $table->json('tool_chain');
            $table->decimal('outcome_score', 5, 2)->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failure_count')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'agent', 'intent'], 'ai_workflow_mem_unique');
        });

        Schema::create('ai_learning_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('agent')->index();
            $table->string('signal_type')->index();
            $table->string('source'); // patient|clinic|doctor|system
            $table->foreignId('usage_record_id')->nullable()->constrained('ai_usage_records')->nullOnDelete();
            $table->foreignId('audit_log_id')->nullable()->constrained('ai_audit_logs')->nullOnDelete();
            $table->nullableMorphs('subject');
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'agent', 'signal_type']);
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('ai_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('agent')->index();
            $table->string('source'); // patient|clinic|doctor|system
            $table->unsignedTinyInteger('rating')->nullable(); // 1-5
            $table->boolean('helpful')->nullable();
            $table->text('comment')->nullable();
            $table->longText('original_output')->nullable();
            $table->longText('corrected_output')->nullable();
            $table->foreignId('usage_record_id')->nullable()->constrained('ai_usage_records')->nullOnDelete();
            $table->foreignId('audit_log_id')->nullable()->constrained('ai_audit_logs')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'agent']);
        });

        Schema::create('ai_learning_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('agent')->index();
            $table->string('status')->default('draft')->index();
            // draft|pending_review|approved|rejected|queued_for_dataset
            $table->text('input_redacted')->nullable();
            $table->longText('original_output')->nullable();
            $table->longText('corrected_output')->nullable();
            $table->boolean('deidentified')->default(false);
            $table->foreignId('feedback_id')->nullable()->constrained('ai_feedback')->nullOnDelete();
            $table->foreignId('signal_id')->nullable()->constrained('ai_learning_signals')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'agent', 'status']);
        });

        Schema::create('ai_training_datasets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('key');
            $table->string('name');
            $table->string('version');
            $table->string('agent')->nullable()->index();
            $table->string('status')->default('draft'); // draft|ready|exported|archived
            $table->unsignedInteger('item_count')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['key', 'version']);
        });

        Schema::create('ai_training_dataset_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_id')->constrained('ai_training_datasets')->cascadeOnDelete();
            $table->foreignId('candidate_id')->nullable()->constrained('ai_learning_candidates')->nullOnDelete();
            $table->string('agent');
            $table->text('prompt_redacted')->nullable();
            $table->longText('completion_redacted')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_eval_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('status')->default('completed');
            $table->decimal('overall_score', 5, 4)->nullable();
            $table->decimal('groundedness', 5, 4)->nullable();
            $table->decimal('safety', 5, 4)->nullable();
            $table->decimal('helpfulness', 5, 4)->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('source_type'); // guideline|clinic_doc|faq|seo
            $table->string('status')->default('draft')->index(); // draft|approved|archived
            $table->longText('body');
            $table->json('meta')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::table('ai_model_versions', function (Blueprint $table) {
            $table->string('lifecycle_status')->default('base')->after('is_default');
            // base|candidate|evaluating|approved|canary|production|retired
            $table->json('lifecycle_meta')->nullable()->after('lifecycle_status');
        });
    }

    public function down(): void
    {
        Schema::table('ai_model_versions', function (Blueprint $table) {
            $table->dropColumn(['lifecycle_status', 'lifecycle_meta']);
        });

        Schema::dropIfExists('ai_knowledge_documents');
        Schema::dropIfExists('ai_eval_runs');
        Schema::dropIfExists('ai_training_dataset_items');
        Schema::dropIfExists('ai_training_datasets');
        Schema::dropIfExists('ai_learning_candidates');
        Schema::dropIfExists('ai_feedback');
        Schema::dropIfExists('ai_learning_signals');
        Schema::dropIfExists('ai_workflow_memories');
        Schema::dropIfExists('ai_conversation_memories');
    }
};
