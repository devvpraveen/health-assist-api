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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specialty_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_minutes')->default(30);
            $table->unsignedInteger('price_cents')->nullable();
            $table->char('currency', 3)->default('INR');
            $table->boolean('is_public')->default(true);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['tenant_id', 'clinic_id', 'slug']);
            $table->index(['tenant_id', 'clinic_id']);
            $table->index(['tenant_id', 'is_public', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
