import { useEffect, useMemo, useRef, useState } from 'react';
import {
  canSendSessionAction,
  createEmptyAdminDraft,
  normalizeSessionPage,
  sanitizeAdminKeyInput,
  sanitizeAppliedParameters,
  selectDraftAction,
  selectDraftPage,
  setDraftParameters,
  type AdminDraft,
} from '@shared';
import { LoginPanel } from '../pages/AdminLogin/AdminLoginPage';
import { AdminShell } from '../components/layout/AdminLayout';
import { PreviewFrame } from '../components/layout/PreviewFrame';
import { TopStatus } from '../components/layout/TopStatus';
import { SessionBoard } from '../components/sessions/SessionBoard';
import { SessionWorkspace } from '../components/sessions/SessionWorkspace';
import { SettingsPanel, type PanelSettings } from '../pages/Settings/SettingsPage';
import { useAdminAuth } from '../hooks/useAdminAuth';
import { useAdminSessions } from '../hooks/useAdminSessions';
import { adminApi } from '../lib/adminApi';
import { previewLocationHref, readPreviewLocation } from '../lib/preview';
import { defaultSettings } from '../lib/settings';
import { PREVIEW_SESSION, isPreviewSession } from '../lib/previewSession';

function initialPreview() {
  if (typeof window === 'undefined') return { page: null as string | null, params: {} as Record<string, unknown> };
  return readPreviewLocation(window.location.href);
}

export default function App() {
  const [view, setView] = useState<'panel' | 'settings'>('settings');
  const [focusedToken, setFocusedToken] = useState<string | null>(null);
  const [adminDraft, setAdminDraft] = useState<AdminDraft>(() => createEmptyAdminDraft('/welcome'));
  const [settings, setSettings] = useState<PanelSettings>(defaultSettings);
  const [previewUrl, setPreviewUrl] = useState<string | null>(() => initialPreview().page);
  const [previewParams, setPreviewParams] = useState<Record<string, unknown>>(() => initialPreview().params);
  const { authenticated, adminKey, setAdminKey, login, logout } = useAdminAuth();
  const { sessions } = useAdminSessions(focusedToken);
  const seenSessionTokens = useRef<Set<string> | null>(null);

  useEffect(() => {
    const tokens = sessions.map((session) => session.token);
    if (seenSessionTokens.current === null) {
      seenSessionTokens.current = new Set(tokens);
      return;
    }
    const added = tokens.some((token) => !seenSessionTokens.current?.has(token));
    for (const token of tokens) seenSessionTokens.current.add(token);
    if (!added || !settings.sound) return;
    try {
      const audio = new AudioContext();
      const tone = audio.createOscillator();
      const level = audio.createGain();
      tone.frequency.value = 880;
      level.gain.value = 0.04;
      tone.connect(level);
      level.connect(audio.destination);
      tone.start();
      tone.stop(audio.currentTime + 0.12);
      window.setTimeout(() => void audio.close(), 300);
    } catch {
      // The browser can block audio until the admin has interacted with the page.
    }
  }, [sessions, settings.sound]);

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const urlKey = sanitizeAdminKeyInput(params.get('key'));
    if (!urlKey) return;
    setAdminKey(urlKey);
    let cancelled = false;
    const delays = [0, 400, 800, 1600, 2500, 4000];
    (async () => {
      for (const delay of delays) {
        if (cancelled) return;
        if (delay) await new Promise((resolve) => setTimeout(resolve, delay));
        const ok = await login(urlKey);
        if (ok || cancelled) return;
      }
    })();
    return () => {
      cancelled = true;
    };
    // Initial sign-in only.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    void adminApi.getSettings().then((data) => {
      if (data.ok) setSettings({ ...defaultSettings, ...(data.settings as PanelSettings) });
    });
  }, []);

  const focused = useMemo(() => {
    if (isPreviewSession(focusedToken)) return PREVIEW_SESSION;
    return sessions.find((session) => session.token === focusedToken) ?? null;
  }, [sessions, focusedToken]);

  const cardSessions = sessions;

  const openPanelCards = () => {
    setFocusedToken(null);
    setView('panel');
    setAdminDraft((prev) => selectDraftAction(prev, 'preview'));
  };

  const syncPreviewLocation = (page: string | null, params: Record<string, unknown> = {}, replace = false) => {
    const next = previewLocationHref(window.location.href, page, params);
    const current = `${window.location.pathname}${window.location.search}${window.location.hash}`;
    if (next === current) return;
    const state = { screen: page };
    if (replace) window.history.replaceState(state, '', next);
    else window.history.pushState(state, '', next);
  };

  const openPagePreview = (page: string, params: Record<string, unknown> = {}) => {
    const requested = String(page || '').trim();
    const withSlash = requested.startsWith('/') ? requested : `/${requested}`;
    const nextPage = normalizeSessionPage(withSlash);
    if (nextPage !== withSlash) return;
    setAdminDraft((prev) => selectDraftAction(selectDraftPage(prev, nextPage), 'preview'));
    const clean = sanitizeAppliedParameters(params);
    setPreviewParams(clean);
    setPreviewUrl(nextPage);
    syncPreviewLocation(nextPage, clean);
  };

  useEffect(() => {
    if (!authenticated) return;
    const next = readPreviewLocation(window.location.href);
    if (!next.page) return;
    setPreviewUrl((current) => current ?? next.page);
    setPreviewParams((current) => (Object.keys(current).length > 0 ? current : next.params));
  }, [authenticated]);

  useEffect(() => {
    const onPop = () => {
      const next = readPreviewLocation(window.location.href);
      setPreviewUrl(next.page);
      setPreviewParams(next.params);
    };
    const onPreviewPage = (event: MessageEvent) => {
      const allowedOrigin = import.meta.env.DEV ? 'http://127.0.0.1:4000' : window.location.origin;
      if (event.origin !== allowedOrigin) return;
      const data = event.data as { type?: string; page?: string; params?: Record<string, unknown> } | null;
      if (!data || data.type !== 'harbor-preview-page') return;
      const requested = String(data.page || '');
      const path = requested.startsWith('/') ? requested : `/${requested}`;
      if (normalizeSessionPage(path) !== path) return;
      const params = sanitizeAppliedParameters(data.params);
      syncPreviewLocation(path, params, true);
    };
    window.addEventListener('popstate', onPop);
    window.addEventListener('message', onPreviewPage);
    return () => {
      window.removeEventListener('popstate', onPop);
      window.removeEventListener('message', onPreviewPage);
    };
  }, []);

  const sendToPage = async (page: string, params: Record<string, unknown> = {}) => {
    if (!focused || isPreviewSession(focused.token)) {
      return { ok: false, message: 'No live session.' };
    }
    if (!canSendSessionAction(focused.status)) {
      return { ok: false, message: 'Idle — Send does nothing until the tab is viewed.' };
    }
    const nextDraft = selectDraftAction(
      setDraftParameters(
        selectDraftPage(adminDraft, normalizeSessionPage(page)),
        sanitizeAppliedParameters(params),
      ),
      'send',
    );
    setAdminDraft(nextDraft);
    const data = await adminApi.command({
      sessionToken: focused.token,
      action: 'send',
      page: nextDraft.selectedPage,
      selectedFamily: nextDraft.selectedFamily,
      temporaryParameters: nextDraft.temporaryParameters,
    });
    if (!data.ok) {
      return { ok: false, message: data.message || 'Send failed.' };
    }
    setAdminDraft((prev) => ({
      ...prev,
      selectedPage: normalizeSessionPage(data.applied?.currentPage || nextDraft.selectedPage),
      selectedAction: null,
    }));
    return { ok: true, message: 'Sent' };
  };

  const patchDemoFields = async (fields: { location: string; time: string; name: string }) => {
    if (!focused || isPreviewSession(focused.token)) return;
    await adminApi.demoFields(focused.token, fields);
  };

  const saveSettings = async (next?: PanelSettings) => {
    await adminApi.saveSettings(next ?? settings);
  };

  const closeSession = async (token: string) => {
    if (isPreviewSession(token)) {
      setFocusedToken(null);
      return;
    }
    await adminApi.endSession(token);
    if (focusedToken === token) setFocusedToken(null);
  };

  if (!authenticated) {
    return <LoginPanel adminKey={adminKey} onKeyChange={setAdminKey} onLogin={() => login()} />;
  }

  return (
    <AdminShell
      view={view}
      onLogout={logout}
      onView={(next) => {
        if (next === 'panel') setFocusedToken(null);
        setView(next);
      }}
    >
      {view === 'settings' ? (
        <SettingsPanel
          settings={settings}
          onChange={setSettings}
          onSave={(next) => void saveSettings(next)}
          onPreview={(url) => {
            if (url) openPagePreview(url);
            else openPanelCards();
          }}
          onKeyRotated={() => logout()}
        />
      ) : (
        <div className="panel-stage">
          {focused ? (
            <SessionWorkspace
              session={focused}
              onSend={(url, params) => sendToPage(url, params)}
              onPreview={openPagePreview}
              onDemoFields={(fields) => void patchDemoFields(fields)}
            />
          ) : (
            <>
              <TopStatus count={cardSessions.length} enabled={settings.enabled} />
              <SessionBoard
                sessions={cardSessions}
                onView={(token) => {
                  const next = isPreviewSession(token)
                    ? PREVIEW_SESSION
                    : sessions.find((item) => item.token === token);
                  setFocusedToken(token);
                  setAdminDraft(createEmptyAdminDraft(normalizeSessionPage(next?.currentPage)));
                }}
                onClose={(token) => void closeSession(token)}
              />
            </>
          )}
        </div>
      )}
      {previewUrl ? (
        <PreviewFrame
          url={previewUrl}
          params={previewParams}
          onClose={() => {
            setPreviewUrl(null);
            setPreviewParams({});
            syncPreviewLocation(null);
          }}
        />
      ) : null}
    </AdminShell>
  );
}
