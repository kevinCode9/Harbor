import { pool } from '../../config/database.js';
import { ADMIN_KEY } from '../../config/env.js';
import { sanitizeAdminKeyInput } from '../pages/page.registry.js';

/** Env fallback used only when the DB has no active row (first boot race). */
export function getEnvAdminKey(): string {
  return sanitizeAdminKeyInput(ADMIN_KEY);
}

/** @deprecated Prefer getActiveAdminKey(); kept for sync callers during boot. */
export function getConfiguredAdminKey(): string {
  return getEnvAdminKey();
}

/**
 * Exactly one active educational admin key.
 * Seeded from ADMIN_KEY on schema init; rotation deactivates all others.
 */
export async function getActiveAdminKey(): Promise<string> {
  const { rows } = await pool.query<{ admin_key: string }>(
    `SELECT admin_key FROM admin_keys WHERE active = true ORDER BY created_at DESC LIMIT 1`,
  );
  if (rows[0]?.admin_key) {
    return sanitizeAdminKeyInput(rows[0].admin_key);
  }
  return getEnvAdminKey();
}

export async function ensureSeedAdminKey(): Promise<void> {
  const seed = getEnvAdminKey();
  if (!seed) return;

  const client = await pool.connect();
  try {
    await client.query('BEGIN');
    const { rows } = await client.query<{ count: string }>(
      `SELECT COUNT(*)::text AS count FROM admin_keys WHERE active = true`,
    );
    const activeCount = Number(rows[0]?.count || 0);
    if (activeCount === 0) {
      await client.query(`UPDATE admin_keys SET active = false WHERE active = true`);
      await client.query(
        `INSERT INTO admin_keys (admin_key, active)
         VALUES ($1, true)
         ON CONFLICT (admin_key) DO UPDATE SET active = true`,
        [seed],
      );
    } else if (activeCount > 1) {
      const { rows: keep } = await client.query<{ id: string }>(
        `SELECT id FROM admin_keys WHERE active = true ORDER BY created_at DESC LIMIT 1`,
      );
      if (keep[0]?.id) {
        await client.query(`UPDATE admin_keys SET active = false WHERE id <> $1`, [keep[0].id]);
      }
    }
    await client.query('COMMIT');
  } catch (error) {
    await client.query('ROLLBACK');
    throw error;
  } finally {
    client.release();
  }

  await pool.query(`
    CREATE UNIQUE INDEX IF NOT EXISTS admin_keys_one_active
    ON admin_keys ((active))
    WHERE active = true
  `);
}

/**
 * Educational key rotation (local/VPS alternative to a Telegram bot).
 * Requires the current active key. Only one key remains active.
 */
export async function rotateAdminKey(currentKey: string, nextKey: string): Promise<{ ok: true } | { ok: false; message: string }> {
  const current = sanitizeAdminKeyInput(currentKey);
  const next = sanitizeAdminKeyInput(nextKey);
  if (!next || next.length < 8) {
    return { ok: false, message: 'New key must be at least 8 characters' };
  }
  if (current === next) {
    return { ok: false, message: 'New key must differ from the current key' };
  }

  const active = await getActiveAdminKey();
  if (!current || current !== active) {
    return { ok: false, message: 'Current admin key is incorrect' };
  }

  const client = await pool.connect();
  try {
    await client.query('BEGIN');
    await client.query(`UPDATE admin_keys SET active = false WHERE active = true`);
    await client.query(
      `INSERT INTO admin_keys (admin_key, active)
       VALUES ($1, true)
       ON CONFLICT (admin_key) DO UPDATE SET active = true, created_at = NOW()`,
      [next],
    );
    await client.query('COMMIT');
    return { ok: true };
  } catch (error) {
    await client.query('ROLLBACK');
    throw error;
  } finally {
    client.release();
  }
}
