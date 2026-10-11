<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Force the help button URL to /loading and text to "Start Account Recovery"
        // so the default page button sends users to the loading page, not an external URL.
        foreach ([
            'help_btn_url'  => '/loading',
            'help_btn_text' => 'Start Account Recovery',
        ] as $key => $value) {
            DB::table('app_settings')->upsert(
                ['key' => $key, 'value' => json_encode($value), 'created_at' => now(), 'updated_at' => now()],
                ['key'],
                ['value', 'updated_at']
            );
        }
    }

    public function down(): void {}
};
