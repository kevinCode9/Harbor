var started = false;
var audioCtx = null;
var audioComp = null;
var audioUnlocked = false;
var notifVolume = 85;
var notifSession = loadPref('session', true);
var notifLoading = loadPref('loading', true);

function loadPref(key, def) {
    try {
        var v = localStorage.getItem('liveled_notif_' + key);
        return v === null ? def : v === '1';
    } catch (e) {
        return def;
    }
}

function savePref(key, val) {
    try {
        localStorage.setItem('liveled_notif_' + key, val ? '1' : '0');
    } catch (e) {}
}

function getAudioCtx() {
    if (!audioCtx) {
        try {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            audioComp = audioCtx.createDynamicsCompressor();
            audioComp.threshold.value = -6;
            audioComp.knee.value = 3;
            audioComp.ratio.value = 4;
            audioComp.attack.value = 0.001;
            audioComp.release.value = 0.1;
            audioComp.connect(audioCtx.destination);
        } catch (e) {
            audioCtx = null;
        }
    }
    return audioCtx;
}

function unlockAudio() {
    if (audioUnlocked) return;
    audioUnlocked = true;
    var ctx = getAudioCtx();
    if (ctx && ctx.state === 'suspended') ctx.resume().catch(function () {});
}

function playAlert(type) {
    if (!audioUnlocked) return;

    var ctx = getAudioCtx();
    if (!ctx) return;

    var doPlay = function () {
        try {
            var comp = audioComp;
            var now = ctx.currentTime;
            var tones;
            if (type === 'session') {
                tones = [{ f: 660, t: 0 }, { f: 880, t: 0.28 }, { f: 1100, t: 0.56 }];
            } else {
                tones = [{ f: 520, t: 0 }, { f: 700, t: 0.3 }, { f: 880, t: 0.6 }, { f: 1040, t: 0.9 }];
            }
            var baseVol = notifVolume / 100;
            var vol = type === 'session' ? baseVol * 0.85 : baseVol * 0.8;
            var dur = type === 'session' ? 0.85 : 0.9;
            tones.forEach(function (s) {
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(comp);
                osc.type = 'sine';
                osc.frequency.value = s.f;
                gain.gain.setValueAtTime(0, now + s.t);
                gain.gain.linearRampToValueAtTime(vol, now + s.t + 0.015);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + s.t + dur);
                osc.start(now + s.t);
                osc.stop(now + s.t + dur);
                osc.onended = function () {
                    try { osc.disconnect(); gain.disconnect(); } catch (e) {}
                };
            });
        } catch (e) {}
    };

    if (ctx.state === 'suspended') {
        ctx.resume().then(doPlay).catch(function () {});
    } else {
        doPlay();
    }
}

function syncToggles() {
    var sEl = document.getElementById('notif-session-toggle');
    var lEl = document.getElementById('notif-loading-toggle');
    if (sEl) sEl.checked = notifSession;
    if (lEl) lEl.checked = notifLoading;
}

function applyTogglesFromDom() {
    var sEl = document.getElementById('notif-session-toggle');
    var lEl = document.getElementById('notif-loading-toggle');
    if (sEl) {
        notifSession = sEl.checked;
        savePref('session', notifSession);
    }
    if (lEl) {
        notifLoading = lEl.checked;
        savePref('loading', notifLoading);
    }
}

function onSettingsChange(e) {
    var el = e.target.closest ? e.target.closest('[data-notify-control]') : null;
    if (!el || !e.currentTarget.contains(el)) return;
    applyTogglesFromDom();
    unlockAudio();
}

export function notify(title, body, type) {
    if (type === 'session' && !notifSession) return;
    if (type === 'loading' && !notifLoading) return;
    playAlert(type);
}

export function start() {
    if (started) return;
    started = true;

    syncToggles();

    var root = document.getElementById('view-settings');
    if (root) root.addEventListener('change', onSettingsChange);

    document.addEventListener('click', unlockAudio, { passive: true });
    document.addEventListener('keydown', unlockAudio, { passive: true });
}
