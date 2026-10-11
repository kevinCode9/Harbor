import path from 'path';
import { fileURLToPath } from 'url';
import {
  DEFAULT_PAGE,
  PAGE_FILE_BY_URL,
  PAGE_MENU,
} from './page.registry.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const visitorRoot = path.join(__dirname, '..', '..', '..', '..', '..', 'apps', 'visitor-web');

export function getPageMenu() {
  return PAGE_MENU;
}

export function getVisitorRoot() {
  return visitorRoot;
}

export function visitorFileForUrl(url: string): string {
  const relative = PAGE_FILE_BY_URL[url] || PAGE_FILE_BY_URL[DEFAULT_PAGE];
  return path.join(visitorRoot, relative);
}
