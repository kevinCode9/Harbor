/** Socket.IO room contract — matches services/realtime. */

export const ROOM_PREFIX = 'session:';
export const ADMIN_LOBBY = 'admin';

/** Per-session room, e.g. session:ABC */
export function sessionRoom(token: string): string {
  const cleaned = String(token || '').trim();
  if (!cleaned) return `${ROOM_PREFIX}unknown`;
  if (cleaned.startsWith(ROOM_PREFIX)) return cleaned;
  return `${ROOM_PREFIX}${cleaned}`;
}

export function tokenFromRoom(room: string): string | null {
  if (!room.startsWith(ROOM_PREFIX)) return null;
  return room.slice(ROOM_PREFIX.length) || null;
}

export function isSessionRoom(room: string): boolean {
  return room.startsWith(ROOM_PREFIX);
}
