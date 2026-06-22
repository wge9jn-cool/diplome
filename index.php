<?php
session_start();
require_once __DIR__ . '/db.php';

$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Курганский областной союз потребителей</title>
    <meta name="description" content="Автоматизированный сайт Курганского областного союза потребителей. Онлайн-обращение граждан, расчёт ориентировочной стоимости помощи, описание услуг и контакты." />
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <?php
    $headerOnHomePage = true;
    $headerActive = 'home';
    require __DIR__ . '/includes/header.php';
    ?>

    <main>
        <section class="hero" id="top">
            <div class="container hero__inner">
                <div class="hero__content">
                    <h1 class="hero__title">
                        Поможем защитить<br />права потребителей
                    </h1>
                    <p class="hero__subtitle">
                        Консультируем граждан и организации, помогаем подготовить документы и отстаиваем интересы
                        в суде по вопросам защиты прав потребителей.
                    </p>
                    <div class="hero__actions">
                        <button class="btn btn--hero-primary" data-scroll-to="#calculator">
                            Нужна консультация
                        </button>
                        <button class="btn btn--hero-secondary" data-scroll-to="#calculator">
                            Рассчитать стоимость помощи
                        </button>
                    </div>
                </div>
                <div class="hero__side">
                    <div class="hero__side-panel">
                        <p class="hero__side-kicker">От консультации до суда</p>
                        <p class="hero__side-copy">
                            Разберём ситуацию, подготовим претензии и иски, при необходимости организуем экспертизу
                            и представим ваши интересы в суде общей юрисдикции.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <div class="hero-tags">
            <div class="hero-tags__track">
                <div class="hero-tags__inner">
                    <span>Услуги союза:</span>
                    <span>Консультации</span>
                    <span>Экспертиза товаров</span>
                    <span>Претензии и иски</span>
                    <span>Представительство в суде</span>
                    <span>Защита прав потребителей</span>
                </div>
                <div class="hero-tags__inner">
                    <span>Услуги союза:</span>
                    <span>Консультации</span>
                    <span>Экспертиза товаров</span>
                    <span>Претензии и иски</span>
                    <span>Представительство в суде</span>
                    <span>Защита прав потребителей</span>
                </div>
            </div>
        </div>

        <section class="features">
            <div class="container features__inner">

                <div class="feature-item">
                    <div class="feature-item__icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                            <line x1="8" y1="2" x2="16" y2="2"/>
                        </svg>
                    </div>
                    <p class="feature-item__label">Оплата по факту</p>
                    <p class="feature-item__text">Платите только за нужные услуги, без найма отдельного штатного специалиста.</p>
                </div>

                <div class="feature-item">
                    <div class="feature-item__icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            <polyline points="9 12 11 14 15 10"/>
                        </svg>
                    </div>
                    <p class="feature-item__label">Юридическая защита</p>
                    <p class="feature-item__text">Снижайте риски нарушений закона, доверяя работу профильной организации.</p>
                </div>

                <div class="feature-item">
                    <div class="feature-item__icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="9" y1="13" x2="15" y2="13"/>
                            <line x1="9" y1="17" x2="13" y2="17"/>
                        </svg>
                    </div>
                    <p class="feature-item__label">Документы за вас</p>
                    <p class="feature-item__text">Подготовку претензий, исков и сопровождение в суде мы берём на себя.</p>
                </div>

                <div class="feature-item">
                    <div class="feature-item__icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                            <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/>
                        </svg>
                    </div>
                    <p class="feature-item__label">До 5 рабочих дней</p>
                    <p class="feature-item__text">Большинство типовых обращений обрабатывается в срок до 5 рабочих дней.</p>
                </div>

            </div>
        </section>

        <section class="section section--light" id="services">
            <div class="container">
                <div class="section__head">
                    <h2>Услуги Курганского областного союза потребителей</h2>
                    <p>
                        Помощь оказывается как гражданам, так и организациям по вопросам защиты прав потребителей.
                    </p>
                </div>

                <div class="cards cards--3">
                    <article class="card">
                        <h3>Консультирование</h3>
                        <p>
                            Консультирование по вопросам соблюдения действующего законодательства в сфере защиты прав
                            потребителей.
                        </p>
                        <ul class="card__list">
                            <li>Разъяснение прав потребителей</li>
                            <li>Оценка ситуации и возможных требований</li>
                            <li>Рекомендации по досудебному урегулированию</li>
                            <li>Рекомендации по сбору документов и доказательств</li>
                        </ul>
                        <div class="card__bottom">
                            <div class="card__price">от 0 ₽ за первичное обращение</div>
                        </div>
                    </article>

                    <article class="card">
                        <h3>Экспертиза товаров</h3>
                        <p>
                            Консультирование по вопросам экспертизы потребительских товаров: обуви, мебели, швейных,
                            кожаных и меховых изделий.
                        </p>
                        <ul class="card__list">
                            <li>Рекомендации по проведению экспертиз</li>
                            <li>Помощь в оформлении заключений</li>
                            <li>Анализ результатов экспертиз</li>
                            <li>Подготовка к досудебной и судебной защите</li>
                        </ul>
                        <div class="card__bottom">
                            <div class="card__price">от 1 000 ₽</div>
                        </div>
                    </article>

                    <article class="card">
                        <h3>Документы и представительство</h3>
                        <p>
                            Составление претензий, исковых заявлений и иных документов, а также представительство в
                            судах общей юрисдикции.
                        </p>
                        <ul class="card__list">
                            <li>Подготовка претензий продавцам и исполнителям</li>
                            <li>Составление исковых заявлений</li>
                            <li>Представительство в суде</li>
                            <li>Сопровождение на всех стадиях рассмотрения дела</li>
                        </ul>
                        <div class="card__bottom">
                            <div class="card__price">по договорённости</div>
                        </div>
                    </article>
                </div>

                <div class="services-cta">
                    <button class="btn btn--services-main" data-scroll-to="#calculator">
                        Оставить обращение
                    </button>
                    <p class="services-cta__hint">Бесплатная первичная консультация · Онлайн · Без очередей</p>
                </div>
            </div>
        </section>

        <?php require __DIR__ . '/includes/calc_section.php'; ?>

        <section class="section section--light" id="faq">
            <div class="container">
                <div class="section__head">
                    <h2>Часто задаваемые вопросы</h2>
                </div>

                <div class="faq faq--grid" id="faqList">
                    <div class="faq__col">
                        <div class="faq__item">
                            <button class="faq__question">
                                Какие услуги оказывает Курганский областной союз потребителей?
                            </button>
                            <div class="faq__answer">
                                <p>
                                    Союз консультирует по вопросам защиты прав потребителей, помогает в организации
                                    экспертиз товаров, готовит претензии и иски, а также представляет интересы граждан
                                    и организаций в судах общей юрисдикции.
                                </p>
                            </div>
                        </div>

                        <div class="faq__item">
                            <button class="faq__question">
                                Нужно ли лично приходить в офис для обращения?
                            </button>
                            <div class="faq__answer">
                                <p>
                                    Первичное обращение можно направить через сайт или по электронной почте. Для отдельных
                                    видов услуг (например, экспертизы) может потребоваться личное посещение или передача
                                    товара.
                                </p>
                            </div>
                        </div>

                        <div class="faq__item">
                            <button class="faq__question">
                                Сколько стоит обращение в союз потребителей?
                            </button>
                            <div class="faq__answer">
                                <p>
                                    Первичное информирование может быть бесплатным, далее стоимость зависит от сложности
                                    ситуации, необходимости экспертизы и представительства в суде. Калькулятор на сайте
                                    позволяет получить ориентировочную оценку.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="faq__col">
                        <div class="faq__item">
                            <button class="faq__question">
                                Как подать обращение через сайт?
                            </button>
                            <div class="faq__answer">
                                <p>
                                    Зарегистрируйтесь и откройте личный кабинет: в калькуляторе выберите услуги и оплатите заказ онлайн через ЮKassa.
                                    После успешной оплаты на той же странице откроется форма обращения: выбранные в заказе услуги подставятся автоматически, вам останется подробно описать ситуацию и при необходимости прикрепить файл.
                                    Статус обращения отображается в списке ниже на странице кабинета.
                                </p>
                            </div>
                        </div>

                        <div class="faq__item">
                            <button class="faq__question">
                                Как отслеживать статус своего обращения?
                            </button>
                            <div class="faq__answer">
                                <p>
                                    В личном кабинете отображается текущий статус каждого обращения: «Принято»,
                                    «В работе», «Ответ сформирован» или «Завершено». Специалист также может оставить
                                    комментарий и документ для скачивания.
                                </p>
                            </div>
                        </div>

                        <div class="faq__item">
                            <button class="faq__question">
                                Какие документы нужны для обращения?
                            </button>
                            <div class="faq__answer">
                                <p>
                                    Как правило, достаточно описания ситуации. Если есть — прикрепите чек, договор,
                                    переписку с продавцом или фото товара. Специалист запросит дополнительные материалы
                                    при необходимости.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="faq-cta">
                    <div class="faq-cta__text">
                        <p class="faq-cta__heading">Остались вопросы?</p>
                        <p class="faq-cta__sub">Позвоните нам — ответим бесплатно</p>
                    </div>
                    <a href="tel:+73522241720" class="faq-cta__phone">+7 (3522) 241-720</a>
                    <a href="tel:+73522241720" class="btn btn--primary faq-cta__btn">Позвонить</a>
                </div>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/includes/footer.php'; ?>

    <?php require __DIR__ . '/includes/scripts_public.php'; ?>
</body>
</html>

