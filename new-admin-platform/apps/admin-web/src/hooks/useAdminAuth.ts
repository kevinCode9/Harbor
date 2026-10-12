import { useCallback, useEffect, useState } from 'react';
import { adminApi } from '../lib/adminApi';

const ADMIN_KEY_STORAGE = 'harbor.adminUnlockKey';

function stripKeyFromUrl() {
  const url = new URL(window.location.href);
  if (!url.searchParams.has('key')) return;
  url.searchParams.delete('key');
  const next = `${url.pathname}${url.search}${url.hash}` || '/';
  window.history.replaceState(window.history.state, '', next);
}

function readStoredKey(): string {
  try {
    return String(sessionStorage.getItem(ADMIN_KEY_STORAGE) || '').trim();
  } catch {
    return '';
  }
}

function writeStoredKey(key: string) {
  try {
    sessionStorage.setItem(ADMIN_KEY_STORAGE, key);
  } catch {
    // ignore quota / private-mode failures
  }
}

function clearStoredKey() {
  try {
    sessionStorage.removeItem(ADMIN_KEY_STORAGE);
  } catch {
    // ignore
  }
}

export function useAdminAuth() {
  const [authenticated, setAuthenticated] = useState(false);
  const [authReady, setAuthReady] = useState(() => !readStoredKey());
  const [adminKey, setAdminKey] = useState('');

  const login = useCallback(async (key?: string) => {
    const value = (key ?? adminKey).trim();
    if (!value) {
      setAuthenticated(false);
      return false;
    }
    stripKeyFromUrl();
    try {
      const data = await adminApi.login(value);
      const ok = Boolean(data.ok);
      setAuthenticated(ok);
      if (ok) {
        writeStoredKey(value);
        setAdminKey('');
      } else {
        clearStoredKey();
      }
      return ok;
    } catch {
      setAuthenticated(false);
      clearStoredKey();
      return false;
    }
  }, [adminKey]);

  useEffect(() => {
    const stored = readStoredKey();
    if (!stored) {
      setAuthReady(true);
      return;
    }
    let cancelled = false;
    (async () => {
      const ok = await login(stored);
      if (!cancelled) {
        if (!ok) clearStoredKey();
        setAuthReady(true);
      }
    })();
    return () => {
      cancelled = true;
    };
    // Restore once on mount from sessionStorage.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return {
    authenticated,
    authReady,
    adminKey,
    setAdminKey,
    login,
    logout: () => {
      clearStoredKey();
      setAuthenticated(false);
    },
  };
}
