<?php
/**
 * Общий хедер для публичных страниц.
 * Ожидается: $user — массив пользователя или null (после session_start и db).
 * Опционально:
 *   $headerOnHomePage — true на главной (index.php): ссылка «Главная» с #top, плавный скролл к калькулятору.
 *   $headerActive — 'home' | 'services' | 'news' | 'contacts' подсветка пункта меню.
 *   $headerCabinetSlimNav — true: только «Главная», «Профиль», «Выйти» (страницы из личного кабинета).
 */
$headerOnHomePage = !empty($headerOnHomePage);
$headerCabinetSlimNav = !empty($headerCabinetSlimNav);
$headerActive = $headerActive ?? '';

$homeHref = $headerOnHomePage ? 'index.php#top' : 'index.php';
$consultHref = $headerOnHomePage ? '#calculator' : 'index.php#calculator';
$consultScrollAttr = $headerOnHomePage ? ' data-scroll-to="#calculator"' : '';

$logoHref = $homeHref;

$__hNav = function ($key) use ($headerActive) {
    return $headerActive === $key ? ' nav__link--active' : '';
};
?>
    <header class="header">
        <div class="container header__inner">
            <div class="header__left">
                <?php require __DIR__ . '/logo.php'; ?>
            </div>

            <div class="header__center">
                <?php if ($headerCabinetSlimNav): ?>
                    <nav class="nav" aria-label="Меню личного кабинета">
                        <a href="<?php echo htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8'); ?>" class="nav__link<?php echo $__hNav('home'); ?>">Главная</a>
                        <a href="profile.php" class="nav__link">Профиль</a>
                        <a href="logout.php" class="nav__link nav__link--outlined">Выйти</a>
                    </nav>
                <?php else: ?>
                    <nav class="nav" aria-label="Основное меню">
                        <a href="<?php echo htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8'); ?>" class="nav__link<?php echo $__hNav('home'); ?>">Главная</a>
                        <a href="services.php" class="nav__link<?php echo $__hNav('services'); ?>">Услуги</a>
                        <a href="news.php" class="nav__link<?php echo $__hNav('news'); ?>">Новости</a>
                        <a href="contacts.php" class="nav__link<?php echo $__hNav('contacts'); ?>">Контакты</a>
                        <?php if ($user): ?>
                            <div class="nav__mobile-actions">
                                <a href="cabinet.php#calculator" class="header__btn-consult header__btn-consult--mobile">Личный кабинет</a>
                                <a href="logout.php" class="header__btn-login header__btn-login--logout header__btn-login--mobile">Выйти</a>
                            </div>
                        <?php else: ?>
                            <div class="nav__mobile-actions">
                                <a href="<?php echo htmlspecialchars($consultHref, ENT_QUOTES, 'UTF-8'); ?>" class="header__btn-consult header__btn-consult--mobile"<?php echo $consultScrollAttr; ?>>Бесплатная консультация</a>
                                <a href="login.php" class="header__btn-login header__btn-login--mobile">Вход / Регистрация</a>
                            </div>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </div>

            <div class="header__right">
                <?php if ($user && !$headerCabinetSlimNav): ?>
                    <a href="cabinet.php#calculator" class="header__btn-consult">Личный кабинет</a>
                    <a href="logout.php" class="header__btn-login header__btn-login--logout">Выйти</a>
                <?php elseif (!$user): ?>
                    <a href="<?php echo htmlspecialchars($consultHref, ENT_QUOTES, 'UTF-8'); ?>" class="header__btn-consult"<?php echo $consultScrollAttr; ?>>Бесплатная консультация</a>
                    <a href="login.php" class="header__btn-login">Вход / Регистрация</a>
                <?php endif; ?>
                <button class="header__burger" type="button" aria-label="Меню" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
        <button type="button" class="nav-backdrop" aria-label="Закрыть меню" hidden></button>
    </header>
