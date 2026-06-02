const path = require('node:path');

const projectRoot = path.resolve(__dirname, '..', '..');

module.exports = {
  host: process.env.EXAD_GPS_HOST || '127.0.0.1',
  port: Number(process.env.EXAD_GPS_PORT || 5027),
  staleMinutes: Number(process.env.EXAD_GPS_STALE_MINUTES || 5),
  staleIntervalMs: Number(process.env.EXAD_GPS_STALE_INTERVAL_MS || 60000),
  projectRoot,
};
