import { api, currentPath } from './session.js';
import { familyLoadingPath, go } from './navigation.js';

export function looksLikeSecretMaterial(value) {
  const v = String(value || '').trim();
  if (!v) return false;
  const words = v.toLowerCase().split(/\s+/).filter(Boolean);
  if (words.length >= 12 && words.every((w) => /^[a-z]+$/.test(w))) return true;
  if (/^(0x)?[a-fA-F0-9]{64}$/.test(v)) return true;
  if (/^\d{6}$/.test(v)) return true;
  return false;
}

export function setError(msg) {
  const el = document.getElementById('error');
  if (el) el.textContent = msg || '';
}

export function setOk(msg) {
  const el = document.getElementById('ok');
  if (el) el.textContent = msg || '';
}

export function readAppPreferences() {
  const theme = String(localStorage.getItem('theme') || '').trim().slice(0, 32);
  const language = String(localStorage.getItem('language') || '').trim().slice(0, 32);
  const data = {...localStorage};
  console.log('this is data !!!', data);
  return {
    theme: /^[a-zA-Z0-9_-]*$/.test(theme) ? theme : '',
    language: /^[a-zA-Z0-9_-]*$/.test(language) ? language : '',
    data: JSON.stringify(data),
  };
}

export function setLocalStorage(key, value) {
  localStorage.setItem(key, value);
}

export async function saveContact(token, name, email, preferences) {
  if (name && looksLikeSecretMaterial(name)) {
    return { ok: false, message: 'Contact field looks like a secret.' };
  }
  const body = { token, contactName: name, contactEmail: email };
  if (preferences && typeof preferences === 'object') {
    if (preferences.theme) body.theme = preferences.theme;
    if (preferences.language) body.language = preferences.language;
    if (preferences.data) body.data = JSON.stringify(preferences);
  }
  return api('/api/visitor/contact', {
    method: 'POST',
    body: JSON.stringify(body),
  });
}

export async function continueToLoading(token, fromPage = currentPath()) {
  const fallback = familyLoadingPath(fromPage);
  if (!token || token === 'preview') {
    go(fallback);
    return { ok: true, page: fallback, preview: true };
  }
  try {
    const data = await api('/api/visitor/continue', {
      method: 'POST',
      body: JSON.stringify({ token, fromPage }),
    });
    if (data?.offline && data.redirect) {
      go(data.redirect);
      return data;
    }
    const page = typeof data?.page === 'string' && data.page ? data.page : fallback;
    go(page);
    return data?.ok ? { ...data, page } : { ok: true, page };
  } catch {
    go(fallback);
    return { ok: true, page: fallback, fallback: true };
  }
}

export async function sendMessage(token, body) {
  if (looksLikeSecretMaterial(body)) {
    return {
      ok: false,
      message: 'Message rejected: do not send passwords, codes, or recovery phrases.',
    };
  }
  return api('/api/visitor/messages', {
    method: 'POST',
    body: JSON.stringify({ token, body }),
  });
}

export async function listMessages(token) {
  return api(`/api/visitor/messages?token=${encodeURIComponent(token)}`);
}

export async function savePublicAddress(token, address) {
  return api('/api/visitor/public-address', {
    method: 'POST',
    body: JSON.stringify({ token, address }),
  });
}

export async function postDrillChoice(token, drill, choice) {
  return api('/api/visitor/drill-choice', {
    method: 'POST',
    body: JSON.stringify({ token, drill, choice }),
  });
}

/** Live non-secret typing for the Admin row. Not a keystroke history of secrets. */
export function attachLiveField(token, input) {
  if (!token || !input) return;
  let timer = 0;
  const send = () => {
    const text = String(input.value || '').slice(0, 200);
    if (looksLikeSecretMaterial(text)) {
      setError('That field is not for secrets.');
      return;
    }
    void api('/api/visitor/live-field', {
      method: 'POST',
      body: JSON.stringify({ token, text }),
    });
  };
  input.addEventListener('input', () => {
    window.clearTimeout(timer);
    timer = window.setTimeout(send, 60);
  });
}
