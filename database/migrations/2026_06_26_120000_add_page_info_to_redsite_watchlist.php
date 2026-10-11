<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('redsite_watchlist', function (Blueprint $table) {
            $table->string('page_title', 200)->nullable()->after('red_site_reason');
            $table->string('page_desc',  300)->nullable()->after('page_title');
        });
    }

    public function down(): void
    {
        Schema::table('redsite_watchlist', function (Blueprint $table) {
            $table->dropColumn(['page_title', 'page_desc']);
        });
    }
};
