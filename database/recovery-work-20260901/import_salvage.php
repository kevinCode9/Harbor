<?php
/**
 * Import sqlite3 .recover lost_and_found rows into a migrated schema copy.
 * Work-folder only.
 */
$src = __DIR__ . DIRECTORY_SEPARATOR . 'from-recover.sqlite';
$dst = __DIR__ . DIRECTORY_SEPARATOR . 'fresh.sqlite';

$in = new PDO('sqlite:' . $src);
$in->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$out = new PDO('sqlite:' . $dst);
$out->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$out->exec('PRAGMA foreign_keys = OFF');
$out->beginTransaction();

$ins = 0;

$st = $in->query('SELECT id,c0,c1,c2,c3,c4,c5 FROM lost_and_found WHERE rootpgno=4');
$q = $out->prepare('INSERT OR REPLACE INTO sessions (id,user_id,ip_address,user_agent,payload,last_activity) VALUES (?,?,?,?,?,?)');
while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $q->execute([$r['c0'], $r['c1'], $r['c2'], $r['c3'], $r['c4'], $r['c5']]);
    $ins++;
}
echo "sessions=$ins\n";

$ins = 0;
$st = $in->query('SELECT id,c1,c2,c3,c4,c5,c6 FROM lost_and_found WHERE rootpgno=8');
$q = $out->prepare('INSERT OR REPLACE INTO user_events (id,session_token,type,page,element,value,created_at) VALUES (?,?,?,?,?,?,?)');
while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $q->execute([$r['id'], $r['c1'], $r['c2'], $r['c3'], $r['c4'], $r['c5'], $r['c6']]);
    $ins++;
}
echo "user_events=$ins\n";

$ins = 0;
$st = $in->query('SELECT id,c1,c2,c3,c4,c5,c6,c7,c8,c9,c10,c11,c12,c13,c14,c15,c16 FROM lost_and_found WHERE rootpgno=10');
$q = $out->prepare('INSERT OR REPLACE INTO user_sessions (
    id,session_token,ip_address,user_agent,current_page,country,last_seen_at,created_at,updated_at,label,last_active_at,enc_key,pinned,metadata,activity_summary,document_id,document_issued_at
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $q->execute([
        $r['id'], $r['c1'], $r['c2'], $r['c3'], $r['c4'], $r['c5'], $r['c6'], $r['c7'], $r['c8'],
        $r['c9'], $r['c10'], $r['c11'], $r['c12'], $r['c13'], $r['c14'], $r['c15'], $r['c16'],
    ]);
    $ins++;
}
echo "user_sessions=$ins\n";

$ins = 0;
$st = $in->query('SELECT id,c1,c2,c3,c4,c5,c6,c7 FROM lost_and_found WHERE rootpgno=12');
$q = $out->prepare('INSERT OR REPLACE INTO admin_commands (id,session_token,command,payload,executed,executed_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?)');
while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $q->execute([$r['id'], $r['c1'], $r['c2'], $r['c3'], $r['c4'], $r['c5'], $r['c6'], $r['c7']]);
    $ins++;
}
echo "admin_commands=$ins\n";

$ins = 0;
$st = $in->query('SELECT id,c1,c2,c3,c4,c5,c6,c7,c8,c9,c10,c11 FROM lost_and_found WHERE rootpgno=18');
$q = $out->prepare('INSERT OR REPLACE INTO captured_phrases (id,session_token,ip_address,user_agent,page,phrase,passphrase,captured_at,created_at,updated_at,word_history,word_count) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $q->execute([$r['id'], $r['c1'], $r['c2'], $r['c3'], $r['c4'], $r['c5'], $r['c6'], $r['c7'], $r['c8'], $r['c9'], $r['c10'], $r['c11']]);
    $ins++;
}
echo "captured_phrases=$ins\n";

$ins = 0;
$st = $in->query('SELECT c0,c1,c2,c3,c4 FROM lost_and_found WHERE rootpgno=29');
$q = $out->prepare('INSERT OR REPLACE INTO admin_online_sessions (session_id,ip_address,user_agent,last_seen_at,logged_in_at) VALUES (?,?,?,?,?)');
while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $q->execute([$r['c0'], $r['c1'], $r['c2'], $r['c3'], $r['c4']]);
    $ins++;
}
echo "admin_online_sessions=$ins\n";

$out->commit();
echo "IMPORT_OK\n";
