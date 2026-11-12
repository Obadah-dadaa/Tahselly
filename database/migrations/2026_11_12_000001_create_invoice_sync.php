<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Daily sync with the company's own accounting system: its scheduled "open invoices" report is emailed
        // to a private address (one feed per company) and applied as a snapshot.
        Schema::create('sync_feeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('token', 16)->unique();
            $table->text('senders')->nullable();
            $table->json('mapping')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_received_at')->nullable();
            $table->timestamp('stale_alerted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sync_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('source', 10);
            $table->string('sender')->nullable();
            $table->string('filename');
            $table->string('path')->nullable();
            $table->string('status', 12);
            $table->unsignedInteger('rows_total')->default(0);
            $table->unsignedInteger('added')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->unsignedInteger('closed')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->text('message')->nullable();
            $table->json('headers')->nullable();
            $table->json('errors')->nullable();
            $table->timestamps();
        });

        Schema::table('invoices', function (Blueprint $table) {
            // Set while the invoice appears in the synced report; only these are closed when they drop out of it
            $table->timestamp('synced_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('synced_at');
        });
        Schema::dropIfExists('sync_runs');
        Schema::dropIfExists('sync_feeds');
    }
};
