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

$perPage = 5;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

$sort = isset($_GET['sort']) ? trim((string) $_GET['sort']) : 'new';
if (!in_array($sort, ['new', 'old'], true)) {
    $sort = 'new';
}
$orderBy = $sort === 'old' ? 'published_at ASC, id ASC' : 'published_at DESC, id DESC';

$totalStmt = $pdo->query('SELECT COUNT(*) AS cnt FROM news');
$total = (int) $totalStmt->fetch()['cnt'];

$stmt = $pdo->prepare('SELECT id, title, body, published_at FROM news ORDER BY ' . $orderBy . ' LIMIT :offset, :per_page');
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':per_page', $perPage, PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll();

$pages = $total > 0 ? (int) ceil($total / $perPage) : 1;
$useDemoNews = false;

if ($total === 0) {
    $useDemoNews = true;
    $items = [
        [
            'id' => 0,
            'title' => 'Расширен график консультаций для граждан',
            'body' => 'Союз потребителей увеличил количество консультационных часов в будние дни. Теперь приём ведётся с понедельника по пятницу с 9:00 до 18:00 без перерыва. Запись доступна через сайт и по телефону +7 (3522) 241-720. Специалисты помогут разобраться с любым потребительским вопросом: возврат товара, некачественная услуга, претензии к застройщику или управляющей компании.',
            'published_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
        ],
        [
            'id' => 0,
            'title' => 'Обновлены рекомендации по досудебному урегулированию',
            'body' => 'Подготовлены новые практические рекомендации по составлению претензий в адрес продавцов и исполнителей услуг. В документе учтены последние изменения в Законе о защите прав потребителей и судебная практика 2024–2025 годов. Рекомендации доступны бесплатно на приёме у специалиста или по запросу через форму обращения на сайте.',
            'published_at' => date('Y-m-d H:i:s', strtotime('-6 days')),
        ],
        [
            'id' => 0,
            'title' => 'Проведена выездная консультация по вопросам ЖКХ',
            'body' => 'Специалисты союза провели тематическую встречу для жителей Кургана по вопросам защиты прав потребителей в сфере коммунальных услуг. На мероприятии обсуждались: правомерность начисления платежей, порядок оспаривания действий управляющих компаний, практика возврата переплат. Следующая встреча запланирована на первую неделю апреля.',
            'published_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
        ],
        [
            'id' => 0,
            'title' => 'Топ-5 нарушений прав потребителей в торговле',
            'body' => 'Эксперты союза подготовили обзор наиболее распространённых нарушений, с которыми сталкиваются покупатели в магазинах и интернет-торговле. В список вошли: отказ принять товар ненадлежащего качества, навязывание дополнительных услуг, несоответствие цены на витрине и кассе, нарушение сроков доставки, а также отсутствие книги жалоб. По каждому случаю специалисты дали практические советы по защите своих прав.',
            'published_at' => date('Y-m-d H:i:s', strtotime('-15 days')),
        ],
        [
            'id' => 0,
            'title' => 'Новые правила возврата интернет-покупок в 2025 году',
            'body' => 'С 1 января 2025 года вступили в силу уточнения к правилам дистанционной торговли. Теперь продавцы обязаны принимать возврат товара без объяснения причин в течение 7 дней с момента получения, а при отсутствии письменного уведомления о порядке возврата — в течение 3 месяцев. Оплату за доставку при возврате нести покупатель не обязан, если товар был с дефектом. Подробную консультацию можно получить у наших специалистов.',
            'published_at' => date('Y-m-d H:i:s', strtotime('-21 days')),
        ],
        [
            'id' => 0,
            'title' => 'Итоги работы союза за первый квартал 2025 года',
            'body' => 'За первый квартал 2025 года специалисты Курганского областного союза потребителей провели более 340 консультаций, подготовили 87 претензий и 24 исковых заявления. Из рассмотренных судебных дел 91% завершились в пользу потребителей. Общая сумма взысканных компенсаций составила около 2,3 млн. Союз продолжает бесплатный приём граждан каждый рабочий день.',
            'published_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
        ],
    ];
    usort($items, static function (array $a, array $b) use ($sort): int {
        $ta = strtotime((string) ($a['published_at'] ?? ''));
        $tb = strtotime((string) ($b['published_at'] ?? ''));

        return $sort === 'old' ? $ta <=> $tb : $tb <=> $ta;
    });
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Новости — Курганский областной союз потребителей</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <?php
    $headerActive = 'news';
    require __DIR__ . '/includes/header.php';
    ?>

    <div class="page-hero">
        <div class="container">
            <p class="page-hero__kicker">КОСП — Курган</p>
            <h1 class="page-hero__title">Новости и акции</h1>
            <p class="page-hero__sub">Актуальная информация о деятельности союза потребителей.</p>
        </div>
    </div>

    <main class="section">
        <div class="container">
            <div class="news-sort-bar">
                <span class="news-sort-bar__label">Сортировка:</span>
                <a href="?<?php echo http_build_query(['sort' => 'new', 'page' => 1]); ?>" class="news-sort-bar__link<?php echo $sort === 'new' ? ' news-sort-bar__link--active' : ''; ?>">Сначала новые</a>
                <a href="?<?php echo http_build_query(['sort' => 'old', 'page' => 1]); ?>" class="news-sort-bar__link<?php echo $sort === 'old' ? ' news-sort-bar__link--active' : ''; ?>">Сначала старые</a>
            </div>
            <?php if (!$items): ?>
                <p class="news-empty">Новости пока не опубликованы.</p>
            <?php else: ?>
                <div class="news-grid">
                    <?php foreach ($items as $news): ?>
                        <?php
                            $shortText = mb_substr($news['body'], 0, 140, 'UTF-8');
                            if (mb_strlen($news['body'], 'UTF-8') > 140) $shortText .= '…';
                            $dateStr = htmlspecialchars(news_published_label_ru((string) ($news['published_at'] ?? '')), ENT_QUOTES, 'UTF-8');
                        ?>
                        <article class="news-tile"
                            data-modal-title="<?php echo htmlspecialchars($news['title'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-modal-date="<?php echo $dateStr; ?>"
                            data-modal-body="<?php echo htmlspecialchars($news['body'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="news-tile__date"><?php echo $dateStr; ?></div>
                            <h3 class="news-tile__title">
                                <?php echo htmlspecialchars($news['title'], ENT_QUOTES, 'UTF-8'); ?>
                            </h3>
                            <p class="news-tile__text">
                                <?php echo nl2br(htmlspecialchars($shortText, ENT_QUOTES, 'UTF-8')); ?>
                            </p>
                            <span class="news-tile__link">Читать →</span>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Модальное окно -->
                <div class="news-modal" id="newsModal" role="dialog" aria-modal="true" aria-labelledby="newsModalTitle">
                    <div class="news-modal__overlay" id="newsModalOverlay"></div>
                    <div class="news-modal__box">
                        <button class="news-modal__close" id="newsModalClose" aria-label="Закрыть">✕</button>
                        <p class="news-modal__date" id="newsModalDate"></p>
                        <h2 class="news-modal__title" id="newsModalTitle"></h2>
                        <div class="news-modal__body" id="newsModalBody"></div>
                    </div>
                </div>

                <?php if (!$useDemoNews && $pages > 1): ?>
                    <div class="news-pagination">
                        <?php for ($p = 1; $p <= $pages; $p++): ?>
                            <?php if ($p === $page): ?>
                                <span class="news-pagination__item news-pagination__item--active"><?php echo $p; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(['page' => $p, 'sort' => $sort]); ?>" class="news-pagination__item"><?php echo $p; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php require __DIR__ . '/includes/footer.php'; ?>

    <?php require __DIR__ . '/includes/scripts_public.php'; ?>
</body>
</html>

