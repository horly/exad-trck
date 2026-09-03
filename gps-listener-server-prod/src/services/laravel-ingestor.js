import { spawn } from 'node:child_process';

function runArtisan(laravelPath, args) {
  return new Promise((resolve, reject) => {
    const child = spawn('php', ['artisan', ...args], {
      cwd: laravelPath,
      env: process.env,
    });
    let stdout = '';
    let stderr = '';

    child.stdout.on('data', (data) => { stdout += data.toString(); });
    child.stderr.on('data', (data) => { stderr += data.toString(); });
    child.on('close', (code) => {
      if (code !== 0) {
        reject(new Error(stderr || stdout || `Laravel command failed with code ${code}`));
        return;
      }

      const line = stdout.trim().split(/\r?\n/).filter(Boolean).at(-1) || '{}';

      try {
        resolve(JSON.parse(line));
      } catch {
        reject(new Error(`Laravel returned invalid JSON: ${line}`));
      }
    });
  });
}

function normalizeVoltage(value) {
  if (value === '' || value === null || value === undefined) return null;
  const numeric = Number(value);
  if (!Number.isFinite(numeric)) return null;
  return numeric > 100 ? Number((numeric / 1000).toFixed(3)) : numeric;
}

function nullIfEmpty(value) {
  return value === '' || value === undefined ? null : value;
}

export function createLaravelIngestor() {
  const laravelPath = process.env.GPS_LISTENER_LARAVEL_PATH || '/var/www/exadtracking.app';

  return {
    ingest(payload) {
      const normalizedPayload = {
        imei: payload.imei,
        protocol: payload.protocol ?? null,
        codec: payload.codec ?? null,
        timestamp: payload.timestamp ?? new Date().toISOString(),
        gps_time: payload.timestamp ?? null,
        lat: payload.latitude,
        lng: payload.longitude,
        altitude: payload.altitude ?? null,
        speed: payload.speed ?? 0,
        angle: payload.angle ?? 0,
        satellites: payload.satellites ?? null,
        event_id: payload.event_id ?? null,
        gsm_signal: nullIfEmpty(payload.gsm_signal),
        battery_level: nullIfEmpty(payload.battery_level),
        external_voltage: normalizeVoltage(payload.external_voltage),
        battery_voltage: normalizeVoltage(payload.battery_voltage),
        movement: nullIfEmpty(payload.movement),
        ignition: nullIfEmpty(payload.ignition),
        odometer: payload.odometer ?? payload.can?.total_mileage_km ?? null,
        engine_seconds: payload.engine_seconds ?? null,
        driver_identifier_uid: payload.driver_identifier_uid ?? null,
        io: payload.io ?? payload.raw_io ?? null,
        sensors: payload.sensors ?? null,
        obd: payload.obd ?? {},
        can: payload.can ?? {},
        raw: {
          source: 'gps-listener-server-prod',
          protocol: payload.protocol ?? null,
          codec: payload.codec ?? null,
          codec_id: payload.codec_id ?? null,
          event_id: payload.event_id ?? null,
          total_io: payload.total_io ?? null,
          io: payload.io ?? payload.raw_io ?? null,
          obd: payload.obd ?? {},
          can: payload.can ?? {},
        },
      };

      return runArtisan(laravelPath, [
        'gps:ingest-position',
        `--payload=${JSON.stringify(normalizedPayload)}`,
      ]);
    },

    claimCommand(deviceId) {
      return runArtisan(laravelPath, ['gps:claim-device-command', String(deviceId)]);
    },

    updateCommand(claimToken, event, response = null, failureCode = null) {
      const args = ['gps:update-device-command', claimToken, event];
      if (response !== null) args.push(`--response=${response}`);
      if (failureCode !== null) args.push(`--failure-code=${failureCode}`);
      return runArtisan(laravelPath, args);
    },
  };
}
