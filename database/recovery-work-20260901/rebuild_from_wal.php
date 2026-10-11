<?php
/**
 * Offline WAL frame rebuild. Operates only on copies in this folder.
 */
$walPath = __DIR__ . DIRECTORY_SEPARATOR . 'damaged.sqlite-wal';
$outPath = __DIR__ . DIRECTORY_SEPARATOR . 'rebuilt.sqlite';

$wal = fopen($walPath, 'rb');
if ($wal === false) {
    fwrite(STDERR, "OPEN_WAL_FAIL\n");
    exit(1);
}

$header = fread($wal, 32);
if (strlen($header) < 32) {
    fwrite(STDERR, "WAL_HEADER_SHORT\n");
    exit(1);
}

$magic = unpack('N', substr($header, 0, 4))[1];
if ($magic !== 0x377F0682 && $magic !== 0x377F0683) {
    fwrite(STDERR, sprintf("BAD_MAGIC=%08X\n", $magic));
    exit(1);
}

$pageSize = unpack('N', substr($header, 8, 4))[1];
if ($pageSize === 1) {
    $pageSize = 65536;
}
if ($pageSize < 512 || $pageSize > 65536) {
    fwrite(STDERR, "BAD_PAGE_SIZE=$pageSize\n");
    exit(1);
}

echo "MAGIC_OK PAGE_SIZE=$pageSize\n";

$lastOffset = [];
$dbSizePages = 0;
$frames = 0;
$commits = 0;
$offset = 32;

while (true) {
    $fh = fread($wal, 24);
    if ($fh === false || strlen($fh) < 24) {
        break;
    }
    $pageNo = unpack('N', substr($fh, 0, 4))[1];
    $truncate = unpack('N', substr($fh, 4, 4))[1];
    $dataOff = $offset + 24;
    $data = fread($wal, $pageSize);
    if ($data === false || strlen($data) < $pageSize) {
        echo "TRAILING_SHORT_FRAME frames=$frames\n";
        break;
    }
    if ($pageNo === 0) {
        echo "PAGE0_STOP frames=$frames\n";
        break;
    }
    $lastOffset[$pageNo] = $dataOff;
    if ($truncate > 0) {
        $dbSizePages = $truncate;
        $commits++;
    }
    $frames++;
    $offset += 24 + $pageSize;
    if (($frames % 20000) === 0) {
        echo "SCAN_FRAMES=$frames unique_pages=" . count($lastOffset) . "\n";
    }
}

echo "FRAMES=$frames UNIQUE_PAGES=" . count($lastOffset) . " COMMITS=$commits DB_SIZE_PAGES=$dbSizePages\n";

if (!$lastOffset) {
    fwrite(STDERR, "NO_FRAMES\n");
    exit(1);
}

$maxPage = max(array_keys($lastOffset));
$outPages = $dbSizePages > 0 ? $dbSizePages : $maxPage;
echo "WRITE_PAGES=$outPages MAX_SEEN=$maxPage\n";

$out = fopen($outPath, 'wb');
if ($out === false) {
    fwrite(STDERR, "OPEN_OUT_FAIL\n");
    exit(1);
}

$missing = 0;
$zero = str_repeat("\0", $pageSize);
for ($i = 1; $i <= $outPages; $i++) {
    if (!isset($lastOffset[$i])) {
        fwrite($out, $zero);
        $missing++;
        continue;
    }
    if (fseek($wal, $lastOffset[$i]) !== 0) {
        fwrite(STDERR, "SEEK_FAIL page=$i\n");
        exit(1);
    }
    $page = fread($wal, $pageSize);
    if ($page === false || strlen($page) < $pageSize) {
        fwrite(STDERR, "READ_FAIL page=$i\n");
        exit(1);
    }
    fwrite($out, $page);
}

fclose($out);
fclose($wal);

echo "MISSING_PAGES=$missing OUT_BYTES=" . filesize($outPath) . "\n";

$hdr = file_get_contents($outPath, false, null, 0, 16);
echo "OUT_HEADER=" . json_encode($hdr) . "\n";
if (!str_starts_with($hdr, 'SQLite format 3')) {
    fwrite(STDERR, "NO_SQLITE_HEADER\n");
    exit(2);
}
echo "SQLITE_HEADER_OK\n";
exit(0);
