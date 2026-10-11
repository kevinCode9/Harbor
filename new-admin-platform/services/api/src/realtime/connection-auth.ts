export type SocketRole = 'admin' | 'visitor';

export function resolveSocketRole(payload: unknown): SocketRole | null {
  if (!payload || typeof payload !== 'object') return null;
  const role = (payload as { role?: unknown }).role;
  if (role === 'admin') return 'admin';
  if (role === 'visitor') return 'visitor';
  return null;
}

export function sessionTokenFromPayload(payload: unknown): string | undefined {
  if (!payload || typeof payload !== 'object') return undefined;
  const token = (payload as { sessionToken?: unknown; token?: unknown }).sessionToken
    ?? (payload as { token?: unknown }).token;
  return typeof token === 'string' && token.trim() ? token.trim() : undefined;
}

/** @deprecated use resolveSocketRole */
export function isAdminJoin(payload: unknown): boolean {
  return resolveSocketRole(payload) === 'admin';
}
