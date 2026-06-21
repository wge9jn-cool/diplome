<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/calc_catalog.php';
require_once __DIR__ . '/../includes/appeal_topic_from_calc.php';
require_once __DIR__ . '/../includes/review_stars.php';

admin_ensure_role($pdo);
admin_require_admin();

$adminId = admin_staff_id();

$statusFilter = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
$allowedFilter = ['', 'pending', 'approved', 'rejected'];
if (!in_array($statusFilter, $allowedFilter, true)) {
    $statusFilter = '';
}

$validSvcIds = calc_catalog_valid_service_ids();
$serviceFilter = isset($_GET['service']) ? trim((string) $_GET['service']) : '';
if ($serviceFilter !== '' && !in_array($serviceFilter, $validSvcIds, true)) {
    $serviceFilter = '';
}
if ($statusFilter !== 'approved') {
    $serviceFilter = '';
}

$reviewsQueryString = static function () use ($statusFilter, $serviceFilter): string {
    $parts = [];
    if ($statusFilter !== '') {
        $parts['status'] = $statusFilter;
    }
    if ($serviceFilter !== '') {
        $parts['service'] = $serviceFilter;
    }

    return $parts === [] ? '' : ('?' . http_build_query($parts));
};

$reviewsUrl = static function (array $override = []) use ($statusFilter, $serviceFilter): string {
    $st = array_key_exists('status', $override) ? (string) $override['status'] : $statusFilter;
    $sv = array_key_exists('service', $override) ? (string) $override['service'] : $serviceFilter;
    $parts = [];
    if ($st !== '') {
        $parts['status'] = $st;
    }
    if ($sv !== '') {
        $parts['service'] = $sv;
    }

    return $parts === [] ? 'reviews.php' : ('reviews.php?' . http_build_query($parts));
};

$moderateError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['moderate_review'])) {
    $cid = (int) ($_POST['comment_id'] ?? 0);
    $action = (string) ($_POST['moderation_action'] ?? '');
    $svcPick = trim((string) ($_POST['calc_service_id'] ?? ''));
    if ($cid > 0 && ($action === 'approve' || $action === 'reject')) {
        try {
            $stmt = $pdo->prepare('SELECT id, appeal_id, status FROM appeal_archive_comments WHERE id = ? LIMIT 1');
            $stmt->execute([$cid]);
            $row = $stmt->fetch();
            if ($row && (string) ($row['status'] ?? '') === 'pending') {
                if ($action === 'approve') {
                    if (!in_array($svcPick, $validSvcIds, true)) {
                        $moderateError = 'Выберите раздел услуг для публикации отзыва.';
                    } else {
                        $stmt = $pdo->prepare('UPDATE appeal_archive_comments SET status = ?, calc_service_id = ?, moderated_at = NOW(), moderator_id = ? WHERE id = ?');
                        $stmt->execute(['approved', $svcPick, $adminId, $cid]);
                    }
                } else {
                    $stmt = $pdo->prepare('UPDATE appeal_archive_comments SET status = ?, calc_service_id = NULL, moderated_at = NOW(), moderator_id = ? WHERE id = ?');
                    $stmt->execute(['rejected', $adminId, $cid]);
                }
            }
        } catch (Throwable $e) {
            $moderateError = 'Не удалось сохранить. Проверьте, что выполнена миграция таблицы отзывов.';
        }
    }
    if ($moderateError === '') {
        header('Location: reviews.php' . $reviewsQueryString());
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    $rid = (int) ($_POST['review_id'] ?? 0);
    if ($rid > 0) {
        try {
            $stmt = $pdo->prepare('DELETE FROM appeal_archive_comments WHERE id = ?');
            $stmt->execute([$rid]);
        } catch (Throwable $e) {
            // таблица отсутствует и т.п.
        }
    }
    header('Location: reviews.php' . $reviewsQueryString());
    exit;
}

$rows = [];
$tableError = '';
try {
    $sql = '
        SELECT c.id, c.appeal_id, c.body, c.status, c.calc_service_id, c.rating, c.is_anonymous, c.created_at, c.moderated_at,
               u.name AS user_name, u.phone AS user_phone,
               LEFT(a.description, 12000) AS appeal_description_preview
        FROM appeal_archive_comments c
        INNER JOIN users u ON u.id = c.user_id
        INNER JOIN appeals a ON a.id = c.appeal_id
    ';
    $params = [];
    $conds = [];
    if ($statusFilter !== '') {
        $conds[] = 'c.status = ?';
        $params[] = $statusFilter;
    }
    if ($serviceFilter !== '') {
        $conds[] = 'c.calc_service_id = ?';
        $params[] = $serviceFilter;
    }
    if ($conds !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $conds);
    }
    $sql .= ' ORDER BY c.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $tableError = 'Таблица отзывов не найдена. Выполните миграцию database_alter_appeal_archive_comments.sql.';
}

$svcTitles = [];
foreach (calc_catalog_list() as $svc) {
    $svcTitles[(string) $svc['id']] = calc_catalog_service_title_plain((string) $svc['title']);
}

$calcSvcOptions = [];
foreach (calc_catalog_list() as $svc) {
    $calcSvcOptions[] = [
        'id' => (string) $svc['id'],
        'label' => calc_catalog_service_title_plain((string) $svc['title']),
    ];
}

function reviews_status_label(string $s): string {
    return match ($s) {
        'pending' => 'На проверке',
        'approved' => 'На сайте',
        'rejected' => 'Отклонён',
        default => $s,
    };
}

function reviews_preview(string $body, int $max = 160): string {
    $body = trim($body);
    if (function_exists('mb_strlen') && mb_strlen($body, 'UTF-8') > $max) {
        return rtrim(mb_substr($body, 0, $max - 1, 'UTF-8')) . '…';
    }
    if (strlen($body) > $max) {
        return rtrim(substr($body, 0, $max - 1)) . '…';
    }

    return $body;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Отзывы клиентов — Админпанель КОСП</title>
    <link rel="stylesheet" href="../styles.css" />
</head>
<body>
    <?php
    $logoBase = '../';
    $logoHref = 'index.php';
    $logoAdmin = true;
    require __DIR__ . '/includes/header_bar.php';
    ?>

    <main class="section section--light">
        <div class="container">
            <div class="section__head">
                <h2>Отзывы после обращений</h2>
            </div>

            <?php if ($moderateError): ?>
                <div class="auth-error" style="max-width:720px;"><?php echo htmlspecialchars($moderateError, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if ($tableError): ?>
                <div class="auth-error" style="max-width:720px;"><?php echo htmlspecialchars($tableError, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php else: ?>
                <form method="get" action="reviews.php" class="admin-reviews-filter-bar" id="adminReviewsFilters" autocomplete="off">
                    <div class="admin-reviews-filter-bar__group">
                        <label for="adminReviewsStatus">Статус</label>
                        <select name="status" id="adminReviewsStatus" class="admin-reviews-filter-bar__select">
                            <option value=""<?php echo $statusFilter === '' ? ' selected' : ''; ?>>Все</option>
                            <option value="pending"<?php echo $statusFilter === 'pending' ? ' selected' : ''; ?>>На проверке</option>
                            <option value="approved"<?php echo $statusFilter === 'approved' ? ' selected' : ''; ?>>На сайте</option>
                            <option value="rejected"<?php echo $statusFilter === 'rejected' ? ' selected' : ''; ?>>Отклонённые</option>
                        </select>
                    </div>
                    <div class="admin-reviews-filter-bar__group admin-reviews-filter-bar__group--grow">
                        <label for="adminReviewsService">Раздел на «Услуги»</label>
                        <select name="service" id="adminReviewsService" class="admin-reviews-filter-bar__select">
                            <option value=""<?php echo $serviceFilter === '' ? ' selected' : ''; ?>>Все разделы</option>
                            <?php foreach ($calcSvcOptions as $opt): ?>
                                <?php $oid = (string) $opt['id']; ?>
                                <option value="<?php echo htmlspecialchars($oid, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $serviceFilter === $oid ? ' selected' : ''; ?>>
                                    <?php echo htmlspecialchars($opt['label'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
                <p class="request__hint" style="margin: -4px 0 16px; max-width: 720px;">
                    Чтобы убрать отзыв со страницы «Услуги», выберите статус «На сайте», при необходимости раздел, нажмите «Удалить» у записи — текст исчезнет из модального окна отзывов.
                </p>

                <?php if (!$rows): ?>
                    <p class="request__hint">Записей нет.</p>
                <?php else: ?>
                    <?php $reviewsListAction = 'reviews.php' . $reviewsQueryString(); ?>
                    <div class="admin-reviews-table">
                        <div class="admin-reviews-row admin-reviews-row--head">
                            <div>№</div>
                            <div>Дата</div>
                            <div>Клиент</div>
                            <div>Обращение</div>
                            <div>Статус</div>
                            <div>Раздел</div>
                            <div>Текст</div>
                            <div>Удалить</div>
                        </div>
                        <?php
                        foreach ($rows as $r):
                            $st = (string) ($r['status'] ?? '');
                            $descPrev = (string) ($r['appeal_description_preview'] ?? '');
                            $split = appeal_split_description_for_display($descPrev);
                            $guessId = appeal_guess_calc_service_id_from_text($split['services_block'], $split['user_text']);
                            $guessLabel = $svcTitles[$guessId] ?? $guessId;

                            $sid = (string) ($r['calc_service_id'] ?? '');
                            $svcLabel = $sid !== '' && isset($svcTitles[$sid]) ? $svcTitles[$sid] : ($sid !== '' ? $sid : '—');

                            $sectionMain = $svcLabel;
                            $sectionSub = '';
                            if ($st === 'pending') {
                                $sectionMain = $guessLabel;
                                $sectionSub = 'подсказка по тексту обращения; при публикации выберите раздел ниже';
                            } elseif ($st === 'rejected') {
                                $sectionMain = $guessLabel;
                                $sectionSub = 'подсказка по обращению; отклонён — раздел не назначался, на «Услуги» не выводился';
                            }
                            ?>
                            <div class="admin-reviews-block">
                                <div class="admin-reviews-row">
                                    <div>#<?php echo (int) $r['id']; ?></div>
                                    <div><?php echo htmlspecialchars((string) $r['created_at'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div>
                                        <?php echo htmlspecialchars((string) $r['user_name'], ENT_QUOTES, 'UTF-8'); ?>
                                        <div class="admin-reviews-row__sub"><?php echo htmlspecialchars(review_stars_text(review_normalize_stars($r['rating'] ?? 5)), ENT_QUOTES, 'UTF-8'); ?><?php if (!empty($r['is_anonymous'])): ?> · анонимно на сайте<?php endif; ?></div>
                                        <div class="admin-reviews-row__sub"><?php echo htmlspecialchars((string) $r['user_phone'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    </div>
                                    <div>
                                        <a href="appeal_edit.php?id=<?php echo (int) $r['appeal_id']; ?>">Обращение №<?php echo (int) $r['appeal_id']; ?></a>
                                    </div>
                                    <div>
                                        <span class="admin-reviews-badge admin-reviews-badge--<?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars(reviews_status_label($st), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($sectionMain, ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <?php if ($sectionSub !== ''): ?>
                                            <div class="admin-reviews-row__sub"><?php echo htmlspecialchars($sectionSub, ENT_QUOTES, 'UTF-8'); ?></div>
                                        <?php endif; ?>
                                        <?php if ($st === 'approved' && $sid !== ''): ?>
                                            <div class="admin-reviews-row__sub">ID: <?php echo htmlspecialchars($sid, ENT_QUOTES, 'UTF-8'); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="admin-reviews-row__text"><?php echo htmlspecialchars(reviews_preview((string) $r['body']), ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div>
                                        <form method="post" action="<?php echo htmlspecialchars($reviewsListAction, ENT_QUOTES, 'UTF-8'); ?>" onsubmit="return confirm('Удалить эту запись? Для опубликованных отзывов текст пропадёт со страницы «Услуги».');">
                                            <input type="hidden" name="delete_review" value="1" />
                                            <input type="hidden" name="review_id" value="<?php echo (int) $r['id']; ?>" />
                                            <button type="submit" class="btn btn--ghost" style="padding:4px 10px;font-size:13px;">Удалить</button>
                                        </form>
                                    </div>
                                </div>
                                <?php if ($st === 'pending'): ?>
                                    <div class="admin-reviews-moderate">
                                        <p class="admin-reviews-moderate__lead">
                                            Опубликовать в отзывах на странице «Услуги»: выберите раздел и нажмите «На сайт», либо отклоните.
                                            <a href="../services.php" target="_blank" rel="noopener">Открыть страницу «Услуги»</a>
                                        </p>
                                        <div class="admin-reviews-moderate__actions">
                                            <form method="post" action="<?php echo htmlspecialchars($reviewsListAction, ENT_QUOTES, 'UTF-8'); ?>" class="admin-reviews-moderate__approve-form">
                                                <input type="hidden" name="moderate_review" value="1" />
                                                <input type="hidden" name="comment_id" value="<?php echo (int) $r['id']; ?>" />
                                                <input type="hidden" name="moderation_action" value="approve" />
                                                <div class="field admin-reviews-moderate__field">
                                                    <label for="review_calc_<?php echo (int) $r['id']; ?>">Раздел</label>
                                                    <select id="review_calc_<?php echo (int) $r['id']; ?>" name="calc_service_id" required>
                                                        <?php foreach ($calcSvcOptions as $opt): ?>
                                                            <option value="<?php echo htmlspecialchars($opt['id'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $opt['id'] === $guessId ? ' selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($opt['label'], ENT_QUOTES, 'UTF-8'); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <button type="submit" class="btn btn--primary">На сайт</button>
                                            </form>
                                            <form method="post" action="<?php echo htmlspecialchars($reviewsListAction, ENT_QUOTES, 'UTF-8'); ?>" class="admin-reviews-moderate__reject-form" onsubmit="return confirm('Отклонить этот отзыв?');">
                                                <input type="hidden" name="moderate_review" value="1" />
                                                <input type="hidden" name="comment_id" value="<?php echo (int) $r['id']; ?>" />
                                                <input type="hidden" name="moderation_action" value="reject" />
                                                <button type="submit" class="btn btn--ghost">Отклонить</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php $footerPrefix = '../'; require __DIR__ . '/../includes/footer.php'; ?>
    <?php require __DIR__ . '/includes/scripts.php'; ?>
    <script>
    (function () {
        var form = document.getElementById('adminReviewsFilters');
        if (!form) return;
        var st = document.getElementById('adminReviewsStatus');
        var sv = document.getElementById('adminReviewsService');
        if (!st || !sv) return;
        st.addEventListener('change', function () {
            if (st.value !== 'approved') {
                sv.value = '';
            }
            form.submit();
        });
        sv.addEventListener('change', function () {
            if (sv.value !== '') {
                st.value = 'approved';
            }
            form.submit();
        });
    })();
    </script>
</body>
</html>
