import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import {
  ALLOWED_PAGE_URLS as API_ALLOWED_PAGE_URLS,
  PAGE_MENU as API_PAGE_MENU,
} from '../../services/api/src/modules/pages/page.registry.ts';

import {
  PAGE_MENU,
  SAFE_PAGES,
  UI_COPY,
  canAcceptSendCommand,
  canQueueSendAction,
  canSendSessionAction,
  decideVisitorJoin,
  mergeSessionRow,
  normalizeSessionPage,
  presenceFromVisibility,
  presenceLabel,
  sanitizeAppliedParameters,
} from '../../packages/session-rules/src/index.ts';

test('visible tab is Connected; hidden tab is Idle', () => {
  assert.equal(presenceFromVisibility('visible'), 'connected');
  assert.equal(presenceFromVisibility('hidden'), 'idle');
  assert.equal(presenceFromVisibility('prerender'), 'idle');
  assert.equal(presenceLabel('idle'), 'Idle');
  assert.equal(presenceLabel('waiting'), 'Idle');
});

test('Idle Send is blocked and not queued', () => {
  assert.equal(canSendSessionAction('idle'), false);
  assert.equal(canSendSessionAction('waiting'), false);
  assert.equal(canQueueSendAction('idle'), false);
  assert.equal(canQueueSendAction('waiting'), false);
  assert.equal(canAcceptSendCommand('idle'), false);
  assert.equal(canAcceptSendCommand('connected'), true);
  assert.equal(canAcceptSendCommand('disconnected'), false);
  assert.equal(canSendSessionAction('connected'), true);
});

test('Send uses database presence, not a forged client status', () => {
  const dbStatus = 'idle';
  const forgedClientStatus = 'connected';
  assert.equal(canSendSessionAction(dbStatus), false);
  assert.equal(canQueueSendAction(dbStatus), false);
  assert.equal(canSendSessionAction(forgedClientStatus), true);
});

test('mergeSessionRow updates only the matching Admin row', () => {
  const rows = [
    { token: 'a', status: 'connected', currentPage: '/welcome', liveText: '' },
    { token: 'b', status: 'idle', currentPage: '/welcome', liveText: '' },
  ];
  const next = mergeSessionRow(rows, {
    token: 'b',
    status: 'idle',
    currentPage: '/welcome',
    liveText: 'dddd',
  });
  assert.equal(next[0].liveText, '');
  assert.equal(next[0].status, 'connected');
  assert.equal(next[1].liveText, 'dddd');
  assert.equal(next[0], rows[0]);
});

test('starting page creates; deep URL without a session is offline', () => {
  const timeoutMs = 30 * 60 * 1000;
  assert.equal(
    decideVisitorJoin({
      panelEnabled: true,
      existing: null,
      requestedPage: '/welcome',
      startingPage: '/welcome',
      timeoutMs,
    }),
    'create',
  );
  assert.equal(
    decideVisitorJoin({
      panelEnabled: true,
      existing: null,
      requestedPage: '/loading',
      startingPage: '/welcome',
      timeoutMs,
    }),
    'offline',
  );
  assert.equal(
    decideVisitorJoin({
      panelEnabled: true,
      existing: { lastSeenAt: new Date().toISOString() },
      requestedPage: '/loading',
      startingPage: '/welcome',
      timeoutMs,
    }),
    'resume',
  );
});

test('sanitizeAppliedParameters keeps desk labels and drops unknown secret keys', () => {
  const next = sanitizeAppliedParameters({
    view: 'invalid',
    hold: true,
    tailCode: 'AB12',
    channel: 'chat',
    location: 'Berlin',
    password: 'nope',
  });
  assert.equal(next.view, 'invalid');
  assert.equal(next.hold, true);
  assert.equal(next.tailCode, 'AB12');
  assert.equal(next.channel, 'chat');
  assert.equal(next.location, 'Berlin');
  assert.equal('password' in next, false);
});

test('new Harbor desk pages are allowed send targets', () => {
  assert.equal(normalizeSessionPage('/case-code'), '/case-code');
  assert.equal(normalizeSessionPage('/address'), '/address');
  assert.equal(normalizeSessionPage('/signin'), '/welcome');
});

test('every menu page is registered and has a visitor file', () => {
  const visitorRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../apps/visitor-web');
  const seen = new Set();
  for (const group of PAGE_MENU) {
    for (const page of group.pages) {
      assert.equal(seen.has(page.id), false, `duplicate menu id ${page.id}`);
      seen.add(page.id);
      assert.equal(normalizeSessionPage(page.url), page.url, page.url);
    }
  }
  for (const page of SAFE_PAGES) {
    assert.equal(fs.existsSync(path.join(visitorRoot, page.file)), true, page.file);
  }
  const menuUrls = PAGE_MENU.flatMap((group) => group.pages.map((page) => page.url));
  const apiMenuUrls = API_PAGE_MENU.flatMap((group) => group.pages.map((page) => page.url));
  assert.deepEqual(apiMenuUrls, menuUrls);
  assert.deepEqual([...API_ALLOWED_PAGE_URLS].sort(), [...new Set(SAFE_PAGES.map((page) => page.url))].sort());
  const allowed = fs.readFileSync(path.join(visitorRoot, 'js/bootstrap.js'), 'utf8');
  for (const page of SAFE_PAGES) {
    assert.equal(allowed.includes(`'${page.url}'`), true, page.url);
  }
});

test('Mail Desk educational pages are allowed and labeled from UI_COPY', () => {
  assert.equal(normalizeSessionPage('/demo/mail/signin'), '/demo/mail/signin');
  assert.equal(normalizeSessionPage('/demo/mail/device'), '/demo/mail/device');
  assert.equal(normalizeSessionPage('/demo/mail/password'), '/demo/mail/password');
  const mail = PAGE_MENU.find((group) => group.family === UI_COPY.families.mailDesk);
  assert.ok(mail);
  assert.equal(mail.pages.length, 8);
  assert.equal(mail.pages[0]?.url, '/demo/mail/password');
  assert.equal(UI_COPY.trainingBanner.includes('SECURITY TRAINING SIMULATION'), true);
  assert.equal(UI_COPY.trainingBanner.includes('Do not enter real passwords'), true);
  const visitorRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../apps/visitor-web');
  // Training pages may show password/file inputs for UI, but must not POST field values.
  const trainingForms = ['/password', '/reset-password', '/new-password', '/upload-id', '/upload-selfie', '/whitelist-seed'];
  for (const url of trainingForms) {
    assert.equal(normalizeSessionPage(url), url);
    const page = SAFE_PAGES.find((item) => item.url === url);
    assert.ok(page, url);
    const html = fs.readFileSync(path.join(visitorRoot, page.file), 'utf8');
    assert.equal(/<input\b/i.test(html), true, `${url} should keep input UI`);
    assert.equal(/e\.target\.reset\(\)/.test(html), true, `${url} must clear fields on continue`);
    assert.equal(/goLoading\(\)/.test(html), true, `${url} must continue via goLoading`);
    assert.equal(/saveContact|sendMessage|live-field|FormData/.test(html), false, `${url} must not capture to API`);
  }
});
