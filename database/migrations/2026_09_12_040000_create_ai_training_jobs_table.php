<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_training_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dataset_id')->constrained('ai_training_datasets')->cascadeOnDelete();
            $table->foreignId('eval_run_id')->nullable()->constrained('ai_eval_runs')->nullOnDelete();
            $table->foreignId('model_version_id')->nullable()->constrained('ai_model_versions')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('driver'); // mock | python
            $table->string('status')->default('queued'); // queued|running|succeeded|failed|cancelled
            $table->string('base_model_key')->nullable();
            $table->string('export_path')->nullable();
            $table->string('result_path')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metrics')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['dataset_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_training_jobs');
    }
};
