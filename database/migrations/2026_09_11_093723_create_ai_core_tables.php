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
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('driver');
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('ai_providers')->cascadeOnDelete();
            $table->string('key');
            $table->string('name');
            $table->json('task_types')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['provider_id', 'key']);
            $table->index('is_active');
        });

        Schema::create('ai_model_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_id')->constrained('ai_models')->cascadeOnDelete();
            $table->string('version');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['model_id', 'version']);
        });

        Schema::create('ai_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_prompt_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_id')->constrained('ai_prompts')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->text('system_prompt');
            $table->text('template')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->unique(['prompt_id', 'version']);
            $table->index(['prompt_id', 'status']);
        });

        Schema::create('ai_usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('agent');
            $table->string('feature');
            $table->string('model');
            $table->string('model_version')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->string('status')->default('success');
            $table->unsignedInteger('estimated_cost_cents')->nullable();
            $table->uuid('request_id');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'agent']);
            $table->unique('request_id');
        });

        Schema::create('ai_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('agent');
            $table->string('model');
            $table->string('model_version')->nullable();
            $table->string('prompt_key')->nullable();
            $table->unsignedInteger('prompt_version')->nullable();
            $table->string('input_type')->default('text');
            $table->text('input_redacted')->nullable();
            $table->text('output_redacted')->nullable();
            $table->string('review_status')->default('not_required');
            $table->foreignId('reviewer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('usage_record_id')->nullable()->constrained('ai_usage_records')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'agent']);
            $table->index(['tenant_id', 'review_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_audit_logs');
        Schema::dropIfExists('ai_usage_records');
        Schema::dropIfExists('ai_prompt_versions');
        Schema::dropIfExists('ai_prompts');
        Schema::dropIfExists('ai_model_versions');
        Schema::dropIfExists('ai_models');
        Schema::dropIfExists('ai_providers');
    }
};
