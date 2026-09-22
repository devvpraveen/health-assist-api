<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ui_translations', function (Blueprint $table) {
            $table->id();
            $table->string('group', 64)->default('website')->index();
            $table->string('key', 191);
            $table->string('locale', 16)->index();
            $table->text('value');
            $table->timestamps();

            $table->unique(['group', 'key', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ui_translations');
    }
};
