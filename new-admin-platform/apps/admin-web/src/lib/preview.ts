import { normalizeSessionPage, sanitizeAppliedParameters } from '../../../../packages/session-rules/src/index.ts';

const PREVIEW_PARAM_KEYS = [
  'view',
  'hold',
  'tailCode',
  'channel',
  'location',
  'time',
  'name',
  'topic',
  'callbackWindow',
  'itemLabel',
  'itemCount',
] as const;

export function readPreviewLocation(href: string): { page: string | null; params: Record<string, unknown> } {
  const url = new URL(href, 'http://localhost');
  const screen = url.searchParams.get('screen');
  if (!screen) return { page: null, params: {} };
  const path = screen.startsWith('/') ? screen : `/${screen}`;
  if (normalizeSessionPage(path) !== path) return { page: null, params: {} };
  const raw: Record<string, unknown> = {};
  for (const key of PREVIEW_PARAM_KEYS) {
    const value = url.searchParams.get(key);
    if (value == null || value === '') continue;
    raw[key] = key === 'hold' ? value === '1' : value;
  }
  return { page: path, params: sanitizeAppliedParameters(raw) };
}

export function previewLocationHref(
  currentHref: string,
  page: string | null,
  params: Record<string, unknown> = {},
): string {
  const url = new URL(currentHref, 'http://localhost');
  for (const key of PREVIEW_PARAM_KEYS) url.searchParams.delete(key);
  if (!page) {
    url.searchParams.delete('screen');
  } else {
    url.searchParams.set('screen', page);
    const clean = sanitizeAppliedParameters(params);
    for (const [key, value] of Object.entries(clean)) {
      if (value === true) url.searchParams.set(key, '1');
      else if (value === false || value == null || value === '') continue;
      else url.searchParams.set(key, String(value));
    }
  }
  return `${url.pathname}${url.search}${url.hash}`;
}

export function previewSrc(page: string, params?: Record<string, unknown>) {
  const path = normalizeSessionPage(page);
  const origin = import.meta.env.DEV ? 'http://127.0.0.1:4000' : '';
  const query = new URLSearchParams({ preview: '1' });
  const clean = sanitizeAppliedParameters(params || {});
  for (const [key, value] of Object.entries(clean)) {
    if (value === true) query.set(key, '1');
    else if (value === false || value == null || value === '') continue;
    else query.set(key, String(value));
  }
  return `${origin}${path}?${query.toString()}`;
}
