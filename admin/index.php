<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

admin_ensure_role($pdo);
admin_require_admin();

$adminId = admin_staff_id();

// статистика
$totalAppeals = (int) $pdo->query('SELECT COUNT(*) AS c FROM appeals')->fetch()['c'];
$totalUsers = (int) $pdo->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
$monthAppeals = (int) $pdo->query('SELECT COUNT(*) AS c FROM appeals WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())')->fetch()['c'];

$statusStatsStmt = $pdo->query("
    SELECT s.status, COUNT(*) AS c
    FROM appeal_statuses s
    JOIN (
        SELECT appeal_id, MAX(created_at) AS max_created_at
        FROM appeal_statuses
        GROUP BY appeal_id
    ) last ON last.appeal_id = s.appeal_id AND last.max_created_at = s.created_at
    GROUP BY s.status
");
$statusStats = $statusStatsStmt->fetchAll();

function status_title_admin(string $code): string {
    $map = [
        'accepted' => 'Принято',
        'processing' => 'В работе',
        'answered' => 'Ответ сформирован',
        'completed' => 'Завершено',
        'rejected' => 'Отклонено',
    ];
    return $map[$code] ?? $code;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Административная панель — КОСП</title>
    <link rel="stylesheet" href="../styles.css" />
</head>
<body>
    <?php
    $logoBase = '../';
    $logoHref = 'index.php';
    $logoAdmin = true;
    require __DIR__ . '/includes/header_bar.php';
    ?>

    <main class="section section--light">
        <div class="container">
            <div class="section__head">
                <h2>Статистика обращений</h2>
            </div>

            <div class="metrics-row metrics-row--admin">
                <div class="metric">
                    <div class="metric__value"><?php echo $totalAppeals; ?></div>
                    <div class="metric__label">Всего обращений</div>
                </div>
                <div class="metric">
                    <div class="metric__value"><?php echo $monthAppeals; ?></div>
                    <div class="metric__label">Обращений в текущем месяце</div>
                </div>
                <div class="metric">
                    <div class="metric__value"><?php echo $totalUsers; ?></div>
                    <div class="metric__label">Зарегистрированных пользователей</div>
                </div>
            </div>

            <div class="section__head" style="margin-top:24px;">
                <h2>Распределение по статусам (текущий статус обращений)</h2>
            </div>

            <?php if (!$statusStats): ?>
                <p>Пока нет данных по статусам.</p>
            <?php else: ?>
                <div class="requests-table">
                    <div class="requests-row requests-row--head">
                        <div>Статус</div>
                        <div>Количество</div>
                    </div>
                    <?php foreach ($statusStats as $row): ?>
                        <div class="requests-row">
                            <div><?php echo htmlspecialchars(status_title_admin($row['status']), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><?php echo (int) $row['c']; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php $footerPrefix = '../'; require __DIR__ . '/../includes/footer.php'; ?>
    <?php require __DIR__ . '/includes/scripts.php'; ?>
</body>
</html>

