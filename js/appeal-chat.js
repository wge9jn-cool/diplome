/**
 * Чат по обращению: история через HTTP, доставка в реальном времени через WebSocket.
 * window.APPEAL_CHAT_CONFIG — задаётся на странице до подключения скрипта.
 */
(function () {
    'use strict';

    var cfg = window.APPEAL_CHAT_CONFIG;
    if (!cfg || !cfg.appealId) return;

    var appealId = cfg.appealId;
    var chatRole = cfg.chatRole === 'admin' ? 'admin' : 'user';
    var tokenUrl = cfg.tokenUrl || 'appeal_chat_ws_token.php';
    var historyUrl = cfg.historyUrl || 'appeal_chat.php';
    var fallbackPostUrl = cfg.fallbackPostUrl || historyUrl;
    var labels = cfg.labels || { user: 'Вы', admin: 'Специалист' };

    var chatScroll = document.getElementById('chatScroll');
    var chatStream = document.getElementById('chatStream');
    var chatEmpty = document.getElementById('chatEmpty');
    var form = document.getElementById('chatForm');
    var textarea = document.getElementById('chatMessage');
    if (!chatScroll || !chatStream || !form || !textarea) return;

    var seenIds = Object.create(null);
    var ws = null;
    var wsReady = false;
    var reconnectTimer = null;
    var reconnectDelay = 2000;

    function syncEmptyState() {
        var has = chatStream.children.length > 0;
        chatEmpty.hidden = has;
        chatScroll.classList.toggle('appeal-chat-scroll--has-messages', has);
    }

    function escapeHtml(s) {
        var div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    function formatDate(raw) {
        if (!raw) return '';
        return String(raw).replace('T', ' ').slice(0, 16);
    }

    function appendMessage(senderType, text, date, msgId) {
        if (msgId && seenIds[msgId]) return;
        if (msgId) seenIds[msgId] = true;

        var who = senderType === 'admin' ? 'admin' : 'user';
        var label = senderType === 'admin' ? labels.admin : labels.user;
        var div = document.createElement('div');
        div.className = 'appeal-chat-bubble appeal-chat-bubble--' + who;
        div.innerHTML =
            '<div class="appeal-chat-bubble__label">' +
            escapeHtml(label) +
            '</div>' +
            '<div class="appeal-chat-bubble__text">' +
            escapeHtml(text) +
            '</div>' +
            (date ? '<div class="appeal-chat-bubble__time">' + escapeHtml(formatDate(date)) + '</div>' : '');
        chatStream.appendChild(div);
        syncEmptyState();
        chatScroll.scrollTop = chatScroll.scrollHeight;
    }

    syncEmptyState();

    function loadHistory() {
        fetch(historyUrl + '?appeal_id=' + encodeURIComponent(appealId))
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (data.messages && data.messages.length) {
                    data.messages.forEach(function (m) {
                        appendMessage(m.sender_type, m.message, m.created_at, m.id);
                    });
                }
                syncEmptyState();
            })
            .catch(function () {});
    }

    function scheduleReconnect() {
        if (reconnectTimer) return;
        reconnectTimer = setTimeout(function () {
            reconnectTimer = null;
            connectWebSocket();
            reconnectDelay = Math.min(reconnectDelay * 1.5, 30000);
        }, reconnectDelay);
    }

    function connectWebSocket() {
        if (ws) {
            wsReady = false;
            try {
                ws.close();
            } catch (e) {}
            ws = null;
        }
        fetch(tokenUrl + '?appeal_id=' + encodeURIComponent(appealId) + '&role=' + encodeURIComponent(chatRole))
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (!data.ws_url || !data.token) return;
                try {
                    ws = new WebSocket(data.ws_url);
                } catch (e) {
                    return;
                }
                ws.onopen = function () {
                    reconnectDelay = 2000;
                    ws.send(JSON.stringify({ type: 'auth', token: data.token }));
                };
                ws.onmessage = function (ev) {
                    var msg;
                    try {
                        msg = JSON.parse(ev.data);
                    } catch (e) {
                        return;
                    }
                    if (msg.type === 'auth_ok') {
                        wsReady = true;
                        return;
                    }
                    if (msg.type === 'message' && msg.message) {
                        appendMessage(msg.sender_type, msg.message, msg.created_at, msg.id);
                    }
                };
                ws.onclose = function () {
                    wsReady = false;
                    ws = null;
                    scheduleReconnect();
                };
                ws.onerror = function () {
                    wsReady = false;
                };
            })
            .catch(function () {});
    }

    function sendViaHttp(text) {
        var fd = new FormData();
        fd.append('appeal_id', appealId);
        fd.append('chat_role', chatRole);
        fd.append('message', text);
        return fetch(fallbackPostUrl, { method: 'POST', body: fd })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (data.error) return;
                appendMessage(data.sender_type, data.message, data.created_at, data.id);
            });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = textarea.value.trim();
        if (!text) return;

        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        function done() {
            textarea.value = '';
            if (submitBtn) submitBtn.disabled = false;
        }

        if (ws && wsReady && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({ type: 'message', text: text }));
            done();
            return;
        }

        sendViaHttp(text).then(done).catch(done);
    });

    loadHistory();
    connectWebSocket();
})();
