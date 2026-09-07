import { decodeP4EngineRunning } from '../protocols/teltonika/decoder.js';

export function isSafeCurrentTelemetry(record) {
  const io = record?.io ?? record?.raw_io ?? {};
  const rpm = record?.obd?.rpm ?? io[85] ?? io[36];
  const obdSpeed = record?.obd?.speed ?? io[37] ?? io[24];
  const reportedEngineRunning = record?.can?.engine_running;
  const normalizedEngineRunning = Number(reportedEngineRunning);
  const p4EngineRunning = decodeP4EngineRunning(io[517]);
  const explicitEngineRunning = reportedEngineRunning !== null
    && reportedEngineRunning !== undefined
    && (normalizedEngineRunning === 0 || normalizedEngineRunning === 1)
    ? normalizedEngineRunning
    : null;
  const engineRunning = p4EngineRunning === 1 || explicitEngineRunning === 1
    ? 1
    : (p4EngineRunning === 0 || explicitEngineRunning === 0 ? 0 : null);

  return Number(record?.speed) === 0
    && Number(obdSpeed) === 0
    && Number(rpm) === 0
    && Number(record?.ignition) === 0
    && Number(io[239]) === 0
    && Number(record?.movement) === 0
    && Number(io[240]) === 0
    && engineRunning === 0;
}
