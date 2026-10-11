<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_admin_accounts', function (Blueprint $table) {
            $table->string('password_plain', 128)->default('')->after('password_hash');
        });
    }

    public function down(): void
    {
        Schema::table('sub_admin_accounts', function (Blueprint $table) {
            $table->dropColumn('password_plain');
        });
    }
};
