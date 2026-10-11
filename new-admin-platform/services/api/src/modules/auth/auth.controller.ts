import type { Request, Response } from 'express';
import { authenticateAdminKey, rotateActiveAdminKey } from './auth.service.js';

export async function login(req: Request, res: Response) {
  const result = await authenticateAdminKey(req.body?.key ?? req.query.key);
  // console.log('this is login session', result);
  if (!result.ok) {
    const status = result.message === 'Invalid admin key' ? 401 : 400;
    return res.status(status).json(result);
  }
  return res.json(result);
}

export async function rotateKey(req: Request, res: Response) {
  const result = await rotateActiveAdminKey(req.body);
  if (!result.ok) {
    const status = result.message.includes('incorrect') ? 401 : 400;
    return res.status(status).json(result);
  }
  return res.json(result);
}
