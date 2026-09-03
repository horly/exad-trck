import { startTcpListener } from './listeners/tcp-listener.js';
import { startUdpListener } from './listeners/udp-listener.js';

const protocol = (process.env.GPS_LISTENER_PROTOCOL || 'tcp').toLowerCase();

if (protocol === 'tcp') {
  startTcpListener();
} else if (protocol === 'udp') {
  startUdpListener();
} else {
  console.error(`Unsupported GPS_LISTENER_PROTOCOL: ${protocol}`);
  process.exit(1);
}
