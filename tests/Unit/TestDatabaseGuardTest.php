<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\TestDatabaseGuard;

class TestDatabaseGuardTest extends TestCase
{
    public function test_allows_memory(): void
    {
        $this->assertTrue(TestDatabaseGuard::isAllowedSqliteDatabase(':memory:'));
        TestDatabaseGuard::assertSafeSqliteDatabase(':memory:');
    }

    public function test_allows_sandbox_sqlite_slash_variants(): void
    {
        $this->assertTrue(TestDatabaseGuard::isAllowedSqliteDatabase(
            'C:/laragon/www/liveled-ai/database/sandbox.sqlite'
        ));
        $this->assertTrue(TestDatabaseGuard::isAllowedSqliteDatabase(
            'C:\\laragon\\www\\liveled-ai\\database\\sandbox.sqlite'
        ));
    }

    public function test_allows_disposable_path_under_sandbox_database(): void
    {
        $this->assertTrue(TestDatabaseGuard::isAllowedSqliteDatabase(
            'C:\\laragon\\www\\liveled-ai\\database\\canary-guard.sqlite'
        ));
    }

    public function test_refuses_protected_sqlite_by_path_string_without_opening_it(): void
    {
        $protected = TestDatabaseGuard::PROTECTED_SQLITE;
        $this->assertFalse(TestDatabaseGuard::isAllowedSqliteDatabase($protected));
        $this->assertFalse(TestDatabaseGuard::isAllowedSqliteDatabase(
            'C:/laragon/www/liveled/database/database.sqlite'
        ));
        $this->assertFalse(TestDatabaseGuard::isAllowedSqliteDatabase(
            'c:\\LARAGON\\WWW\\LIVELED\\database\\database.sqlite'
        ));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TEST DATABASE KILL-SWITCH');
        TestDatabaseGuard::assertSafeSqliteDatabase($protected);
    }

    public function test_refuses_empty_ambiguous_backups_and_outside_sandbox_db_dir(): void
    {
        $this->assertFalse(TestDatabaseGuard::isAllowedSqliteDatabase(null));
        $this->assertFalse(TestDatabaseGuard::isAllowedSqliteDatabase(''));
        $this->assertFalse(TestDatabaseGuard::isAllowedSqliteDatabase(
            'C:\\laragon\\backups\\liveled\\CHECKPOINT-S2PRE-20260901-101933\\sqlite\\database.sqlite'
        ));
        $this->assertFalse(TestDatabaseGuard::isAllowedSqliteDatabase(
            'C:\\laragon\\www\\liveled-ai\\storage\\app\\random.sqlite'
        ));
    }

    public function test_does_not_treat_liveled_ai_as_protected_root(): void
    {
        $this->assertFalse(TestDatabaseGuard::isUnder(
            TestDatabaseGuard::normalizePath('C:\\laragon\\www\\liveled-ai\\database\\sandbox.sqlite'),
            TestDatabaseGuard::normalizePath(TestDatabaseGuard::PROTECTED_ROOT)
        ));
    }

    public function test_missing_testing_env_is_rejected_before_laravel_boot(): void
    {
        $prevApp = getenv('APP_ENV');
        $prevDb = getenv('DB_DATABASE');
        putenv('APP_ENV');
        putenv('DB_DATABASE');
        unset($_ENV['APP_ENV'], $_SERVER['APP_ENV'], $_ENV['DB_DATABASE'], $_SERVER['DB_DATABASE']);

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('before Laravel boot');
            TestDatabaseGuard::assertSafeTestProcessOrFailClosed();
        } finally {
            if ($prevApp !== false) {
                putenv('APP_ENV='.$prevApp);
                $_ENV['APP_ENV'] = $prevApp;
            }
            if ($prevDb !== false) {
                putenv('DB_DATABASE='.$prevDb);
                $_ENV['DB_DATABASE'] = $prevDb;
            }
        }
    }
}
