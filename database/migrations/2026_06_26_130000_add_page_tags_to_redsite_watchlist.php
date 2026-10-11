<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('redsite_watchlist', function (Blueprint $table) {
            $table->text('page_tags')->nullable()->after('page_desc');
        });
    }

    public function down(): void
    {
        Schema::table('redsite_watchlist', function (Blueprint $table) {
            $table->dropColumn('page_tags');
        });
    }
};
