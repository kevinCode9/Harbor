<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LiveledSettings
{
    private static array $defaults = [
        'site_online'    => true,
        'pow_enabled'    => true,
    ];

    public static function get(): array
    {
        try {
            $rows = DB::table('app_settings')->get();
            $data = [];
            foreach ($rows as $row) {
                $decoded = json_decode($row->value, true);
                $data[$row->key] = ($decoded !== null) ? $decoded : $row->value;
            }
            return array_merge(self::$defaults, $data);
        } catch (\Exception $e) {
            // Table may not exist yet (first deploy before migration runs)
            return self::$defaults;
        }
    }

    public static function set(string $key, mixed $value): void
    {
        try {
            DB::table('app_settings')->upsert(
                [
                    'key'        => $key,
                    'value'      => json_encode($value),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                ['key'],
                ['value', 'updated_at']
            );
        } catch (\Exception $e) {
            Log::error('LiveledSettings::set failed: ' . $e->getMessage());
        }
    }

    // ── Entry page (stored in a plain text file — no DB, no upsert, always works) ──

    private static string $entryPageFile = '';

    private static function epFile(): string
    {
        if (!self::$entryPageFile) {
            self::$entryPageFile = storage_path('app/entry_page.txt');
        }
        return self::$entryPageFile;
    }

    public static function getEntryPage(): string
    {
        $v = @file_get_contents(self::epFile());
        $v = $v ? trim($v) : '';
        if ($v === '' || $v === '/need-assistance') {
            return '';
        }
        return $v;
    }

    public static function setEntryPage(string $url): void
    {
        $dir = dirname(self::epFile());
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        file_put_contents(self::epFile(), $url, LOCK_EX);
    }

    /** Bulk save — wrapped in transaction so all keys are written atomically */
    public static function save(array $settings): void
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($settings) {
                $rows = [];
                foreach ($settings as $key => $value) {
                    $rows[] = ['key' => $key, 'value' => json_encode($value), 'created_at' => now(), 'updated_at' => now()];
                }
                \Illuminate\Support\Facades\DB::table('app_settings')
                    ->upsert($rows, ['key'], ['value', 'updated_at']);
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('LiveledSettings::save failed: ' . $e->getMessage());
        }
    }
}
