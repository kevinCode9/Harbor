import { isPreviewMode } from './session.js';

export function readApplied(session) {
  const applied = session?.metadata?.applied?.appliedParameters || {};
  const demo = session?.metadata?.demoFields || {};
  return { ...demo, ...applied, ...previewQueryParams() };
}

function previewQueryParams() {
  if (!isPreviewMode()) return {};
  const query = new URLSearchParams(window.location.search);
  const next = {};
  const view = query.get('view');
  if (view === 'invalid' || view === 'default') next.view = view;
  if (query.get('hold') === '1') next.hold = true;
  for (const key of ['tailCode', 'channel', 'location', 'time', 'name', 'topic', 'callbackWindow', 'itemLabel', 'itemCount']) {
    const value = query.get(key);
    if (value) next[key] = value;
  }
  return next;
}

export function paintApplied(session) {
  const params = readApplied(session);
  const banner = document.getElementById('page-variant');
  if (banner) {
    if (params.view === 'invalid') {
      banner.hidden = false;
      banner.textContent = 'That entry was not accepted. Use the default desk copy.';
    } else if (params.hold) {
      banner.hidden = false;
      banner.textContent = 'This case is on hold.';
    } else {
      banner.hidden = true;
      banner.textContent = '';
    }
  }

  const heading = document.getElementById('applied-heading');
  if (heading && params.view === 'invalid' && heading.dataset.invalid) {
    heading.textContent = heading.dataset.invalid;
  }

  const tail = document.getElementById('applied-tail');
  if (tail) tail.textContent = params.tailCode ? String(params.tailCode) : '';

  const loc = document.getElementById('applied-location');
  if (loc) loc.textContent = params.location ? String(params.location) : '—';
  const time = document.getElementById('applied-time');
  if (time) time.textContent = params.time ? String(params.time) : '—';
  const name = document.getElementById('applied-name');
  if (name) name.textContent = params.name ? String(params.name) : '—';

  if (params.channel) {
    document.querySelectorAll('[data-channel]').forEach((el) => {
      el.classList.toggle('on', el.getAttribute('data-channel') === params.channel);
    });
  }

  const topic = document.getElementById('applied-topic');
  if (topic) topic.textContent = params.topic ? String(params.topic) : '—';
  const callback = document.getElementById('applied-callback');
  if (callback) callback.textContent = params.callbackWindow ? String(params.callbackWindow) : '—';
  const item = document.getElementById('applied-item');
  if (item) {
    const label = params.itemLabel ? String(params.itemLabel) : '';
    const count = params.itemCount ? String(params.itemCount) : '';
    item.textContent = label ? `${label}${count ? ` × ${count}` : ''}` : '—';
  }
}
