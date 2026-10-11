import { previewHref, currentPath } from './session.js';

export function go(path) {
  window.location.assign(String(path || ''));
}

export function familyLoadingPath(page) {
  const path = String(page || currentPath() || '').trim() || '/welcome';
  const withSlash = path.startsWith('/') ? path : `/${path}`;
  if (withSlash.startsWith('/demo/family-a')) return '/demo/family-a/loading';
  if (withSlash.startsWith('/demo/family-b')) return '/demo/family-b/loading';
  if (withSlash.startsWith('/demo/mail')) return '/demo/mail/waiting';
  return '/loading';
}

export function isFamilyLoadingPath(page) {
  const raw = String(page || '').trim();
  const path = (raw.startsWith('/') ? raw : `/${raw}`).split(/[?#]/)[0] || '';
  return (
    path === '/loading' ||
    path === '/demo/family-a/loading' ||
    path === '/demo/family-b/loading' ||
    path === '/demo/mail/waiting'
  );
}

export function isVisitorLandingPage(page) {
  const raw = String(page || '').trim();
  const path = (raw.startsWith('/') ? raw : `/${raw}`).split(/[?#]/)[0] || '';
  return path === '/welcome' || path === '/login' || path === '/desk-signin';
}

export function goLoading() {
  go(familyLoadingPath(currentPath()));
}

export function watchAdminSend(heartbeat, currentPage, allowed, socket) {
  let navigating = false;

  const maybeGo = (next, fromCommand = false) => {
    if (navigating || !next) return;
    const herePath = window.location.pathname.replace(/\/$/, '') || '/welcome';
    if (!fromCommand && isFamilyLoadingPath(herePath) && isVisitorLandingPage(next)) {
      return;
    }
    if (next !== currentPage && next !== herePath && (allowed.includes(next) || next === '/offline')) {
      navigating = true;
      go(next);
    }
  };

  if (socket) {
    socket.on('session:command', (payload) => {
      if (!payload || payload.action === 'preview') return;
      if (payload.action === 'end' || payload.action === 'redirect') {
        navigating = true;
        go(String(payload.page || '/offline'));
        return;
      }
      maybeGo(payload.page, true);
    });
  }

  setInterval(async () => {
    const data = await heartbeat();
    if (data?.offline && data.redirect) {
      navigating = true;
      go(data.redirect);
      return;
    }
    const next = data.session && (data.session.current_page || data.session.currentPage);
    maybeGo(next, false);
  }, 2500);
}

export function goPreview(path) {
  go(previewHref(path));
}
