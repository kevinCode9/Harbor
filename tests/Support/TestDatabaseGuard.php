<?php

namespace Tests\Support;

use RuntimeException;

/**
 * Fail-closed test-database kill-switch.
 *
 * Does not open or write C:\laragon\www\liveled\database\database.sqlite.
 * Path decisions are string-based after normalization.
 */
final class TestDatabaseGuard
{
    public const PROTECTED_SQLITE = 'C:\\laragon\\www\\liveled\\database\\database.sqlite';

    public const SANDBOX_SQLITE = 'C:\\laragon\\www\\liveled-ai\\database\\sandbox.sqlite';

    public const PROTECTED_ROOT = 'C:\\laragon\\www\\liveled';

    public const SANDBOX_ROOT = 'C:\\laragon\\www\\liveled-ai';

    public const BACKUPS_ROOT = 'C:\\laragon\\backups\\liveled';

    public static function isAllowedSqliteDatabase(?string $database): bool
    {
        if ($database === null) {
            return false;
        }

        $raw = trim($database);
        if ($raw === '') {
            return false;
        }

        if ($raw === ':memory:') {
            return true;
        }

        $norm = self::normalizePath($raw);

        if (self::samePath($norm, self::normalizePath(self::PROTECTED_SQLITE))) {
            return false;
        }

        if (self::isUnder($norm, self::normalizePath(self::BACKUPS_ROOT))) {
            return false;
        }

        $protectedRoot = self::normalizePath(self::PROTECTED_ROOT);

        // liveled-ai starts with liveled — require the sandbox database dir first.
        $sandboxDatabaseDir = self::normalizePath(self::SANDBOX_ROOT.'\\database');
        if (self::isUnder($norm, $sandboxDatabaseDir)) {
            return true;
        }

        if (self::isUnder($norm, $protectedRoot)) {
            return false;
        }

        return false;
    }

    public static function assertSafeSqliteDatabase(?string $database, string $context = 'test database'): void
    {
        if (! self::isAllowedSqliteDatabase($database)) {
            $shown = $database === null || $database === '' ? '(empty/ambiguous)' : $database;
            throw new RuntimeException(
                'TEST DATABASE KILL-SWITCH: refused '.$context.' '.$shown
                .'. Destructive tests may use only :memory: or an approved sandbox sqlite'
                .' ( :memory: or a sqlite file under C:\\laragon\\www\\liveled-ai\\database\\ ).'
                .' Protected '.self::PROTECTED_SQLITE.' is forbidden.'
            );
        }
    }

    /**
     * Process-level check that MUST run before Laravel boots or opens a DB.
     * Fail-closed unless APP_ENV=testing and DB_DATABASE is an approved disposable value.
     */
    public static function assertSafeTestProcessOrFailClosed(): void
    {
        $appEnv = self::readProcessValue('APP_ENV');
        if (! is_string($appEnv) || strtolower(trim($appEnv)) !== 'testing') {
            throw new RuntimeException(
                'TEST DATABASE KILL-SWITCH: refused process before Laravel boot'
                .' because APP_ENV is not explicitly testing (got '
                .(is_string($appEnv) && $appEnv !== '' ? $appEnv : 'empty/absent')
                .'). phpunit.xml safety values are required; --no-configuration is rejected.'
            );
        }

        $database = self::readProcessValue('DB_DATABASE');
        self::assertSafeSqliteDatabase(
            is_string($database) ? $database : null,
            'process DB_DATABASE before Laravel boot'
        );
    }

    /**
     * @deprecated use assertSafeTestProcessOrFailClosed
     */
    public static function assertPhpunitEnvironmentOrFailClosed(): void
    {
        self::assertSafeTestProcessOrFailClosed();
    }

    private static function readProcessValue(string $key): ?string
    {
        $fromEnv = getenv($key);
        if ($fromEnv !== false && is_string($fromEnv)) {
            return $fromEnv;
        }
        if (isset($_ENV[$key]) && is_string($_ENV[$key])) {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && is_string($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        return null;
    }

    /**
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     */
    public static function assertLaravelApplicationIsSafe($app): void
    {
        $connections = $app['config']->get('database.connections', []);
        if (! is_array($connections) || $connections === []) {
            throw new RuntimeException('TEST DATABASE KILL-SWITCH: no database connections configured (fail-closed).');
        }

        foreach ($connections as $name => $config) {
            if (! is_array($config)) {
                continue;
            }
            $driver = $config['driver'] ?? null;
            if ($driver !== 'sqlite') {
                continue;
            }
            $database = $config['database'] ?? null;
            self::assertSafeSqliteDatabase(
                is_string($database) ? $database : null,
                'sqlite connection '.$name
            );
        }
    }

    public static function normalizePath(string $path): string
    {
        $path = trim($path, " \t\n\r\0\x0B\"'");
        $path = str_replace('/', '\\', $path);
        $path = preg_replace('/\\\\+/', '\\', $path) ?? $path;
        $path = rtrim($path, '\\');

        if (preg_match('/^([A-Za-z]):\\\/', $path, $m)) {
            $path = strtoupper($m[1]).substr($path, 1);
        }

        return $path;
    }

    public static function samePath(string $a, string $b): bool
    {
        return strcasecmp($a, $b) === 0;
    }

    public static function isUnder(string $path, string $root): bool
    {
        $path = rtrim($path, '\\');
        $root = rtrim($root, '\\');
        if (strcasecmp($path, $root) === 0) {
            return true;
        }

        return strncasecmp($path, $root.'\\', strlen($root) + 1) === 0;
    }
}
