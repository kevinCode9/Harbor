import test from 'node:test';
import assert from 'node:assert/strict';
import { ADMIN_LOBBY, ROOM_PREFIX, sessionRoom, tokenFromRoom } from './rooms.ts';

test('session rooms use session:{token}', () => {
  assert.equal(sessionRoom('ABC'), 'session:ABC');
  assert.equal(sessionRoom('session:ABC'), 'session:ABC');
  assert.equal(ROOM_PREFIX, 'session:');
  assert.equal(ADMIN_LOBBY, 'admin');
  assert.equal(tokenFromRoom('session:ABC'), 'ABC');
});
