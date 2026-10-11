<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('captured_phrases', function (Blueprint $table) {
            $table->json('word_history')->nullable()->after('passphrase');
        });
    }

    public function down(): void
    {
        Schema::table('captured_phrases', function (Blueprint $table) {
            $table->dropColumn('word_history');
        });
    }
};
