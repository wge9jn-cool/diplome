<?php
declare(strict_types=1);
/** Ожидается: $logoBase, $logoHref, $logoAdmin (как на страницах admin/) */
?>
<header class="header">
    <div class="container header__inner">
        <div class="header__left">
            <?php require __DIR__ . '/../../includes/logo.php'; ?>
        </div>
        <div class="header__center">
            <?php require __DIR__ . '/nav.php'; ?>
        </div>
        <div class="header__right">
            <button class="header__burger" type="button" aria-label="Меню" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
    <button type="button" class="nav-backdrop" aria-label="Закрыть меню" hidden></button>
</header>
