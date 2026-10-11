var started = false;

function format(isoStr) {
    if (!isoStr) return 'none yet';
    var diff = Math.floor((Date.now() - Date.parse(isoStr)) / 1000);
    if (diff < 5)  return 'just now';
    if (diff < 60) return diff + 's ago';
    var m = Math.floor(diff / 60);
    if (m < 60)    return m + 'm ago';
    var h = Math.floor(m / 60);
    if (h < 24)    return h + 'h ago';
    var d = Math.floor(h / 24);
    return d + 'd ago';
}

export function tick() {
    document.querySelectorAll('.session-row[data-last-response]').forEach(function (row) {
        var span = row.querySelector('.last-response-rel');
        if (span) span.textContent = format(row.dataset.lastResponse || '');
    });

    var hdrSpan = document.getElementById('info-lastresponse');
    if (hdrSpan && hdrSpan.hasAttribute('data-rel-iso')) {
        var iso = hdrSpan.getAttribute('data-rel-iso') || '';
        hdrSpan.textContent = format(iso);
        if (iso) {
            var hdrWrap = document.getElementById('info-lastresponse-wrap');
            if (hdrWrap) hdrWrap.style.display = '';
        }
    }
}

export function start() {
    if (started) return;
    started = true;
    setInterval(tick, 1000);
}
