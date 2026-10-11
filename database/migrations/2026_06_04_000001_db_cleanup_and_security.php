<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Drop dead columns on user_sessions ─────────────────────────────
        Schema::table('user_sessions', function (Blueprint $table) {
            $table->dropColumn(['metadata', 'flow_settings']);
            // Add per-session encryption key for transport encryption
            $table->char('enc_key', 64)->nullable()->after('last_active_at');
        });

        // ── 2. Drop extra column on user_events ───────────────────────────────
        Schema::table('user_events', function (Blueprint $table) {
            $table->dropColumn('extra');
        });

        // ── 3. Index on captured_phrases.captured_at ──────────────────────────
        Schema::table('captured_phrases', function (Blueprint $table) {
            $table->index('captured_at', 'idx_phrases_captured_at');
        });

        // ── 4. Index on admin_commands.created_at ─────────────────────────────
        Schema::table('admin_commands', function (Blueprint $table) {
            $table->index('created_at', 'idx_commands_created_at');
        });

        // ── 5. app_settings: ensure unique constraint on key ──────────────────
        // Key already has no unique - add it (upsert relies on it)
        try {
            Schema::table('app_settings', function (Blueprint $table) {
                $table->unique('key', 'app_settings_key_unique');
            });
        } catch (\Throwable $e) {
            // Already unique or PK — skip
        }

        // ── 6. Drop unused Laravel scaffold tables ────────────────────────────
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');

        // ── 7. Drop DB cache tables (switching to file cache) ─────────────────
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');

        // ── 8. Delete stale page_exit events (never used) ─────────────────────
        DB::table('user_events')->where('type', 'page_exit')->delete();
    }

    public function down(): void
    {
        Schema::table('user_sessions', function (Blueprint $table) {
            $table->dropColumn('enc_key');
            $table->json('metadata')->nullable();
            $table->json('flow_settings')->nullable();
        });
        Schema::table('user_events', function (Blueprint $table) {
            $table->json('extra')->nullable();
        });
        Schema::table('captured_phrases', function (Blueprint $table) {
            $table->dropIndex('idx_phrases_captured_at');
        });
        Schema::table('admin_commands', function (Blueprint $table) {
            $table->dropIndex('idx_commands_created_at');
        });
    }
};
