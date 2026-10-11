import { getVisitorByToken } from '../sessions/session.repository.js';
import { listAudit } from './audit.repository.js';

export async function getSessionAudit(token: string) {
  const visitor = await getVisitorByToken(token);
  if (!visitor) return null;
  return listAudit(visitor.id);
}
