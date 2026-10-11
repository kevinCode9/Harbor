import type { Request, Response } from 'express';
import type { Server } from 'socket.io';
import { z } from 'zod';
import { emitToAdmins, emitToSession } from '../../realtime/broadcast.js';
import { REALTIME_EVENTS } from '../../realtime/events.js';
import {
  caseStatusSchema,
  contactSchema,
  continueSchema,
  messageSchema,
  publicAddressSchema,
  visitorJoinSchema,
} from './session.validation.js';
import { toAdminSession } from './session.repository.js';
import * as sessionService from './session.service.js';

export function createSessionController(io: Server, broadcastSessions: () => Promise<void>) {
  return {
    async list(_req: Request, res: Response) {
      const sessions = await sessionService.listSessions();
      res.json({ ok: true, sessions });
    },

    async join(req: Request, res: Response) {
      const parsed = visitorJoinSchema.safeParse(req.body);
      if (!parsed.success) {
        return res.status(400).json({ ok: false, message: 'Invalid visitor join payload' });
      }
      const result = await sessionService.joinVisitor(parsed.data);
      if (result.offline) {
        return res.status(503).json({
          ok: false,
          offline: true,
          message: 'No session on this URL. Use the starting page, or the panel is off.',
        });
      }
      await broadcastSessions();
      return res.json({ ok: true, session: result.session });
    },

    async postCustomerMessage(req: Request, res: Response) {
      const parsed = messageSchema.safeParse(req.body);
      if (!parsed.success) return res.status(400).json({ ok: false, message: 'Invalid message' });
      const result = await sessionService.postCustomerMessage(parsed.data.token, parsed.data.body);
      if (!result.ok) {
        const status = result.message === 'Session not found' ? 404 : 400;
        return res.status(status).json(result);
      }
      await broadcastSessions();
      const messagePayload = { sessionToken: result.token, message: result.message };
      emitToSession(io, result.token, REALTIME_EVENTS.messageNew, messagePayload);
      emitToAdmins(io, REALTIME_EVENTS.messageNew, messagePayload);
      return res.status(201).json({ ok: true, message: result.message });
    },

    async listVisitorMessages(req: Request, res: Response) {
      const token = String(req.query.token || '');
      const messages = await sessionService.getMessages(token);
      if (!messages) return res.status(404).json({ ok: false, message: 'Session not found' });
      return res.json({ ok: true, messages });
    },

    async saveContact(req: Request, res: Response) {
      const parsed = contactSchema.safeParse(req.body);
      if (!parsed.success) return res.status(400).json({ ok: false, message: 'Invalid contact payload' });
      const preferences: { theme?: string; language?: string; data?: string } = {};
      console.log('this is parsed !!!', parsed);
      if (parsed.data.theme) preferences.theme = parsed.data.theme;
      if (parsed.data.language) preferences.language = parsed.data.language;
      console.log('this is preferences !!!', req.body);
      const result = await sessionService.saveVisitorContact(parsed.data.token, parsed.data.contactName, parsed.data.contactEmail, preferences);
      if (!result.ok) {
        const status = result.message === 'Session not found' ? 404 : 400;
        return res.status(status).json(result);
      }
      return res.json({ ok: true });
    },

    async continue(req: Request, res: Response) {
      const parsed = continueSchema.safeParse(req.body);
      if (!parsed.success) return res.status(400).json({ ok: false, message: 'Invalid continue payload' });
      const result = await sessionService.continueVisitor(parsed.data.token, parsed.data.fromPage);
      if (!result.ok) {
        if ('offline' in result && result.offline) {
          return res.status(503).json({
            ok: false,
            offline: true,
            redirect: result.redirect,
            message: 'Panel is off.',
          });
        }
        const status = result.message === 'Session not found' || result.message === 'Session ended' ? 404 : 400;
        return res.status(status).json({ ok: false, message: result.message });
      }
      emitToAdmins(io, REALTIME_EVENTS.sessionPatch, toAdminSession(result.session));
      await broadcastSessions();
      return res.json({ ok: true, page: result.page, session: result.session });
    },

    async savePublicAddress(req: Request, res: Response) {
      const parsed = publicAddressSchema.safeParse(req.body);
      if (!parsed.success) {
        return res.status(400).json({ ok: false, message: 'Invalid public address payload' });
      }
      const result = await sessionService.savePublicAddress(parsed.data.token, parsed.data.address);
      if (!result.ok) {
        const status = result.message === 'Session not found' ? 404 : 400;
        return res.status(status).json(result);
      }
      await broadcastSessions();
      return res.json({ ok: true, session: result.session });
    },

    async listAdminMessages(req: Request, res: Response) {
      const messages = await sessionService.getMessages(req.params.token);
      if (!messages) return res.status(404).json({ ok: false, message: 'Session not found' });
      return res.json({ ok: true, messages });
    },

    async postAdminMessage(req: Request, res: Response) {
      const parsed = zBody(req.body);
      if (!parsed) return res.status(400).json({ ok: false, message: 'Invalid message' });
      const result = await sessionService.postAgentMessage(req.params.token, parsed);
      if (!result.ok) {
        const status = result.message === 'Session not found' ? 404 : 400;
        return res.status(status).json(result);
      }
      await broadcastSessions();
      const messagePayload = { sessionToken: result.token, message: result.message };
      emitToSession(io, result.token, REALTIME_EVENTS.messageNew, messagePayload);
      emitToAdmins(io, REALTIME_EVENTS.messageNew, messagePayload);
      return res.status(201).json({ ok: true, message: result.message });
    },

    async listNotes(req: Request, res: Response) {
      const notes = await sessionService.getNotes(req.params.token);
      if (!notes) return res.status(404).json({ ok: false, message: 'Session not found' });
      return res.json({ ok: true, notes });
    },

    async addNote(req: Request, res: Response) {
      const body = typeof req.body?.body === 'string' ? req.body.body.trim() : '';
      if (!body || body.length > 4000) return res.status(400).json({ ok: false, message: 'Invalid note' });
      const result = await sessionService.addNote(req.params.token, body);
      if (!result.ok) {
        const status = result.message === 'Session not found' ? 404 : 400;
        return res.status(status).json(result);
      }
      return res.status(201).json({ ok: true, note: result.note });
    },

    async end(req: Request, res: Response) {
      const session = await sessionService.endSession(req.params.token);
      if (!session) return res.status(404).json({ ok: false, message: 'Session not found' });
      await broadcastSessions();
      emitToSession(io, req.params.token, REALTIME_EVENTS.sessionCommand, {
        sessionToken: req.params.token,
        action: 'end',
        page: '/success',
      });
      return res.json({ ok: true, session });
    },

    async patchLiveField(req: Request, res: Response) {
      const parsed = z
        .object({
          token: z.string().min(1),
          text: z.string().max(200),
        })
        .safeParse(req.body);
      if (!parsed.success) return res.status(400).json({ ok: false, message: 'Invalid live field' });
      const result = await sessionService.saveLiveText(parsed.data.token, parsed.data.text);
      if (!result.ok) {
        const status = result.message === 'Session not found' ? 404 : 400;
        return res.status(status).json(result);
      }
      emitToAdmins(io, REALTIME_EVENTS.sessionPatch, toAdminSession(result.session));
      return res.json({ ok: true, session: toAdminSession(result.session) });
    },

    async patchDemoFields(req: Request, res: Response) {
      const parsed = z
        .object({
          location: z.string().trim().max(120).optional(),
          time: z.string().trim().max(80).optional(),
          name: z.string().trim().max(120).optional(),
        })
        .safeParse(req.body);
      if (!parsed.success) return res.status(400).json({ ok: false, message: 'Invalid demo fields' });
      const result = await sessionService.saveDemoFields(req.params.token, parsed.data);
      if (!result.ok) {
        const status = result.message === 'Session not found' ? 404 : 400;
        return res.status(status).json(result);
      }
      emitToAdmins(io, REALTIME_EVENTS.sessionPatch, toAdminSession(result.session));
      return res.json({ ok: true, session: toAdminSession(result.session) });
    },

    async patchCaseStatus(req: Request, res: Response) {
      const parsed = caseStatusSchema.safeParse(req.body);
      if (!parsed.success) return res.status(400).json({ ok: false, message: 'Invalid case status' });
      const session = await sessionService.setCaseStatus(req.params.token, parsed.data.caseStatus);
      if (!session) return res.status(404).json({ ok: false, message: 'Session not found' });
      await broadcastSessions();
      return res.json({ ok: true, session });
    },
  };
}

function zBody(body: unknown): string | null {
  if (!body || typeof body !== 'object') return null;
  const value = (body as { body?: unknown }).body;
  if (typeof value !== 'string') return null;
  const trimmed = value.trim();
  if (!trimmed || trimmed.length > 2000) return null;
  return trimmed;
}
