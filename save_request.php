<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$service = $_POST['service'] ?? '';
$sum = isset($_POST['sum']) ? (int) $_POST['sum'] : 0;
$comment = trim($_POST['comment'] ?? '');
$paymentMethod = $_POST['payment_method'] ?? 'yookassa';
$agree = isset($_POST['agree']);

if (!$agree || $service === '') {
    header('Location: index.php');
    exit;
}

$allowedServices = [
    'consult_rights',
    'consult_expertise',
    'docs_phys',
    'docs_org',
    'court',
    'expertise_hourly',
    'other',
];
if (!in_array($service, $allowedServices, true)) {
    $service = 'other';
}

if ($sum < 0) {
    $sum = 0;
}

$allowedPayments = ['yookassa', 'later'];
if (!in_array($paymentMethod, $allowedPayments, true)) {
    $paymentMethod = 'yookassa';
}

$stmt = $pdo->prepare('INSERT INTO requests (user_id, service, sum, comment, payment_method, status, is_paid, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
$stmt->execute([
    $userId,
    $service,
    $sum,
    $comment,
    $paymentMethod,
    'Новое',
    0,
]);

$requestId = $pdo->lastInsertId();

if ($paymentMethod === 'yookassa') {
    header('Location: pay.php?request_id=' . (int) $requestId);
    exit;
}

header('Location: requests.php');
exit;

