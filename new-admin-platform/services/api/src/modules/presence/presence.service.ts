import { looksLikeSecretMaterial, normalizePresenceStatus } from '../pages/page.registry.js';
import { logVisitorEvent } from '../events/event.service.js';
import { getVisitorByToken, updateLiveSessionState } from '../sessions/session.repository.js';
import { toLiveSessionState } from '../state/core-state.js';
import type { PresencePayload } from './presence.types.js';

/** LiveSessionState only — never writes AppliedVisitorState.currentPage. */
export async function updatePresence(payload: PresencePayload) {
  if (payload.lastMessage && looksLikeSecretMaterial(payload.lastMessage)) {
    return {
      ok: false as const,
      message: 'Do not send passwords, codes, seed phrases, or private keys.',
    };
  }

  const before = await getVisitorByToken(payload.token);
  if (!before) {
    return { ok: false as const, message: 'Session not found' };
  }

  const nextStatus = payload.status ? normalizePresenceStatus(payload.status) : before.status;
  const statusChanged = before.status !== nextStatus;

  const session = await updateLiveSessionState(payload.token, {
    status: payload.status ? nextStatus : undefined,
    browser: payload.browser,
    device: payload.device,
    location: payload.location,
    ip: payload.ip,
    lastMessage: payload.lastMessage,
    liveText: payload.lastMessage,
  });

  if (!session) {
    return { ok: false as const, message: 'Session not found' };
  }

  const live = toLiveSessionState({
    presence: session.status,
    currentPage: session.current_page,
    lastActivity: session.last_seen_at,
  });

  // Avoid flooding activity with every heartbeat — log presence only on Connected/Idle change.
  if (statusChanged) {
    await logVisitorEvent(session.id, 'presence', {
      status: live.presence,
      from: before.status,
    });
  }

  return { ok: true as const, session, live, statusChanged };
}
