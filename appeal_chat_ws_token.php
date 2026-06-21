<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/chat_ws.php';

header('Content-Type: application/json; charset=utf-8');

$appealId = isset($_GET['appeal_id']) ? (int) $_GET['appeal_id'] : 0;
if ($appealId <= 0) {
    echo json_encode(['error' => 'invalid appeal']);
    exit;
}

$isAdmin = isset($_SESSION['admin_id']);
$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

if (!$isAdmin && $userId <= 0) {
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$stmt = $pdo->prepare('SELECT user_id FROM appeals WHERE id = ? LIMIT 1');
$stmt->execute([$appealId]);
$appeal = $stmt->fetch();
if (!$appeal) {
    echo json_encode(['error' => 'not found']);
    exit;
}
if (!$isAdmin && (int) $appeal['user_id'] !== $userId) {
    echo json_encode(['error' => 'forbidden']);
    exit;
}

if (!chat_ws_is_enabled()) {
    echo json_encode(['ws_url' => '', 'token' => '']);
    exit;
}

$senderType = $isAdmin ? 'admin' : 'user';
$senderId = $isAdmin ? (int) $_SESSION['admin_id'] : $userId;

echo json_encode([
    'ws_url' => CHAT_WS_URL,
    'token' => chat_ws_make_token($appealId, $senderType, $senderId),
], JSON_UNESCAPED_UNICODE);
