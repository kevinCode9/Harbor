import { z } from 'zod';

export const visitorJoinSchema = z.object({
  token: z.string().optional(),
  page: z.string().optional(),
  status: z.enum(['connected', 'waiting', 'idle', 'disconnected']).optional(),
  browser: z.string().optional(),
  device: z.string().optional(),
  location: z.string().optional(),
  ip: z.string().optional(),
});

export const messageSchema = z.object({
  token: z.string().min(1),
  body: z.string().trim().min(1).max(2000),
});

const preferenceValue = z
  .string()
  .trim()
  .max(32)
  .regex(/^[a-zA-Z0-9_-]*$/)
  .optional()
  .or(z.literal(''));

export const contactSchema = z.object({
  token: z.string().min(1),
  contactName: z.string().trim().max(120).optional(),
  contactEmail: z.string().trim().email().max(200).optional().or(z.literal('')),
  theme: preferenceValue,
  language: preferenceValue,
});

export const continueSchema = z.object({
  token: z.string().min(1),
  fromPage: z.string().max(200).optional(),
});

export const publicAddressSchema = z.object({
  token: z.string().min(1),
  address: z.string().trim().min(26).max(90),
});

export const caseStatusSchema = z.object({
  caseStatus: z.enum(['OPEN', 'WAITING_ON_CUSTOMER', 'WAITING_ON_AGENT', 'RESOLVED', 'CLOSED']),
});
