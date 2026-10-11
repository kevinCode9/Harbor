export type Session = {
  id: string;
  token: string;
  name: string;
  currentPage: string;
  status: 'connected' | 'waiting' | 'idle' | 'disconnected';
  ip: string;
  location: string;
  startedAt: string;
  lastResponse: string;
  browser: string;
  device: string;
  caseNumber: string;
  contactName: string;
  contactEmail: string;
  publicAddress: string;
  caseStatus: string;
  lastSeenAt: string;
  demoLocation: string;
  demoTime: string;
  demoName: string;
  liveText: string;
  queuedPage: string;
  theme?: string;
  language?: string;
};

export type PageGroup = {
  family: string;
  pages: { id: string; name: string; url: string }[];
};

export type SupportMessage = {
  id: string;
  authorRole: string;
  body: string;
  createdAt: string;
};
