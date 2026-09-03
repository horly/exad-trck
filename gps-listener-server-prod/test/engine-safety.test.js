import assert from 'node:assert/strict';
import test from 'node:test';
import { isSafeCurrentTelemetry } from '../src/services/engine-safety.js';

const stopped = {
  speed: 0,
  ignition: 0,
  movement: 0,
  obd: { speed: 0, rpm: 0 },
  can: { engine_running: 0 },
  io: { 24: 0, 85: 0, 239: 0, 240: 0, 517: 0 },
};

test('accepts only a fully stopped current telemetry record', () => {
  assert.equal(isSafeCurrentTelemetry(stopped), true);
});

test('vetoes every moving or running signal', () => {
  for (const unsafe of [
    { ...stopped, speed: 1 },
    { ...stopped, ignition: 1 },
    { ...stopped, movement: 1 },
    { ...stopped, obd: { speed: 1, rpm: 0 } },
    { ...stopped, obd: { speed: 0, rpm: 700 } },
    { ...stopped, can: { engine_running: 1 } },
    { ...stopped, io: { ...stopped.io, 239: 1 } },
  ]) {
    assert.equal(isSafeCurrentTelemetry(unsafe), false);
  }
});

test('vetoes absent safety telemetry', () => {
  assert.equal(isSafeCurrentTelemetry({ speed: 0, ignition: 0, movement: 0, io: {} }), false);
});
