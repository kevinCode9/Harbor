import { normalizeSessionPage } from '../pages/page.registry.js';
import { getPanelSettings, savePanelSettings, type PanelSettings } from './settings.repository.js';

export async function readSettings() {
  return getPanelSettings();
}

export async function updateSettings(patch: Partial<PanelSettings>) {
  const next = { ...patch };
  if (next.startingPage) {
    next.startingPage = normalizeSessionPage(next.startingPage);
  }
  return savePanelSettings(next);
}
