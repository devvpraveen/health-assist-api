<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('slug');
            $table->string('phone', 50)->nullable()->after('description');
            $table->string('email')->nullable()->after('phone');
            $table->string('website')->nullable()->after('email');
            $table->string('city', 100)->nullable()->after('website');
            $table->string('state', 100)->nullable()->after('city');
            $table->string('country', 100)->nullable()->after('state');
            $table->json('meta')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn([
                'description',
                'phone',
                'email',
                'website',
                'city',
                'state',
                'country',
                'meta',
            ]);
        });
    }
};
