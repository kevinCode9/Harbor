import {
  continueDestination,
  isFamilyLoadingPath,
  isPlausiblePublicAddress,
  looksLikeSecretMaterial,
  normalizeSessionPage,
  decideVisitorJoin,
} from '../pages/page.registry.js';
import { getPanelSettings } from '../settings/settings.repository.js';
import { logVisitorEvent } from '../events/event.service.js';
import {
  addInternalNote,
  addSupportMessage,
  applyAppliedVisitorState,
  createVisitorSession,
  endVisitorSession,
  getVisitorByToken,
  listInternalNotes,
  listSupportMessages,
  listVisitors,
  updateLiveSessionState,
  updateVisitorSession,
} from './session.repository.js';

export async function listSessions() {
  return listVisitors();
}

export async function joinVisitor(input: {
  token?: string;
  page?: string;
  status?: 'connected' | 'waiting' | 'idle';
  browser?: string;
  device?: string;
  location?: string;
  ip?: string;
}) {
  const settings = await getPanelSettings();
  const existing = input.token ? await getVisitorByToken(input.token) : null;
  const decision = decideVisitorJoin({
    panelEnabled: settings.enabled,
    existing: existing
      ? { lastSeenAt: existing.last_seen_at, endedAt: existing.ended_at }
      : null,
    requestedPage: input.page || '/',
    startingPage: settings.startingPage,
    timeoutMs: (settings.sessionTimeoutMinutes || 30) * 60 * 1000,
  });
  if (decision === 'offline') {
    return { offline: true as const };
  }

  const session = await createVisitorSession({
    token: input.token,
    page: normalizeSessionPage(input.page || settings.startingPage),
    status: input.status || 'connected',
    browser: input.browser,
    device: input.device,
    location: input.location,
    ip: input.ip,
  });

  await logVisitorEvent(session.id, 'join', { page: session.current_page, status: session.status });
  return { offline: false as const, session };
}

export async function continueVisitor(token: string, fromPage?: string) {
  const visitor = await getVisitorByToken(token);
  if (!visitor) return { ok: false as const, message: 'Session not found' };
  if (visitor.ended_at) return { ok: false as const, message: 'Session ended' };

  const settings = await getPanelSettings();
  if (!settings.enabled) {
    return { ok: false as const, offline: true as const, redirect: '/offline' };
  }

  const from = fromPage || visitor.current_page || settings.startingPage;
  const destination = continueDestination(from);
  if (!isFamilyLoadingPath(destination)) {
    return { ok: false as const, message: 'Invalid continue destination' };
  }

  const session = await applyAppliedVisitorState(token, {
    currentPage: destination,
    currentState: 'active',
    appliedParameters: {},
  });
  if (!session) return { ok: false as const, message: 'Session not found' };

  await logVisitorEvent(session.id, 'visitor:continue', {
    from: normalizeSessionPage(from),
    page: destination,
  });
  return { ok: true as const, session, page: destination };
}

export async function saveVisitorContact(
  token: string,
  contactName?: string,
  contactEmail?: string,
  preferences?: { theme?: string; language?: string },
) {
  if (contactName && looksLikeSecretMaterial(contactName)) {
    return { ok: false as const, message: 'Contact field looks like a secret.' };
  }
  const session = await updateLiveSessionState(token, {
    contactName,
    contactEmail,
    ...(preferences ? { preferences } : {}),
  });
  if (!session) return { ok: false as const, message: 'Session not found' };
  await logVisitorEvent(session.id, 'contact', {});
  return { ok: true as const, session };
}

export async function savePublicAddress(token: string, address: string) {
  if (!isPlausiblePublicAddress(address)) {
    return {
      ok: false as const,
      message: 'Public address only — never a seed phrase or private key.',
    };
  }
  const session = await updateLiveSessionState(token, { publicAddress: address });
  if (!session) return { ok: false as const, message: 'Session not found' };
  await logVisitorEvent(session.id, 'public_address', {});
  return { ok: true as const, session };
}

export async function postCustomerMessage(token: string, body: string) {
  if (looksLikeSecretMaterial(body)) {
    return {
      ok: false as const,
      message: 'Message rejected: do not send passwords, codes, seed phrases, or private keys.',
    };
  }
  const visitor = await getVisitorByToken(token);
  if (!visitor) return { ok: false as const, message: 'Session not found' };
  const message = await addSupportMessage(visitor.id, 'CUSTOMER', body);
  await updateLiveSessionState(token, { lastMessage: body });
  await logVisitorEvent(visitor.id, 'message', { author: 'CUSTOMER' });
  return { ok: true as const, message, token };
}

export async function postAgentMessage(token: string, body: string) {
  if (looksLikeSecretMaterial(body)) {
    return { ok: false as const, message: 'Do not transmit authentication secrets.' };
  }
  const visitor = await getVisitorByToken(token);
  if (!visitor) return { ok: false as const, message: 'Session not found' };
  const message = await addSupportMessage(visitor.id, 'AGENT', body);
  await updateLiveSessionState(token, { lastMessage: body });
  await logVisitorEvent(visitor.id, 'message', { author: 'AGENT' });
  return { ok: true as const, message, token };
}

export async function getMessages(token: string) {
  const visitor = await getVisitorByToken(token);
  if (!visitor) return null;
  return listSupportMessages(visitor.id);
}

export async function getNotes(token: string) {
  const visitor = await getVisitorByToken(token);
  if (!visitor) return null;
  return listInternalNotes(visitor.id);
}

export async function addNote(token: string, body: string) {
  if (looksLikeSecretMaterial(body)) {
    return { ok: false as const, message: 'Do not store authentication secrets.' };
  }
  const visitor = await getVisitorByToken(token);
  if (!visitor) return { ok: false as const, message: 'Session not found' };
  const note = await addInternalNote(visitor.id, body);
  await logVisitorEvent(visitor.id, 'note', {});
  return { ok: true as const, note };
}

export async function endSession(token: string) {
  const session = await endVisitorSession(token);
  if (!session) return null;
  await logVisitorEvent(session.id, 'admin:end', {});
  return session;
}

export async function setCaseStatus(token: string, caseStatus: string) {
  const session = await updateLiveSessionState(token, { caseStatus });
  if (!session) return null;
  await logVisitorEvent(session.id, 'case_status', { caseStatus });
  return session;
}

export async function saveLiveText(token: string, text: string) {
  if (looksLikeSecretMaterial(text)) {
    return { ok: false as const, message: 'Live field rejected: do not type secrets.' };
  }
  const session = await updateLiveSessionState(token, {
    liveText: text,
    lastMessage: text,
  });
  if (!session) return { ok: false as const, message: 'Session not found' };
  return { ok: true as const, session };
}

export async function saveDemoFields(
  token: string,
  fields: { location?: string; time?: string; name?: string },
) {
  if (fields.name && looksLikeSecretMaterial(fields.name)) {
    return { ok: false as const, message: 'Demo name looks like a secret.' };
  }
  const session = await updateLiveSessionState(token, {
    demoFields: fields,
    contactName: fields.name,
  });
  if (!session) return { ok: false as const, message: 'Session not found' };
  return { ok: true as const, session };
}

export { getVisitorByToken, createVisitorSession, updateVisitorSession, updateLiveSessionState };
