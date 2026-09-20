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
        Schema::create('wellness_contents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tenant_key')->default('system');
            $table->foreignId('category_id')->constrained('wellness_categories')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('summary');
            $table->longText('body');
            $table->string('locale')->default('en');
            $table->boolean('is_clinical_advice')->default(false);
            $table->string('status')->default('draft');
            $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->json('personalization_tags')->nullable();
            $table->timestamps();

            $table->unique(['tenant_key', 'slug', 'locale']);
            $table->index(['tenant_id', 'status']);
            $table->index(['category_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wellness_contents');
    }
};
