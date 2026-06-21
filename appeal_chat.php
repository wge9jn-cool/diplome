<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/chat_ws.php';
require_once __DIR__ . '/includes/app_datetime.php';

header('Content-Type: application/json; charset=utf-8');

$appealId = isset($_GET['appeal_id']) ? (int) $_GET['appeal_id'] : (isset($_POST['appeal_id']) ? (int) $_POST['appeal_id'] : 0);
$requestedRole = isset($_GET['role']) ? (string) $_GET['role'] : (isset($_POST['chat_role']) ? (string) $_POST['chat_role'] : 'user');
if ($appealId <= 0) {
    echo json_encode(['error' => 'invalid appeal']);
    exit;
}

$sender = chat_resolve_sender($pdo, $appealId, $requestedRole);
if ($sender === null) {
    echo json_encode(['error' => 'forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare('
        SELECT id, sender_type, sender_id, message, created_at
        FROM appeal_messages
        WHERE appeal_id = ?
        ORDER BY created_at ASC, id ASC
    ');
    $stmt->execute([$appealId]);
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($list as &$row) {
        $row['created_at'] = app_format_datetime((string) ($row['created_at'] ?? ''));
    }
    unset($row);
    echo json_encode(['messages' => $list], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    if ($message === '') {
        echo json_encode(['error' => 'empty']);
        exit;
    }

    $senderType = $sender['sender_type'];
    $senderId = $sender['sender_id'];
    $createdAt = app_now();

    $stmt = $pdo->prepare('INSERT INTO appeal_messages (appeal_id, sender_type, sender_id, message, created_at) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$appealId, $senderType, $senderId, $message, $createdAt]);
    $id = (int) $pdo->lastInsertId();

    echo json_encode([
        'id' => $id,
        'sender_type' => $senderType,
        'sender_id' => $senderId,
        'message' => $message,
        'created_at' => $createdAt,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['error' => 'method']);
exit;
