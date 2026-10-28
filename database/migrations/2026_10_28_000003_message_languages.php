<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('reminder_templates', 'locale')) {
            Schema::table('reminder_templates', function (Blueprint $table) {
                $table->string('locale', 2)->default('ar')->after('channel');
            });
        }
        // Create the new index first: the company_id foreign key needs an index to lean on
        Schema::table('reminder_templates', function (Blueprint $table) {
            $table->unique(['company_id', 'stage', 'channel', 'locale']);
        });
        Schema::table('reminder_templates', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'stage', 'channel']);
        });

        if (! Schema::hasColumn('clients', 'locale')) {
            Schema::table('clients', function (Blueprint $table) {
                // Language reminders are written in for this client; null = the company default
                $table->string('locale', 2)->nullable()->after('reminders_enabled');
            });
        }
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
        Schema::table('reminder_templates', function (Blueprint $table) {
            $table->unique(['company_id', 'stage', 'channel']);
        });
        Schema::table('reminder_templates', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'stage', 'channel', 'locale']);
            $table->dropColumn('locale');
        });
    }
};
