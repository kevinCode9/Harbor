<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('redsite_watchlist', function (Blueprint $table) {
            $table->boolean('is_reachable')->nullable()->after('last_checked_at');
            $table->integer('http_status')->nullable()->after('is_reachable');
            $table->boolean('is_red_sited')->nullable()->after('http_status');
            $table->string('red_site_reason')->nullable()->after('is_red_sited');
        });
    }

    public function down(): void
    {
        Schema::table('redsite_watchlist', function (Blueprint $table) {
            $table->dropColumn(['is_reachable', 'http_status', 'is_red_sited', 'red_site_reason']);
        });
    }
};
