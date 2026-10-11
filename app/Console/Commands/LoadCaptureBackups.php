<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CapturedPhrase;

class LoadCaptureBackups extends Command
{
    protected $signature = 'capture:load-backups';
    protected $description = 'Load captured phrases from JSON backup files into database';

    public function handle()
    {
        $backupDir = storage_path('captured_phrases_backup');
        if (!is_dir($backupDir)) {
            $this->info('No backup directory found.');
            return;
        }

        $files = glob($backupDir . '/*.json');
        $loaded = 0;
        $skipped = 0;

        foreach ($files as $file) {
            try {
                $data = json_decode(file_get_contents($file), true);
                if (!$data || !isset($data['id'])) continue;

                // Check if already exists
                if (CapturedPhrase::where('id', $data['id'])->exists()) {
                    $skipped++;
                    continue;
                }

                // Insert into DB
                CapturedPhrase::create($data);
                $loaded++;

                // Delete backup file after successful load
                @unlink($file);
            } catch (\Exception $e) {
                $this->error("Failed to load {$file}: " . $e->getMessage());
            }
        }

        $this->info("Loaded $loaded phrases from backups, skipped $skipped duplicates");
    }
}
