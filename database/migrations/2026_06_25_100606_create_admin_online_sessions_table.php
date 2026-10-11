<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_online_sessions', function (Blueprint $table) {
            $table->string('session_id', 128)->primary();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_seen_at');
            $table->timestamp('logged_in_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_online_sessions');
    }
};
