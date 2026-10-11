import type { Server } from 'socket.io';
import {
  getVisitorByToken,
  listVisitors,
  toAdminSession,
  updateLiveSessionState,
} from '../modules/sessions/session.repository.js';
import { visitorJoinSchema } from '../modules/sessions/session.validation.js';
import {
  emitToAdmins,
  joinAdminRooms,
  joinVisitorRoom,
} from './broadcast.js';
import { resolveSocketRole, sessionTokenFromPayload } from './connection-auth.js';
import { REALTIME_EVENTS } from './events.js';

/**
 * WebSocket branch of the Node server:
 *   connection → admin lobby | session:{token}
 * Admin and Visitor both join the same session room for that case.
 */
export function attachRealtimeGateway(io: Server) {
  async function broadcastSessions() {
    emitToAdmins(io, REALTIME_EVENTS.sessionsUpdated, await listVisitors());
  }

  io.on('connection', (socket) => {
    socket.on(REALTIME_EVENTS.adminJoin, (payload: unknown) => {
      const role = resolveSocketRole(payload);
      if (role && role !== 'admin') return;

      joinAdminRooms(socket, sessionTokenFromPayload(payload));
      socket.emit(REALTIME_EVENTS.adminReady, {
        ok: true,
        socketId: socket.id,
        time: new Date().toISOString(),
      });
      void broadcastSessions();
    });

    socket.on(REALTIME_EVENTS.visitorJoin, async (payload) => {
      const safe = visitorJoinSchema.safeParse(payload || {});
      if (!safe.success) return;

      const existing = await getVisitorByToken(safe.data.token || '');
      if (!existing) return;

      joinVisitorRoom(socket, existing.token);
      socket.emit(REALTIME_EVENTS.visitorSession, existing);
    });

    socket.on(REALTIME_EVENTS.sessionPresence, async (payload) => {
      if (!payload || !payload.sessionToken) return;
      // LiveSessionState only — AppliedVisitorState.currentPage is Send-owned.
      const session = await updateLiveSessionState(payload.sessionToken, {
        status: payload.status,
        browser: payload.browser,
        device: payload.device,
        location: payload.location,
        ip: payload.ip,
        lastMessage: payload.lastMessage,
      });
      if (!session) return;
      socket.emit(REALTIME_EVENTS.presenceUpdate, session);
      emitToAdmins(io, REALTIME_EVENTS.sessionPatch, toAdminSession(session));
      await broadcastSessions();
    });
  });

  return { broadcastSessions };
}
