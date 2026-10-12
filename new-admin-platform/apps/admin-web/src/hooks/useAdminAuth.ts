import { useCallback, useEffect, useRef, useState } from 'react';
import { sanitizeAdminKeyInput } from '@shared';
import { adminApi } from '../lib/adminApi';

const ADMIN_KEY_STORAGE = 'harbor.adminKey';

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
    // ignore quota / private mode
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
  const [adminKey, setAdminKey] = useState('');
  const [authReady, setAuthReady] = useState(false);
  const loginGeneration = useRef(0);

  const login = useCallback(async (key?: string) => {
    const value = (key ?? adminKey).trim();
    if (!value) {
      setAuthenticated(false);
      return false;
    }
    const generation = ++loginGeneration.current;
    try {
      const data = await adminApi.login(value);
      if (generation !== loginGeneration.current) return false;
      const ok = Boolean(data.ok);
      if (ok) {
        setAuthenticated(true);
        writeStoredKey(value);
        setAdminKey('');
        stripKeyFromUrl();
      } else {
        setAuthenticated(false);
        if (readStoredKey() === value) clearStoredKey();
      }
      return ok;
    } catch {
      if (generation !== loginGeneration.current) return false;
      setAuthenticated(false);
      // Keep stored key on transport errors so refresh can retry.
      return false;
    }
  }, [adminKey]);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      const urlKey = sanitizeAdminKeyInput(new URLSearchParams(window.location.search).get('key'));
      const stored = readStoredKey();
      const candidates = urlKey ? (stored && stored !== urlKey ? [urlKey, stored] : [urlKey]) : stored ? [stored] : [];

      for (const candidate of candidates) {
        if (cancelled) return;
        try {
          const data = await adminApi.login(candidate);
          if (cancelled) return;
          if (data.ok) {
            setAuthenticated(true);
            writeStoredKey(candidate);
            if (urlKey) stripKeyFromUrl();
            break;
          }
          if (readStoredKey() === candidate) clearStoredKey();
        } catch {
          // Leave storage intact; operator can retry after the API is back.
          break;
        }
      }

      if (!cancelled) setAuthReady(true);
    })();
    return () => {
      cancelled = true;
    };
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
