<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_admin_accounts', function (Blueprint $table) {
            $table->dropColumn(['password_hash', 'password_plain']);
            $table->string('access_key', 64)->unique()->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('sub_admin_accounts', function (Blueprint $table) {
            $table->dropColumn('access_key');
            $table->string('password_hash', 255)->default('');
            $table->string('password_plain', 128)->default('');
        });
    }
};