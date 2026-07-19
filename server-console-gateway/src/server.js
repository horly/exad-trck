import { createHash } from 'node:crypto';
import process from 'node:process';
import { Client as SshClient } from 'ssh2';
import { WebSocket, WebSocketServer } from 'ws';
import { verifyTicket } from './ticket.js';

const config = {
    host: process.env.HOST || '127.0.0.1',
    port: Number(process.env.PORT || 5091),
    path: process.env.WS_PATH || '/socket',
    allowedOrigins: new Set((process.env.ALLOWED_ORIGINS || '').split(',').map((value) => value.trim()).filter(Boolean)),
    allowedUsername: process.env.ALLOWED_USERNAME || 'exad-tracking',
    ticketSecret: process.env.TICKET_SECRET || '',
    ticketTtlSeconds: Number(process.env.TICKET_TTL_SECONDS || 30),
    sshHost: process.env.SSH_HOST || '127.0.0.1',
    sshPort: Number(process.env.SSH_PORT || 22),
    sshHostKeySha256: (process.env.SSH_HOST_KEY_SHA256 || '').replace(/^SHA256:/, ''),
    idleTimeoutMs: Number(process.env.IDLE_TIMEOUT_MS || 1_800_000),
    authTimeoutMs: Number(process.env.AUTH_TIMEOUT_MS || 15_000),
    maxSessions: Number(process.env.MAX_SESSIONS || 2),
};

if (config.ticketSecret.length < 32) throw new Error('TICKET_SECRET must contain at least 32 characters.');
if (!config.sshHostKeySha256) throw new Error('SSH_HOST_KEY_SHA256 is required.');
if (!['127.0.0.1', '::1', 'localhost'].includes(config.sshHost)) throw new Error('SSH_HOST must remain local.');
if (config.allowedOrigins.size === 0) throw new Error('ALLOWED_ORIGINS is required.');

const sessions = new Set();
const usedNonces = new Map();

const audit = (event, context = {}) => {
    process.stdout.write(`${JSON.stringify({ time: new Date().toISOString(), event, ...context })}\n`);
};

const clientIp = (request) => String(request.headers['x-forwarded-for'] || request.socket.remoteAddress || '')
    .split(',')[0]
    .trim();

const send = (socket, payload) => {
    if (socket.readyState === WebSocket.OPEN) socket.send(JSON.stringify(payload));
};

const gateway = new WebSocketServer({
    host: config.host,
    port: config.port,
    path: config.path,
    maxPayload: 64 * 1024,
    perMessageDeflate: false,
    verifyClient: ({ origin }) => config.allowedOrigins.has(origin),
});

gateway.on('connection', (socket, request) => {
    const ip = clientIp(request);
    let ssh = null;
    let stream = null;
    let claims = null;
    let authenticated = false;
    let closed = false;
    let idleTimer = null;
    let authTimer = null;
    let lastInputAt = Date.now();

    socket.isAlive = true;
    socket.on('pong', () => { socket.isAlive = true; });

    if (gateway.clients.size > config.maxSessions) {
        send(socket, { type: 'error', code: 'capacity_reached' });
        socket.close(4004, 'Capacity reached');
        return;
    }

    const closeSession = (reason) => {
        if (closed) return;
        closed = true;
        clearTimeout(authTimer);
        clearInterval(idleTimer);
        try { stream?.end(); } catch {}
        try { stream?.close(); } catch {}
        try { ssh?.end(); } catch {}
        setTimeout(() => { try { ssh?.destroy(); } catch {} }, 500).unref();
        sessions.delete(socket);
        audit('console_session_closed', { user_id: claims?.sub, username: config.allowedUsername, ip, reason });
    };

    authTimer = setTimeout(() => {
        send(socket, { type: 'error', code: 'authentication_timeout' });
        socket.close(4001, 'Authentication timeout');
    }, config.authTimeoutMs);

    socket.on('message', (raw) => {
        let message;
        try { message = JSON.parse(raw.toString('utf8')); } catch { socket.close(4002, 'Invalid message'); return; }

        if (!authenticated) {
            if (message.type !== 'authenticate' || sessions.size >= config.maxSessions) {
                send(socket, { type: 'error', code: 'authentication_failed' });
                socket.close(4003, 'Authentication failed');
                return;
            }

            claims = verifyTicket(
                message.ticket,
                config.ticketSecret,
                Math.floor(Date.now() / 1000),
                config.ticketTtlSeconds,
                usedNonces,
            );
            const username = String(message.username || '');
            let password = typeof message.password === 'string' ? message.password : '';
            message.password = '';

            if (!claims || username !== config.allowedUsername || password.length < 1 || password.length > 512) {
                password = '';
                send(socket, { type: 'error', code: 'authentication_failed' });
                socket.close(4003, 'Authentication failed');
                return;
            }

            ssh = new SshClient();
            ssh.on('ready', () => {
                password = '';
                ssh.shell({
                    term: 'xterm-256color',
                    cols: Math.max(20, Math.min(Number(message.cols || 120), 300)),
                    rows: Math.max(10, Math.min(Number(message.rows || 32), 120)),
                }, (error, channel) => {
                    if (error) {
                        send(socket, { type: 'error', code: 'terminal_failed' });
                        socket.close(4010, 'Terminal failed');
                        return;
                    }

                    clearTimeout(authTimer);
                    stream = channel;
                    authenticated = true;
                    sessions.add(socket);
                    lastInputAt = Date.now();
                    audit('console_session_opened', { user_id: claims.sub, username, ip });
                    send(socket, { type: 'ready', username });

                    stream.on('data', (data) => send(socket, { type: 'output', data: Buffer.from(data).toString('base64') }));
                    stream.stderr.on('data', (data) => send(socket, { type: 'output', data: Buffer.from(data).toString('base64') }));
                    stream.on('close', () => socket.close(1000, 'SSH session closed'));

                    idleTimer = setInterval(() => {
                        if (Date.now() - lastInputAt >= config.idleTimeoutMs) {
                            send(socket, { type: 'error', code: 'idle_timeout' });
                            socket.close(4008, 'Idle timeout');
                        }
                    }, 10_000);
                });
            });
            ssh.on('keyboard-interactive', (_name, _instructions, _language, prompts, finish) => {
                finish(prompts.map(() => password));
            });
            ssh.on('error', (error) => {
                password = '';
                audit('console_ssh_error', {
                    user_id: claims?.sub,
                    username,
                    ip,
                    level: error.level || 'unknown',
                    code: error.code || 'unknown',
                    message: error.message || 'SSH connection failed',
                });
                send(socket, { type: 'error', code: 'authentication_failed' });
                socket.close(4003, 'Authentication failed');
            });
            ssh.on('close', () => {
                if (socket.readyState === WebSocket.OPEN) socket.close(1000, 'SSH session closed');
            });
            ssh.connect({
                host: config.sshHost,
                port: config.sshPort,
                username,
                password,
                tryKeyboard: true,
                readyTimeout: config.authTimeoutMs,
                keepaliveInterval: 10_000,
                keepaliveCountMax: 2,
                algorithms: { serverHostKey: ['ssh-ed25519'] },
                hostVerifier: (key) => {
                    const observedFingerprint = createHash('sha256').update(key).digest('base64').replace(/=+$/, '');
                    const accepted = observedFingerprint === config.sshHostKeySha256;
                    if (!accepted) {
                        audit('console_host_key_rejected', {
                            expected: config.sshHostKeySha256,
                            observed: observedFingerprint,
                        });
                    }
                    return accepted;
                },
            });
            return;
        }

        if (message.type === 'input' && typeof message.data === 'string' && message.data.length <= 16_384) {
            lastInputAt = Date.now();
            stream?.write(message.data);
        } else if (message.type === 'resize') {
            const cols = Math.max(20, Math.min(Number(message.cols || 120), 300));
            const rows = Math.max(10, Math.min(Number(message.rows || 32), 120));
            stream?.setWindow(rows, cols, 0, 0);
        } else if (message.type === 'disconnect') {
            socket.close(1000, 'Client disconnected');
        }
    });

    socket.on('close', (code) => closeSession(`websocket_${code}`));
    socket.on('error', () => closeSession('websocket_error'));
});

const heartbeat = setInterval(() => {
    const now = Math.floor(Date.now() / 1000);
    for (const [nonce, expiry] of usedNonces) if (expiry < now) usedNonces.delete(nonce);

    for (const socket of gateway.clients) {
        if (!socket.isAlive) {
            socket.terminate();
            continue;
        }
        socket.isAlive = false;
        socket.ping();
    }
}, 10_000);

const shutdown = () => {
    clearInterval(heartbeat);
    for (const socket of gateway.clients) socket.close(1012, 'Gateway restarting');
    gateway.close(() => process.exit(0));
    setTimeout(() => process.exit(1), 3_000).unref();
};

process.on('SIGTERM', shutdown);
process.on('SIGINT', shutdown);
gateway.on('listening', () => audit('console_gateway_listening', { host: config.host, port: config.port, path: config.path }));
