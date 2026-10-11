import type { SessionStatus } from '../pages/page.registry.js';

export type PresencePayload = {
  token: string;
  /** Incoming may still send legacy `waiting`; normalize at the service boundary. */
  status?: SessionStatus | 'waiting';
  browser?: string;
  device?: string;
  location?: string;
  ip?: string;
  lastMessage?: string;
};
