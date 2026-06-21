<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/news_date_ru.php';

admin_ensure_role($pdo);
admin_require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        if ($slug === '' && $title !== '') {
            $slug = preg_replace('~[^a-z0-9]+~i', '-', $title);
        }
        if ($title !== '' && $body !== '' && $slug !== '') {
            $stmt = $pdo->prepare('INSERT INTO news (title, slug, body, published_at) VALUES (?, ?, ?, NOW())');
            $stmt->execute([$title, $slug, $body]);
        }
    } elseif ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        if ($id > 0 && $title !== '' && $body !== '') {
            $stmt = $pdo->prepare('UPDATE news SET title = ?, body = ? WHERE id = ?');
            $stmt->execute([$title, $body, $id]);
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM news WHERE id = ?');
            $stmt->execute([$id]);
        }
    }
    header('Location: news.php');
    exit;
}

$stmt = $pdo->query('SELECT id, title, body, published_at FROM news ORDER BY published_at DESC, id DESC');
$news = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Новости — Админпанель КОСП</title>
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
                <h2>Новости и акции</h2>
            </div>

            <form method="post" style="margin-bottom:24px;">
                <input type="hidden" name="action" value="create" />
                <div class="field">
                    <label for="title_new">Заголовок</label>
                    <input type="text" id="title_new" name="title" required />
                </div>
                <div class="field">
                    <label for="slug_new">ЧПУ (опционально)</label>
                    <input type="text" id="slug_new" name="slug" placeholder="novost-1" />
                </div>
                <div class="field">
                    <label for="body_new">Текст новости</label>
                    <textarea id="body_new" name="body" rows="4" required></textarea>
                </div>
                <div class="btn-row">
                    <button type="submit" class="btn btn--primary">Добавить новость</button>
                </div>
            </form>

            <?php if (!$news): ?>
                <p>Новости ещё не добавлены.</p>
            <?php else: ?>
                <?php foreach ($news as $n): ?>
                    <form method="post" style="margin-bottom:16px; border:1px solid #e5e7eb; border-radius:12px; padding:12px;">
                        <input type="hidden" name="id" value="<?php echo (int) $n['id']; ?>" />
                        <div class="field">
                            <label>Заголовок</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8'); ?>" required />
                        </div>
                        <div class="field">
                            <label>Текст</label>
                            <textarea name="body" rows="4" required><?php echo htmlspecialchars($n['body'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <p class="request__hint">
                            Опубликовано: <?php echo htmlspecialchars(news_published_label_ru((string) ($n['published_at'] ?? ''), true), ENT_QUOTES, 'UTF-8'); ?>
                        </p>
                        <div class="btn-row">
                            <button type="submit" name="action" value="update" class="btn btn--secondary">Сохранить</button>
                            <button type="submit" name="action" value="delete" class="btn btn--secondary" onclick="return confirm('Удалить новость?');">
                                Удалить
                            </button>
                        </div>
                    </form>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php $footerPrefix = '../'; require __DIR__ . '/../includes/footer.php'; ?>
    <?php require __DIR__ . '/includes/scripts.php'; ?>
</body>
</html>

