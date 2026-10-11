import type { Request, Response } from 'express';
import type { Server } from 'socket.io';
import { z } from 'zod';
import { emitToAdmins } from '../../realtime/broadcast.js';
import { REALTIME_EVENTS } from '../../realtime/events.js';
import { toAdminSession } from '../sessions/session.repository.js';
import { visitorJoinSchema } from '../sessions/session.validation.js';
import { updatePresence } from './presence.service.js';

const presenceSchema = visitorJoinSchema.extend({
  lastMessage: z.string().max(2000).optional(),
});

export function createPresenceController(io: Server, broadcastSessions: () => Promise<void>) {
  return {
    async heartbeat(req: Request, res: Response) {
      const parsed = presenceSchema.safeParse(req.body);
      if (!parsed.success) {
        return res.status(400).json({ ok: false, message: 'Invalid visitor presence payload' });
      }
      if (!parsed.data.token) {
        return res.status(400).json({ ok: false, message: 'Visitor token required' });
      }

      const result = await updatePresence({
        token: parsed.data.token,
        status: parsed.data.status,
        browser: parsed.data.browser,
        device: parsed.data.device,
        location: parsed.data.location,
        ip: parsed.data.ip,
        lastMessage: parsed.data.lastMessage,
      });

      if (!result.ok) {
        const status = result.message === 'Session not found' ? 404 : 400;
        return res.status(status).json(result);
      }

      // Row-level live update for Admin list (does not redraw unrelated rows client-side).
      emitToAdmins(io, REALTIME_EVENTS.sessionPatch, toAdminSession(result.session));
      if (result.statusChanged) {
        await broadcastSessions();
      }
      return res.json({ ok: true, session: result.session });
    },
  };
}
