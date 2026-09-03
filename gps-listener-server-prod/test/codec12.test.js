import assert from 'node:assert/strict';
import test from 'node:test';
import { crc16Ibm, decodeCodec12Response, encodeCodec12Command } from '../src/protocols/teltonika/codec12.js';

function responseFrame(text) {
  const response = Buffer.from(text, 'ascii');
  const data = Buffer.alloc(response.length + 8);
  data.writeUInt8(0x0c, 0);
  data.writeUInt8(1, 1);
  data.writeUInt8(0x06, 2);
  data.writeUInt32BE(response.length, 3);
  response.copy(data, 7);
  data.writeUInt8(1, data.length - 1);
  const frame = Buffer.alloc(data.length + 12);
  frame.writeUInt32BE(0, 0);
  frame.writeUInt32BE(data.length, 4);
  data.copy(frame, 8);
  frame.writeUInt32BE(crc16Ibm(data), data.length + 8);
  return frame;
}

test('encodes the official Teltonika Codec 12 getinfo example', () => {
  assert.equal(
    encodeCodec12Command('getinfo').toString('hex').toUpperCase(),
    '000000000000000F0C010500000007676574696E666F0100004312'
  );
});

test('encodes ignition-gated DOUT1 and DOUT2 immobilization', () => {
  const frame = encodeCodec12Command('setigndigout 11 0 0');
  assert.equal(frame.readUInt8(8), 0x0c);
  assert.equal(frame.subarray(15, -5).toString('ascii'), 'setigndigout 11 0 0');
});

test('decodes and validates a Codec 12 tracker response', () => {
  const text = 'DOUT1:1 Timeout:INFINITY DOUT2:1 Timeout:INFINITY';
  assert.equal(decodeCodec12Response(responseFrame(text)), text);
});

test('rejects a response with an invalid CRC', () => {
  const frame = responseFrame('DOUT1:1 DOUT2:1');
  frame[frame.length - 1] ^= 0xff;
  assert.throws(() => decodeCodec12Response(frame), /CRC/);
});
