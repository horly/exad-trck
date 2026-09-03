import dgram from 'node:dgram';

export function startUdpListener() {
  const host = process.env.GPS_LISTENER_HOST || '0.0.0.0';
  const port = Number(process.env.GPS_LISTENER_PORT || 5028);
  const server = dgram.createSocket('udp4');

  server.on('message', (buffer, remote) => {
    console.log(`[UDP] unsupported packet from ${remote.address}:${remote.port} size=${buffer.length}`);
  });
  server.bind(port, host, () => console.log(`[UDP] GPS listener started on ${host}:${port}`));
}
