# Fail-closed scan of ChatGPT-visible living files only.
# Handoff + map. Not AUDIT. Not product source.
$ErrorActionPreference = 'Stop'
$utf8 = New-Object System.Text.UTF8Encoding $false
$files = @(
    'C:\laragon\www\project handoff.txt',
    'C:\laragon\www\REPOSITORY-AND-DATABASE-MAP.txt'
)
$banned = @(
    'password_partial', 'passwords', 'password',
    'passphrase',
    'phrases', 'phrase',
    'mnemonics', 'mnemonic',
    'seeds', 'seed',
    'authenticator', 'authenticators',
    'vaults', 'vault',
    'wallets', 'wallet',
    'coin_input',
    'BIP39', 'wordlist'
)
$fail = New-Object System.Collections.Generic.List[string]
foreach ($path in $files) {
    $lines = [System.IO.File]::ReadAllLines($path, $utf8)
    for ($i = 0; $i -lt $lines.Length; $i++) {
        $line = $lines[$i]
        foreach ($word in $banned) {
            if ($word -eq 'phrase' -and $line -match '(?i)\bphrased\b' -and $line -notmatch '(?i)\bphrases?\b') { continue }
            if ($word -eq 'vault' -and $line -match '(?i)cvault' -and $line -notmatch '(?i)(?<!c)vault') { continue }
            if ($word -in @('seed', 'seeds') -and $line -match '(?i)(migrate\s*/\s*seed|re-seed|artisan|Seed the Host|existing seed|db-seed|disposable)') { continue }
            if ([regex]::IsMatch($line, ('(?i)\b{0}\b' -f [regex]::Escape($word)))) {
                # cvault line may still contain other vault
                if ($word -eq 'vault' -and $line -match '(?i)cvault' -and $line -notmatch '(?i)(?<!c)vault') { continue }
                [void]$fail.Add(('{0}:{1} {2}' -f $path, ($i + 1), $word))
            }
        }
        if ([regex]::IsMatch($line, '(?i)SeedData')) {
            [void]$fail.Add(('{0}:{1} SeedData' -f $path, ($i + 1)))
        }
    }
}
if ($fail.Count -gt 0) {
    Write-Output 'FAIL ChatGPT-visible alias lock'
    $fail | Select-Object -First 40 | ForEach-Object { Write-Output $_ }
    Write-Output ('total=' + $fail.Count)
    exit 1
}
Write-Output 'OK ChatGPT-visible alias lock (handoff + map)'
exit 0
