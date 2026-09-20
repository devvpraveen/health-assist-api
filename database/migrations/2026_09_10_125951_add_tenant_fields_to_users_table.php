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
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->after('id');
            $table->foreignId('tenant_id')->nullable()->after('uuid')->constrained()->nullOnDelete();
            $table->string('phone')->nullable()->after('email');
            $table->string('status')->default('active')->after('password');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');

            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn(['uuid', 'phone', 'status', 'last_login_at']);
        });
    }
};
