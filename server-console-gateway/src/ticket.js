import { createHmac, timingSafeEqual } from 'node:crypto';

const decodeBase64Url = (value) => {
    const padding = '='.repeat((4 - (value.length % 4)) % 4);
    return Buffer.from(value.replace(/-/g, '+').replace(/_/g, '/') + padding, 'base64').toString('utf8');
};

export const verifyTicket = (ticket, secret, now, ticketTtlSeconds, usedNonces) => {
    if (typeof ticket !== 'string' || ticket.length > 4096) return null;
    const [payload, signature, extra] = ticket.split('.');
    if (!payload || !signature || extra || !/^[a-f0-9]{64}$/i.test(signature)) return null;

    const expected = createHmac('sha256', secret).update(payload).digest('hex');
    const suppliedBuffer = Buffer.from(signature, 'hex');
    const expectedBuffer = Buffer.from(expected, 'hex');

    if (suppliedBuffer.length !== expectedBuffer.length || !timingSafeEqual(suppliedBuffer, expectedBuffer)) return null;

    try {
        const claims = JSON.parse(decodeBase64Url(payload));
        const maximumExpiry = now + Math.min(Math.max(ticketTtlSeconds, 10), 60) + 5;

        if (claims.aud !== 'exad-server-console' || typeof claims.sub !== 'string' || typeof claims.nonce !== 'string') return null;
        if (!Number.isInteger(claims.iat) || !Number.isInteger(claims.exp) || claims.iat > now + 5 || claims.exp < now || claims.exp > maximumExpiry) return null;
        if (usedNonces.has(claims.nonce)) return null;

        usedNonces.set(claims.nonce, claims.exp);
        return claims;
    } catch {
        return null;
    }
};
