export function show(spec) {
    var url = spec.url;
    var fields = spec.fields;

    // Remove any existing preview
    var existing = document.getElementById('preview-iframe-overlay');
    if (existing) existing.remove();

    // Append preview marker so the server skips session tracking for this request
    function addPreviewParam(u) {
        return u + (u.indexOf('?') >= 0 ? '&' : '?') + '_liveled_preview=1';
    }

    var overlay = document.createElement('div');
    overlay.id = 'preview-iframe-overlay';
    overlay.style.cssText = 'position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,.75);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;padding:24px;';
    overlay.onclick = function(e) { if (e.target === overlay) close(); };

    var box = document.createElement('div');
    box.style.cssText = 'position:relative;width:100%;max-width:920px;height:90vh;max-height:800px;background:#0a0a0a;border:1px solid rgba(255,255,255,.14);border-radius:10px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.7);display:flex;flex-direction:column;';

    // "" Top bar ""
    var bar = document.createElement('div');
    bar.style.cssText = 'flex-shrink:0;display:flex;align-items:center;gap:10px;padding:9px 14px;border-bottom:1px solid rgba(255,255,255,.08);background:#0d0d0d;';

    // Coloured dot indicator
    var dot = document.createElement('span');
    dot.style.cssText = 'display:inline-block;width:8px;height:8px;border-radius:50%;background:#facc15;flex-shrink:0;';
    dot.title = 'Preview mode  no session is tracked';

    var lbl = document.createElement('span');
    lbl.style.cssText = 'font-family:monospace;font-size:11px;color:rgba(255,255,255,.55);flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
    lbl.textContent = 'PREVIEW  ' + url;

    var badge = document.createElement('span');
    badge.style.cssText = 'font-family:monospace;font-size:9px;font-weight:700;color:#facc15;background:rgba(250,204,21,.12);border:1px solid rgba(250,204,21,.25);border-radius:3px;padding:2px 5px;letter-spacing:.05em;flex-shrink:0;';
    badge.textContent = 'NO SESSION';

    var escHint = document.createElement('span');
    escHint.style.cssText = 'font-family:monospace;font-size:10px;color:rgba(255,255,255,.25);flex-shrink:0;display:none;';
    escHint.textContent = 'ESC to close';

    var closeBtn = document.createElement('button');
    closeBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
    closeBtn.style.cssText = 'background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:#fff;border-radius:4px;cursor:pointer;padding:4px 6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;';
    closeBtn.title = 'Close preview (Esc)';
    closeBtn.onmouseenter = function(){this.style.background='rgba(255,255,255,.12)';};
    closeBtn.onmouseleave = function(){this.style.background='rgba(255,255,255,.06)';};

    function close() {
        overlay.remove();
        document.removeEventListener('keydown', escHandler);
    }
    closeBtn.onclick = close;

    bar.appendChild(dot);
    bar.appendChild(lbl);
    bar.appendChild(badge);
    bar.appendChild(escHint);
    bar.appendChild(closeBtn);

    // "" Loading state inside iframe area ""
    var loadWrap = document.createElement('div');
    loadWrap.style.cssText = 'flex:1;position:relative;';

    var loadMsg = document.createElement('div');
    loadMsg.style.cssText = 'position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;background:#0a0a0a;z-index:2;pointer-events:none;';
    loadMsg.innerHTML = '<div style="width:28px;height:28px;border:2px solid rgba(255,255,255,.1);border-top-color:rgba(255,255,255,.5);border-radius:50%;animation:prevSpin .8s linear infinite;"></div>'
        + '<span style="font-family:monospace;font-size:11px;color:rgba(255,255,255,.35);">Loading preview...</span>';

    var iframeName = 'liveled-preview-' + Date.now();
    var iframe = document.createElement('iframe');
    iframe.name = iframeName;
    iframe.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;border:none;background:#fff;display:block;opacity:0;transition:opacity .25s;';
    iframe.onload = function() {
        // Fade in once loaded, hide loading spinner
        iframe.style.opacity = '1';
        loadMsg.style.display = 'none';
        dot.style.background = '#4ade80'; // green = loaded
        escHint.style.display = 'block';
    };
    iframe.onerror = function() {
        loadMsg.innerHTML = '<span style="font-family:monospace;font-size:11px;color:#f87171;">Failed to load preview</span>';
        dot.style.background = '#f87171';
    };

    loadWrap.appendChild(loadMsg);
    loadWrap.appendChild(iframe);

    // Inject keyframe animation into document if not already there
    if (!document.getElementById('preview-keyframes')) {
        var style = document.createElement('style');
        style.id = 'preview-keyframes';
        style.textContent = '@keyframes prevSpin{to{transform:rotate(360deg)}}';
        document.head.appendChild(style);
    }

    box.appendChild(bar);
    box.appendChild(loadWrap);
    overlay.appendChild(box);
    document.body.appendChild(overlay);

    // "" Load content ""
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {content:''}).content;

    if (fields && Object.keys(fields).length) {
        // POST form (e.g. ColdCard)  include preview marker as hidden field
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = addPreviewParam(url);
        form.target = iframeName;
        // CSRF
        var csrfInp = document.createElement('input');
        csrfInp.type = 'hidden'; csrfInp.name = '_token'; csrfInp.value = csrf;
        form.appendChild(csrfInp);
        Object.keys(fields).forEach(function(k) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = k; inp.value = fields[k];
            form.appendChild(inp);
        });
        document.body.appendChild(form);
        form.submit();
        setTimeout(function(){ if(document.body.contains(form)) document.body.removeChild(form); }, 500);
    } else {
        iframe.src = addPreviewParam(url);
    }

    var escHandler = function(e) { if (e.key === 'Escape') close(); };
    document.addEventListener('keydown', escHandler);
}
