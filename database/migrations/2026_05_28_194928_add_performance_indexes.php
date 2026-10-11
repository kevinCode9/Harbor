<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admin_commands', function (Blueprint $table) {
            $table->index(['executed', 'executed_at'], 'idx_commands_executed_at');
        });

        Schema::table('user_events', function (Blueprint $table) {
            $table->index('created_at', 'idx_events_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('admin_commands', function (Blueprint $table) {
            $table->dropIndex('idx_commands_executed_at');
        });

        Schema::table('user_events', function (Blueprint $table) {
            $table->dropIndex('idx_events_created_at');
        });
    }
};
