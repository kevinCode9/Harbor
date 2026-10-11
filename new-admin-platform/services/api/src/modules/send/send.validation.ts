import { z } from 'zod';

export const commandSchema = z.object({
  sessionToken: z.string().min(1),
  action: z.enum(['preview', 'send', 'redirect']),
  page: z.string().optional(),
  // Intentionally omitted: client cannot override Connected/Idle for Send.
  selectedFamily: z.string().optional(),
  temporaryParameters: z.record(z.unknown()).optional(),
});
