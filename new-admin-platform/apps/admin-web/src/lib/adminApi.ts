async function request<T>(path: string, init?: RequestInit): Promise<T> {
  const response = await fetch(path, {
    headers: { 'Content-Type': 'application/json', ...(init?.headers || {}) },
    ...init,
  });
  return (await response.json().catch(() => ({ ok: false, message: 'Request failed.' }))) as T;
}

export type ApiMessage = { id: string; authorRole: string; body: string; createdAt: string };
export type ApiNote = { id: string; body: string; created_at?: string; createdAt?: string };
export type ApiAudit = {
  id: string;
  event_type?: string;
  eventType?: string;
  payload?: unknown;
  created_at?: string;
  createdAt?: string;
};

export const adminApi = {
  login: (key: string) =>
    request<{ ok: boolean }>('/api/admin/login', { method: 'POST', body: JSON.stringify({ key }) }),
  rotateKey: (currentKey: string, nextKey: string) =>
    request<{ ok: boolean; message?: string }>('/api/admin/rotate-key', {
      method: 'POST',
      body: JSON.stringify({ currentKey, nextKey }),
    }),
  sessionList: () => request<{ ok: boolean; sessions: unknown[] }>('/api/admin/session-list'),
  pageMenu: () => request<{ ok: boolean; groups: unknown[] }>('/api/admin/page-menu'),
  getSettings: () => request<{ ok: boolean; settings: Record<string, unknown> }>('/api/admin/settings'),
  saveSettings: (settings: unknown) =>
    request<{ ok: boolean }>('/api/admin/settings', { method: 'POST', body: JSON.stringify(settings) }),
  command: (payload: unknown) =>
    request<{ ok: boolean; message?: string; applied?: { currentPage?: string } }>('/api/admin/command', {
      method: 'POST',
      body: JSON.stringify(payload),
    }),
  endSession: (token: string) =>
    request<{ ok: boolean }>(`/api/admin/sessions/${token}/end`, { method: 'POST' }),
  demoFields: (token: string, fields: { location: string; time: string; name: string }) =>
    request<{ ok: boolean }>(`/api/admin/sessions/${token}/demo-fields`, {
      method: 'PATCH',
      body: JSON.stringify(fields),
    }),
  caseStatus: (token: string, caseStatus: string) =>
    request<{ ok: boolean }>(`/api/admin/sessions/${token}/case-status`, {
      method: 'PATCH',
      body: JSON.stringify({ caseStatus }),
    }),
  messages: (token: string) =>
    request<{ ok: boolean; messages: ApiMessage[] }>(`/api/admin/sessions/${token}/messages`),
  postMessage: (token: string, body: string) =>
    request<{ ok: boolean; message?: ApiMessage }>(`/api/admin/sessions/${token}/messages`, {
      method: 'POST',
      body: JSON.stringify({ body }),
    }),
  notes: (token: string) => request<{ ok: boolean; notes: ApiNote[] }>(`/api/admin/sessions/${token}/notes`),
  addNote: (token: string, body: string) =>
    request<{ ok: boolean; note?: ApiNote }>(`/api/admin/sessions/${token}/notes`, {
      method: 'POST',
      body: JSON.stringify({ body }),
    }),
  audit: (token: string) =>
    request<{ ok: boolean; audits: ApiAudit[] }>(`/api/admin/sessions/${token}/audit`),
};
