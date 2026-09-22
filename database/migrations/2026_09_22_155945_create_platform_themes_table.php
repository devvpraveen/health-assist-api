<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_themes', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 32);
            $table->unsignedInteger('version')->default(1);
            $table->json('config');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique('channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_themes');
    }
};
