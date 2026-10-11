var started = false;
var activeSection = 'sessions';
var sectionChangeListener = null;

export function onSectionChange(fn) {
    if (typeof fn === 'function') sectionChangeListener = fn;
}

function updateNavBg(btnId, isActive) {
    var btn = document.getElementById('nav-' + btnId);
    if (!btn) return;
    btn.classList.toggle('is-active', !!isActive);
}

function applyNavStates(active) {
    ['sessions-btn', 'settings-btn', 'subadmins-btn'].forEach(function (id) {
        updateNavBg(id, id.replace('-btn', '') === active || (active === 'detail' && id === 'sessions-btn'));
    });
}

export function show(section, skipOverlay) {
    if (section === activeSection && section !== 'detail') return;
    var ov = document.getElementById('section-overlay');

    function doSwitch() {
        ['view-sessions', 'view-detail', 'view-settings', 'view-subadmins'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.classList.add('admin-view-hidden');
        });

        var bb = document.getElementById('back-btn');
        var hl = document.getElementById('header-view-label');
        var hm = document.getElementById('header-session-meta');
        var hcp = document.getElementById('header-current-page');
        var sll = document.getElementById('site-live-label');
        var termBtn = document.getElementById('header-terminate-btn');
        if (bb) bb.style.display = 'none';
        if (hm) hm.style.display = 'none';
        if (hcp) hcp.style.display = 'none';
        if (sll) sll.style.display = '';
        if (hl) { hl.style.display = ''; }
        if (termBtn) termBtn.style.display = 'none';

        activeSection = section;
        applyNavStates(section);

        var _fp = document.getElementById('flows-panel');
        var _rh = document.getElementById('resize-handle');
        var _inDetail = section === 'detail';
        if (_fp) _fp.style.display = _inDetail ? '' : 'none';
        if (_rh) _rh.style.display = _inDetail ? '' : 'none';

        if (section === 'sessions') {
            var vs = document.getElementById('view-sessions');
            if (vs) vs.classList.remove('admin-view-hidden');
            if (hl) hl.textContent = 'Sessions';
        } else if (section === 'detail') {
            var vd = document.getElementById('view-detail');
            if (vd) vd.classList.remove('admin-view-hidden');
            if (bb) bb.style.display = 'none';
            if (hl) hl.style.display = 'none';
            if (hcp) hcp.style.display = 'flex';
            if (sll) sll.style.display = 'none';
            if (hm) { hm.style.display = 'flex'; hm.style.flex = '1'; }
        } else if (section === 'settings') {
            var vst = document.getElementById('view-settings');
            if (vst) vst.classList.remove('admin-view-hidden');
            if (hl) hl.textContent = 'Settings';
        } else if (section === 'subadmins') {
            var vsa = document.getElementById('view-subadmins');
            if (vsa) vsa.classList.remove('admin-view-hidden');
            if (hl) hl.textContent = 'Sub-Admins';
        }

        if (typeof sectionChangeListener === 'function') {
            sectionChangeListener({ section: section });
        }

        if (ov) {
            ov.style.opacity = '0';
            setTimeout(function () { ov.style.pointerEvents = 'none'; }, 160);
        }
    }

    if (skipOverlay) { doSwitch(); return; }
    if (ov) { ov.style.pointerEvents = 'all'; ov.style.opacity = '1'; }
    setTimeout(doSwitch, 120);
}

function onEscape(e) {
    if (e.key === 'Escape' && document.getElementById('view-settings') && !document.getElementById('view-settings').classList.contains('admin-view-hidden')) {
        show('sessions', true);
    }
}

function onNavClick(e) {
    var btn = e.target.closest('[data-admin-nav]');
    if (!btn || !e.currentTarget.contains(btn)) return;
    var section = btn.getAttribute('data-admin-nav');
    if (!section) return;
    show(section);
}

export function start() {
    if (started) return;
    started = true;

    var group = document.querySelector('.nav-btn-group');
    if (group) group.addEventListener('click', onNavClick);

    document.addEventListener('keydown', onEscape);
}
