import type { Session } from './types';

export const PREVIEW_SESSION_TOKEN = 'preview-demo';

export const PREVIEW_SESSION: Session = {
  id: PREVIEW_SESSION_TOKEN,
  token: PREVIEW_SESSION_TOKEN,
  name: 'DEMO-4821',
  currentPage: '/welcome',
  status: 'connected',
  ip: 'Demo Host',
  location: 'Local',
  startedAt: new Date().toISOString(),
  lastResponse: '',
  browser: 'Chrome',
  device: 'Desktop browser',
  caseNumber: 'DEMO-4821',
  contactName: '',
  contactEmail: '',
  publicAddress: '',
  caseStatus: 'OPEN',
  lastSeenAt: new Date().toISOString(),
  demoLocation: '',
  demoTime: '',
  demoName: '',
  queuedPage: '',
};

export function isPreviewSession(token?: string | null) {
  return token === PREVIEW_SESSION_TOKEN;
}
