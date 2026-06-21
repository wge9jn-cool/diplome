<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch() ?: null;

$stmt = $pdo->prepare('SELECT id, service, sum, status, payment_method, is_paid, created_at FROM requests WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$userId]);
$requests = $stmt->fetchAll();

function service_title($code)
{
    $map = [
        'consult' => 'Консультация',
        'expertise' => 'Экспертиза товара',
        'documents' => 'Составление документов',
        'court' => 'Представительство в суде',
        'calculator' => 'Калькулятор (заказ)',
        'other' => 'Другая услуга',
    ];
    return $map[$code] ?? 'Услуга';
}

function payment_title($code)
{
    if ($code === 'later') {
        return 'Оплата позже';
    }
    return 'Онлайн через ЮKassa';
}
?>
<!DOCTYPE html>
<html lang="ру">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Мои обращения — Курганский областной союз потребителей</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <?php
    $headerCabinetSlimNav = true;
    require __DIR__ . '/includes/header.php';
    unset($headerCabinetSlimNav);
    ?>

    <main class="section section--light">
        <div class="container">
            <div class="section__head">
                <h2>Мои обращения</h2>
                <p>Здесь отображаются обращения, отправленные через сайт.</p>
            </div>

            <?php if (!$requests): ?>
                <p>Вы ещё не отправляли обращений. <a href="index.php#request">Оставить первое обращение</a></p>
            <?php else: ?>
                <div class="requests-table">
                    <div class="requests-row requests-row--head">
                        <div>№</div>
                        <div>Услуга</div>
                        <div>Сумма, ₽</div>
                        <div>Статус</div>
                        <div>Оплата</div>
                        <div>Создано</div>
                        <div></div>
                    </div>
                    <?php
                    $latestRequestId = $requests ? (int) $requests[0]['id'] : 0;
                    foreach ($requests as $r):
                        $rid = (int) $r['id'];
                        $orderDoneForAppeal = (int) $r['is_paid'] === 1 || ($r['payment_method'] ?? '') === 'later';
                        $showAppealLink = $rid === $latestRequestId
                            && $r['service'] === 'calculator'
                            && $orderDoneForAppeal;
                        ?>
                        <div class="requests-row">
                            <div>#<?php echo $rid; ?></div>
                            <div><?php echo htmlspecialchars(service_title($r['service']), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><?php echo $r['sum'] > 0 ? number_format((int) $r['sum'], 0, ',', ' ') : '—'; ?></div>
                            <div><?php echo htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div>
                                <?php if ((int) $r['is_paid'] === 1): ?>
                                    Оплачено
                                <?php else: ?>
                                    <?php echo htmlspecialchars(payment_title($r['payment_method']), ENT_QUOTES, 'UTF-8'); ?>
                                <?php endif; ?>
                            </div>
                            <div><?php echo htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div>
                                <?php if ((int) $r['is_paid'] === 0 && $r['payment_method'] === 'yookassa'): ?>
                                    <a href="pay.php?request_id=<?php echo $rid; ?>" class="btn btn--link">Оплатить</a>
                                <?php elseif ($showAppealLink): ?>
                                    <a href="cabinet.php" class="btn btn--link">Обращение</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php require __DIR__ . '/includes/footer.php'; ?>

    <script src="script.js"></script>
</body>
</html>

