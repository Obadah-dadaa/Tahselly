<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Subscription plans (editable by the developer panel)
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('max_users')->default(3);
            $table->unsignedInteger('max_invoices')->default(500);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tenants: each customer company of the SaaS
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('trn')->nullable();
            $table->string('address')->nullable();
            $table->string('currency', 3)->default('AED');
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['trial', 'active', 'suspended'])->default('trial');
            $table->date('trial_ends_at')->nullable();
            $table->json('reminder_settings')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['developer', 'owner', 'manager'])->default('manager')->after('email');
            $table->string('phone')->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('phone');
            $table->timestamp('last_login_at')->nullable();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('trn')->nullable();
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('reminders_enabled')->default(true);
            $table->timestamps();
            $table->index(['company_id', 'name']);
        });

        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('filename');
            $table->unsignedInteger('rows_total')->default(0);
            $table->unsignedInteger('rows_imported')->default(0);
            $table->unsignedInteger('rows_failed')->default(0);
            $table->json('errors')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number');
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('AED');
            $table->enum('status', ['unpaid', 'partial', 'paid', 'cancelled'])->default('unpaid');
            $table->text('description')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('last_reminded_at')->nullable();
            $table->unsignedInteger('reminders_count')->default(0);
            $table->timestamps();
            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'status', 'due_date']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('paid_on');
            $table->string('method')->default('bank_transfer');
            $table->string('reference')->nullable();
            $table->timestamps();
        });

        // Message templates; company_id NULL = global default managed by developer
        Schema::create('reminder_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('stage', ['before_due', 'on_due', 'overdue', 'final']);
            $table->enum('channel', ['email', 'whatsapp']);
            $table->string('subject')->nullable();
            $table->text('body');
            $table->timestamps();
            $table->unique(['company_id', 'stage', 'channel']);
        });

        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('channel', ['email', 'whatsapp']);
            $table->enum('stage', ['before_due', 'on_due', 'overdue', 'final', 'manual']);
            $table->integer('day_offset')->nullable(); // days relative to due date (negative = before)
            $table->enum('status', ['sent', 'failed', 'opened'])->default('sent');
            $table->boolean('automatic')->default(true);
            $table->string('recipient')->nullable();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'created_at']);
            $table->index(['invoice_id', 'stage', 'day_offset']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('description');
            $table->string('ip', 45)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'created_at']);
        });

        // Key/value settings & feature flags editable from the developer panel
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string|bool|int|text
            $table->string('group')->default('general');
            $table->string('label');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('reminder_templates');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('import_batches');
        Schema::dropIfExists('clients');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['role', 'phone', 'is_active', 'last_login_at']);
        });
        Schema::dropIfExists('companies');
        Schema::dropIfExists('plans');
    }
};
