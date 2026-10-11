import { randomBytes, randomUUID } from 'crypto';
import { pool } from '../../config/database.js';
import { DEFAULT_PAGE, normalizePresenceStatus, type SessionStatus } from '../pages/page.registry.js';

export type VisitorStatus = SessionStatus;

export type VisitorRecord = {
  id: string;
  token: string;
  name: string;
  current_page: string;
  status: VisitorStatus;
  ip: string | null;
  location: string | null;
  browser: string | null;
  device: string | null;
  started_at: string;
  last_seen_at: string;
  case_number: string | null;
  last_message: string | null;
  contact_name: string | null;
  contact_email: string | null;
  public_address: string | null;
  case_status: string;
  ended_at: string | null;
  metadata: Record<string, unknown>;
};

export type AdminSessionView = {
  id: string;
  token: string;
  name: string;
  currentPage: string;
  status: VisitorStatus;
  ip: string;
  location: string;
  startedAt: string;
  lastResponse: string;
  browser: string;
  device: string;
  caseNumber: string;
  contactName: string;
  contactEmail: string;
  publicAddress: string;
  caseStatus: string;
  lastSeenAt: string;
  demoLocation: string;
  demoTime: string;
  demoName: string;
  liveText: string;
  queuedPage: string;
  theme: string;
  language: string;
};

function sessionPreferences(row: VisitorRecord): { theme: string; language: string } {
  const raw = row.metadata?.preferences;
  if (!raw || typeof raw !== 'object') return { theme: '', language: '' };
  const prefs = raw as { theme?: unknown; language?: unknown };
  return {
    theme: typeof prefs.theme === 'string' ? prefs.theme : '',
    language: typeof prefs.language === 'string' ? prefs.language : '',
  };
}

export function toAdminSession(row: VisitorRecord): AdminSessionView {
  const prefs = sessionPreferences(row);
  return {
    id: row.id,
    token: row.token,
    name: row.name,
    currentPage: row.current_page,
    status: normalizePresenceStatus(row.status),
    ip: row.ip || 'unknown',
    location: row.location || 'unknown',
    startedAt: row.started_at,
    lastResponse: row.last_message || '',
    browser: row.browser || '',
    device: row.device || '',
    caseNumber: row.case_number || '',
    contactName: row.contact_name || demoName(row),
    contactEmail: row.contact_email || '',
    publicAddress: row.public_address || '',
    caseStatus: row.case_status || 'OPEN',
    lastSeenAt: row.last_seen_at,
    demoLocation: demoField(row, 'location'),
    demoTime: demoField(row, 'time'),
    demoName: demoName(row),
    liveText: liveText(row),
    queuedPage: queuedPage(row),
    theme: prefs.theme,
    language: prefs.language,
  };
}

function demoFields(row: VisitorRecord): Record<string, string> {
  const raw = row.metadata?.demoFields;
  if (!raw || typeof raw !== 'object') return {};
  return raw as Record<string, string>;
}

function demoField(row: VisitorRecord, key: string): string {
  return String(demoFields(row)[key] || '');
}

function demoName(row: VisitorRecord): string {
  return demoField(row, 'name') || row.contact_name || '';
}

function liveText(row: VisitorRecord): string {
  const fromMeta = row.metadata?.liveText;
  if (typeof fromMeta === 'string' && fromMeta) return fromMeta;
  return row.last_message || demoName(row) || '';
}

function queuedPage(row: VisitorRecord): string {
  const queued = row.metadata?.queuedSend;
  if (!queued || typeof queued !== 'object') return '';
  return String((queued as { page?: unknown }).page || '');
}

function generateCaseNumber(): string {
  const n = randomBytes(3).readUIntBE(0, 3) % 1_000_000;
  return `HBR-${String(n).padStart(6, '0')}`;
}

export async function createVisitorSession(input: {
  token?: string;
  page?: string;
  status?: VisitorStatus;
  browser?: string;
  device?: string;
  location?: string;
  ip?: string;
}): Promise<VisitorRecord> {
  const token = input.token || `visitor-${randomUUID().slice(0, 12)}`;
  const existing = await getVisitorByToken(token);
  if (existing) {
    // Rejoin updates LiveSessionState only — never AppliedVisitorState.currentPage.
    return (await updateLiveSessionState(token, {
      status: input.status,
      browser: input.browser,
      device: input.device,
      location: input.location,
      ip: input.ip,
    })) as VisitorRecord;
  }

  const now = new Date().toISOString();
  const id = randomUUID();
  const caseNumber = generateCaseNumber();

  const result = await pool.query(
    `
    INSERT INTO visitors (
      id, token, name, current_page, status, ip, location, browser, device,
      started_at, last_seen_at, metadata, case_number, case_status
    )
    VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, 'OPEN')
    RETURNING *;
  `,
    [
      id,
      token,
      `case-${token.slice(-4)}`,
      input.page || DEFAULT_PAGE,
      input.status ? normalizePresenceStatus(input.status) : 'connected',
      input.ip || null,
      input.location || null,
      input.browser || null,
      input.device || null,
      now,
      now,
      JSON.stringify({ connectedAt: now }),
      caseNumber,
    ],
  );

  return mapRow(result.rows[0]);
}

/**
 * LiveSessionState updates (presence, contact, messages).
 * Intentionally has no `page` field — AppliedVisitorState is Send-only.
 */
export async function updateLiveSessionState(
  token: string,
  patch: Partial<{
    status: VisitorStatus;
    browser: string;
    device: string;
    location: string;
    ip: string;
    lastMessage: string;
    contactName: string;
    contactEmail: string;
    publicAddress: string;
    caseStatus: string;
    demoFields: { location?: string; time?: string; name?: string };
    liveText: string;
    preferences: { theme?: string; language?: string };
  }>,
): Promise<VisitorRecord | null> {
  const current = await getVisitorByToken(token);
  if (!current) return null;

  const nextStatus = patch.status ? normalizePresenceStatus(patch.status) : current.status;
  const nextDemo = {
    ...demoFields(current),
    ...(patch.demoFields || {}),
  };
  const metadata: Record<string, unknown> = {
    ...current.metadata,
    demoFields: nextDemo,
  };
  if (patch.liveText !== undefined) {
    metadata.liveText = patch.liveText;
  }
  if (patch.preferences) {
    const previous =
      current.metadata?.preferences && typeof current.metadata.preferences === 'object'
        ? (current.metadata.preferences as Record<string, string>)
        : {};
    const nextTheme = patch.preferences.theme;
    const nextLanguage = patch.preferences.language;
    metadata.preferences = {
      ...previous,
      ...(nextTheme ? { theme: nextTheme } : {}),
      ...(nextLanguage ? { language: nextLanguage } : {}),
    };
  }
  const nextName = patch.contactName ?? (patch.demoFields?.name !== undefined ? patch.demoFields.name : current.contact_name);

  const result = await pool.query(
    `
    UPDATE visitors
    SET status = $2,
        browser = $3,
        device = $4,
        location = $5,
        ip = $6,
        last_seen_at = $7,
        last_message = $8,
        contact_name = $9,
        contact_email = $10,
        public_address = $11,
        case_status = $12,
        metadata = $13::jsonb
    WHERE token = $1
    RETURNING *;
  `,
    [
      token,
      nextStatus,
      patch.browser ?? current.browser,
      patch.device ?? current.device,
      patch.location ?? current.location,
      patch.ip ?? current.ip,
      new Date().toISOString(),
      patch.lastMessage ?? current.last_message,
      nextName,
      patch.contactEmail ?? current.contact_email,
      patch.publicAddress ?? current.public_address,
      patch.caseStatus ?? current.case_status,
      JSON.stringify(metadata),
    ],
  );

  if (!result.rows[0]) return null;
  return mapRow(result.rows[0]);
}

/** @deprecated Prefer updateLiveSessionState or applyAppliedVisitorState. */
export async function updateVisitorSession(
  token: string,
  patch: Partial<{
    page: string;
    status: VisitorStatus;
    browser: string;
    device: string;
    location: string;
    ip: string;
    lastMessage: string;
    contactName: string;
    contactEmail: string;
    publicAddress: string;
    caseStatus: string;
  }>,
): Promise<VisitorRecord | null> {
  if (patch.page !== undefined) {
    const applied = await applyAppliedVisitorState(token, {
      currentPage: patch.page,
      currentState:
        patch.status && normalizePresenceStatus(patch.status) === 'idle' ? 'idle' : 'active',
      appliedParameters: {},
    });
    if (!applied) return null;
    const { page: _page, ...live } = patch;
    if (Object.keys(live).length === 0) return applied;
    return updateLiveSessionState(token, live);
  }
  return updateLiveSessionState(token, patch);
}

/**
 * Send pipeline only: AdminDraft → AppliedVisitorState (DB transaction).
 * Writes current_page + metadata.applied. Never called from presence.
 */
export async function applyAppliedVisitorState(
  token: string,
  applied: {
    currentPage: string;
    currentState: 'active' | 'idle' | 'ended';
    appliedParameters: Record<string, unknown>;
  },
): Promise<VisitorRecord | null> {
  const client = await pool.connect();
  try {
    await client.query('BEGIN');
    const current = await client.query('SELECT * FROM visitors WHERE token = $1 LIMIT 1 FOR UPDATE;', [
      token,
    ]);
    if (!current.rows[0]) {
      await client.query('ROLLBACK');
      return null;
    }

    const row = mapRow(current.rows[0]);
    const nextStatus: VisitorStatus =
      applied.currentState === 'idle'
        ? 'idle'
        : applied.currentState === 'ended'
          ? 'disconnected'
          : row.status === 'disconnected'
            ? 'connected'
            : row.status;

    const metadata: Record<string, unknown> = {
      ...row.metadata,
      applied: {
        currentPage: applied.currentPage,
        currentState: applied.currentState,
        appliedParameters: applied.appliedParameters,
        at: new Date().toISOString(),
      },
    };
    delete metadata.queuedSend;

    const result = await client.query(
      `
      UPDATE visitors
      SET current_page = $2,
          status = $3,
          metadata = $4::jsonb,
          last_seen_at = $5
      WHERE token = $1
      RETURNING *;
    `,
      [token, applied.currentPage, nextStatus, JSON.stringify(metadata), new Date().toISOString()],
    );

    await client.query('COMMIT');
    return result.rows[0] ? mapRow(result.rows[0]) : null;
  } catch (error) {
    await client.query('ROLLBACK');
    throw error;
  } finally {
    client.release();
  }
}

export async function getVisitorByToken(token: string): Promise<VisitorRecord | null> {
  const result = await pool.query('SELECT * FROM visitors WHERE token = $1 LIMIT 1;', [token]);
  return result.rows[0] ? mapRow(result.rows[0]) : null;
}

export async function setQueuedSend(
  token: string,
  queuedSend: {
    page: string;
    action: 'send' | 'redirect';
    selectedFamily: string;
    temporaryParameters: Record<string, unknown>;
    at: string;
  } | null,
): Promise<VisitorRecord | null> {
  const current = await getVisitorByToken(token);
  if (!current) return null;
  const metadata = { ...current.metadata };
  if (queuedSend) metadata.queuedSend = queuedSend;
  else delete metadata.queuedSend;
  const result = await pool.query(
    `UPDATE visitors SET metadata = $2::jsonb, last_seen_at = $3 WHERE token = $1 RETURNING *;`,
    [token, JSON.stringify(metadata), new Date().toISOString()],
  );
  return result.rows[0] ? mapRow(result.rows[0]) : null;
}

export async function listVisitors(): Promise<AdminSessionView[]> {
  const result = await pool.query(
    `SELECT * FROM visitors WHERE ended_at IS NULL ORDER BY last_seen_at DESC;`,
  );
  return result.rows.map((row) => toAdminSession(mapRow(row)));
}

export async function endVisitorSession(token: string): Promise<VisitorRecord | null> {
  const result = await pool.query(
    `UPDATE visitors SET ended_at = NOW(), status = 'disconnected', case_status = 'CLOSED' WHERE token = $1 RETURNING *;`,
    [token],
  );
  return result.rows[0] ? mapRow(result.rows[0]) : null;
}

export type PanelSettings = {
  enabled: boolean;
  notifications: boolean;
  startingPage: string;
  sessionTimeoutMinutes: number;
  sound: boolean;
  theme: string;
};

export async function getPanelSettings(): Promise<PanelSettings> {
  const result = await pool.query(`SELECT value FROM admin_settings WHERE key_name = 'panel' LIMIT 1;`);
  const value = (result.rows[0]?.value || {}) as Partial<PanelSettings>;
  return {
    enabled: value.enabled !== false,
    notifications: value.notifications !== false,
    startingPage: value.startingPage || DEFAULT_PAGE,
    sessionTimeoutMinutes: Number(value.sessionTimeoutMinutes || 30),
    sound: value.sound !== false,
    theme: value.theme || 'dark',
  };
}

export async function savePanelSettings(patch: Partial<PanelSettings>): Promise<PanelSettings> {
  const current = await getPanelSettings();
  const next = { ...current, ...patch };
  if (patch.startingPage) {
    next.startingPage = patch.startingPage.startsWith('/') ? patch.startingPage : `/${patch.startingPage}`;
  }
  await pool.query(
    `INSERT INTO admin_settings (key_name, value, updated_at)
     VALUES ('panel', $1::jsonb, NOW())
     ON CONFLICT (key_name) DO UPDATE SET value = EXCLUDED.value, updated_at = NOW();`,
    [JSON.stringify(next)],
  );
  return next;
}

export async function addInternalNote(visitorId: string, body: string) {
  const result = await pool.query(
    `INSERT INTO internal_notes (visitor_id, body) VALUES ($1, $2) RETURNING id, body, created_at;`,
    [visitorId, body],
  );
  return result.rows[0];
}

export async function listInternalNotes(visitorId: string) {
  const result = await pool.query(
    `SELECT id, body, created_at FROM internal_notes WHERE visitor_id = $1 ORDER BY created_at DESC;`,
    [visitorId],
  );
  return result.rows;
}

export async function listAudit(visitorId: string) {
  const result = await pool.query(
    `SELECT id, event_type, payload, created_at FROM visitor_activity WHERE visitor_id = $1 ORDER BY created_at DESC LIMIT 80;`,
    [visitorId],
  );
  return result.rows;
}

export async function logVisitorEvent(
  visitorId: string,
  eventType: string,
  payload: Record<string, unknown> = {},
): Promise<void> {
  await pool.query(
    'INSERT INTO visitor_activity (visitor_id, event_type, payload) VALUES ($1, $2, $3);',
    [visitorId, eventType, JSON.stringify(payload)],
  );
}

export async function addSupportMessage(
  visitorId: string,
  authorRole: 'CUSTOMER' | 'AGENT',
  body: string,
): Promise<{ id: string; body: string; authorRole: string; createdAt: string }> {
  const result = await pool.query(
    `INSERT INTO support_messages (visitor_id, author_role, body)
     VALUES ($1, $2, $3)
     RETURNING id, body, author_role, created_at;`,
    [visitorId, authorRole, body],
  );
  const row = result.rows[0];
  return {
    id: row.id,
    body: row.body,
    authorRole: row.author_role,
    createdAt: row.created_at,
  };
}

export async function listSupportMessages(visitorId: string) {
  const result = await pool.query(
    `SELECT id, author_role, body, created_at
     FROM support_messages
     WHERE visitor_id = $1
     ORDER BY created_at ASC`,
    [visitorId],
  );
  return result.rows.map((row) => ({
    id: row.id,
    authorRole: row.author_role,
    body: row.body,
    createdAt: row.created_at,
  }));
}

function mapRow(row: Record<string, unknown>): VisitorRecord {
  return {
    id: String(row.id),
    token: String(row.token),
    name: String(row.name ?? ''),
    current_page: String(row.current_page),
    status: normalizePresenceStatus(String(row.status)),
    ip: (row.ip as string) || null,
    location: (row.location as string) || null,
    browser: (row.browser as string) || null,
    device: (row.device as string) || null,
    started_at: String(row.started_at),
    last_seen_at: String(row.last_seen_at),
    case_number: (row.case_number as string) || null,
    last_message: (row.last_message as string) || null,
    contact_name: (row.contact_name as string) || null,
    contact_email: (row.contact_email as string) || null,
    public_address: (row.public_address as string) || null,
    case_status: String(row.case_status || 'OPEN'),
    ended_at: row.ended_at ? String(row.ended_at) : null,
    metadata: (row.metadata as Record<string, unknown>) || {},
  };
}
