<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

admin_ensure_role($pdo);
admin_require_admin();

// обработка форм
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sort = (int) ($_POST['sort_order'] ?? 0);
        if ($title !== '' && $description !== '') {
            $stmt = $pdo->prepare('INSERT INTO services (title, description, sort_order) VALUES (?, ?, ?)');
            $stmt->execute([$title, $description, $sort]);
        }
    } elseif ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sort = (int) ($_POST['sort_order'] ?? 0);
        if ($id > 0 && $title !== '' && $description !== '') {
            $stmt = $pdo->prepare('UPDATE services SET title = ?, description = ?, sort_order = ? WHERE id = ?');
            $stmt->execute([$title, $description, $sort, $id]);
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM services WHERE id = ?');
            $stmt->execute([$id]);
        }
    }
    header('Location: services.php');
    exit;
}

$stmt = $pdo->query('SELECT id, title, description, sort_order FROM services ORDER BY sort_order ASC, id ASC');
$services = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Услуги — Админпанель КОСП</title>
    <link rel="stylesheet" href="../styles.css" />
</head>
<body>
    <header class="header">
        <div class="container header__inner">
            <?php
            $logoBase = '../';
            $logoHref = 'index.php';
            $logoAdmin = true;
            require __DIR__ . '/../includes/logo.php';
            ?>
            <nav class="nav">
                <a href="index.php" class="nav__link">Статистика</a>
                <a href="appeals.php" class="nav__link">Обращения</a>
                <a href="users.php" class="nav__link">Пользователи</a>
                <a href="services.php" class="nav__link">Услуги</a>
                <a href="news.php" class="nav__link">Новости</a>
                <a href="settings.php" class="nav__link">Настройки</a>
                <a href="logout.php" class="nav__link nav__link--outlined">Выйти</a>
            </nav>
        </div>
    </header>

    <main class="section section--light">
        <div class="container">
            <div class="section__head">
                <h2>Услуги</h2>
            </div>

            <form method="post" style="margin-bottom:24px;">
                <input type="hidden" name="action" value="create" />
                <div class="field">
                    <label for="title_new">Название услуги</label>
                    <input type="text" id="title_new" name="title" required />
                </div>
                <div class="field">
                    <label for="description_new">Описание</label>
                    <textarea id="description_new" name="description" rows="3" required></textarea>
                </div>
                <div class="field">
                    <label for="sort_new">Порядок вывода</label>
                    <input type="number" id="sort_new" name="sort_order" value="0" />
                </div>
                <div class="btn-row">
                    <button type="submit" class="btn btn--primary">Добавить услугу</button>
                </div>
            </form>

            <?php if (!$services): ?>
                <p>Услуги пока не добавлены.</p>
            <?php else: ?>
                <?php foreach ($services as $s): ?>
                    <form method="post" style="margin-bottom:16px; border:1px solid #e5e7eb; border-radius:12px; padding:12px;">
                        <input type="hidden" name="id" value="<?php echo (int) $s['id']; ?>" />
                        <div class="field">
                            <label>Название</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($s['title'], ENT_QUOTES, 'UTF-8'); ?>" required />
                        </div>
                        <div class="field">
                            <label>Описание</label>
                            <textarea name="description" rows="3" required><?php echo htmlspecialchars($s['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <div class="field">
                            <label>Порядок вывода</label>
                            <input type="number" name="sort_order" value="<?php echo (int) $s['sort_order']; ?>" />
                        </div>
                        <div class="btn-row">
                            <button type="submit" name="action" value="update" class="btn btn--secondary">Сохранить</button>
                            <button type="submit" name="action" value="delete" class="btn btn--secondary" onclick="return confirm('Удалить услугу?');">
                                Удалить
                            </button>
                        </div>
                    </form>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>

