<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('anonymous_id')->nullable()->index();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('claimed_at')->nullable();
            $table->foreignId('claimed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'expires_at']);
        });

        Schema::table('health_guide_conversations', function (Blueprint $table) {
            $table->foreignId('guest_session_id')
                ->nullable()
                ->after('user_id')
                ->constrained('guest_sessions')
                ->nullOnDelete();
        });

        Schema::table('guest_sessions', function (Blueprint $table) {
            $table->foreignId('conversation_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained('health_guide_conversations')
                ->nullOnDelete();
        });

        Schema::create('otp_challenges', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('channel');
            $table->string('destination')->index();
            $table->string('code_hash');
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['channel', 'destination', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('guest_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('conversation_id');
        });
        Schema::table('health_guide_conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('guest_session_id');
        });
        Schema::dropIfExists('otp_challenges');
        Schema::dropIfExists('guest_sessions');
    }
};
