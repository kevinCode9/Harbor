<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('user_sessions', 'document_id')) {
            Schema::table('user_sessions', function (Blueprint $table) {
                $table->char('document_id', 32)->nullable()->after('enc_key');
            });
        }

        $driver = Schema::getConnection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach (['user_sessions', 'user_events'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            $row = DB::selectOne(
                'SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$table]
            );
            $engine = is_object($row) ? ($row->engine ?? null) : null;
            if (is_string($engine) && strtolower($engine) === 'innodb') {
                continue;
            }
            DB::statement('ALTER TABLE `'.$table.'` ENGINE=InnoDB');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('user_sessions', 'document_id')) {
            Schema::table('user_sessions', function (Blueprint $table) {
                $table->dropColumn('document_id');
            });
        }
    }
};
