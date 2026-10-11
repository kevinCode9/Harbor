<?php
declare(strict_types=1);
$milady = 'C:/laragon/www/milady/.env';
$prot = 'C:/laragon/www/liveled/.env';
$skip = ['APP_KEY' => true, 'DB_DATABASE' => true, 'APP_URL' => true];
function parseEnv(string $path): array {
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
        if ($line === '' || str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $out[trim($k)] = $v;
    }
    return $out;
}
$m = parseEnv($milady);
$p = parseEnv($prot);
$added = [];
$raw = file_get_contents($milady);
foreach ($p as $k => $v) {
    if (isset($skip[$k])) {
        continue;
    }
    if (!array_key_exists($k, $m) || trim((string) $m[$k]) === '') {
        if (preg_match('/^'.preg_quote($k, '/').'=/m', $raw)) {
            $raw = preg_replace('/^'.preg_quote($k, '/').'=.*$/m', $k.'='.$v, $raw, 1);
        } else {
            $raw = rtrim($raw)."\n".$k.'='.$v."\n";
        }
        $added[] = $k;
    }
}
file_put_contents($milady, $raw);
echo 'merged_keys='.implode(',', $added).PHP_EOL;
echo 'kept_APP_URL='.(preg_match('/^APP_URL=http:\/\/milady\.test/m', $raw) ? 'yes' : 'NO').PHP_EOL;
echo 'kept_milady_sqlite='.(preg_match('#^DB_DATABASE=C:/laragon/www/milady/#m', $raw) ? 'yes' : 'NO').PHP_EOL;
