<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$g = getenv('DB_DATABASE');
$getenvSet = is_string($g) && $g !== '';
$getenvOk = true;
if ($getenvSet) {
    $n = str_replace('\\', '/', $g);
    $getenvOk = (bool) preg_match('#/milady/#', $n);
}
$envFile = $root.DIRECTORY_SEPARATOR.'.env';
$dbLine = null;
foreach (file($envFile, FILE_IGNORE_NEW_LINES) as $line) {
    if (preg_match('/^DB_DATABASE=(.*)$/', $line, $m)) {
        $dbLine = trim($m[1], " \t\"'");
        break;
    }
}
$fileOk = is_string($dbLine) && (bool) preg_match('#/milady/#', str_replace('\\', '/', $dbLine));
echo 'getenv_DB_DATABASE_set='.($getenvSet ? 'yes' : 'no').PHP_EOL;
echo 'getenv_points_at_milady='.($getenvSet ? ($getenvOk ? 'yes' : 'NO') : 'n/a').PHP_EOL;
echo 'envfile_DB_DATABASE_points_at_milady='.($fileOk ? 'yes' : 'NO').PHP_EOL;
if ($dbLine !== null) {
    echo 'envfile_DB_DATABASE='.$dbLine.PHP_EOL;
}
if (($getenvSet && ! $getenvOk) || ! $fileOk) {
    exit(2);
}
