function codecName(codecId) {
  if (codecId === 0x08) return 'codec8';
  if (codecId === 0x8e) return 'codec8_extended';
  if (codecId === 0x10) return 'codec16';
  throw new Error(`Unsupported Teltonika AVL codec: 0x${codecId.toString(16)}`);
}

const DRIVER_IDENTIFIER_IO_IDS = new Set([78, 263, 264, 380, 391, 451, 483]);
const SECURITY_STATE_P4_ENGINE_RUNNING_BIT = 11n;

function parseIoElements(buffer, offset, codecId) {
  const io = {};
  let cursor = offset;
  const wide = codecId === 0x8e;
  const readId = () => {
    const value = wide ? buffer.readUInt16BE(cursor) : buffer.readUInt8(cursor);
    cursor += wide ? 2 : 1;
    return value;
  };
  const readCount = () => {
    const value = wide ? buffer.readUInt16BE(cursor) : buffer.readUInt8(cursor);
    cursor += wide ? 2 : 1;
    return value;
  };
  const eventId = wide ? buffer.readUInt16BE(cursor) : buffer.readUInt8(cursor);
  cursor += wide ? 2 : 1;
  const totalIo = readCount();

  const readGroup = (bytes) => {
    const count = readCount();
    for (let index = 0; index < count; index += 1) {
      const id = readId();
      let value;
      if (bytes === 1) value = buffer.readUInt8(cursor);
      if (bytes === 2) value = buffer.readUInt16BE(cursor);
      if (bytes === 4) value = buffer.readUInt32BE(cursor);
      if (bytes === 8) {
        const bigintValue = buffer.readBigUInt64BE(cursor);
        value = DRIVER_IDENTIFIER_IO_IDS.has(id)
          ? bigintValue.toString(16).padStart(16, '0').toUpperCase()
          : Number(bigintValue);
      }
      cursor += bytes;
      io[id] = value;
    }
  };

  readGroup(1);
  readGroup(2);
  readGroup(4);
  readGroup(8);

  if (wide) {
    const variableCount = readCount();
    for (let index = 0; index < variableCount; index += 1) {
      const id = readId();
      const length = readCount();
      io[id] = buffer.subarray(cursor, cursor + length).toString('hex');
      cursor += length;
    }
  }

  return { cursor, eventId, totalIo, io };
}

function firstIo(io, ids) {
  for (const id of ids) {
    if (Object.prototype.hasOwnProperty.call(io, id)) return io[id];
  }
  return null;
}

export function decodeP4EngineRunning(value) {
  if (value === null || value === undefined || value === '') return null;

  try {
    const flags = typeof value === 'bigint' ? value : BigInt(String(value).trim());
    return Number((flags >> SECURITY_STATE_P4_ENGINE_RUNNING_BIT) & 1n);
  } catch {
    return null;
  }
}

function normalizeDriverIdentifier(value) {
  if (value === null || value === undefined) return null;
  const normalized = String(value).replace(/[^A-Za-z0-9]/g, '').toUpperCase();
  return normalized !== '' && !/^0+$/.test(normalized) ? normalized : null;
}

export function driverIdentifierFromIo(io, eventId) {
  if (DRIVER_IDENTIFIER_IO_IDS.has(eventId)) {
    const eventIdentifier = normalizeDriverIdentifier(io[eventId]);
    if (eventIdentifier !== null) return eventIdentifier;
  }

  for (const id of DRIVER_IDENTIFIER_IO_IDS) {
    const identifier = normalizeDriverIdentifier(io[id]);
    if (identifier !== null) return identifier;
  }

  return null;
}

function toNumber(value) {
  if (value === null || value === undefined || value === '') return null;
  const numeric = Number(value);
  return Number.isFinite(numeric) ? numeric : null;
}

function normalizePercent(value) {
  const numeric = toNumber(value);
  return numeric === null ? null : Math.max(0, Math.min(100, numeric <= 1 ? numeric * 100 : numeric));
}

function normalizeVoltage(value) {
  const numeric = toNumber(value);
  return numeric === null ? null : numeric > 100 ? Number((numeric / 1000).toFixed(3)) : numeric;
}

function normalizeDistanceKm(value) {
  const numeric = toNumber(value);
  if (numeric === null) return null;
  return numeric > 100000 ? Number((numeric / 1000).toFixed(2)) : Math.round(numeric);
}

function compact(values) {
  return Object.fromEntries(Object.entries(values).filter(([, value]) => value !== null && value !== undefined && value !== ''));
}

function mapIo(io, speed, eventId) {
  const totalMileageKm = normalizeDistanceKm(firstIo(io, [389, 199, 16]));
  const driverIdentifier = driverIdentifierFromIo(io, eventId);

  return {
    ignition: io[239] === 1 ? 1 : io[239] === 0 ? 0 : '',
    movement: io[240] === 1 ? 1 : io[240] === 0 ? 0 : '',
    gsm_signal: io[21] ?? '',
    external_voltage: io[66] ?? '',
    battery_voltage: io[67] ?? '',
    battery_level: io[113] ?? '',
    odometer: totalMileageKm,
    engine_seconds: toNumber(firstIo(io, [42])),
    driver_identifier_uid: driverIdentifier,
    io,
    sensors: compact({ io_count: Object.keys(io).length }),
    obd: compact({
      rpm: toNumber(firstIo(io, [85, 36])),
      speed: toNumber(firstIo(io, [37, 24])),
      throttle_percent: normalizePercent(firstIo(io, [41])),
      engine_temperature_c: toNumber(firstIo(io, [32])),
      module_voltage: normalizeVoltage(firstIo(io, [51])),
      engine_load_percent: normalizePercent(firstIo(io, [52, 31])),
      fault_distance_km: normalizeDistanceKm(firstIo(io, [43])),
      errors_count: toNumber(firstIo(io, [30])),
      distance_since_clear_km: normalizeDistanceKm(firstIo(io, [49])),
    }),
    can: compact({
      fuel_level_percent: normalizePercent(firstIo(io, [48])),
      total_mileage_km: totalMileageKm,
      engine_running: decodeP4EngineRunning(firstIo(io, [517])),
    }),
  };
}

export function decodeTeltonikaAvlPacket(buffer, imei) {
  if (buffer.length < 18 || buffer.readUInt32BE(0) !== 0) throw new Error('Invalid Teltonika AVL packet');
  const dataLength = buffer.readUInt32BE(4);
  if (buffer.length !== dataLength + 12) throw new Error('Teltonika AVL data length mismatch');
  let cursor = 8;
  const codecId = buffer.readUInt8(cursor++);
  const codec = codecName(codecId);
  const recordsCount = buffer.readUInt8(cursor++);
  const records = [];

  for (let index = 0; index < recordsCount; index += 1) {
    const timestamp = Number(buffer.readBigUInt64BE(cursor)); cursor += 8;
    const priority = buffer.readUInt8(cursor++);
    const longitude = buffer.readInt32BE(cursor) / 10000000; cursor += 4;
    const latitude = buffer.readInt32BE(cursor) / 10000000; cursor += 4;
    const altitude = buffer.readUInt16BE(cursor); cursor += 2;
    const angle = buffer.readUInt16BE(cursor); cursor += 2;
    const satellites = buffer.readUInt8(cursor++);
    const speed = buffer.readUInt16BE(cursor); cursor += 2;
    const parsedIo = parseIoElements(buffer, cursor, codecId);
    cursor = parsedIo.cursor;
    records.push({
      imei, protocol: 'teltonika', codec, codec_id: codecId,
      timestamp: new Date(timestamp).toISOString(), priority, latitude, longitude,
      altitude, angle, satellites, speed, event_id: parsedIo.eventId,
      total_io: parsedIo.totalIo, raw_io: parsedIo.io, ...mapIo(parsedIo.io, speed, parsedIo.eventId),
    });
  }

  const secondRecordsCount = buffer.readUInt8(cursor++);
  if (secondRecordsCount !== recordsCount) throw new Error('Teltonika records count mismatch');

  return { codec, recordsCount, records, ack: recordsCount, dataLength };
}
