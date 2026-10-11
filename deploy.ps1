###############################################################################
#  deploy.ps1  —  Upload files to cPanel and clear caches
#
#  USAGE:
#    .\deploy.ps1 path/to/file.php path/to/other.blade.php
#    .\deploy.ps1 --changed          # all files modified since last deploy
#    .\deploy.ps1 --changed --all    # also force-clear all caches
#    .\deploy.ps1 --all              # just clear all caches, no upload
#
#  PUBLIC FILES (logos, css, js in public/):
#    Add "public/" prefix and they go to public_html/ automatically:
#    .\deploy.ps1 public/AOL-Logo.png public/gmaillogo.webp
###############################################################################

param(
    [switch]$Changed,
    [switch]$All,
    [Parameter(ValueFromRemainingArguments=$true)]
    [string[]]$Files
)

# ── CONFIG ───────────────────────────────────────────────────────────────────
$SSH_USER    = "ledn"
$SSH_HOST    = "cp-01.swiss-host.net"
$REMOTE_APP  = "/home/ledn/liveled"
$REMOTE_PUB  = "/home/ledn/public_html"
$LOCAL_PATH  = "c:\Users\Administrator\Desktop\liveled"
$SSH_KEY     = "$env:USERPROFILE\.ssh\cpanel_key"
$LAST_DEPLOY = "$LOCAL_PATH\.last_deploy"
# ─────────────────────────────────────────────────────────────────────────────

$SSH_TARGET = "${SSH_USER}@${SSH_HOST}"
$SCP_OPTS   = @("-o","StrictHostKeyChecking=no","-o","ServerAliveInterval=10","-o","ConnectTimeout=60","-o","BatchMode=yes")
$SSH_OPTS   = @("-o","StrictHostKeyChecking=no","-o","ServerAliveInterval=10","-o","BatchMode=yes")

if (Test-Path $SSH_KEY) {
    $SCP_OPTS = @("-i",$SSH_KEY) + $SCP_OPTS
    $SSH_OPTS = @("-i",$SSH_KEY) + $SSH_OPTS
} else {
    Write-Host "WARNING: SSH key not found at $SSH_KEY" -ForegroundColor Yellow
}

$uploadedFiles = @()
$failedFiles   = @()

function Upload-File($relPath) {
    $relPath = $relPath -replace '\\','/'

    # Route public/ files to public_html (preserving subdirs), everything else to liveled/
    if ($relPath -match '^public/') {
        $subPath   = $relPath -replace '^public/',''   # e.g. "js/admin-dashboard.js"
        $local     = "$LOCAL_PATH\$($relPath -replace '/','\')"
        $remote    = "$REMOTE_PUB/$subPath"            # e.g. "/home/ledn/public_html/js/admin-dashboard.js"
        $remoteDir = ($remote -replace '/[^/]+$','')   # e.g. "/home/ledn/public_html/js"
    } else {
        $local     = "$LOCAL_PATH\$($relPath -replace '/','\')"
        $remote    = "$REMOTE_APP/$relPath"
        $remoteDir = ($remote -replace '/[^/]+$','')
    }

    if (-not (Test-Path $local)) {
        Write-Host "  SKIP (not found locally): $relPath" -ForegroundColor Yellow
        return
    }

    # Ensure remote directory exists
    & ssh.exe @SSH_OPTS $SSH_TARGET "mkdir -p '$remoteDir'" 2>$null | Out-Null

    $uploaded = $false
    for ($attempt = 1; $attempt -le 3; $attempt++) {
        Write-Host "  -> $relPath$(if($attempt -gt 1){" (attempt $attempt)"})" -ForegroundColor Cyan
        & scp.exe @SCP_OPTS $local "${SSH_TARGET}:${remote}"
        if ($LASTEXITCODE -eq 0) { $uploaded = $true; break }
        Write-Host "     Transfer failed, retrying in 4s..." -ForegroundColor Yellow
        Start-Sleep -Seconds 4
    }

    if ($uploaded) {
        $script:uploadedFiles += $relPath
    } else {
        Write-Host "     FAILED after 3 attempts: $relPath" -ForegroundColor Red
        $script:failedFiles += $relPath
    }
}

function Run-Remote($cmd) {
    Write-Host "  `$ $cmd" -ForegroundColor DarkGray
    & ssh.exe @SSH_OPTS $SSH_TARGET "cd $REMOTE_APP && $cmd"
}

# ── Determine files to upload ─────────────────────────────────────────────────
$toUpload = @()

if ($Changed) {
    Write-Host "`nDetecting changed files since last deploy..." -ForegroundColor Magenta
    $since = if (Test-Path $LAST_DEPLOY) { (Get-Item $LAST_DEPLOY).LastWriteTime } else { (Get-Date).AddDays(-30) }
    Write-Host "  Since: $($since.ToString('yyyy-MM-dd HH:mm:ss'))" -ForegroundColor Gray

    $toUpload = Get-ChildItem -Path $LOCAL_PATH -Recurse -File |
        Where-Object {
            $_.LastWriteTime -gt $since -and
            $_.FullName -notmatch '\\vendor\\' -and
            $_.FullName -notmatch '\\node_modules\\' -and
            $_.FullName -notmatch '\\storage\\' -and
            $_.FullName -notmatch '\\.git\\' -and
            $_.Extension -in @('.php','.blade.php','.env','.json','.js','.css','.html','.png','.webp','.svg','.gif','.jpg','.jpeg')
        } |
        ForEach-Object { $_.FullName.Replace($LOCAL_PATH + '\', '').Replace('\', '/') }

    if (-not $toUpload) {
        Write-Host "  No changed files found." -ForegroundColor Green
        exit 0
    }
    Write-Host "  Found $($toUpload.Count) changed file(s):" -ForegroundColor Green
    $toUpload | ForEach-Object { Write-Host "    $_" -ForegroundColor Gray }
    Write-Host ""

} elseif ($Files) {
    $toUpload = $Files
} elseif (-not $All) {
    Write-Host @"

USAGE:
  .\deploy.ps1 path/to/file1.php path/to/file2.blade.php
  .\deploy.ps1 --changed                   # upload all files modified since last deploy
  .\deploy.ps1 --changed --all             # upload changed files + clear all caches
  .\deploy.ps1 --all                       # just clear all caches

PUBLIC FILES go to public_html/ automatically:
  .\deploy.ps1 public/AOL-Logo.png public/gmaillogo.webp public/yahoicon.png

"@ -ForegroundColor Yellow
    exit 0
}

# ── Upload ───────────────────────────────────────────────────────────────────
if ($toUpload.Count -gt 0) {
    Write-Host "`nUploading $($toUpload.Count) file(s) to $SSH_TARGET..." -ForegroundColor Magenta
    foreach ($f in $toUpload) { Upload-File $f }
}

# ── Clear caches ──────────────────────────────────────────────────────────────
Write-Host "`nClearing caches..." -ForegroundColor Magenta

$hasBlade  = $uploadedFiles | Where-Object { $_ -match '\.(blade\.php|html)$' }
$hasPhp    = $uploadedFiles | Where-Object { $_ -match '\.php$' -and $_ -notmatch '\.blade\.php$' }
$hasRoute  = $uploadedFiles | Where-Object { $_ -match 'routes/' -or $_ -match 'bootstrap/' }
$hasConfig = $uploadedFiles | Where-Object { $_ -match 'config/' -or $_ -match '\.env$' }

if ($All) {
    Run-Remote "php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan cache:clear"
} else {
    if ($hasBlade)  { Run-Remote "php artisan view:clear" }
    if ($hasConfig) { Run-Remote "php artisan config:clear" }
    if ($hasRoute)  { Run-Remote "php artisan route:clear" }
}

# ── Summary ───────────────────────────────────────────────────────────────────
Write-Host ""
if ($uploadedFiles.Count -gt 0) {
    Write-Host "OK  $($uploadedFiles.Count) file(s) uploaded:" -ForegroundColor Green
    $uploadedFiles | ForEach-Object { Write-Host "    $_" -ForegroundColor Gray }
}
if ($failedFiles.Count -gt 0) {
    Write-Host "FAIL  $($failedFiles.Count) file(s) failed:" -ForegroundColor Red
    $failedFiles | ForEach-Object { Write-Host "    $_" -ForegroundColor Red }
}

# ── Update last deploy timestamp ─────────────────────────────────────────────
if ($uploadedFiles.Count -gt 0) {
    Get-Date | Out-File $LAST_DEPLOY -Encoding utf8
}

Write-Host "`nDone!" -ForegroundColor Green
