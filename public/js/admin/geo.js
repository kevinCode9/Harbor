const cache = {};

function isoToFlag(iso) {
    if (!iso || iso.length !== 2) return '';
    var code = iso.toLowerCase();
    return '<img src="https://flagcdn.com/20x15/' + code + '.png" width="20" height="15" alt="' + iso.toUpperCase() + '" style="display:inline-block;vertical-align:middle;border-radius:2px;margin-right:4px;">';
}

export function lookup(ip, onDone) {
    if (cache[ip]) { console.log('[findip.net] ' + ip + ' → cache hit'); onDone(cache[ip]); return; }
    var ipTimeout = new Promise(function(_, rej) { setTimeout(function() { rej(new Error('timeout')); }, 6000); });
    Promise.race([fetch('/admin777888xxx/geo-lookup?ip=' + encodeURIComponent(ip), {headers:{'X-Requested-With':'XMLHttpRequest'}}), ipTimeout])
        .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(function(d) {
            var iso     = (d.country && d.country.iso_code) || '';
            var city    = (d.city && d.city.names && d.city.names.en) || '';
            var country = (d.country && d.country.names && d.country.names.en) || iso;
            var flag    = isoToFlag(iso);
            var parts   = [city, country].filter(Boolean);
            var locText = parts.join(', ');
            var flags   = (d.intelligence && d.intelligence.flags) || {};
            var risk    = (d.intelligence && d.intelligence.risk)  || {};
            var badges  = '';
            if (flags.is_vpn)    badges += '<span style="margin-left:5px;background:#7c3aed22;color:#a78bfa;border:1px solid #7c3aed55;font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;white-space:nowrap;">VPN</span>';
            if (flags.is_tor)    badges += '<span style="margin-left:5px;background:#dc262622;color:#fca5a5;border:1px solid #dc262655;font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;white-space:nowrap;">TOR</span>';
            if (flags.is_proxy)  badges += '<span style="margin-left:5px;background:#92400022;color:#fcd34d;border:1px solid #92400055;font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;white-space:nowrap;">PROXY</span>';
            if (flags.is_relay)  badges += '<span style="margin-left:5px;background:#92400022;color:#fcd34d;border:1px solid #92400055;font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;white-space:nowrap;">RELAY</span>';
            if (flags.is_hosting && !flags.is_vpn) badges += '<span style="margin-left:5px;background:#1e3a5f22;color:#93c5fd;border:1px solid #1e3a5f55;font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;white-space:nowrap;">HOSTING</span>';
            if (risk.level === 'high' || risk.level === 'critical') badges += '<span style="margin-left:5px;background:#dc262622;color:#fca5a5;border:1px solid #dc262655;font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;white-space:nowrap;">RISK:' + risk.level.toUpperCase() + '</span>';
            var result = {flag: flag, locText: locText, badges: badges, iso: iso};
            cache[ip] = result;
            console.log('[findip.net] ' + ip + ' →', {
                flag: flag || '(none)',
                city: city || '(none)',
                country: country || '(none)',
                iso: iso || '(none)',
                vpn: !!flags.is_vpn,
                tor: !!flags.is_tor,
                proxy: !!flags.is_proxy,
                hosting: !!flags.is_hosting,
                risk: (risk.level || 'unknown') + ' (' + (risk.score || 0) + ')',
                raw: d
            });
            // Update all session rows in the list that share this IP
            document.querySelectorAll('.session-row[data-ip="' + ip + '"] .sr-geo').forEach(function(el) {
                el.innerHTML = (flag ? flag + ' ' : '') + '<span style="opacity:.45;font-size:11px;">' + (locText || '') + '</span>' + badges;
            });
            onDone(result);
        })
        .catch(function(e) {
            var msg = e && e.message ? e.message : 'failed';
            console.warn('[findip.net] ' + ip + ' → ERROR: ' + msg);
            var result = {flag: '', locText: '', badges: '', error: 'findip.net: ' + msg};
            cache[ip] = result;
            onDone(result);
        });
}
