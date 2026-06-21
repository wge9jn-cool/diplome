<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare('
    SELECT a.id, a.user_id, a.topic, a.description, a.generated_doc_path, a.created_at,
           u.name, u.phone, u.email
    FROM appeals a
    JOIN users u ON u.id = a.user_id
    WHERE a.id = ? LIMIT 1
');
$stmt->execute([$id]);
$appeal = $stmt->fetch();

if (!$appeal || (int) $appeal['user_id'] !== $userId) {
    header('Location: cabinet.php');
    exit;
}

$docDir = __DIR__ . '/uploads/docs';
if (!is_dir($docDir)) {
    mkdir($docDir, 0777, true);
}

$filename = $id . '_draft_user_' . date('Ymd_His') . '.txt';
$path = $docDir . '/' . $filename;

$content = "Черновик претензии по обращению №{$appeal['id']}\n\n";
$content .= "ФИО заявителя: {$appeal['name']}\n";
$content .= "Телефон: {$appeal['phone']}\n";
$content .= "E-mail: {$appeal['email']}\n\n";
$content .= "Описание ситуации:\n{$appeal['description']}\n\n";
$content .= "Дата обращения: {$appeal['created_at']}\n";

file_put_contents($path, $content);

$relPath = 'uploads/docs/' . $filename;
$stmt = $pdo->prepare('UPDATE appeals SET generated_doc_path = ? WHERE id = ?');
$stmt->execute([$relPath, $id]);

header('Location: cabinet_appeal.php?id=' . $id);
exit;

