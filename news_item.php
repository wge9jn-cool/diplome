<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/news_date_ru.php';

$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT id, name FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT id, title, body, image, published_at FROM news WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$news = $stmt->fetch();

if (!$news) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $news ? htmlspecialchars($news['title'], ENT_QUOTES, 'UTF-8') : 'Новость не найдена'; ?> — Курганский областной союз потребителей</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <header class="header">
        <div class="container header__inner">
            <div class="header__left">
                <?php
                $logoHref = 'index.php';
                require __DIR__ . '/includes/logo.php';
                ?>
            </div>
            <div class="header__center">
                <nav class="nav">
                    <a href="index.php" class="nav__link">Главная</a>
                    <a href="services.php" class="nav__link">Услуги</a>
                    <a href="news.php" class="nav__link">Новости</a>
                    <a href="contacts.php" class="nav__link">Контакты</a>
                </nav>
            </div>
            <div class="header__right">
                <?php if ($user): ?>
                    <a href="cabinet.php" class="header__btn-consult">Личный кабинет</a>
                    <a href="logout.php" class="header__btn-login header__btn-login--logout">Выйти</a>
                <?php else: ?>
                    <a href="login.php" class="header__btn-login">Вход / Регистрация</a>
                <?php endif; ?>
                <button class="header__burger" aria-label="Меню">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    <main class="section section--light">
        <div class="container">
            <?php if (!$news): ?>
                <p>Новость не найдена. <a href="news.php">Вернуться к списку новостей</a></p>
            <?php else: ?>
                <article>
                    <h2><?php echo htmlspecialchars($news['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p style="color:#6b7280; font-size:13px; margin-top:4px;">
                        <?php echo htmlspecialchars(news_published_label_ru((string) ($news['published_at'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                    <?php if ($news['image']): ?>
                        <div style="margin:12px 0;">
                            <img src="<?php echo htmlspecialchars($news['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="" />
                        </div>
                    <?php endif; ?>
                    <p style="white-space:pre-line; margin-top:12px;">
                        <?php echo htmlspecialchars($news['body'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                    <p style="margin-top:16px;">
                        <a href="news.php" class="btn btn--ghost">К списку новостей</a>
                    </p>
                </article>
            <?php endif; ?>
        </div>
    </main>

    <?php require __DIR__ . '/includes/footer.php'; ?>

    <script src="script.js"></script>
</body>
</html>

