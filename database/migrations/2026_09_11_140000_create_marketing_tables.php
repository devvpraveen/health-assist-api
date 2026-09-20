<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribution_touches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('anonymous_id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('campaign')->nullable();
            $table->string('source')->nullable();
            $table->string('medium')->nullable();
            $table->string('content')->nullable();
            $table->string('term')->nullable();
            $table->string('referrer')->nullable();
            $table->string('landing_path')->nullable();
            $table->timestamp('captured_at');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'anonymous_id']);
            $table->index(['tenant_id', 'captured_at']);
        });

        Schema::create('marketing_leads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('source')->nullable();
            $table->string('campaign')->nullable();
            $table->string('provider_interest')->nullable();
            $table->string('status')->default('new');
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->json('attribution')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'email']);
        });

        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owner_patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->string('campaign')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('uses_count')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referrer_code_id')->constrained('referral_codes')->cascadeOnDelete();
            $table->string('referred_anonymous_id')->nullable();
            $table->foreignId('referred_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('referred_patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('converted_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['referrer_code_id', 'referred_anonymous_id']);
        });

        Schema::create('referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referral_id')->constrained('referrals')->cascadeOnDelete();
            $table->string('type')->default('none');
            $table->string('status')->default('pending');
            $table->unsignedInteger('amount_cents')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('experiments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tenant_key')->default('system');
            $table->string('key');
            $table->string('name');
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->unique(['tenant_key', 'key']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('experiment_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained('experiments')->cascadeOnDelete();
            $table->string('key');
            $table->unsignedInteger('weight')->default(50);
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['experiment_id', 'key']);
        });

        Schema::create('experiment_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained('experiments')->cascadeOnDelete();
            $table->string('anonymous_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('variant_key');
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->unique(['experiment_id', 'anonymous_id']);
            $table->index(['experiment_id', 'user_id']);
        });

        Schema::create('email_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('unsubscribe_token', 64)->unique();
            $table->timestamps();

            $table->unique(['tenant_id', 'email']);
        });

        Schema::create('email_workflows', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tenant_key')->default('system');
            $table->string('key');
            $table->string('name');
            $table->string('trigger');
            $table->boolean('is_active')->default(true);
            $table->json('steps')->nullable();
            $table->timestamps();

            $table->unique(['tenant_key', 'key']);
            $table->index(['trigger', 'is_active']);
        });

        Schema::create('email_workflow_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('workflow_id')->constrained('email_workflows')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('email_subscriptions')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->unsignedInteger('current_step')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['workflow_id', 'status']);
        });

        Schema::create('audience_segments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('name');
            $table->json('definition')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'key']);
        });

        Schema::create('push_devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token');
            $table->string('platform')->default('unknown');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'token']);
            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_devices');
        Schema::dropIfExists('audience_segments');
        Schema::dropIfExists('email_workflow_runs');
        Schema::dropIfExists('email_workflows');
        Schema::dropIfExists('email_subscriptions');
        Schema::dropIfExists('experiment_assignments');
        Schema::dropIfExists('experiment_variants');
        Schema::dropIfExists('experiments');
        Schema::dropIfExists('referral_rewards');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('referral_codes');
        Schema::dropIfExists('marketing_leads');
        Schema::dropIfExists('attribution_touches');
    }
};
