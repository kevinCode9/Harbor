<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$pdo = app('db')->connection()->getPdo();
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);

$mysql = "";
foreach ($tables as $table) {
    $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    $cols = $pdo->query("PRAGMA table_info(`$table`)")->fetchAll(PDO::FETCH_ASSOC);

    $colDefs = [];
    $pk = [];
    foreach ($cols as $col) {
        $type = strtoupper($col['type']);
        if (strpos($type, 'INT') !== false) $mysqlType = 'BIGINT';
        elseif (strpos($type, 'TEXT') !== false || strpos($type, 'CLOB') !== false) $mysqlType = 'LONGTEXT';
        elseif (strpos($type, 'BLOB') !== false) $mysqlType = 'LONGBLOB';
        elseif (strpos($type, 'REAL') !== false || strpos($type, 'FLOA') !== false || strpos($type, 'DOUB') !== false) $mysqlType = 'DOUBLE';
        elseif (strpos($type, 'NUMERIC') !== false || strpos($type, 'DECIMAL') !== false) $mysqlType = 'DECIMAL(15,2)';
        elseif ($type === 'BOOLEAN' || $type === 'TINYINT(1)') $mysqlType = 'TINYINT(1)';
        elseif (strpos($type, 'VARCHAR') !== false) $mysqlType = (preg_match('/VARCHAR\(\d+\)/i', $type) ? $type : 'VARCHAR(255)');
        else $mysqlType = 'TEXT';

        $null = $col['notnull'] ? 'NOT NULL' : 'NULL';
        $default = '';
        if ($col['dflt_value'] !== null) {
            $default = 'DEFAULT ' . $col['dflt_value'];
        }
        $auto = '';
        if ($col['pk'] && strpos(strtoupper($col['type']), 'INT') !== false) {
            $auto = 'AUTO_INCREMENT';
            $null = 'NOT NULL';
            $default = '';
            $pk[] = '`'.$col['name'].'`';
        }
        $colDefs[] = "  `{$col['name']}` $mysqlType $null $default $auto";
    }
    if (!empty($pk)) {
        $colDefs[] = "  PRIMARY KEY (" . implode(',', $pk) . ")";
    }

    $mysql .= "DROP TABLE IF EXISTS `$table`;\n";
    $mysql .= "CREATE TABLE `$table` (\n" . implode(",\n", $colDefs) . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

    if (!empty($rows)) {
        $colNames = '`' . implode('`, `', array_keys($rows[0])) . '`';
        $mysql .= "INSERT INTO `$table` ($colNames) VALUES\n";
        $vals = [];
        foreach ($rows as $row) {
            $escaped = array_map(function($v) use ($pdo) {
                return $v === null ? 'NULL' : $pdo->quote($v);
            }, array_values($row));
            $vals[] = '(' . implode(', ', $escaped) . ')';
        }
        $mysql .= implode(",\n", $vals) . ";\n\n";
    }
}

file_put_contents(__DIR__.'/database_export.sql', $mysql);
echo "Done! database_export.sql created.\n";
