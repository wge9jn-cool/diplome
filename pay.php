<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/yookassa_config.php';

function yookassa_request(string $method, string $path, ?array $payload = null, ?string $idempotenceKey = null): array
{
    $url = rtrim(YOOKASSA_API_URL, '/') . $path;
    $ch = curl_init($url);
    if ($ch === false) {
        return [null, 0, 'Не удалось инициализировать cURL.'];
    }

    $headers = [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(YOOKASSA_SHOP_ID . ':' . YOOKASSA_SECRET_KEY),
    ];
    if ($idempotenceKey !== null) {
        $headers[] = 'Idempotence-Key: ' . $idempotenceKey;
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    $responseRaw = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($responseRaw === false) {
        return [null, $httpCode, $curlError !== '' ? $curlError : 'Ошибка запроса к ЮKassa.'];
    }

    $response = json_decode($responseRaw, true);
    if (!is_array($response)) {
        return [null, $httpCode, 'Некорректный ответ от ЮKassa.'];
    }

    return [$response, $httpCode, null];
}

/**
 * Создаёт платёж в ЮKassa и возвращает URL перенаправления на оплату либо текст ошибки.
 */
function yookassa_create_payment_for_request(PDO $pdo, array $request, int $userId, int $requestId): array
{
    if ((int) $request['is_paid'] === 1) {
        return ['ok' => false, 'error' => 'Заявка уже оплачена.'];
    }
    if ((int) $request['sum'] <= 0) {
        return ['ok' => false, 'error' => 'Для данной заявки сумма пока не установлена. Оплата недоступна.'];
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/pay.php';
    $basePath = str_replace('\\', '/', dirname($scriptName));
    if ($basePath !== '/' && $basePath !== '') {
        $payPath = rtrim($basePath, '/') . '/pay.php';
    } else {
        $payPath = '/pay.php';
    }
    $returnUrl = $scheme . '://' . $host . $payPath . '?request_id=' . $requestId . '&check=1';

    $payload = [
        'amount' => [
            'value' => number_format((float) $request['sum'], 2, '.', ''),
            'currency' => 'RUB',
        ],
        'capture' => true,
        'confirmation' => [
            'type' => 'redirect',
            'return_url' => $returnUrl,
        ],
        'description' => 'Оплата заявки №' . $requestId . ' (' . (string) $request['service'] . ')',
        'metadata' => [
            'request_id' => (string) $requestId,
            'user_id' => (string) $userId,
        ],
    ];

    $idempotenceKey = 'req-' . $requestId . '-' . bin2hex(random_bytes(6));
    [$payment, $httpCode, $requestError] = yookassa_request('POST', '/payments', $payload, $idempotenceKey);

    if ($requestError !== null || $httpCode >= 400 || !isset($payment['id'], $payment['confirmation']['confirmation_url'])) {
        return ['ok' => false, 'error' => 'Ошибка создания платежа в ЮKassa. Проверьте данные магазина в yookassa_config.php и попробуйте снова.'];
    }

    $_SESSION['yookassa_payments'][$requestId] = (string) $payment['id'];
    $stmt = $pdo->prepare('UPDATE requests SET status = ? WHERE id = ? AND is_paid = 0');
    $stmt->execute(['Ожидает оплаты', $requestId]);

    return ['ok' => true, 'url' => (string) $payment['confirmation']['confirmation_url']];
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$requestId = isset($_GET['request_id']) ? (int) $_GET['request_id'] : 0;

$stmt = $pdo->prepare('SELECT id, user_id, service, sum, is_paid FROM requests WHERE id = ? LIMIT 1');
$stmt->execute([$requestId]);
$request = $stmt->fetch();

if (!$request || (int) $request['user_id'] !== $userId) {
    header('Location: requests.php');
    exit;
}

$error = '';
$success = '';

if (!isset($_SESSION['yookassa_payments']) || !is_array($_SESSION['yookassa_payments'])) {
    $_SESSION['yookassa_payments'] = [];
}

// После калькулятора: сразу создаём платёж и ведём на ЮKassa (без второго нажатия «Перейти к оплате»).
if (
    $_SERVER['REQUEST_METHOD'] === 'GET'
    && isset($_GET['auto'], $_GET['request_id'])
    && $_GET['auto'] === '1'
    && (!isset($_GET['check']) || $_GET['check'] !== '1')
) {
    $existingPid = $_SESSION['yookassa_payments'][$requestId] ?? '';
    if ($existingPid !== '') {
        [$paymentInfo, $httpCode, $requestError] = yookassa_request('GET', '/payments/' . rawurlencode($existingPid));
        if (
            $requestError === null
            && $httpCode < 400
            && is_array($paymentInfo)
            && ($paymentInfo['status'] ?? '') === 'pending'
            && !empty($paymentInfo['confirmation']['confirmation_url'])
        ) {
            header('Location: ' . $paymentInfo['confirmation']['confirmation_url']);
            exit;
        }
    } else {
        $result = yookassa_create_payment_for_request($pdo, $request, $userId, $requestId);
        if (!empty($result['ok']) && !empty($result['url'])) {
            header('Location: ' . $result['url']);
            exit;
        }
        if (!empty($result['error'])) {
            $error = $result['error'];
        }
    }
}

if (isset($_GET['check']) && $_GET['check'] === '1') {
    $paymentId = $_SESSION['yookassa_payments'][$requestId] ?? '';
    if ($paymentId === '') {
        $error = 'Не найден идентификатор платежа. Запустите оплату повторно.';
    } else {
        [$paymentInfo, $httpCode, $requestError] = yookassa_request('GET', '/payments/' . rawurlencode($paymentId));
        if ($requestError !== null || $httpCode >= 400 || !isset($paymentInfo['status'])) {
            $error = 'Не удалось проверить статус оплаты в ЮKassa.';
        } else {
            $status = (string) $paymentInfo['status'];
            if ($status === 'succeeded') {
                $stmt = $pdo->prepare('UPDATE requests SET is_paid = 1, status = ? WHERE id = ?');
                $stmt->execute(['Оплачено', $requestId]);
                unset($_SESSION['yookassa_payments'][$requestId]);
                if ((string) $request['service'] === 'calculator') {
                    $stmt = $pdo->prepare('SELECT created_at FROM requests WHERE id = ? AND user_id = ? LIMIT 1');
                    $stmt->execute([$requestId, $userId]);
                    $reqCreated = $stmt->fetchColumn();
                    if ($reqCreated !== false && $reqCreated !== null) {
                        $stmt = $pdo->prepare('SELECT id FROM appeals WHERE user_id = ? AND created_at >= ? ORDER BY id ASC LIMIT 1');
                        $stmt->execute([$userId, $reqCreated]);
                        $appealAfterPay = $stmt->fetchColumn();
                        if ($appealAfterPay) {
                            header('Location: cabinet_appeal.php?id=' . (int) $appealAfterPay);
                            exit;
                        }
                    }
                    header('Location: cabinet.php?paid=1#cabinet-appeal');
                    exit;
                }
                header('Location: requests.php');
                exit;
            }
            if ($status === 'canceled') {
                $stmt = $pdo->prepare('UPDATE requests SET status = ? WHERE id = ? AND is_paid = 0');
                $stmt->execute(['Оплата отменена', $requestId]);
                $error = 'Платеж отменен.';
            } else {
                $success = 'Платеж еще не завершен. Проверьте статус повторно.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_payment'])) {
    $result = yookassa_create_payment_for_request($pdo, $request, $userId, $requestId);
    if (!empty($result['ok']) && !empty($result['url'])) {
        header('Location: ' . $result['url']);
        exit;
    }
    if (!empty($result['error'])) {
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Оплата через ЮKassa</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body class="layout-auth">
    <div class="auth-page">
        <div class="auth-card">
            <h1>Оплата обращения</h1>
            <p>Обращение №<?php echo (int) $request['id']; ?>, услуга: <?php echo htmlspecialchars($request['service'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Сумма: <?php echo $request['sum'] > 0 ? number_format((int) $request['sum'], 0, ',', ' ') . ' ₽' : 'будет определена дополнительно'; ?></p>
            <?php if ($error): ?>
                <div class="auth-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php elseif ($success): ?>
                <div class="auth-error" style="background:#dcfce7; color:#166534;"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ((int) $request['is_paid'] === 1): ?>
                <div class="auth-error" style="background:#dcfce7; color:#166534;">Заявка уже оплачена.</div>
            <?php else: ?>
                <form method="post">
                    <input type="hidden" name="create_payment" value="1" />
                    <button type="submit" class="btn btn--primary" style="width: 100%; margin-top: 8px;">
                        Перейти к оплате ЮKassa
                    </button>
                </form>
                <p class="auth-note" style="margin-top:10px;">
                    После возврата на сайт статус оплаты будет проверен автоматически.
                </p>
            <?php endif; ?>
            <p class="auth-note">
                <a href="requests.php">Вернуться к списку обращений</a>
            </p>
        </div>
    </div>

    <?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>

