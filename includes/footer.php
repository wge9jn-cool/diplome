<?php
/**
 * Подвал сайта (текст, без логотипа).
 * $footerPrefix — префикс путей ('' с корня сайта, '../' из вложенных папок при необходимости).
 */
$footerPrefix = $footerPrefix ?? '';
$p = $footerPrefix;
$y = (int) date('Y');
?>
<footer class="footer">
    <div class="container footer__inner">
        <div class="footer__brand">
            <p class="footer__title">Курганский союз потребителей</p>
            <p class="footer__muted">
                Консультации и юридическая помощь по защите прав потребителей в Курганской области.
            </p>
        </div>
        <div class="footer__aside">
            <nav class="footer__nav" aria-label="Нижнее меню">
                <a href="<?php echo htmlspecialchars($p . 'index.php', ENT_QUOTES, 'UTF-8'); ?>">Главная</a>
                <a href="<?php echo htmlspecialchars($p . 'services.php', ENT_QUOTES, 'UTF-8'); ?>">Услуги</a>
                <a href="<?php echo htmlspecialchars($p . 'news.php', ENT_QUOTES, 'UTF-8'); ?>">Новости</a>
                <a href="<?php echo htmlspecialchars($p . 'contacts.php', ENT_QUOTES, 'UTF-8'); ?>">Контакты</a>
            </nav>
            <span class="footer__copy">© <?php echo $y; ?></span>
        </div>
    </div>
</footer>
