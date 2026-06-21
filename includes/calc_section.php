<?php
/**
 * Блок калькулятора услуг (та же разметка на главной и в кабинете).
 * Ожидается в области видимости: $user — массив пользователя или null.
 *
 * Опционально:
 *   $calcLayout         — 'page' (по умолчанию): секция + контейнер + заголовок; 'embed' — только сетка калькулятора (без обёртки страницы).
 *   $calcSectionClass   — классы на <section> в режиме page
 *   $calcSectionId      — id секции для якорей в режиме page
 *   $calcTitle          — заголовок блока (только page)
 *   $calcLead           — подзаголовок (только page)
 */
$calcLayout = $calcLayout ?? 'page';
$calcSectionClass = $calcSectionClass ?? 'section';
$calcSectionId = $calcSectionId ?? 'calculator';
$calcTitle = $calcTitle ?? 'Калькулятор стоимости услуг';
$calcLead = $calcLead ?? 'Выберите нужные услуги, добавьте их в корзину и оформите заявку или оплату.';

$__calcEmbed = $calcLayout === 'embed';

if (!$__calcEmbed) {
    ?>
<section class="<?php echo htmlspecialchars($calcSectionClass, ENT_QUOTES, 'UTF-8'); ?>" id="<?php echo htmlspecialchars($calcSectionId, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="container">
        <div class="section__head">
            <h2><?php echo htmlspecialchars($calcTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
            <p><?php echo htmlspecialchars($calcLead, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    <?php
}
?>

        <div class="calc calc--builder calc--compact<?php echo $__calcEmbed ? ' calc--embed' : ''; ?>" id="calcBuilder">
            <aside class="calc__result calc__panel calc__panel--cart">
                <div class="calc__title">Корзина</div>

                <?php if (!$user): ?>
                    <div class="auth-note" style="margin:0 0 10px;">
                        Чтобы оформить заявку или оплату, войдите в аккаунт.
                        <div style="margin-top: 8px;">
                            <a href="login.php" class="btn btn--ghost" style="margin-right: 8px;">Войти</a>
                            <a href="register.php" class="btn btn--primary">Регистрация</a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="calc-cart" id="calcCart"></div>

                <div class="calc__price" id="calcTotal">0 ₽</div>
                <p class="calc__comment" id="calcWarnings"></p>

                <form id="calcCheckout" class="calc-checkout" action="calc_checkout.php" method="post">
                    <input type="hidden" name="items_json" id="calcItemsJson" value="[]" />
                    <input type="hidden" name="total" id="calcTotalInput" value="0" />
                    <input type="hidden" name="action" id="calcAction" value="yookassa" />

                    <div class="field field--compact">
                        <label for="calcSituation">Комментарий к заказу</label>
                        <textarea id="calcSituation" name="comment" rows="2" <?php if (!$user) echo 'disabled'; ?> placeholder="По желанию"></textarea>
                    </div>

                    <div class="field">
                        <label>Способ оплаты</label>
                        <label class="checkbox">
                            <input type="radio" name="payment_method" value="yookassa" checked <?php if (!$user) echo 'disabled'; ?> />
                            <span>Оплатить онлайн через ЮKassa</span>
                        </label>
                        <label class="checkbox">
                            <input type="radio" name="payment_method" value="later" <?php if (!$user) echo 'disabled'; ?> />
                            <span>Оплатить позже по договорённости</span>
                        </label>
                    </div>

                    <label class="checkbox" style="margin-top:6px;">
                        <input type="checkbox" name="agree" required <?php if (!$user) echo 'disabled'; ?> />
                        <span>Я согласен(на) на обработку <a href="politika-pdn.php" target="_blank" rel="noopener noreferrer">персональных данных</a></span>
                    </label>

                    <div class="btn-row" style="margin-top:12px;">
                        <button type="submit" class="btn btn--primary" id="calcPayBtn" <?php if (!$user) echo 'disabled'; ?>>
                            Оплатить через ЮKassa
                        </button>
                        <button type="button" class="btn btn--secondary" id="calcCallBtn" <?php if (!$user) echo 'disabled'; ?>>
                            Заказать звонок
                        </button>
                    </div>

                    <p class="request__note calc-checkout__note">
                        Итог уточняется специалистом по сложности дела.
                    </p>
                </form>
            </aside>

            <div class="calc__form calc__panel calc__panel--catalog">
                <div class="calc__head-row">
                    <span class="calc__title">Услуги</span>
                    <span class="calc__hint-inline">Откройте раздел — вариант и «В корзину»</span>
                </div>
                <div class="calc__services-scroll">
                    <div class="calc__services" id="calcServices"></div>
                </div>
            </div>
        </div>
<?php
require_once __DIR__ . '/calc_catalog.php';
?>
        <script>window.__CALC_CATALOG = <?php echo json_encode(calc_catalog_for_js(), JSON_UNESCAPED_UNICODE); ?>;</script>
<?php
if (!$__calcEmbed) {
    ?>
    </div>
</section>
    <?php
}
