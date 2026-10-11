<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redsite_watchlist', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique();
            $table->string('label')->nullable();
            $table->string('overall_verdict')->default('unknown'); // dangerous|suspicious|clean|unknown
            $table->string('cf_verdict')->default('unknown');
            $table->json('cf_tags')->nullable();
            $table->float('cf_risk_score')->nullable();
            $table->string('google_verdict')->default('unknown');
            $table->json('google_details')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redsite_watchlist');
    }
};
