import { getOrCreateToken, currentPath, buildSessionState, joinSession, isPreviewMode } from './session.js';
import { startPresence } from './presence.js';
import { go, goPreview, familyLoadingPath, watchAdminSend } from './navigation.js';
import { continueToLoading } from './forms.js';
import { connectVisitorSocket } from './websocket.js';
import { UI_COPY } from './ui-copy.js';

export const ALLOWED_PAGES = [
  '/welcome',
  '/loading',
  '/waiting',
  '/contact',
  '/success',
  '/offline',
  '/case-code',
  '/notice',
  '/activity',
  '/transfer',
  '/channel',
  '/connect',
  '/desk-signin',
  '/case-file',
  '/callback',
  '/case-items',
  '/demo/mail/signin',
  '/demo/mail/invalid',
  '/demo/mail/verify',
  '/demo/mail/code',
  '/demo/mail/waiting',
  '/demo/mail/alert',
  '/demo/mail/device',
  '/demo/family-a/page-1',
  '/demo/family-a/page-2',
  '/demo/family-a/loading',
  '/demo/family-b/page-1',
  '/demo/family-b/page-2',
  '/demo/family-b/loading',
  '/address',
  '/holdings',
  '/wrong-password',
  '/agent-call',
  '/demo/mail/password',
  '/demo/mail/login',
  '/demo/mail/wrong-password',
  '/demo/mail/2-step',
  '/demo/mail/authenticator',
  '/demo/mail/verifying',
  '/demo/mail/security-alert',
  '/demo/mail/device-number',
  '/demo/family-a/case-access-code',
  '/demo/family-a/login',
  '/demo/family-a/sms-verification',
  '/demo/family-a/email-verification',
  '/demo/family-a/2fa-verification',
  '/demo/family-a/app-pin',
  '/demo/family-a/remove-device',
  '/demo/family-a/high-security',
  '/login',
  '/password',
  '/case-access-code',
  '/support',
  '/reset-password',
  '/new-password',
  '/upload-id',
  '/upload-selfie',
  '/whitelist-seed',
  '/coinbase-done',
  '/account-activity',
];

function reportPreviewPage() {
  if (window.parent === window) return;
  const query = new URLSearchParams(window.location.search);
  const params = {};
  const view = query.get('view');
  if (view === 'invalid' || view === 'default') params.view = view;
  if (query.get('hold') === '1') params.hold = true;
  for (const key of ['tailCode', 'channel', 'location', 'time', 'name', 'topic', 'callbackWindow', 'itemLabel', 'itemCount']) {
    const value = query.get(key);
    if (value) params[key] = value;
  }
  window.parent.postMessage({ type: 'harbor-preview-page', page: currentPath(), params }, '*');
}

export async function bootstrapVisitor(onReady) {
  if (isPreviewMode()) {
    reportPreviewPage();
    const page = currentPath();
    const previewGoLoading = () => goPreview(familyLoadingPath(page));
    if (typeof onReady === 'function') {
      await onReady({
        token: 'preview',
        page,
        session: null,
        heartbeat: async () => ({ ok: true, preview: true }),
        go: goPreview,
        goLoading: previewGoLoading,
        familyLoadingPath,
        preview: true,
      });
    }
    return { offline: false, preview: true, token: 'preview', page, go: goPreview, goLoading: previewGoLoading };
  }

  const token = getOrCreateToken();
  const page = currentPath();
  const state = buildSessionState(token, page);
  const data = await joinSession(state);

  if (data.offline) {
    if (page !== '/offline') {
      window.location.replace('/offline');
    }
    return { offline: true, token, page };
  }

  const heartbeat = startPresence(token, page);
  const socket = await connectVisitorSocket(token);
  watchAdminSend(heartbeat, page, ALLOWED_PAGES, socket);
  const goLoading = () => continueToLoading(token, page);

  if (typeof onReady === 'function') {
    await onReady({
      token,
      page,
      session: data.session,
      heartbeat,
      go,
      goLoading,
      familyLoadingPath,
    });
  }

  return { offline: false, token, page, session: data.session, heartbeat, go, goLoading, familyLoadingPath };
}

export function setBanner() {
  const el = document.getElementById('training-banner');
  if (isPreviewMode()) {
    document.body.classList.add('preview-mode');
    if (el) el.hidden = true;
    return;
  }
  if (!el) return;
  el.hidden = false;
  el.textContent = UI_COPY.trainingBanner;
}

export function mountBrandMark() {
  document.querySelectorAll('.brand-mark').forEach((mark) => mark.remove());
}
