import { useEffect, useState } from 'react';
import { io } from 'socket.io-client';
import { mergeSessionRow } from '@shared';
import type { Session } from '../lib/types';
import { adminApi } from '../lib/adminApi';

function asSessions(payload: unknown): Session[] | null {
  if (Array.isArray(payload)) return payload as Session[];
  if (payload && typeof payload === 'object' && Array.isArray((payload as { sessions?: unknown }).sessions)) {
    return (payload as { sessions: Session[] }).sessions;
  }
  return null;
}

export function useAdminSessions(_focusedToken: string | null) {
  const [sessions, setSessions] = useState<Session[]>([]);

  useEffect(() => {
    let cancelled = false;

    const load = async () => {
      const data = await adminApi.sessionList();
      if (!cancelled && data?.ok && Array.isArray(data.sessions)) {
        setSessions(data.sessions as Session[]);
      }
    };

    void load();

    const socket = io({ path: '/socket.io', transports: ['websocket', 'polling'] });
    socket.emit('admin:join');
    socket.on('sessions:updated', (payload: unknown) => {
      const next = asSessions(payload);
      if (next) setSessions(next);
    });
    socket.on('session:patch', (patch: Session) => {
      if (patch?.token) setSessions((prev) => mergeSessionRow(prev, patch));
    });
    socket.on('message:new', (payload: { sessionToken?: string; message?: { body?: string } }) => {
      const token = payload?.sessionToken;
      const body = payload?.message?.body;
      if (!token || !body) return;
      setSessions((prev) => {
        const index = prev.findIndex((row) => row.token === token);
        if (index === -1) return prev;
        const next = prev.slice();
        next[index] = { ...prev[index], lastResponse: body };
        return next;
      });
    });

    return () => {
      cancelled = true;
      socket.close();
    };
  }, []);

  return { sessions };
}
