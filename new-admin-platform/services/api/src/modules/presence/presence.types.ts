export type PresencePayload = {
  token: string;
  status?: 'connected' | 'waiting' | 'idle';
  browser?: string;
  device?: string;
  location?: string;
  ip?: string;
  lastMessage?: string;
};
