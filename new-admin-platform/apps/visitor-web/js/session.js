const TOKEN_KEY = 'visitorToken';

export function isPreviewMode() {
  return new URLSearchParams(window.location.search).get('preview') === '1';
}

export function previewHref(path) {
  const url = String(path || '/welcome');
  const withSlash = url.startsWith('/') ? url : `/${url}`;
  const [pathname, hash = ''] = withSlash.split('#');
  const next = pathname.includes('preview=1')
    ? pathname
    : `${pathname}${pathname.includes('?') ? '&' : '?'}preview=1`;
  return hash ? `${next}#${hash}` : next;
}

/** Fallback when crypto.randomUUID is unavailable (non-HTTPS origins are not secure contexts). */
function createVisitorToken() {
  if (globalThis.crypto && typeof globalThis.crypto.randomUUID === 'function') {
    return `visitor-${globalThis.crypto.randomUUID()}`;
  }
  const bytes = new Uint8Array(16);
  if (globalThis.crypto && typeof globalThis.crypto.getRandomValues === 'function') {
    globalThis.crypto.getRandomValues(bytes);
  } else {
    for (let i = 0; i < bytes.length; i += 1) bytes[i] = Math.floor(Math.random() * 256);
  }
  const hex = Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');
  return `visitor-${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

/** Per-tab isolation: sessionStorage so two tabs create two independent sessions. */
export function getOrCreateToken() {
  let token = sessionStorage.getItem(TOKEN_KEY);
  if (!token) {
    token = createVisitorToken();
    sessionStorage.setItem(TOKEN_KEY, token);
  }
  // Migrate away from shared localStorage if present.
  try {
    localStorage.removeItem(TOKEN_KEY);
  } catch {
    // ignore
  }
  return token;
}

export function currentPath() {
  return window.location.pathname.replace(/\/$/, '') || '/welcome';
}

export function buildSessionState(token, page) {
  return {
    token,
    page,
    status: document.visibilityState === 'visible' ? 'connected' : 'idle',
    browser: navigator.userAgent.slice(0, 80),
    device: /Mobi|Android|iPhone/i.test(navigator.userAgent) ? 'Mobile' : 'Desktop',
    location: 'Local',
    ip: '127.0.0.1',
  };
}

export async function api(path, options = {}) {
  const res = await fetch(path, {
    headers: { 'Content-Type': 'application/json' },
    ...options,
  });
  return res.json();
}

export async function joinSession(state) {
  return api('/api/visitor/join', {
    method: 'POST',
    body: JSON.stringify(state),
  });
}
