import { lookup } from './geo.js?v=262';
import { start } from './clock.js?v=262';
import { show as showPreview } from './preview.js?v=262';
import { show as showShell, start as startShell } from './shell.js?v=263';
import { start as startSettings } from './settings.js?v=266';
import { start as startSubAdmins } from './subadmins.js?v=265';
import { start as startNotify } from './notify.js?v=266';
import { start as startSessions } from './sessions.js?v=272';

var pending = [];

if (
    typeof window._fetchIpGeo === 'function' &&
    Array.isArray(window._fetchIpGeo.q)
) {
    pending = window._fetchIpGeo.q.slice();
}

window._fetchIpGeo = lookup;

pending.forEach(function (item) {
    lookup(item[0], item[1]);
});

start();

window._openPreviewIframe = function(url, fields) {
    showPreview({
        url: url,
        fields: fields
    });
};

var pendingSwitch = (typeof window.switchSection === 'function' && window.switchSection._pending)
    ? window.switchSection._pending
    : null;

if (typeof window.switchSection === 'function') {
    window.switchSection._pending = null;
}

startShell();
startSubAdmins();

if (pendingSwitch) {
    showShell(pendingSwitch.section, pendingSwitch.skipOverlay);
}

startSettings();
startNotify();
startSessions();
