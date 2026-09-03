const CODEC_ID = 0x0c;
const COMMAND_TYPE = 0x05;
const RESPONSE_TYPE = 0x06;

export function crc16Ibm(buffer) {
  let crc = 0;

  for (const byte of buffer) {
    crc ^= byte;
    for (let bit = 0; bit < 8; bit += 1) {
      crc = (crc & 1) !== 0 ? (crc >>> 1) ^ 0xa001 : crc >>> 1;
    }
  }

  return crc & 0xffff;
}

export function encodeCodec12Command(command) {
  const commandBuffer = Buffer.from(command, 'ascii');
  const data = Buffer.alloc(1 + 1 + 1 + 4 + commandBuffer.length + 1);
  let offset = 0;
  data.writeUInt8(CODEC_ID, offset++);
  data.writeUInt8(1, offset++);
  data.writeUInt8(COMMAND_TYPE, offset++);
  data.writeUInt32BE(commandBuffer.length, offset);
  offset += 4;
  commandBuffer.copy(data, offset);
  offset += commandBuffer.length;
  data.writeUInt8(1, offset);

  const frame = Buffer.alloc(8 + data.length + 4);
  frame.writeUInt32BE(0, 0);
  frame.writeUInt32BE(data.length, 4);
  data.copy(frame, 8);
  frame.writeUInt32BE(crc16Ibm(data), 8 + data.length);

  return frame;
}

export function decodeCodec12Response(frame) {
  if (frame.length < 16 || frame.readUInt32BE(0) !== 0) {
    throw new Error('Invalid Codec 12 frame');
  }

  const dataLength = frame.readUInt32BE(4);
  if (frame.length !== dataLength + 12) throw new Error('Invalid Codec 12 data length');

  const data = frame.subarray(8, 8 + dataLength);
  if (data.readUInt8(0) !== CODEC_ID || data.readUInt8(1) !== 1) {
    throw new Error('Unexpected Codec 12 header');
  }
  if (data.readUInt8(2) !== RESPONSE_TYPE) throw new Error('Unexpected Codec 12 response type');

  const responseLength = data.readUInt32BE(3);
  const responseEnd = 7 + responseLength;
  if (responseEnd + 1 !== data.length || data.readUInt8(responseEnd) !== 1) {
    throw new Error('Invalid Codec 12 response payload');
  }

  const expectedCrc = frame.readUInt32BE(8 + dataLength) & 0xffff;
  if (crc16Ibm(data) !== expectedCrc) throw new Error('Invalid Codec 12 CRC');

  return data.subarray(7, responseEnd).toString('ascii').trim();
}

export function isCodec12Frame(frame) {
  return frame.length >= 9 && frame.readUInt8(8) === CODEC_ID;
}
