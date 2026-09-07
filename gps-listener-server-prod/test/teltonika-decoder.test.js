import assert from 'node:assert/strict';
import test from 'node:test';
import {
  decodeP4EngineRunning,
  driverIdentifierFromIo,
} from '../src/protocols/teltonika/decoder.js';

test('decodes the engine bit from the complete P4 security word', () => {
  assert.equal(decodeP4EngineRunning(317), 0);
  assert.equal(decodeP4EngineRunning(2048), 1);
  assert.equal(decodeP4EngineRunning('0x800'), 1);
  assert.equal(decodeP4EngineRunning(null), null);
});

test('prefers the non-zero event iButton and skips zero sentinels', () => {
  assert.equal(driverIdentifierFromIo({ 78: 0, 263: '38000009A29C2114' }, 263), '38000009A29C2114');
  assert.equal(driverIdentifierFromIo({ 78: '0000000000000000', 263: '14219CA209000038' }, 78), '14219CA209000038');
  assert.equal(driverIdentifierFromIo({ 78: 0, 263: '0000000000000000' }, 78), null);
});
