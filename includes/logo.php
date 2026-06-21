<?php
/**
 * Логотип сайта. Перед подключением можно задать:
 * - $logoBase — префикс пути к assets/ ('' из корня сайта, '../' из admin/)
 * - $logoHref — ссылка (по умолчанию index.php)
 * - $logoAdmin — true, чтобы показать подпись «Админ»
 * - $logoFooter — true для компактного варианта в подвале
 */
$logoBase = $logoBase ?? '';
$logoHref = $logoHref ?? 'index.php';
$isFooter = !empty($logoFooter);
$isAdmin = !empty($logoAdmin);

$src = $logoBase . 'assets/kosp_logo.png';

$linkClass = 'logo logo--brand';
if ($isFooter) {
    $linkClass .= ' logo--footer';
}
$imgClass = 'logo__image';
if ($isFooter) {
    $imgClass .= ' logo__image--footer';
}
?>
<a href="<?php echo htmlspecialchars($logoHref, ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo htmlspecialchars($linkClass, ENT_QUOTES, 'UTF-8'); ?>">
    <img
        src="<?php echo htmlspecialchars($src, ENT_QUOTES, 'UTF-8'); ?>"
        alt="КОСП — Курганский областной союз потребителей"
        class="<?php echo htmlspecialchars($imgClass, ENT_QUOTES, 'UTF-8'); ?>"
        <?php if (!$isFooter): ?>
        width="260"
        height="92"
        <?php else: ?>
        width="200"
        height="71"
        <?php endif; ?>
        decoding="async"
    />
    <?php if ($isAdmin): ?>
        <span class="logo__admin-mark">Админ</span>
    <?php endif; ?>
</a>
