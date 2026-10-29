<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per anonymous visitor per day on the public pages (no IP or cookie is stored).
     */
    public function up(): void
    {
        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->char('visitor', 64);
            $table->string('path', 190);
            $table->string('referrer', 190)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unique(['day', 'visitor']);
            $table->index('day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visits');
    }
};
