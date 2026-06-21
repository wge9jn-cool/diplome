/**
 * WebSocket-сервер чата по обращениям.
 * Запуск: cd realtime/chat-server && npm install && npm start
 * Скопируйте .env.example в .env и укажите те же DB_* и WS_SECRET, что в config.php.
 */
const fs = require('fs');
const path = require('path');
const http = require('http');
const crypto = require('crypto');
const WebSocket = require('ws');
const mysql = require('mysql2/promise');

function loadEnvFile() {
    const envPath = path.join(__dirname, '.env');
    if (!fs.existsSync(envPath)) return;
    const lines = fs.readFileSync(envPath, 'utf8').split(/\r?\n/);
    for (const line of lines) {
        const trimmed = line.trim();
        if (!trimmed || trimmed.startsWith('#')) continue;
        const eq = trimmed.indexOf('=');
        if (eq === -1) continue;
        const key = trimmed.slice(0, eq).trim();
        let val = trimmed.slice(eq + 1).trim();
        if ((val.startsWith('"') && val.endsWith('"')) || (val.startsWith("'") && val.endsWith("'"))) {
            val = val.slice(1, -1);
        }
        if (process.env[key] === undefined) process.env[key] = val;
    }
}

loadEnvFile();

const WS_PORT = parseInt(process.env.WS_PORT || '8080', 10);
const WS_SECRET = process.env.WS_SECRET || '';
const DB_HOST = process.env.DB_HOST || '127.0.0.1';
const DB_NAME = process.env.DB_NAME || 'diplom';
const DB_USER = process.env.DB_USER || 'root';
const DB_PASS = process.env.DB_PASS || '';
const DB_TIMEZONE = process.env.DB_TIMEZONE || '+05:00';

if (!WS_SECRET) {
    console.error('WS_SECRET is required. Copy .env.example to .env');
    process.exit(1);
}

const pool = mysql.createPool({
    host: DB_HOST,
    user: DB_USER,
    password: DB_PASS,
    database: DB_NAME,
    charset: 'utf8mb4',
    timezone: DB_TIMEZONE,
    waitForConnections: true,
    connectionLimit: 10,
});

/** @type {Map<string, Set<WebSocket>>} */
const rooms = new Map();

function base64urlDecode(str) {
    const pad = str.length % 4 === 0 ? '' : '='.repeat(4 - (str.length % 4));
    return Buffer.from(str.replace(/-/g, '+').replace(/_/g, '/') + pad, 'base64');
}

function verifyToken(token) {
    if (!token || typeof token !== 'string') return null;
    const parts = token.split('.');
    if (parts.length !== 2) return null;
    const [body, sig] = parts;
    const expected = base64urlDecode(sig);
    const actual = crypto.createHmac('sha256', WS_SECRET).update(body).digest();
    if (expected.length !== actual.length || !crypto.timingSafeEqual(expected, actual)) {
        return null;
    }
    let payload;
    try {
        payload = JSON.parse(base64urlDecode(body).toString('utf8'));
    } catch {
        return null;
    }
    if (!payload || !payload.a || !payload.s || !payload.i || !payload.e) return null;
    if (payload.s !== 'user' && payload.s !== 'admin') return null;
    if (Math.floor(Date.now() / 1000) > payload.e) return null;
    return {
        appealId: String(payload.a),
        senderType: payload.s,
        senderId: parseInt(payload.i, 10),
    };
}

function joinRoom(ws, appealId) {
    let set = rooms.get(appealId);
    if (!set) {
        set = new Set();
        rooms.set(appealId, set);
    }
    set.add(ws);
    ws._appealId = appealId;
}

function leaveRoom(ws) {
    const appealId = ws._appealId;
    if (!appealId) return;
    const set = rooms.get(appealId);
    if (!set) return;
    set.delete(ws);
    if (set.size === 0) rooms.delete(appealId);
}

function broadcast(appealId, payload) {
    const set = rooms.get(String(appealId));
    if (!set) return;
    const raw = JSON.stringify(payload);
    for (const client of set) {
        if (client.readyState === WebSocket.OPEN) {
            client.send(raw);
        }
    }
}

const server = http.createServer((_req, res) => {
    res.writeHead(200, { 'Content-Type': 'text/plain; charset=utf-8' });
    res.end('WebSocket chat server is running.\n');
});

const wss = new WebSocket.Server({ server });

wss.on('connection', (ws) => {
    let auth = null;

    ws.on('message', async (raw) => {
        let data;
        try {
            data = JSON.parse(String(raw));
        } catch {
            return;
        }
        if (!data || !data.type) return;

        if (data.type === 'auth') {
            auth = verifyToken(data.token);
            if (!auth) {
                ws.send(JSON.stringify({ type: 'error', code: 'auth_failed' }));
                ws.close(4001, 'auth_failed');
                return;
            }
            try {
                const [rows] = await pool.query(
                    'SELECT id FROM appeals WHERE id = ? LIMIT 1',
                    [auth.appealId]
                );
                if (!rows.length) {
                    ws.send(JSON.stringify({ type: 'error', code: 'appeal_not_found' }));
                    ws.close(4004, 'appeal_not_found');
                    return;
                }
            } catch (err) {
                console.error('DB error on auth:', err.message);
                ws.close(1011, 'db_error');
                return;
            }
            joinRoom(ws, auth.appealId);
            ws.send(JSON.stringify({ type: 'auth_ok', appealId: auth.appealId }));
            return;
        }

        if (data.type === 'message') {
            if (!auth) {
                ws.send(JSON.stringify({ type: 'error', code: 'not_authenticated' }));
                return;
            }
            const text = String(data.text || '').trim();
            if (!text) return;

            try {
                const [result] = await pool.query(
                    `INSERT INTO appeal_messages (appeal_id, sender_type, sender_id, message)
                     VALUES (?, ?, ?, ?)`,
                    [auth.appealId, auth.senderType, auth.senderId, text]
                );
                const id = result.insertId;
                const [rows] = await pool.query(
                    'SELECT created_at FROM appeal_messages WHERE id = ? LIMIT 1',
                    [id]
                );
                const createdAt = rows[0]
                    ? rows[0].created_at
                    : new Date().toISOString().slice(0, 19).replace('T', ' ');

                const payload = {
                    type: 'message',
                    id,
                    appeal_id: parseInt(auth.appealId, 10),
                    sender_type: auth.senderType,
                    sender_id: auth.senderId,
                    message: text,
                    created_at: createdAt,
                };
                broadcast(auth.appealId, payload);
            } catch (err) {
                console.error('DB error on message:', err.message);
                ws.send(JSON.stringify({ type: 'error', code: 'save_failed' }));
            }
        }
    });

    ws.on('close', () => leaveRoom(ws));
});

const WS_HOST = process.env.WS_HOST || '0.0.0.0';

server.listen(WS_PORT, WS_HOST, () => {
    console.log(`WebSocket chat server: ws://${WS_HOST === '0.0.0.0' ? '127.0.0.1' : WS_HOST}:${WS_PORT}`);
    console.log(`Database: ${DB_USER}@${DB_HOST}/${DB_NAME}`);
});
