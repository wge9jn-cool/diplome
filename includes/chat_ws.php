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
