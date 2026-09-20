<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('instance_name');
            $table->string('phone_number')->nullable();
            $table->string('status')->default('disconnected'); // disconnected|connecting|connected|error
            $table->text('webhook_secret')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'instance_name']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('remote_phone');
            $table->string('remote_jid')->nullable();
            $table->string('state')->default('ai'); // ai|waiting_human|human|closed
            $table->foreignId('health_guide_conversation_id')->nullable()
                ->constrained('health_guide_conversations')->nullOnDelete();
            $table->text('handoff_summary')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'remote_phone']);
            $table->index(['tenant_id', 'state']);
            $table->index(['tenant_id', 'patient_id']);
        });

        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('conversation_id')->constrained('whatsapp_conversations')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->string('direction'); // inbound|outbound
            $table->string('type')->default('text'); // text|image|document|audio|location|other
            $table->text('body')->nullable();
            $table->string('evolution_message_id')->nullable();
            $table->json('payload')->nullable();
            $table->string('status')->default('received'); // received|sent|delivered|failed
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['tenant_id', 'account_id']);
            $table->index(['evolution_message_id']);
        });

        Schema::create('whatsapp_handoffs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('whatsapp_conversations')->cascadeOnDelete();
            $table->string('requested_by'); // patient|system|staff
            $table->string('status')->default('open'); // open|accepted|resolved|cancelled
            $table->string('urgency')->nullable();
            $table->text('summary')->nullable();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['conversation_id', 'status']);
        });

        Schema::create('whatsapp_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('account_id')->nullable()->constrained('whatsapp_accounts')->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['account_id', 'created_at']);
            $table->index(['processed_at']);
        });

        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->text('body');
            $table->string('language')->default('en');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'key', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
        Schema::dropIfExists('whatsapp_webhook_events');
        Schema::dropIfExists('whatsapp_handoffs');
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('whatsapp_conversations');
        Schema::dropIfExists('whatsapp_accounts');
    }
};
