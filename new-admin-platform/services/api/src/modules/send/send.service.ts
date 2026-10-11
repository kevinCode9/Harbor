import type { Server } from 'socket.io';
import {
  ALLOWED_PAGE_URLS,
  canSendSessionAction,
  normalizeSessionPage,
  sanitizeAppliedParameters,
} from '../pages/page.registry.js';
import { draftToApplied, type AdminDraft } from '../state/core-state.js';
import { logVisitorEvent } from '../events/event.service.js';
import {
  applyAppliedVisitorState,
  getVisitorByToken,
  toAdminSession,
} from '../sessions/session.repository.js';
import { emitToAdmins, emitToSession } from '../../realtime/broadcast.js';
import { REALTIME_EVENTS } from '../../realtime/events.js';
import type { SendCommand } from './send.types.js';

/**
 * AdminDraft → validate → apply now if Connected. Idle Send is refused (no queue).
 * Preview never enters the applied path.
 */
export async function applyAdminCommand(
  io: Server,
  command: SendCommand,
  broadcastSessions: () => Promise<void>,
) {
  const currentSession = await getVisitorByToken(command.sessionToken);
  if (!currentSession) {
    return { ok: false as const, status: 404, message: 'Session not found' };
  }

  if (command.action !== 'preview' && !canSendSessionAction(currentSession.status)) {
    return {
      ok: false as const,
      status: 409,
      message: 'Idle — Send does nothing until the tab is viewed.',
    };
  }

  const normalizedPage = normalizeSessionPage(command.page ?? currentSession.current_page);
  if (command.page && !ALLOWED_PAGE_URLS.has(normalizedPage)) {
    return { ok: false as const, status: 400, message: 'Unknown support screen' };
  }

  const draft: AdminDraft = {
    selectedFamily: command.selectedFamily || '',
    selectedPage: normalizedPage,
    selectedAction: command.action,
    temporaryParameters: sanitizeAppliedParameters(command.temporaryParameters),
  };

  if (command.action === 'preview') {
    await logVisitorEvent(currentSession.id, 'admin:preview', { page: draft.selectedPage });
    return {
      ok: true as const,
      preview: true,
      page: draft.selectedPage,
      draft,
      session: currentSession,
    };
  }

  return commitSend(io, command.sessionToken, draft, command.action, broadcastSessions);
}

async function commitSend(
  io: Server,
  token: string,
  draft: AdminDraft,
  action: 'send' | 'redirect',
  broadcastSessions: () => Promise<void>,
) {
  const applied = draftToApplied(draft, action);
  const session = await applyAppliedVisitorState(token, applied);
  if (!session) {
    return { ok: false as const, status: 404, message: 'Session not found' };
  }

  await logVisitorEvent(session.id, `admin:${action}`, {
    page: session.current_page,
    status: session.status,
    applied,
  });

  const adminRow = toAdminSession(session);
  emitToAdmins(io, REALTIME_EVENTS.sessionPatch, adminRow);
  await broadcastSessions();

  emitToSession(io, token, REALTIME_EVENTS.sessionCommand, {
    sessionToken: token,
    action,
    page: applied.currentPage,
    status: session.status,
    applied,
    at: new Date().toISOString(),
  });

  return { ok: true as const, session, applied };
}
