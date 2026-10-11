import test from 'node:test';
import assert from 'node:assert/strict';
import { createEmptyAdminDraft, selectDraftAction } from '../../packages/core-state/src/index.ts';
import { normalizeSessionPage } from '../../packages/session-rules/src/index.ts';
import { previewLocationHref, readPreviewLocation } from '../../apps/admin-web/src/lib/preview.ts';

test('Preview keeps AdminDraft and does not produce applied state', () => {
  const draft = createEmptyAdminDraft('/welcome');
  const next = selectDraftAction(draft, 'preview');
  assert.equal(next.selectedAction, 'preview');
  assert.equal(next.selectedPage, '/welcome');
  assert.equal(draft.selectedAction, null);
});

test('refresh keeps the preview page and its parameters', () => {
  const href = previewLocationHref('http://localhost:5174/', '/channel', { view: 'invalid', hold: true });
  assert.equal(href, '/?screen=%2Fchannel&view=invalid&hold=1');
  const restored = readPreviewLocation(`http://localhost:5174${href}`);
  assert.equal(restored.page, '/channel');
  assert.equal(restored.params.view, 'invalid');
  assert.equal(restored.params.hold, true);
  assert.equal(readPreviewLocation('http://localhost:5174/?screen=%2Fcoinbase%2Fsignin').page, null);
});

test('preview iframe uses preview=1 so the visitor does not join', () => {
  assert.equal(`${normalizeSessionPage('/waiting')}?preview=1`, '/waiting?preview=1');
  assert.equal(`${normalizeSessionPage('/address')}?preview=1`, '/address?preview=1');
  assert.equal(`${normalizeSessionPage('/demo/mail/password')}?preview=1`, '/demo/mail/password?preview=1');
  assert.equal(`${normalizeSessionPage('/coinbase/signin')}?preview=1`, '/welcome?preview=1');
});
