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
        Schema::create('billing_tax_rates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->unsignedInteger('rate_bps');
            $table->boolean('is_inclusive')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('billing_packages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedInteger('session_count');
            $table->unsignedInteger('validity_days')->nullable();
            $table->unsignedInteger('price_cents');
            $table->char('currency', 3)->default('INR');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('issued_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('number')->nullable();
            $table->string('status')->default('draft');
            $table->char('currency', 3)->default('INR');
            $table->unsignedInteger('subtotal_cents')->default(0);
            $table->unsignedInteger('discount_cents')->default(0);
            $table->unsignedInteger('tax_cents')->default(0);
            $table->unsignedInteger('total_cents')->default(0);
            $table->unsignedInteger('amount_paid_cents')->default(0);
            $table->unsignedInteger('amount_due_cents')->default(0);
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'number']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'patient_id']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedInteger('discount_cents')->default(0);
            $table->unsignedInteger('tax_rate_bps')->nullable();
            $table->unsignedInteger('tax_cents')->default(0);
            $table->unsignedInteger('line_total_cents')->default(0);
            $table->nullableMorphs('reference');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'invoice_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->char('currency', 3)->default('INR');
            $table->string('method');
            $table->string('gateway')->default('manual');
            $table->string('gateway_reference')->nullable();
            $table->string('status')->default('pending');
            $table->string('idempotency_key')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'idempotency_key']);
            $table->index(['tenant_id', 'invoice_id']);
            $table->index(['tenant_id', 'patient_id']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('number');
            $table->unsignedInteger('amount_cents');
            $table->char('currency', 3)->default('INR');
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->unique(['tenant_id', 'number']);
            $table->index(['tenant_id', 'patient_id']);
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->char('currency', 3)->default('INR');
            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->string('gateway')->default('manual');
            $table->string('gateway_reference')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'payment_id']);
            $table->index(['tenant_id', 'invoice_id']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('patient_packages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('billing_packages')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('sessions_total');
            $table->unsignedInteger('sessions_used')->default(0);
            $table->unsignedInteger('sessions_remaining');
            $table->timestamp('purchased_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('status')->default('active');
            $table->string('payment_status')->default('pending');
            $table->timestamps();

            $table->index(['tenant_id', 'patient_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'payment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_packages');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('billing_packages');
        Schema::dropIfExists('billing_tax_rates');
    }
};
