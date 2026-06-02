const net = require('node:net');
const config = require('../config');

const [imei, lat, lng, speed = '0', angle = '0'] = process.argv.slice(2);

if (!imei || !lat || !lng) {
  console.error('Usage: node gps-listener-server-local/src/simulators/fake-tracker-client.js <imei> <lat> <lng> [speed] [angle]');
  process.exit(1);
}

const payload = {
  imei,
  lat: Number(lat),
  lng: Number(lng),
  speed: Number(speed),
  angle: Number(angle),
  satellites: 12,
  gps_time: new Date().toISOString(),
};

const socket = net.createConnection({ host: config.host, port: config.port }, () => {
  socket.write(`${JSON.stringify(payload)}\n`);
});

socket.setEncoding('utf8');

socket.on('data', (chunk) => {
  process.stdout.write(chunk);
  socket.end();
});

socket.on('error', (error) => {
  console.error(error.message);
  process.exitCode = 1;
});
