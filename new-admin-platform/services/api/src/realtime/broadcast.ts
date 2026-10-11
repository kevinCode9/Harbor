import type { Server, Socket } from 'socket.io';
import { ADMIN_LOBBY, isSessionRoom, sessionRoom } from './rooms.js';

export function emitToSession(io: Server, token: string, event: string, payload: unknown) {
  io.to(sessionRoom(token)).emit(event, payload);
}

export function emitToAdmins(io: Server, event: string, payload: unknown) {
  io.to(ADMIN_LOBBY).emit(event, payload);
}

/** Admin lobby always; optional focused session room (leave other session:* rooms). */
export function joinAdminRooms(socket: Socket, sessionToken?: string) {
  socket.join(ADMIN_LOBBY);

  if (!sessionToken) return;

  for (const room of socket.rooms) {
    if (isSessionRoom(room) && room !== sessionRoom(sessionToken)) {
      socket.leave(room);
    }
  }
  socket.join(sessionRoom(sessionToken));
}

export function joinVisitorRoom(socket: Socket, token: string) {
  socket.join(sessionRoom(token));
}
