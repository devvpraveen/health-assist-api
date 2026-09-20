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
        Schema::create('specialties', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            // Non-null scope key so SQLite/MySQL unique works when tenant_id is null (system catalog).
            $table->string('tenant_key')->default('system');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['tenant_key', 'slug']);
            $table->index('tenant_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specialties');
    }
};
