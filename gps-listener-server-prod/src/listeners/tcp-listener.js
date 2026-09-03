import crypto from 'node:crypto';
import net from 'node:net';
import { decodeTeltonikaAvlPacket } from '../protocols/teltonika/decoder.js';
import { decodeCodec12Response, encodeCodec12Command, isCodec12Frame } from '../protocols/teltonika/codec12.js';
import { createDeviceRegistry } from '../services/device-registry.js';
import { isSafeCurrentTelemetry } from '../services/engine-safety.js';
import { createLaravelIngestor } from '../services/laravel-ingestor.js';

const HANDSHAKE_TIMEOUT_MS = 15_000;
const AUTHENTICATED_TIMEOUT_MS = 15 * 60_000;
const COMMAND_RESPONSE_TIMEOUT_MS = 45_000;
const MAX_PENDING_BYTES = 1024 * 1024;

function positiveInteger(value, fallback) {
  const parsed = Number(value);
  return Number.isInteger(parsed) && parsed > 0 ? parsed : fallback;
}

function extractImei(buffer) {
  if (buffer.length < 2) return null;
  const length = buffer.readUInt16BE(0);
  if (length <= 0 || length > 32) throw new Error(`Invalid IMEI length: ${length}`);
  if (buffer.length < length + 2) return null;
  return { imei: buffer.subarray(2, 2 + length).toString('ascii'), consumed: length + 2 };
}

function writeAvlAck(socket, count) {
  const ack = Buffer.alloc(4);
  ack.writeUInt32BE(count);
  socket.write(ack);
}

export function startTcpListener() {
  const host = process.env.GPS_LISTENER_HOST || '0.0.0.0';
  const port = Number(process.env.GPS_LISTENER_PORT || 5027);
  const handshakeTimeoutMs = positiveInteger(process.env.GPS_LISTENER_HANDSHAKE_TIMEOUT_MS, HANDSHAKE_TIMEOUT_MS);
  const authenticatedTimeoutMs = positiveInteger(process.env.GPS_LISTENER_AUTHENTICATED_TIMEOUT_MS, AUTHENTICATED_TIMEOUT_MS);
  const maxPendingBytes = positiveInteger(process.env.GPS_LISTENER_MAX_PENDING_BYTES, MAX_PENDING_BYTES);
  const registry = createDeviceRegistry();
  const ingestor = createLaravelIngestor();

  const server = net.createServer((socket) => {
    let pending = Buffer.alloc(0);
    let imei = null;
    let device = null;
    let authenticated = false;
    let inFlight = null;
    let commandTimer = null;
    let chain = Promise.resolve();

    socket.setNoDelay(true);
    socket.setKeepAlive(true, 60_000);
    const handshakeTimer = setTimeout(() => !authenticated && socket.destroy(), handshakeTimeoutMs);
    handshakeTimer.unref();

    const finishInFlight = async (event, response = null, failureCode = null) => {
      if (!inFlight) return;
      const command = inFlight;
      inFlight = null;
      clearTimeout(commandTimer);
      commandTimer = null;
      await ingestor.updateCommand(command.claim_token, event, response, failureCode);
    };

    const dispatchNext = async (latestRecord) => {
      if (inFlight || !device || socket.destroyed) return;
      const result = await ingestor.claimCommand(device.id);
      const command = result.command;
      if (!command) return;

      if (command.action === 'immobilize' && !isSafeCurrentTelemetry(latestRecord)) {
        await ingestor.updateCommand(command.claim_token, 'failed', 'Unsafe telemetry veto before socket write.', 'unsafe_before_send');
        return;
      }

      const frame = encodeCodec12Command(command.text);
      const frameHash = crypto.createHash('sha256').update(frame).digest('hex');
      inFlight = command;
      await ingestor.updateCommand(command.claim_token, 'sent', frameHash);
      socket.write(frame, async (error) => {
        if (error) {
          await finishInFlight('failed', error.message, 'socket_write_failed');
        }
      });
      commandTimer = setTimeout(() => {
        finishInFlight('failed', 'Codec 12 response timeout.', 'response_timeout').catch(console.error);
      }, COMMAND_RESPONSE_TIMEOUT_MS);
      commandTimer.unref();
    };

    const processPending = async () => {
      if (!imei) {
        const parsed = extractImei(pending);
        if (!parsed) return;
        imei = parsed.imei;
        pending = pending.subarray(parsed.consumed);
        device = await registry.findByImei(imei);
        if (!device) {
          socket.end(Buffer.from([0x00]));
          return;
        }
        authenticated = true;
        clearTimeout(handshakeTimer);
        socket.setTimeout(authenticatedTimeoutMs);
        socket.write(Buffer.from([0x01]));
      }

      while (pending.length >= 12) {
        const dataLength = pending.readUInt32BE(4);
        const frameLength = dataLength + 12;
        if (dataLength <= 0 || frameLength > maxPendingBytes) throw new Error('Invalid Teltonika frame length');
        if (pending.length < frameLength) return;
        const frame = pending.subarray(0, frameLength);
        pending = pending.subarray(frameLength);

        if (isCodec12Frame(frame)) {
          const response = decodeCodec12Response(frame);
          await finishInFlight('acknowledged', response);
          continue;
        }

        const decoded = decodeTeltonikaAvlPacket(frame, imei);
        await registry.updateCodec(imei, decoded.codec);
        for (const record of decoded.records) await ingestor.ingest(record);
        writeAvlAck(socket, decoded.ack);
        await dispatchNext(decoded.records.at(-1));
      }
    };

    socket.on('data', (chunk) => {
      pending = Buffer.concat([pending, chunk]);
      if (pending.length > maxPendingBytes) return socket.destroy(new Error('Pending buffer limit exceeded'));
      chain = chain.then(processPending).catch((error) => {
        console.error(`[TCP] ${imei || 'unknown'} ${error.message}`);
        socket.destroy();
      });
    });

    socket.on('close', () => {
      clearTimeout(handshakeTimer);
      clearTimeout(commandTimer);
      if (inFlight) finishInFlight('failed', 'Device connection closed.', 'connection_closed').catch(console.error);
    });
    socket.on('timeout', () => socket.destroy());
    socket.on('error', (error) => console.error(`[TCP] ${imei || 'unknown'} ${error.message}`));
  });

  server.maxConnections = positiveInteger(process.env.GPS_LISTENER_MAX_CONNECTIONS, 512);
  server.listen(port, host, () => console.log(`[TCP] GPS listener started on ${host}:${port}`));
}
