import type { PanelSettings } from '../pages/Settings/SettingsPage';

export const defaultSettings: PanelSettings = {
  enabled: true,
  notifications: true,
  startingPage: '/welcome',
  sessionTimeoutMinutes: 30,
  sound: true,
  theme: 'dark',
};
