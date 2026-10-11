import type { Request, Response } from 'express';
import { z } from 'zod';
import { readSettings, updateSettings } from './settings.service.js';

const settingsSchema = z.object({
  enabled: z.boolean().optional(),
  notifications: z.boolean().optional(),
  startingPage: z.string().optional(),
  sessionTimeoutMinutes: z.number().int().min(5).max(240).optional(),
  sound: z.boolean().optional(),
  theme: z.string().optional(),
});

export async function getSettings(_req: Request, res: Response) {
  const settings = await readSettings();
  res.json({ ok: true, settings });
}

export async function postSettings(req: Request, res: Response) {
  const parsed = settingsSchema.safeParse(req.body);
  if (!parsed.success) {
    return res.status(400).json({ ok: false, message: 'Invalid settings' });
  }
  const settings = await updateSettings(parsed.data);
  return res.json({ ok: true, settings });
}
