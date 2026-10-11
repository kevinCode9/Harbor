import { onSectionChange } from './shell.js?v=263';

var started = false;
var pollTimer = null;

function csrf() {
    return document.querySelector('meta[name="csrf-token"]').content;
}

function urls() {
    return window._ADMIN || {};
}

function setListMessage(list, text, color) {
    list.replaceChildren();
    var el = document.createElement('div');
    el.style.cssText = 'text-align:center;padding:40px 0;font-family:monospace;font-size:11px;color:' + color + ';';
    el.textContent = text;
    list.appendChild(el);
}

function makeEl(tag, style, text) {
    var el = document.createElement(tag);
    if (style) el.style.cssText = style;
    if (text != null) el.textContent = text;
    return el;
}

function renderRow(a) {
    var accUrl = a.access_url || '';
    var accPath = accUrl ? (new URL(accUrl)).pathname : '';

    var row = makeEl('div', 'display:flex;align-items:center;gap:12px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:6px;padding:10px 14px;');
    row.setAttribute('data-subadmin-row', '');
    row.dataset.id = String(a.id);
    row.dataset.full = accUrl;

    var body = makeEl('div', 'flex:1;min-width:0;');

    var line1 = makeEl('div', 'display:flex;align-items:center;gap:8px;margin-bottom:5px;');
    line1.appendChild(makeEl('span', 'font-family:monospace;font-size:12px;font-weight:700;color:#fff;', a.label || ''));
    if (a.is_online) {
        line1.appendChild(makeEl('span', 'display:inline-block;width:7px;height:7px;border-radius:50%;background:#4ade80;box-shadow:0 0 5px #4ade80;flex-shrink:0;'));
        line1.appendChild(makeEl('span', 'font-family:monospace;font-size:9px;color:#4ade80;', 'Online'));
    } else {
        line1.appendChild(makeEl('span', 'display:inline-block;width:7px;height:7px;border-radius:50%;background:#475569;flex-shrink:0;'));
        line1.appendChild(makeEl('span', 'font-family:monospace;font-size:9px;color:rgba(255,255,255,.25);', 'Offline'));
    }
    body.appendChild(line1);

    var line2 = makeEl('div', 'display:flex;align-items:center;gap:6px;margin-bottom:4px;');
    var pathEl = makeEl('span', 'font-family:monospace;font-size:10px;color:rgba(255,255,255,.35);letter-spacing:.01em;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:260px;', '\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022');
    pathEl.id = 'sa-url-' + a.id;
    pathEl.dataset.path = accPath;
    pathEl.dataset.full = accUrl;
    pathEl.dataset.visible = '0';
    line2.appendChild(pathEl);

    var showBtn = makeEl('button', 'background:transparent;border:none;color:rgba(255,255,255,.3);font-family:monospace;font-size:9px;cursor:pointer;padding:0;text-decoration:underline;flex-shrink:0;', 'show');
    showBtn.type = 'button';
    showBtn.setAttribute('data-subadmin-control', 'show-url');
    line2.appendChild(showBtn);

    var copyBtn = makeEl('button', 'background:rgba(99,102,241,.12);border:1px solid rgba(99,102,241,.25);color:#a5b4fc;font-family:monospace;font-size:9px;font-weight:700;padding:2px 8px;border-radius:3px;cursor:pointer;flex-shrink:0;', 'Copy URL');
    copyBtn.type = 'button';
    copyBtn.setAttribute('data-subadmin-control', 'copy-url');
    line2.appendChild(copyBtn);
    body.appendChild(line2);

    var line3 = makeEl('div', 'display:flex;align-items:center;gap:12px;');
    if (a.last_login_at) {
        line3.appendChild(makeEl('span', 'font-family:monospace;font-size:10px;color:rgba(255,255,255,.3);', 'Last login: ' + a.last_login_at));
    } else {
        line3.appendChild(makeEl('span', 'font-family:monospace;font-size:10px;color:rgba(255,255,255,.15);', 'Never logged in'));
    }
    line3.appendChild(makeEl('span', 'font-family:monospace;font-size:10px;color:rgba(255,255,255,.15);', 'Created ' + (a.created_at || '')));
    body.appendChild(line3);

    row.appendChild(body);

    var delBtn = makeEl('button', 'background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#f87171;font-family:monospace;font-size:10px;font-weight:700;padding:4px 10px;border-radius:3px;cursor:pointer;flex-shrink:0;', 'Delete');
    delBtn.type = 'button';
    delBtn.setAttribute('data-subadmin-control', 'delete');
    delBtn.dataset.id = String(a.id);
    row.appendChild(delBtn);

    return row;
}

function load() {
    var cfg = urls();
    if (!cfg.subAdminsJsonUrl) return;
    var list = document.getElementById('sub-admins-list');
    if (!list) return;
    fetch(cfg.subAdminsJsonUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
        .then(function (accounts) {
            if (!accounts.length) {
                setListMessage(list, 'No sub-admins yet. Click "+ New Sub-Admin" to create one.', 'rgba(255,255,255,.2)');
                return;
            }
            list.replaceChildren();
            accounts.forEach(function (a) {
                list.appendChild(renderRow(a));
            });
        })
        .catch(function () {
            if (list) setListMessage(list, 'Failed to load.', 'rgba(248,113,113,.5)');
        });
}

function stopPoll() {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

function onSection(detail) {
    stopPoll();
    if (detail && detail.section === 'subadmins') {
        load();
        pollTimer = setInterval(load, 12000);
    }
}

function openCreate() {
    var m = document.getElementById('create-subadmin-modal');
    var li = document.getElementById('subadmin-label-input');
    var er = document.getElementById('subadmin-create-err');
    if (li) li.value = '';
    if (er) er.style.display = 'none';
    if (m) m.classList.remove('hidden');
    setTimeout(function () { if (li) li.focus(); }, 80);
}

function closeCreate() {
    var m = document.getElementById('create-subadmin-modal');
    if (m) m.classList.add('hidden');
}

function submitCreate() {
    var cfg = urls();
    if (!cfg.subAdminsCreateUrl) return;
    var label = (document.getElementById('subadmin-label-input') || {}).value || '';
    var er = document.getElementById('subadmin-create-err');
    if (!label.trim()) {
        if (er) { er.textContent = 'Name is required.'; er.style.display = ''; }
        return;
    }
    if (er) er.style.display = 'none';
    fetch(cfg.subAdminsCreateUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify({ label: label.trim() }),
    }).then(function (r) { return r.json(); }).then(function (d) {
        if (d.ok) {
            closeCreate();
            load();
        } else if (er) {
            er.textContent = d.error || 'Failed to create.';
            er.style.display = '';
        }
    }).catch(function () {
        if (er) { er.textContent = 'Network error.'; er.style.display = ''; }
    });
}

function toggleShow(btn) {
    var span = btn.previousElementSibling;
    if (!span) return;
    if (span.dataset.visible === '1') {
        span.textContent = '';
        span.dataset.visible = '0';
        btn.textContent = 'show';
    } else {
        span.textContent = span.dataset.path || '(not available)';
        span.dataset.visible = '1';
        btn.textContent = 'hide';
    }
}

function copyUrl(btn) {
    var row = btn.closest('[data-subadmin-row]');
    var url = row ? row.dataset.full : '';
    if (!url) return;
    navigator.clipboard.writeText(url).then(function () {
        var orig = btn.textContent;
        btn.textContent = 'Copied!';
        btn.style.color = '#4ade80';
        setTimeout(function () { btn.textContent = orig; btn.style.color = '#a5b4fc'; }, 1500);
    }).catch(function () {});
}

function deleteAccount(btn) {
    if (!confirm('Delete this sub-admin? They will immediately lose access.')) return;
    var cfg = urls();
    if (!cfg.subAdminDeleteBase) return;
    var id = btn.dataset.id;
    if (!id) return;
    fetch(cfg.subAdminDeleteBase + '/' + id, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
    }).then(function (r) { return r.json(); }).then(function (d) {
        if (d.ok) { load(); }
    }).catch(function () {});
}

function onViewClick(e) {
    var btn = e.target.closest('[data-subadmin-control]');
    if (!btn || !e.currentTarget.contains(btn)) return;
    var control = btn.getAttribute('data-subadmin-control');
    if (control === 'create-open') openCreate();
    else if (control === 'show-url') toggleShow(btn);
    else if (control === 'copy-url') copyUrl(btn);
    else if (control === 'delete') deleteAccount(btn);
}

function onModalClick(e) {
    var btn = e.target.closest('[data-subadmin-control]');
    if (!btn || !e.currentTarget.contains(btn)) return;
    var control = btn.getAttribute('data-subadmin-control');
    if (control === 'create-close') closeCreate();
    else if (control === 'create-submit') submitCreate();
}

export function start() {
    if (started) return;
    var view = document.getElementById('view-subadmins');
    if (!view) return;
    started = true;

    view.addEventListener('click', onViewClick);
    var modal = document.getElementById('create-subadmin-modal');
    if (modal) modal.addEventListener('click', onModalClick);

    onSectionChange(onSection);
}
