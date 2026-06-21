// Простой WebSocket-сервер чата по обращениям
// Запуск: node chat-server.js

const WebSocket = require('ws');

const PORT = 8080;
const wss = new WebSocket.Server({ port: PORT });

// { appealId: string -> Set<ws> }
const rooms = new Map();

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
    if (set.size === 0) {
        rooms.delete(appealId);
    }
}

wss.on('connection', (ws) => {
    ws.on('message', (msg) => {
        let data;
        try {
            data = JSON.parse(msg);
        } catch {
            return;
        }
        if (!data || !data.type) return;

        if (data.type === 'join' && data.appealId) {
            joinRoom(ws, String(data.appealId));
            return;
        }

        if (data.type === 'message' && data.appealId && data.text) {
            const appealId = String(data.appealId);
            const set = rooms.get(appealId);
            if (!set) return;

            const payload = JSON.stringify({
                type: 'message',
                appealId,
                senderType: data.senderType || 'user',
                senderName: data.senderName || '',
                text: String(data.text),
                createdAt: new Date().toISOString(),
            });

            for (const client of set) {
                if (client.readyState === WebSocket.OPEN) {
                    client.send(payload);
                }
            }
        }
    });

    ws.on('close', () => {
        leaveRoom(ws);
    });
});

console.log(`WebSocket chat server listening on ws://localhost:${PORT}`);

