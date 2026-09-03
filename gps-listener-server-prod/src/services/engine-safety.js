export function isSafeCurrentTelemetry(record) {
  const io = record?.io ?? record?.raw_io ?? {};
  const rpm = record?.obd?.rpm ?? io[85] ?? io[36];
  const obdSpeed = record?.obd?.speed ?? io[37] ?? io[24];
  const engineRunning = record?.can?.engine_running ?? io[517];

  return Number(record?.speed) === 0
    && Number(obdSpeed) === 0
    && Number(rpm) === 0
    && Number(record?.ignition) === 0
    && Number(io[239]) === 0
    && Number(record?.movement) === 0
    && Number(io[240]) === 0
    && Number(engineRunning ?? 0) !== 1;
}
