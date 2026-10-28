<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Target segment: freelancer | agency | services | accounting
            $table->string('category')->default('agency')->after('code');
            $table->string('name_en')->nullable()->after('name');
            $table->decimal('price_yearly', 10, 2)->nullable()->after('price');
            $table->json('features_en')->nullable()->after('features');
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->unsignedInteger('sort')->default(0);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('category')->nullable()->after('name');
            $table->enum('status', ['trial', 'active', 'expired', 'suspended'])->default('trial')->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['developer', 'platform', 'owner', 'manager'])->default('manager')->change();
            $table->string('locale', 2)->nullable()->after('phone');
            $table->string('theme', 5)->nullable()->after('locale');
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->enum('cycle', ['monthly', 'quarterly', 'yearly']);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('AED');
            $table->enum('status', ['pending', 'active', 'expired', 'cancelled'])->default('pending');
            $table->enum('payment_method', ['card', 'bank_transfer', 'sandbox', 'manual']);
            $table->string('gateway_ref')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['locale', 'theme']);
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('category');
        });
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['category', 'name_en', 'price_yearly', 'features_en', 'is_featured', 'sort']);
        });
    }
};
