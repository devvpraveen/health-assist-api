<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('owner_key'); // platform | tenant:{id}
            $table->string('key');
            $table->string('type'); // assessment, soap, notification, …
            $table->string('module_key')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // draft|published|archived
            $table->unsignedBigInteger('active_version_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['owner_key', 'key']);
            $table->index(['tenant_id', 'type']);
            $table->index(['module_key', 'status']);
        });

        Schema::create('template_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('template_id')->constrained('templates')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('status')->default('draft'); // draft|published|archived
            $table->string('label')->nullable();
            /** @see App\Services\Templates\TemplateSchema — sections + fields + body */
            $table->json('schema');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['template_id', 'version']);
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->foreign('active_version_id')
                ->references('id')
                ->on('template_versions')
                ->nullOnDelete();
        });

        Schema::create('form_definitions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('owner_key');
            $table->string('key');
            $table->string('type');
            $table->string('module_key')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('active_version_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['owner_key', 'key']);
            $table->index(['tenant_id', 'type']);
            $table->index(['module_key', 'status']);
        });

        Schema::create('form_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('form_definition_id')->constrained('form_definitions')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('status')->default('draft');
            $table->string('label')->nullable();
            /** @see App\Services\Forms\FormSchema — fields with validation / visibility */
            $table->json('schema');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['form_definition_id', 'version']);
        });

        Schema::table('form_definitions', function (Blueprint $table) {
            $table->foreign('active_version_id')
                ->references('id')
                ->on('form_versions')
                ->nullOnDelete();
        });

        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_definition_id')->constrained('form_definitions')->cascadeOnDelete();
            $table->foreignId('form_version_id')->constrained('form_versions')->cascadeOnDelete();
            $table->nullableMorphs('subject');
            $table->json('payload');
            $table->json('meta')->nullable();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'form_definition_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');

        Schema::table('form_definitions', function (Blueprint $table) {
            $table->dropForeign(['active_version_id']);
        });
        Schema::dropIfExists('form_versions');
        Schema::dropIfExists('form_definitions');

        Schema::table('templates', function (Blueprint $table) {
            $table->dropForeign(['active_version_id']);
        });
        Schema::dropIfExists('template_versions');
        Schema::dropIfExists('templates');
    }
};
