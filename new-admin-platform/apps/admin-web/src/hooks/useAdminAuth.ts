import { useCallback, useState } from 'react';
import { adminApi } from '../lib/adminApi';

function stripKeyFromUrl() {
  const url = new URL(window.location.href);
  if (!url.searchParams.has('key')) return;
  url.searchParams.delete('key');
  const next = `${url.pathname}${url.search}${url.hash}` || '/';
  window.history.replaceState(window.history.state, '', next);
}

export function useAdminAuth() {
  const [authenticated, setAuthenticated] = useState(false);
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
      if (ok) setAdminKey('');
      return ok;
    } catch {
      setAuthenticated(false);
      return false;
    }
  }, [adminKey]);

  return {
    authenticated,
    adminKey,
    setAdminKey,
    login,
    logout: () => {
      setAuthenticated(false);
    },
  };
}
