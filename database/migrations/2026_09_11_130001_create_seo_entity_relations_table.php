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
        Schema::create('seo_entity_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_entity_id')->constrained('seo_entities')->cascadeOnDelete();
            $table->foreignId('to_entity_id')->constrained('seo_entities')->cascadeOnDelete();
            $table->string('relation');
            $table->timestamps();

            $table->unique(['from_entity_id', 'to_entity_id', 'relation']);
            $table->index(['to_entity_id', 'relation']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_entity_relations');
    }
};
