<?php
session_start();
require_once __DIR__ . '/db.php';

$user = null;
$viewerUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
if ($viewerUserId > 0) {
    $stmt = $pdo->prepare('SELECT id, name FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$viewerUserId]);
    $user = $stmt->fetch() ?: null;
}

require_once __DIR__ . '/includes/calc_catalog.php';
require_once __DIR__ . '/includes/review_stars.php';
require_once __DIR__ . '/includes/news_date_ru.php';

$reviewSort = isset($_GET['review_sort']) ? trim((string) $_GET['review_sort']) : 'new';
if (!in_array($reviewSort, ['new', 'old'], true)) {
    $reviewSort = 'new';
}

$services = calc_catalog_services_for_page();

$approvedArchiveReviews = [];
try {
    $stmt = $pdo->query("
        SELECT c.body, c.calc_service_id, c.user_id, c.rating, c.is_anonymous, c.moderated_at, c.created_at, c.id, u.name AS user_name
        FROM appeal_archive_comments c
        INNER JOIN users u ON u.id = c.user_id
        WHERE c.status = 'approved'
          AND c.calc_service_id IS NOT NULL
          AND c.calc_service_id <> ''
        ORDER BY c.moderated_at DESC, c.id DESC
        LIMIT 200
    ");
    $approvedArchiveReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    try {
        $stmt = $pdo->query("
            SELECT c.body, c.calc_service_id, c.user_id, u.name AS user_name, c.created_at, c.id
            FROM appeal_archive_comments c
            INNER JOIN users u ON u.id = c.user_id
            WHERE c.status = 'approved'
              AND c.calc_service_id IS NOT NULL
              AND c.calc_service_id <> ''
            ORDER BY c.created_at DESC, c.id DESC
            LIMIT 200
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['rating'] = 5;
            $row['is_anonymous'] = 0;
            $row['moderated_at'] = null;
        }
        unset($row);
        $approvedArchiveReviews = $rows;
    } catch (Throwable $e2) {
        $approvedArchiveReviews = [];
    }
}

foreach ($services as $idx => $svc) {
    $sid = (string) $svc['id'];
    $extra = [];
    foreach ($approvedArchiveReviews as $row) {
        if (($row['calc_service_id'] ?? '') === $sid) {
            $authorId = (int) ($row['user_id'] ?? 0);
            $displayName = !empty($row['is_anonymous'])
                ? 'Анонимный клиент'
                : (string) ($row['user_name'] ?? 'Клиент');
            if ($viewerUserId > 0 && $authorId > 0 && $viewerUserId === $authorId) {
                $displayName .= ' (вы)';
            }
            $pubRaw = trim((string) ($row['moderated_at'] ?? ''));
            if ($pubRaw === '') {
                $pubRaw = trim((string) ($row['created_at'] ?? ''));
            }
            if ($pubRaw === '') {
                $pubRaw = date('Y-m-d H:i:s');
            }
            $extra[] = [
                'name' => $displayName,
                'text' => (string) ($row['body'] ?? ''),
                'stars' => review_normalize_stars($row['rating'] ?? 5),
                'published_at' => $pubRaw,
            ];
        }
    }
    $merged = array_merge($extra, $svc['reviews'] ?? []);
    if ($merged !== []) {
        $services[$idx]['reviews'] = $merged;
    }
}

foreach ($services as $idx => $svc) {
    $list = $svc['reviews'] ?? [];
    if ($list === []) {
        continue;
    }
    $sampleTs = (int) strtotime('2014-01-01 12:00:00');
    foreach ($list as &$rev) {
        $pa = isset($rev['published_at']) ? trim((string) $rev['published_at']) : '';
        if ($pa === '') {
            $rev['sort_ts'] = $sampleTs;
            $pa = date('Y-m-d H:i:s', $sampleTs);
        } else {
            $t = strtotime($pa);
            $rev['sort_ts'] = $t > 0 ? $t : $sampleTs;
        }
        $rev['published_label'] = news_published_label_ru($pa);
    }
    unset($rev);
    usort($list, static function (array $a, array $b) use ($reviewSort): int {
        $ta = (int) ($a['sort_ts'] ?? 0);
        $tb = (int) ($b['sort_ts'] ?? 0);
        if ($ta === $tb) {
            return 0;
        }

        return $reviewSort === 'old' ? $ta <=> $tb : $tb <=> $ta;
    });
    $services[$idx]['reviews'] = $list;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Услуги — Курганский областной союз потребителей</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <?php
    $headerActive = 'services';
    require __DIR__ . '/includes/header.php';
    ?>

    <div class="page-hero">
        <div class="container">
            <p class="page-hero__kicker">КОСП — Курган</p>
            <h1 class="page-hero__title">Услуги по защите<br>прав потребителей</h1>
            <p class="page-hero__sub">Консультируем, готовим документы и представляем интересы в суде.</p>
        </div>
    </div>

    <main class="section">
        <div class="container">
            <div class="svc-list">
                <?php foreach ($services as $i => $service): ?>
                    <?php
                    $sid = $service['id'];
                    $modalId = 'svcReview-' . $sid;
                    $titleId = 'svcReviewTitle-' . $sid;
                    ?>
                    <article class="svc-item">
                        <span class="svc-item__num"><?php echo str_pad($i + 1, 2, '0', STR_PAD_LEFT); ?></span>
                        <div class="svc-item__body">
                            <h3 class="svc-item__title" id="<?php echo htmlspecialchars($titleId, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($service['title_plain'], ENT_QUOTES, 'UTF-8'); ?>
                            </h3>
                            <p class="svc-item__desc"><?php echo nl2br(htmlspecialchars($service['description'], ENT_QUOTES, 'UTF-8')); ?></p>
                        </div>
                        <button type="button" class="svc-item__link" data-modal-open="#<?php echo htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8'); ?>">
                            Посмотреть отзывы →
                        </button>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="page-cta">
                <div class="page-cta__text">
                    <p class="page-cta__title">Не нашли нужную услугу?</p>
                    <p class="page-cta__hint">Позвоните нам — расскажем, чем можем помочь</p>
                </div>
                <a href="tel:+73522241720" class="btn btn--services-main">+7 (3522) 241-720</a>
            </div>
        </div>
    </main>

    <?php foreach ($services as $service): ?>
        <?php
        $sid = $service['id'];
        $modalId = 'svcReview-' . $sid;
        $titleId = 'svcReviewTitle-' . $sid;
        ?>
        <div class="modal" id="<?php echo htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8'); ?>" role="dialog" aria-modal="true" aria-labelledby="<?php echo htmlspecialchars($titleId, ENT_QUOTES, 'UTF-8'); ?>-modal" aria-hidden="true">
            <div class="modal__overlay" data-modal-close></div>
            <div class="modal__content">
                <button type="button" class="modal__close" aria-label="Закрыть" data-modal-close>✕</button>
                <h3 class="svc-review-modal__title" id="<?php echo htmlspecialchars($titleId, ENT_QUOTES, 'UTF-8'); ?>-modal">
                    Отзывы: <?php echo htmlspecialchars($service['title_plain'], ENT_QUOTES, 'UTF-8'); ?>
                </h3>
                <div class="svc-review-modal__sort news-sort-bar news-sort-bar--compact">
                    <span class="news-sort-bar__label">Порядок:</span>
                    <a href="?<?php echo http_build_query(['review_sort' => 'new']); ?>#<?php echo htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8'); ?>" class="news-sort-bar__link<?php echo $reviewSort === 'new' ? ' news-sort-bar__link--active' : ''; ?>" data-modal-close>Сначала новые</a>
                    <a href="?<?php echo http_build_query(['review_sort' => 'old']); ?>#<?php echo htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8'); ?>" class="news-sort-bar__link<?php echo $reviewSort === 'old' ? ' news-sort-bar__link--active' : ''; ?>" data-modal-close>Сначала старые</a>
                </div>
                <div class="modal__body svc-review-modal__body">
                    <div class="svc-review-list">
                        <?php foreach ($service['reviews'] as $rev): ?>
                            <?php
                            $rName = isset($rev['name']) ? (string) $rev['name'] : '';
                            $rText = isset($rev['text']) ? (string) $rev['text'] : '';
                            $rStars = review_normalize_stars($rev['stars'] ?? 5);
                            $rDate = isset($rev['published_label']) ? (string) $rev['published_label'] : '';
                            ?>
                            <article class="svc-review-card">
                                <div class="svc-review-card__top">
                                    <span class="svc-review-card__name"><?php echo htmlspecialchars($rName, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php echo review_stars_html($rStars); ?>
                                </div>
                                <?php if ($rDate !== ''): ?>
                                    <p class="svc-review-card__date"><?php echo htmlspecialchars($rDate, ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                                <p class="svc-review-card__text"><?php echo nl2br(htmlspecialchars($rText, ENT_QUOTES, 'UTF-8')); ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php require __DIR__ . '/includes/footer.php'; ?>

    <?php require __DIR__ . '/includes/scripts_public.php'; ?>
</body>
</html>

