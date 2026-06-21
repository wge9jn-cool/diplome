<?php
session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$appealId = isset($_GET['appeal_id']) ? (int) $_GET['appeal_id'] : (isset($_POST['appeal_id']) ? (int) $_POST['appeal_id'] : 0);
if ($appealId <= 0) {
    echo json_encode(['error' => 'invalid appeal']);
    exit;
}

$isAdmin = isset($_SESSION['admin_id']);
$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

// Проверка доступа
$stmt = $pdo->prepare('SELECT user_id FROM appeals WHERE id = ? LIMIT 1');
$stmt->execute([$appealId]);
$appeal = $stmt->fetch();
if (!$appeal) {
    echo json_encode(['error' => 'not found']);
    exit;
}
if (!$isAdmin && (int)$appeal['user_id'] !== $userId) {
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
    echo json_encode(['messages' => $list]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    if ($message === '') {
        echo json_encode(['error' => 'empty']);
        exit;
    }
    $senderType = $isAdmin ? 'admin' : 'user';
    $senderId = $isAdmin ? (int) $_SESSION['admin_id'] : $userId;

    $stmt = $pdo->prepare('INSERT INTO appeal_messages (appeal_id, sender_type, sender_id, message) VALUES (?, ?, ?, ?)');
    $stmt->execute([$appealId, $senderType, $senderId, $message]);
    $id = (int) $pdo->lastInsertId();
    $stmt = $pdo->prepare('SELECT created_at FROM appeal_messages WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    $createdAt = $row ? $row['created_at'] : date('Y-m-d H:i:s');

    echo json_encode([
        'id' => $id,
        'sender_type' => $senderType,
        'sender_id' => $senderId,
        'message' => $message,
        'created_at' => $createdAt,
    ]);
    exit;
}

echo json_encode(['error' => 'method']);
exit;
