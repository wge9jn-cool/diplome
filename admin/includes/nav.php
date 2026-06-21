<?php
declare(strict_types=1);
/** Включать из файлов в каталоге admin/ */
require_once __DIR__ . '/../../includes/admin_auth.php';

$isAdminNav = admin_is_admin();
?>
<nav class="nav">
    <?php if ($isAdminNav): ?>
        <a href="index.php" class="nav__link">Статистика</a>
    <?php endif; ?>
    <a href="appeals.php" class="nav__link">Обращения</a>
    <?php if ($isAdminNav): ?>
        <a href="reviews.php" class="nav__link">Отзывы</a>
        <a href="users.php" class="nav__link">Пользователи</a>
        <a href="news.php" class="nav__link">Новости</a>
        <a href="settings.php" class="nav__link">Настройки</a>
    <?php endif; ?>
    <a href="logout.php" class="nav__link nav__link--outlined">Выйти</a>
</nav>
