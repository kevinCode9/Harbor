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

/** Per-tab isolation: sessionStorage so two tabs create two independent sessions. */
export function getOrCreateToken() {
  let token = sessionStorage.getItem(TOKEN_KEY);
  if (!token) {
    token = `visitor-${crypto.randomUUID()}`;
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
