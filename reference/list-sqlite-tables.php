<?php
$d = new PDO('sqlite:C:/laragon/www/milady/database/database.sqlite');
$r = $d->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY 1");
foreach ($r as $row) {
    echo $row[0], PHP_EOL;
}
