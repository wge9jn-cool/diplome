<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/appeal_topic_from_calc.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php#calculator');
    exit;
}

$agree = isset($_POST['agree']);
$comment = trim($_POST['comment'] ?? '');
$paymentMethod = $_POST['payment_method'] ?? 'yookassa';
$action = $_POST['action'] ?? $paymentMethod;
$itemsJson = $_POST['items_json'] ?? '[]';

if (!$agree) {
    header('Location: index.php#calculator');
    exit;
}

$allowedPayments = ['yookassa', 'later'];
if (!in_array($paymentMethod, $allowedPayments, true)) {
    $paymentMethod = 'yookassa';
}
if (!in_array($action, $allowedPayments, true)) {
    $action = $paymentMethod;
}

$items = json_decode($itemsJson, true);
if (!is_array($items) || !$items) {
    header('Location: index.php#calculator');
    exit;
}

// Серверный пересчёт суммы (защита от подмены на клиенте) — тот же каталог, что в includes/calc_catalog.php и в калькуляторе
require_once __DIR__ . '/includes/calc_catalog.php';
$catalog = calc_catalog_checkout_map();

$total = 0;
$lines = [];
$requiresContact = false;
$hasQuoteItems = false;
$sumByService = [];

foreach ($items as $it) {
    if (!is_array($it)) {
        continue;
    }
    $serviceId = (string) ($it['service_id'] ?? '');
    $variantId = (string) ($it['variant_id'] ?? '');
    $qty = (int) ($it['qty'] ?? 1);
    $urgency = (bool) ($it['urgency'] ?? false);

    if (!isset($catalog[$serviceId])) {
        continue;
    }
    $svc = $catalog[$serviceId];
    $isQuote = (($svc['kind'] ?? '') === 'quote');
    $isHourly = (($svc['kind'] ?? '') === 'hourly');
    $qty = max(1, min($isHourly ? 8 : 10, $qty));

    if ($isQuote) {
        if (!isset($svc['variants'][$variantId])) {
            continue;
        }
        $v = $svc['variants'][$variantId];
        $hasQuoteItems = true;
        $sumByService[$serviceId] = $sumByService[$serviceId] ?? 0;
        $lines[] = "• {$svc['title']} — {$v['title']}: стоимость рассчитывается специалистом после уточнения объёма работ";
        continue;
    }

    if ($isHourly) {
        if (!isset($svc['variants'][$variantId])) {
            continue;
        }
        $v = $svc['variants'][$variantId];
        $price = (int) round($qty * (int) $v['rate']);
        $total += $price;
        $sumByService[$serviceId] = ($sumByService[$serviceId] ?? 0) + $price;
        $rate = (int) $v['rate'];
        $lines[] = "• {$svc['title']} — {$v['title']}: {$qty} ч × {$rate} ₽ = {$price} ₽";
        continue;
    }

    if (!isset($svc['variants'][$variantId])) {
        continue;
    }
    $v = $svc['variants'][$variantId];
    $price = (int) $v['price'] * $qty;
    if ($urgency && !empty($svc['extras']['urgency'])) {
        $price = (int) round($price * 1.3);
    }
    $total += $price;
    $sumByService[$serviceId] = ($sumByService[$serviceId] ?? 0) + $price;
    $requiresContact = $requiresContact || !empty($v['requires_contact']);
    $suffix = $qty > 1 ? " × {$qty}" : '';
    $extra = ($urgency && !empty($svc['extras']['urgency'])) ? ' + срочно (+30%)' : '';
    $lines[] = "• {$svc['title']} — {$v['title']}{$extra}{$suffix}: {$price} ₽";
}

if ($total <= 0 && !$hasQuoteItems) {
    header('Location: index.php#calculator');
    exit;
}

if ($hasQuoteItems) {
    $action = 'later';
    $paymentMethod = 'later';
}

// Раньше при любой «сложной» позиции принудительно ставилась оплата «позже» — из-за этого при выборе
// «ЮKassa» пользователь попадал в requests.php («Мои обращения»), а не на оплату. Если клиент выбрал
// онлайн-оплату, сохраняем её.
if ($requiresContact && $paymentMethod !== 'yookassa') {
    $action = 'later';
}

$finalComment = "Заявка из калькулятора.\n\nСостав заказа:\n" . implode("\n", $lines);
if ($comment !== '') {
    $finalComment .= "\n\nСитуация:\n" . $comment;
}

$appealTopicPreset = appeal_topic_preset_from_service_totals($sumByService);
$appealServiceSummary = implode("\n", $lines);

$stmt = $pdo->prepare('INSERT INTO requests (user_id, service, sum, comment, payment_method, status, is_paid, appeal_topic_preset, appeal_service_summary, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
$stmt->execute([
    $userId,
    'calculator',
    $total,
    $finalComment,
    $action,
    'Новое',
    0,
    $appealTopicPreset,
    $appealServiceSummary,
]);

$requestId = (int)$pdo->lastInsertId();

if ($action === 'yookassa') {
    header('Location: pay.php?request_id=' . $requestId . '&auto=1');
    exit;
}

header('Location: cabinet.php#cabinet-appeal');
exit;

