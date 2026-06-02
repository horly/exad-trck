const { spawn } = require('node:child_process');
const config = require('./config');

function runArtisan(args) {
  return new Promise((resolve) => {
    const child = spawn('php', ['artisan', ...args], {
      cwd: config.projectRoot,
      windowsHide: true,
    });

    let stdout = '';
    let stderr = '';

    child.stdout.on('data', (chunk) => {
      stdout += chunk.toString();
    });

    child.stderr.on('data', (chunk) => {
      stderr += chunk.toString();
    });

    child.on('close', (code) => {
      resolve({
        code,
        stdout: stdout.trim(),
        stderr: stderr.trim(),
      });
    });
  });
}

async function ingestPosition(payload) {
  return runArtisan(['gps:ingest-position', `--payload=${JSON.stringify(payload)}`]);
}

async function markStaleTrackers() {
  return runArtisan(['gps:mark-stale', `--minutes=${config.staleMinutes}`]);
}

module.exports = {
  ingestPosition,
  markStaleTrackers,
};
