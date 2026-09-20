<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_workflows', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('owner_key'); // platform | tenant:{id}
            $table->string('key');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger'); // appointment.completed, appointment.cancelled, …
            $table->string('module_key')->nullable();
            $table->string('status')->default('draft'); // draft|published|archived
            $table->boolean('is_active')->default(false);
            $table->unsignedBigInteger('active_version_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['owner_key', 'key']);
            $table->index(['trigger', 'is_active', 'status']);
            $table->index(['tenant_id', 'trigger']);
        });

        Schema::create('automation_workflow_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('workflow_id')->constrained('automation_workflows')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('status')->default('draft');
            $table->string('label')->nullable();
            /** steps: condition | delay | action (allowlisted only) */
            $table->json('steps');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['workflow_id', 'version']);
        });

        Schema::table('automation_workflows', function (Blueprint $table) {
            $table->foreign('active_version_id')
                ->references('id')
                ->on('automation_workflow_versions')
                ->nullOnDelete();
        });

        Schema::create('automation_workflow_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_id')->constrained('automation_workflows')->cascadeOnDelete();
            $table->foreignId('workflow_version_id')->constrained('automation_workflow_versions')->cascadeOnDelete();
            $table->string('trigger');
            $table->string('status')->default('pending'); // pending|running|waiting|completed|failed|cancelled|skipped
            $table->unsignedInteger('current_step')->default(0);
            $table->json('context')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['workflow_id', 'status']);
        });

        Schema::create('automation_workflow_run_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('automation_workflow_runs')->cascadeOnDelete();
            $table->unsignedInteger('step_index');
            $table->string('step_type');
            $table->string('status'); // completed|skipped|failed|waiting
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['run_id', 'step_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_workflow_run_steps');
        Schema::dropIfExists('automation_workflow_runs');

        Schema::table('automation_workflows', function (Blueprint $table) {
            $table->dropForeign(['active_version_id']);
        });
        Schema::dropIfExists('automation_workflow_versions');
        Schema::dropIfExists('automation_workflows');
    }
};
