<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->enum('status', ['scheduled', 'sent', 'failed', 'opened', 'cancelled'])->default('sent')->change();
            $table->timestamp('scheduled_for')->nullable()->after('status');
            $table->index(['status', 'scheduled_for']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('reminders_paused')->default(false)->after('reminders_count');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('reminders_paused');
        });
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropIndex(['status', 'scheduled_for']);
            $table->dropColumn('scheduled_for');
        });
    }
};
