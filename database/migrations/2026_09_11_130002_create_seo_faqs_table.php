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
        Schema::create('seo_faqs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tenant_key')->default('system');
            $table->foreignId('entity_id')->nullable()->constrained('seo_entities')->nullOnDelete();
            $table->string('question');
            $table->text('answer');
            $table->string('locale')->default('en');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->index(['tenant_key', 'locale', 'status']);
            $table->index(['entity_id', 'status', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_faqs');
    }
};
