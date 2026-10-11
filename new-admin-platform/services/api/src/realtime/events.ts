/**
 * Wire event names shared by REST→WS fan-out and client listeners.
 *
 * REST mutates PostgreSQL; WebSocket notifies room members.
 * Preview never emits session:command (visitor must not move).
 */
export const REALTIME_EVENTS = {
  sessionsUpdated: 'sessions:updated',
  sessionPatch: 'session:patch',
  messageNew: 'message:new',
  sessionCommand: 'session:command',
  adminReady: 'admin:ready',
  visitorSession: 'visitor:session',
  presenceUpdate: 'session:presence:update',
  adminJoin: 'admin:join',
  visitorJoin: 'visitor:join',
  sessionPresence: 'session:presence',
} as const;

export type RealtimeEvent = (typeof REALTIME_EVENTS)[keyof typeof REALTIME_EVENTS];
