<?php

/**
 * Parallel Activity V2 worker. Invoked only by ActivityV2InnoDbConcurrencyTest.
 *
 * argv: job json
 * job keys: mysql, action, token, doc, stay, page, type, element, value, path, count
 */
$raw = $argv[1] ?? '{}';
if (is_file($raw)) {
    $raw = file_get_contents($raw) ?: '{}';
}
$job = json_decode($raw, true);
if (!is_array($job)) {
    fwrite(STDERR, "bad job\n");
    exit(2);
}

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

config([
    'database.default' => 'mysql',
    'database.connections.mysql.host' => $job['mysql']['host'],
    'database.connections.mysql.port' => $job['mysql']['port'],
    'database.connections.mysql.database' => $job['mysql']['database'],
    'database.connections.mysql.username' => $job['mysql']['username'],
    'database.connections.mysql.password' => $job['mysql']['password'],
    'database.connections.mysql.engine' => 'InnoDB',
    'session.driver' => 'array',
    'cache.default' => 'array',
]);
Illuminate\Support\Facades\DB::purge('mysql');
Illuminate\Support\Facades\DB::reconnect('mysql');

$http = $app->make(Illuminate\Contracts\Http\Kernel::class);

$send = function (string $method, string $uri, array $params = []) use ($http, $job, $app) {
    $server = [];
    if (isset($job['request_time_float'])) {
        $server['REQUEST_TIME_FLOAT'] = (float) $job['request_time_float'];
    }
    $request = Illuminate\Http\Request::create($uri, $method, $params, [], [], $server);
    $encrypter = $app->make('encrypter');
    $raw = Illuminate\Cookie\CookieValuePrefix::create('liveled_token', $encrypter->getKey()).$job['token'];
    $request->cookies->set('liveled_token', $encrypter->encrypt($raw, false));
    $request->headers->set('Accept', 'application/json');
    return $http->handle($request);
};

$out = ['ok' => true, 'codes' => [], 'docs' => [], 'bodies' => []];
$action = $job['action'] ?? '';
$count = (int) ($job['count'] ?? 1);

try {
    if (!empty($job['sleep_ms'])) {
        usleep((int) $job['sleep_ms'] * 1000);
    }
    if ($action === 'get') {
        for ($i = 0; $i < $count; $i++) {
            $res = $send('GET', $job['path'] ?? '/ledger/3');
            $out['codes'][] = $res->getStatusCode();
            if (preg_match("/window\\._llDocId='([a-f0-9]{32})'/", $res->getContent(), $m)) {
                $out['docs'][] = $m[1];
            }
        }
    } elseif ($action === 'event') {
        for ($i = 0; $i < $count; $i++) {
            $res = $send('POST', '/api/log', [
                'type' => $job['type'],
                'page' => $job['page'] ?? null,
                'element' => $job['element'] ?? null,
                'value' => $job['value'] ?? null,
                'doc' => $job['doc'] ?? null,
                'stay' => $job['stay'] ?? null,
            ]);
            $out['codes'][] = $res->getStatusCode();
            $out['bodies'][] = json_decode($res->getContent(), true);
        }
    } elseif ($action === 'hold_then_event') {
        $pdo = Illuminate\Support\Facades\DB::connection('mysql')->getPdo();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT id FROM user_sessions WHERE session_token = ? FOR UPDATE');
        $stmt->execute([$job['token']]);
        $stmt->fetch();
        usleep((int) ($job['hold_ms'] ?? 200) * 1000);
        $pdo->commit();
        $res = $send('POST', '/api/log', [
            'type' => $job['type'] ?? 'page_visit',
            'page' => $job['page'] ?? 'ledger_3',
            'element' => $job['element'] ?? 'page',
            'doc' => $job['doc'] ?? '',
        ]);
        $out['codes'][] = $res->getStatusCode();
        $out['bodies'][] = json_decode($res->getContent(), true);
    } else {
        $out['ok'] = false;
        $out['error'] = 'unknown action';
    }
} catch (Throwable $e) {
    $out['ok'] = false;
    $out['error'] = $e->getMessage();
}

echo json_encode($out);
