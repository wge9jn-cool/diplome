<?php

/**
 * Токен для WebSocket-чата (HMAC, base64url).
 */
function chat_ws_make_token(int $appealId, string $senderType, int $senderId, int $ttlSeconds = 7200): string
{
    $payload = [
        'a' => $appealId,
        's' => $senderType,
        'i' => $senderId,
        'e' => time() + $ttlSeconds,
    ];
    $body = chat_ws_base64url_encode(json_encode($payload, JSON_UNESCAPED_UNICODE));
    $sig = chat_ws_base64url_encode(hash_hmac('sha256', $body, CHAT_WS_SECRET, true));

    return $body . '.' . $sig;
}

function chat_ws_base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function chat_ws_is_enabled(): bool
{
    return defined('CHAT_WS_URL') && CHAT_WS_URL !== '';
}

/**
 * Кто отправляет сообщение в чат: клиент (user) или сотрудник/админ (admin).
 *
 * @return array{sender_type: string, sender_id: int}|null
 */
function chat_resolve_sender(PDO $pdo, int $appealId, string $requestedRole): ?array
{
    $requestedRole = $requestedRole === 'admin' ? 'admin' : 'user';

    $stmt = $pdo->prepare('SELECT user_id FROM appeals WHERE id = ? LIMIT 1');
    $stmt->execute([$appealId]);
    $appeal = $stmt->fetch();
    if (!$appeal) {
        return null;
    }

    $appealUserId = (int) $appeal['user_id'];
    $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    $isStaff = isset($_SESSION['admin_id'])
        && in_array((string) ($_SESSION['admin_role'] ?? ''), ['admin', 'employee'], true);

    if ($requestedRole === 'user') {
        if ($userId <= 0 || $userId !== $appealUserId) {
            return null;
        }

        return ['sender_type' => 'user', 'sender_id' => $userId];
    }

    if (!$isStaff) {
        return null;
    }

    return ['sender_type' => 'admin', 'sender_id' => (int) $_SESSION['admin_id']];
}
