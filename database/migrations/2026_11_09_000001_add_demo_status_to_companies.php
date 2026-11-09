<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "demo": a throwaway company made for a visitor from the landing page, deleted after a day
        Schema::table('companies', function (Blueprint $table) {
            $table->enum('status', ['trial', 'active', 'expired', 'suspended', 'demo'])->default('trial')->change();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->enum('status', ['trial', 'active', 'expired', 'suspended'])->default('trial')->change();
        });
    }
};
