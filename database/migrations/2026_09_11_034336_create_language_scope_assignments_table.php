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
        Schema::create('language_scope_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->string('scope', 64);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['language_id', 'scope']);
            $table->index(['scope', 'is_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('language_scope_assignments');
    }
};
