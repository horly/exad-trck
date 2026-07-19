import { createHmac } from 'node:crypto';
import test from 'node:test';
import assert from 'node:assert/strict';
import { verifyTicket } from '../src/ticket.js';

const secret = 'test-secret-that-is-longer-than-thirty-two-characters';
const now = 1_721_370_000;

const ticketFor = (overrides = {}) => {
    const claims = {
        aud: 'exad-server-console',
        sub: '42',
        name: 'Super Admin',
        nonce: 'nonce-1',
        iat: now,
        exp: now + 30,
        ...overrides,
    };
    const payload = Buffer.from(JSON.stringify(claims)).toString('base64url');
    const signature = createHmac('sha256', secret).update(payload).digest('hex');
    return `${payload}.${signature}`;
};

test('accepts one valid short-lived ticket', () => {
    const nonces = new Map();
    const claims = verifyTicket(ticketFor(), secret, now, 30, nonces);

    assert.equal(claims?.sub, '42');
    assert.equal(nonces.get('nonce-1'), now + 30);
});

test('rejects a replayed or tampered ticket', () => {
    const nonces = new Map();
    const ticket = ticketFor();

    assert.ok(verifyTicket(ticket, secret, now, 30, nonces));
    assert.equal(verifyTicket(ticket, secret, now, 30, nonces), null);
    assert.equal(verifyTicket(`${ticket}0`, secret, now, 30, new Map()), null);
});

test('rejects expired and excessively long-lived tickets', () => {
    assert.equal(verifyTicket(ticketFor({ exp: now - 1 }), secret, now, 30, new Map()), null);
    assert.equal(verifyTicket(ticketFor({ exp: now + 120 }), secret, now, 30, new Map()), null);
});
