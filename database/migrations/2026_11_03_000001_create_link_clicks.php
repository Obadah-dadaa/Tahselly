<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Opens of the personal links in outreach emails (tahselly.com/go/{ref})
        Schema::create('link_clicks', function (Blueprint $table) {
            $table->id();
            $table->string('ref', 80)->index();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_clicks');
    }
};
