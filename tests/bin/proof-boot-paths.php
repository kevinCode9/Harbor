<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$db = (string) config('database.connections.sqlite.database');
$norm = strtolower(str_replace('/', '\\', $db));
$prot = strtolower('c:\\laragon\\www\\liveled\\database\\database.sqlite');

echo 'BASE='.$app->basePath().PHP_EOL;
echo 'STORAGE='.$app->storagePath().PHP_EOL;
echo 'BCACHE='.$app->bootstrapPath('cache').PHP_EOL;
echo 'ENV='.config('app.env').PHP_EOL;
echo 'URL='.config('app.url').PHP_EOL;
echo 'CONN='.config('database.default').PHP_EOL;
echo 'DB='.$db.PHP_EOL;
echo 'SESS='.config('session.driver').PHP_EOL;
echo 'PROTECTED_DB_RESOLVED='.($norm === $prot ? 'YES' : 'NO').PHP_EOL;
