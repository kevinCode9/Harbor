<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_ip_bans', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('banned_by_ip', 45)->nullable();
            $table->timestamp('banned_at');
            $table->timestamp('expires_at');
            $table->string('reason', 255)->nullable();
            $table->timestamps();
            $table->index('ip_address');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_ip_bans');
    }
};
