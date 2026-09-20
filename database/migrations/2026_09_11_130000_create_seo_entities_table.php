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
        Schema::create('seo_entities', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tenant_key')->default('system');
            $table->string('type');
            $table->string('title');
            $table->string('slug');
            $table->text('summary');
            $table->longText('body')->nullable();
            $table->string('locale')->default('en');
            $table->foreignId('parent_entity_id')->nullable()->constrained('seo_entities')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('canonical_path')->nullable();
            $table->string('schema_type')->nullable();
            $table->json('structured_facts')->nullable();
            $table->text('direct_answer')->nullable();
            $table->json('citations')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_key', 'type', 'slug', 'locale']);
            $table->index(['status', 'type', 'locale']);
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_entities');
    }
};
