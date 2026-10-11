import test from 'node:test';
import assert from 'node:assert/strict';
import {
  createEmptyAdminDraft,
  draftDiffersFromApplied,
  draftToApplied,
  selectDraftPage,
} from '../../packages/core-state/src/index.ts';

test('page click only mutates AdminDraft', () => {
  const draft = createEmptyAdminDraft('/welcome', 'Family A');
  const next = selectDraftPage(draft, '/demo/family-a/page-1', 'Family A');
  assert.equal(next.selectedPage, '/demo/family-a/page-1');
  assert.equal(draft.selectedPage, '/welcome');
  assert.equal(draftDiffersFromApplied(next, '/welcome'), true);
});

test('Send maps AdminDraft to AppliedVisitorState', () => {
  const draft = selectDraftPage(
    createEmptyAdminDraft('/welcome', 'Family A'),
    '/waiting',
    'Family B',
  );
  const applied = draftToApplied({ ...draft, temporaryParameters: { note: 'x' } }, 'send');
  assert.deepEqual(applied, {
    currentPage: '/waiting',
    currentState: 'active',
    appliedParameters: { note: 'x' },
  });
});

test('redirect applies idle state without changing draft ownership', () => {
  const draft = selectDraftPage(createEmptyAdminDraft(), '/success');
  const applied = draftToApplied(draft, 'redirect');
  assert.equal(applied.currentState, 'idle');
  assert.equal(applied.currentPage, '/success');
});
