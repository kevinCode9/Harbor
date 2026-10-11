import test from 'node:test';
import assert from 'node:assert/strict';

import { continueDestination, familyLoadingPath } from '../../services/api/src/modules/pages/page.registry.ts';
import { contactSchema } from '../../services/api/src/modules/sessions/session.validation.ts';

test('continue from welcome goes to loading', () => {
  assert.equal(continueDestination('/welcome'), '/loading');
  assert.equal(familyLoadingPath('/demo/family-a/login'), '/demo/family-a/loading');
  assert.equal(familyLoadingPath('/demo/mail/signin'), '/demo/mail/waiting');
});

test('contact payload accepts theme and language only', () => {
  const parsed = contactSchema.parse({
    token: 'tok',
    contactName: 'ada@example.com',
    contactEmail: 'ada@example.com',
    theme: 'dark',
    language: 'en',
  });
  assert.equal(parsed.theme, 'dark');
  assert.equal(parsed.language, 'en');
});

test('contact payload rejects non-safe preference values', () => {
  const parsed = contactSchema.safeParse({
    token: 'tok',
    theme: 'dark;localStorage',
    language: 'en',
  });
  assert.equal(parsed.success, false);
});
