<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0=Sun .. 6=Sat
            $table->time('opens_at');
            $table->time('closes_at');
            $table->time('break_starts_at')->nullable();
            $table->time('break_ends_at')->nullable();
            $table->unsignedSmallInteger('shift_index')->default(1);
            $table->boolean('is_closed')->default(false);
            $table->string('label')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'clinic_id', 'day_of_week']);
        });

        Schema::create('working_hour_exceptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->boolean('is_closed')->default(true);
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'clinic_id', 'date']);
        });

        Schema::create('clinic_notices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_public')->default(true);
            $table->string('status')->default('draft'); // draft|published|archived
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('staff_invites', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('role_slug')->default('provider');
            $table->string('designation')->nullable();
            $table->string('token', 64)->unique();
            $table->string('status')->default('pending'); // pending|accepted|revoked
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('organization_reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('author_name')->nullable();
            $table->string('status')->default('pending'); // pending|published|hidden
            $table->text('response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_reviews');
        Schema::dropIfExists('staff_invites');
        Schema::dropIfExists('clinic_notices');
        Schema::dropIfExists('working_hour_exceptions');
        Schema::dropIfExists('working_hours');
    }
};
