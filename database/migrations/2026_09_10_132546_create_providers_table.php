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
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('display_name')->nullable();
            $table->string('type')->default('doctor');
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();
            $table->unsignedInteger('years_experience')->nullable();
            $table->json('languages')->nullable();
            $table->string('license_number')->nullable();
            $table->string('verification_status')->default('pending');
            $table->boolean('is_public')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['tenant_id', 'clinic_id']);
            $table->index(['tenant_id', 'is_public']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
