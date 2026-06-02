const net = require('node:net');
const config = require('./config');
const { ingestPosition, markStaleTrackers } = require('./artisan');

function writeJson(socket, payload) {
  socket.write(`${JSON.stringify(payload)}\n`);
}

async function handleMessage(socket, line) {
  let payload;

  try {
    payload = JSON.parse(line);
  } catch (error) {
    writeJson(socket, {
      ok: false,
      message: 'Invalid JSON payload.',
    });

    return;
  }

  const result = await ingestPosition(payload);
  const rawResponse = result.stdout || result.stderr || '{}';

  try {
    writeJson(socket, JSON.parse(rawResponse));
  } catch (error) {
    writeJson(socket, {
      ok: false,
      message: 'Laravel command returned an invalid response.',
      code: result.code,
      raw: rawResponse,
    });
  }
}

const server = net.createServer((socket) => {
  const remote = `${socket.remoteAddress}:${socket.remotePort}`;
  let buffer = '';

  console.log(`[gps] client connected ${remote}`);

  socket.setEncoding('utf8');

  socket.on('data', (chunk) => {
    buffer += chunk;
    const lines = buffer.split(/\r?\n/);
    buffer = lines.pop() || '';

    lines
      .map((line) => line.trim())
      .filter(Boolean)
      .forEach((line) => {
        handleMessage(socket, line).catch((error) => {
          writeJson(socket, {
            ok: false,
            message: error.message,
          });
        });
      });
  });

  socket.on('close', () => {
    console.log(`[gps] client disconnected ${remote}`);
  });

  socket.on('error', (error) => {
    console.error(`[gps] client error ${remote}: ${error.message}`);
  });
});

server.on('error', (error) => {
  console.error(`[gps] server error: ${error.message}`);
  process.exitCode = 1;
});

server.listen(config.port, config.host, () => {
  console.log(`[gps] local listener ready on ${config.host}:${config.port}`);
  console.log(`[gps] registered IMEIs only. stale timeout: ${config.staleMinutes} minute(s)`);
});

setInterval(() => {
  markStaleTrackers()
    .then((result) => {
      if (result.code !== 0) {
        console.error(`[gps] stale check failed: ${result.stderr || result.stdout}`);
      }
    })
    .catch((error) => {
      console.error(`[gps] stale check error: ${error.message}`);
    });
}, config.staleIntervalMs);
