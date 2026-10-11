/**
 * Core state model — keep these three separate.
 *
 * AdminDraft ──Send──► AppliedVisitorState
 * Visitor live ──────► LiveSessionState
 *
 * Page clicks only mutate AdminDraft. Visitor moves only after Send.
 */

export type AdminAction = 'preview' | 'send' | 'redirect';

export type AdminDraft = {
  selectedFamily: string;
  selectedPage: string;
  selectedAction: AdminAction | null;
  temporaryParameters: Record<string, unknown>;
};

export type AppliedVisitorState = {
  currentPage: string;
  currentState: 'active' | 'idle' | 'ended';
  appliedParameters: Record<string, unknown>;
};

export type LivePresence = 'connected' | 'idle' | 'disconnected';

/** Fields presence / visitor live updates may touch — never AdminDraft. */
export const PERMITTED_LIVE_FIELDS = [
  'presence',
  'lastActivity',
  'browser',
  'device',
  'location',
  'ip',
  'lastMessage',
  'contactName',
  'contactEmail',
  'publicAddress',
] as const;

export type PermittedLiveField = (typeof PERMITTED_LIVE_FIELDS)[number];

export type LiveSessionState = {
  presence: LivePresence;
  /** Mirror of applied page for display; presence must not write this. */
  currentPage: string;
  lastActivity: string;
  permittedLiveFields: readonly PermittedLiveField[];
};

export function createEmptyAdminDraft(page = '/welcome', family = ''): AdminDraft {
  return {
    selectedFamily: family,
    selectedPage: page,
    selectedAction: null,
    temporaryParameters: {},
  };
}

/** Page / family clicks — AdminDraft only. Never touches Applied or Live. */
export function selectDraftPage(
  draft: AdminDraft,
  page: string,
  family?: string,
): AdminDraft {
  return {
    ...draft,
    selectedPage: page,
    selectedFamily: family ?? draft.selectedFamily,
    selectedAction: null,
  };
}

export function selectDraftAction(draft: AdminDraft, action: AdminAction): AdminDraft {
  return { ...draft, selectedAction: action };
}

export function setDraftParameters(
  draft: AdminDraft,
  temporaryParameters: Record<string, unknown>,
): AdminDraft {
  return { ...draft, temporaryParameters };
}

/**
 * Send transition: AdminDraft → AppliedVisitorState.
 * Preview must not call this. Redirect may set currentState to idle.
 */
export function draftToApplied(
  draft: AdminDraft,
  action: Exclude<AdminAction, 'preview'>,
): AppliedVisitorState {
  return {
    currentPage: draft.selectedPage,
    currentState: action === 'redirect' ? 'idle' : 'active',
    appliedParameters: { ...draft.temporaryParameters },
  };
}

export function toLiveSessionState(input: {
  presence: LivePresence;
  currentPage: string;
  lastActivity: string;
}): LiveSessionState {
  return {
    presence: input.presence,
    currentPage: input.currentPage,
    lastActivity: input.lastActivity,
    permittedLiveFields: PERMITTED_LIVE_FIELDS,
  };
}

/** True when draft page differs from applied — Send still required. */
export function draftDiffersFromApplied(
  draft: AdminDraft,
  appliedPage: string | null | undefined,
): boolean {
  return draft.selectedPage !== (appliedPage || '');
}
