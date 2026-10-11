import test from 'node:test';
import assert from 'node:assert/strict';

import {
  canSendSessionAction,
  looksLikeSecretMaterial,
  normalizeSessionPage,
  sanitizeAdminKeyInput,
} from '../../packages/session-rules/src/index.ts';

test('connected sessions can send commands', () => {
  assert.equal(canSendSessionAction('connected'), true);
});

test('idle sessions cannot send commands', () => {
  assert.equal(canSendSessionAction('waiting'), false);
  assert.equal(canSendSessionAction('idle'), false);
});

test('disconnected sessions cannot send commands', () => {
  assert.equal(canSendSessionAction('disconnected'), false);
});

test('admin keys are trimmed and normalized', () => {
  assert.equal(sanitizeAdminKeyInput('  SKDKD-DKDKD-DKDKD  '), 'SKDKD-DKDKD-DKDKD');
});

test('unknown or brand-clone paths fall back to welcome', () => {
  assert.equal(normalizeSessionPage('/coinbase/sign-in'), '/welcome');
  assert.equal(normalizeSessionPage('/metamask/import'), '/welcome');
  assert.equal(normalizeSessionPage('/waiting'), '/waiting');
  assert.equal(normalizeSessionPage('/demo/family-a/page-1'), '/demo/family-a/page-1');
});

test('seed-like text is rejected', () => {
  assert.equal(
    looksLikeSecretMaterial(
      'abandon ability able about above absent absorb abstract absurd abuse access accident',
    ),
    true,
  );
  assert.equal(looksLikeSecretMaterial('Need help with my invoice'), false);
});
