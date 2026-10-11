import { Router } from 'express';
import type { Server } from 'socket.io';
import { login, rotateKey } from '../modules/auth/auth.controller.js';
import { getSessionAudit } from '../modules/audit/audit.service.js';
import { createPresenceController } from '../modules/presence/presence.controller.js';
import { getPageMenu } from '../modules/pages/page.service.js';
import { DRILL_CHOICES } from '../modules/pages/page.registry.js';
import { createSessionController } from '../modules/sessions/session.controller.js';
import { getVisitorByToken } from '../modules/sessions/session.repository.js';
import { logVisitorEvent } from '../modules/events/event.service.js';
import { applyAdminCommand } from '../modules/send/send.service.js';
import { commandSchema } from '../modules/send/send.validation.js';
import { getSettings, postSettings } from '../modules/settings/settings.controller.js';
import { z } from 'zod';

export function createApiRouter(io: Server, broadcastSessions: () => Promise<void>) {
  const router = Router();
  const sessions = createSessionController(io, broadcastSessions);
  const presence = createPresenceController(io, broadcastSessions);

  router.get('/health', (_req, res) => {
    res.json({ ok: true, service: 'support-admin-platform', time: new Date().toISOString() });
  });

  router.post('/admin/login', login);
  router.post('/admin/rotate-key', rotateKey);
  router.get('/admin/session-list', (req, res) => sessions.list(req, res));
  router.get('/admin/page-menu', (_req, res) => res.json({ ok: true, groups: getPageMenu() }));
  router.get('/admin/settings', getSettings);
  router.post('/admin/settings', postSettings);

  router.post('/visitor/join', (req, res) => sessions.join(req, res));
  router.post('/visitor/presence', (req, res) => presence.heartbeat(req, res));
  router.post('/visitor/live-field', (req, res) => sessions.patchLiveField(req, res));
  router.post('/visitor/messages', (req, res) => sessions.postCustomerMessage(req, res));
  router.get('/visitor/messages', (req, res) => sessions.listVisitorMessages(req, res));
  router.post('/visitor/contact', (req, res) => sessions.saveContact(req, res));
  router.post('/visitor/continue', (req, res) => sessions.continue(req, res));
  router.post('/visitor/public-address', (req, res) => sessions.savePublicAddress(req, res));

  router.post('/visitor/drill-choice', async (req, res) => {
    const drillIds = ['domain', 'urgency', 'warning', 'request', 'buttons', 'connect'] as const;
    const parsed = z
      .object({
        token: z.string().min(1),
        drill: z.enum(drillIds),
        choice: z.enum(DRILL_CHOICES),
      })
      .safeParse(req.body);
    if (!parsed.success) {
      return res.status(400).json({ ok: false, message: 'Invalid drill result' });
    }
    const rejectedKeys = Object.keys(req.body || {}).filter(
      (key) => !['token', 'drill', 'choice'].includes(key),
    );
    if (rejectedKeys.length) {
      return res.status(400).json({
        ok: false,
        message: 'Drill results cannot include typed secrets or extra fields.',
      });
    }
    const visitor = await getVisitorByToken(parsed.data.token);
    if (!visitor) return res.status(404).json({ ok: false, message: 'Session not found' });
    await logVisitorEvent(visitor.id, 'drill_choice', {
      drill: parsed.data.drill,
      choice: parsed.data.choice,
    });
    return res.json({ ok: true });
  });

  router.get('/admin/sessions/:token/messages', (req, res) => sessions.listAdminMessages(req, res));
  router.post('/admin/sessions/:token/messages', (req, res) => sessions.postAdminMessage(req, res));
  router.get('/admin/sessions/:token/notes', (req, res) => sessions.listNotes(req, res));
  router.post('/admin/sessions/:token/notes', (req, res) => sessions.addNote(req, res));
  router.get('/admin/sessions/:token/audit', async (req, res) => {
    const audits = await getSessionAudit(req.params.token);
    if (!audits) return res.status(404).json({ ok: false, message: 'Session not found' });
    return res.json({ ok: true, audits });
  });
  router.post('/admin/sessions/:token/end', (req, res) => sessions.end(req, res));
  router.patch('/admin/sessions/:token/case-status', (req, res) => sessions.patchCaseStatus(req, res));
  router.patch('/admin/sessions/:token/demo-fields', (req, res) => sessions.patchDemoFields(req, res));

  router.post('/admin/command', async (req, res) => {
    const parsed = commandSchema.safeParse(req.body);
    if (!parsed.success) {
      return res.status(400).json({ ok: false, message: 'Invalid command payload' });
    }
    const result = await applyAdminCommand(io, parsed.data, broadcastSessions);
    if (!result.ok) {
      return res.status(result.status).json({ ok: false, message: result.message });
    }
    if ('preview' in result && result.preview) {
      return res.json({
        ok: true,
        preview: true,
        page: result.page,
        draft: result.draft,
        session: result.session,
      });
    }
    return res.json({ ok: true, session: result.session, applied: result.applied });
  });

  return router;
}
