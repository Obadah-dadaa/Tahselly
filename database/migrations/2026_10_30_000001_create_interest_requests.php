<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Interested in subscribing" requests: the beta's replacement for plans and checkout.
     */
    public function up(): void
    {
        Schema::create('interest_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->string('company_name', 190)->nullable();
            $table->string('email', 190);
            $table->string('phone', 30)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('new');
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interest_requests');
    }
};
