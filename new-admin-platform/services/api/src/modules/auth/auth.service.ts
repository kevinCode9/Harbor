import { z } from 'zod';
import { sanitizeAdminKeyInput } from '../pages/page.registry.js';
import { getActiveAdminKey, rotateAdminKey } from './auth.repository.js';
import type { AdminAuthResult } from './auth.types.js';

const adminAuthSchema = z.object({
  key: z.string().min(4),
});

const rotateSchema = z.object({
  currentKey: z.string().min(4),
  nextKey: z.string().min(8),
});

export async function authenticateAdminKey(rawKey: unknown): Promise<AdminAuthResult> {
  const suppliedKey = sanitizeAdminKeyInput(String(rawKey ?? ''));

  if (!suppliedKey || !adminAuthSchema.safeParse({ key: suppliedKey }).success) {
    return { ok: false, message: 'Invalid key payload' };
  }

  const active = await getActiveAdminKey();
  if (suppliedKey !== active) {
    return { ok: false, message: 'Invalid admin key' };
  }

  return { ok: true, authenticated: true, admin: 'master' };
}

export async function rotateActiveAdminKey(body: unknown): Promise<
  { ok: true; message: string } | { ok: false; message: string }
> {
  const parsed = rotateSchema.safeParse(body);
  if (!parsed.success) {
    return { ok: false, message: 'Invalid rotate payload' };
  }
  const result = await rotateAdminKey(parsed.data.currentKey, parsed.data.nextKey);
  if (!result.ok) return result;
  return {
    ok: true,
    message: 'Admin key rotated. Only the new key is active. Sign in again with the new key.',
  };
}
