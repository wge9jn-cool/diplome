<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

admin_ensure_role($pdo);
admin_require_staff();

$statusFilter = $_GET['status'] ?? '';
$topicFilter = $_GET['topic'] ?? '';
$difficultyFilter = $_GET['difficulty'] ?? '';
$userFilter = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

$where = [];
$params = [];
if ($statusFilter !== '') {
    $where[] = "last_status.status = :status";
    $params[':status'] = $statusFilter;
}
if ($topicFilter !== '') {
    $where[] = "a.topic = :topic";
    $params[':topic'] = $topicFilter;
}
if ($difficultyFilter !== '') {
    $where[] = "a.difficulty = :difficulty";
    $params[':difficulty'] = $difficultyFilter;
}
if ($userFilter > 0) {
    $where[] = "a.user_id = :user_id";
    $params[':user_id'] = $userFilter;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
SELECT a.id, a.topic, a.difficulty, a.created_at, u.name, u.phone,
       last_status.status AS last_status
FROM appeals a
JOIN users u ON u.id = a.user_id
LEFT JOIN (
    SELECT s1.appeal_id, s1.status
    FROM appeal_statuses s1
    JOIN (
        SELECT appeal_id, MAX(created_at) AS max_created_at
        FROM appeal_statuses
        GROUP BY appeal_id
    ) s2 ON s1.appeal_id = s2.appeal_id AND s1.created_at = s2.max_created_at
) last_status ON last_status.appeal_id = a.id
$whereSql
ORDER BY a.created_at DESC, a.id DESC
";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute();
$allAppeals = $stmt->fetchAll();

$appealsActive = [];
$appealsArchived = [];
foreach ($allAppeals as $row) {
    if (in_array($row['last_status'], ['completed', 'rejected'], true)) {
        $appealsArchived[] = $row;
    } else {
        $appealsActive[] = $row;
    }
}

function topic_title_admin(string $code): string {
    $map = [
        'bad_product' => 'Некачественный товар',
        'delay' => 'Нарушение сроков',
        'warranty_refusal' => 'Отказ в гарантийном ремонте',
        'housing' => 'Услуги ЖКХ',
        'other' => 'Другое',
    ];
    return $map[$code] ?? 'Обращение';
}

function status_title_admin(string $code = null): string {
    if ($code === null) return '—';
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
    <title>Обращения — Админпанель КОСП</title>
    <link rel="stylesheet" href="../styles.css" />
</head>
<body>
    <?php
    $logoBase = '../';
    $logoHref = admin_home_href();
    $logoAdmin = true;
    require __DIR__ . '/includes/header_bar.php';
    ?>

    <main class="section section--light">
        <div class="container">
            <div class="section__head">
                <h2>Обращения</h2>
            </div>

            <form method="get" class="admin-filters-form" style="margin-bottom:16px; display:flex; gap:8px; flex-wrap:wrap;">
                <div class="field" style="max-width:200px;">
                    <label for="status">Статус</label>
                    <select id="status" name="status">
                        <option value="">Все</option>
                        <option value="accepted" <?php if ($statusFilter==='accepted') echo 'selected'; ?>>Принято</option>
                        <option value="processing" <?php if ($statusFilter==='processing') echo 'selected'; ?>>В работе</option>
                        <option value="answered" <?php if ($statusFilter==='answered') echo 'selected'; ?>>Ответ сформирован</option>
                        <option value="completed" <?php if ($statusFilter==='completed') echo 'selected'; ?>>Завершено</option>
                        <option value="rejected" <?php if ($statusFilter==='rejected') echo 'selected'; ?>>Отклонено</option>
                    </select>
                </div>
                <div class="field" style="max-width:260px;">
                    <label for="topic">Тема</label>
                    <select id="topic" name="topic">
                        <option value="">Все</option>
                        <option value="bad_product" <?php if ($topicFilter==='bad_product') echo 'selected'; ?>>Некачественный товар</option>
                        <option value="delay" <?php if ($topicFilter==='delay') echo 'selected'; ?>>Нарушение сроков</option>
                        <option value="warranty_refusal" <?php if ($topicFilter==='warranty_refusal') echo 'selected'; ?>>Отказ в гарантийном ремонте</option>
                        <option value="housing" <?php if ($topicFilter==='housing') echo 'selected'; ?>>Услуги ЖКХ</option>
                        <option value="other" <?php if ($topicFilter==='other') echo 'selected'; ?>>Другое</option>
                    </select>
                </div>
                <div class="field" style="max-width:200px;">
                    <label for="difficulty">Сложность</label>
                    <select id="difficulty" name="difficulty">
                        <option value="">Все</option>
                        <option value="easy" <?php if ($difficultyFilter==='easy') echo 'selected'; ?>>Лёгкий</option>
                        <option value="medium" <?php if ($difficultyFilter==='medium') echo 'selected'; ?>>Средний</option>
                        <option value="hard" <?php if ($difficultyFilter==='hard') echo 'selected'; ?>>Сложный</option>
                    </select>
                </div>
                <div class="field" style="align-self:flex-end;">
                    <button type="submit" class="btn btn--secondary">Применить</button>
                </div>
            </form>

            <div class="section__head" style="margin-bottom:12px;">
                <h3>Текущие обращения</h3>
            </div>

            <?php if (!$appealsActive): ?>
                <p>Нет активных обращений по выбранным фильтрам.</p>
            <?php else: ?>
                <div class="requests-table requests-table--admin-appeals">
                    <div class="requests-row requests-row--head">
                        <div>№</div>
                        <div>Тема</div>
                        <div>Сложность</div>
                        <div>Пользователь</div>
                        <div>Телефон</div>
                        <div>Статус</div>
                        <div>Создано</div>
                        <div></div>
                    </div>
                    <?php foreach ($appealsActive as $a): ?>
                        <div class="requests-row">
                            <div>#<?php echo (int) $a['id']; ?></div>
                            <div><?php echo htmlspecialchars(topic_title_admin($a['topic']), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><?php echo htmlspecialchars(appeal_difficulty_label($a['difficulty'] ?? null), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><?php echo htmlspecialchars($a['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><?php echo htmlspecialchars($a['phone'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><?php echo htmlspecialchars(status_title_admin($a['last_status']), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><?php echo htmlspecialchars($a['created_at'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div>
                                <a href="appeal_edit.php?id=<?php echo (int) $a['id']; ?>" class="btn btn--link">Открыть</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="section__head" style="margin:24px 0 12px;">
                <h3>Архив обращений</h3>
            </div>

            <?php if (!$appealsArchived): ?>
                <p>В архиве нет обращений (завершённые и отклонённые обращения будут отображаться здесь).</p>
            <?php else: ?>
                <div class="requests-table requests-table--admin-appeals">
                    <div class="requests-row requests-row--head">
                        <div>№</div>
                        <div>Тема</div>
                        <div>Сложность</div>
                        <div>Пользователь</div>
                        <div>Телефон</div>
                        <div>Статус</div>
                        <div>Создано</div>
                        <div></div>
                    </div>
                    <?php foreach ($appealsArchived as $a): ?>
                        <div class="requests-row">
                            <div>#<?php echo (int) $a['id']; ?></div>
                            <div><?php echo htmlspecialchars(topic_title_admin($a['topic']), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><?php echo htmlspecialchars(appeal_difficulty_label($a['difficulty'] ?? null), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><?php echo htmlspecialchars($a['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><?php echo htmlspecialchars($a['phone'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><?php echo htmlspecialchars(status_title_admin($a['last_status']), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><?php echo htmlspecialchars($a['created_at'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div>
                                <a href="appeal_edit.php?id=<?php echo (int) $a['id']; ?>" class="btn btn--link">Открыть</a>
                            </div>
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

