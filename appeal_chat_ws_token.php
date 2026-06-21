<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/chat_ws.php';

header('Content-Type: application/json; charset=utf-8');

$appealId = isset($_GET['appeal_id']) ? (int) $_GET['appeal_id'] : 0;
$requestedRole = isset($_GET['role']) ? (string) $_GET['role'] : 'user';
if ($appealId <= 0) {
    echo json_encode(['error' => 'invalid appeal']);
    exit;
}

$sender = chat_resolve_sender($pdo, $appealId, $requestedRole);
if ($sender === null) {
    echo json_encode(['error' => 'forbidden']);
    exit;
}

if (!chat_ws_is_enabled()) {
    echo json_encode(['ws_url' => '', 'token' => '']);
    exit;
}

echo json_encode([
    'ws_url' => CHAT_WS_URL,
    'token' => chat_ws_make_token($appealId, $sender['sender_type'], $sender['sender_id']),
], JSON_UNESCAPED_UNICODE);
