<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/appeal_topic_from_calc.php';
require_once __DIR__ . '/includes/review_stars.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT id, name FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch() ?: null;

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare('SELECT id, user_id, topic, description, attachment_path, generated_doc_path, created_at FROM appeals WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$appeal = $stmt->fetch();

if (!$appeal || (int) $appeal['user_id'] !== $userId) {
    header('Location: cabinet.php');
    exit;
}

$stmt = $pdo->prepare('SELECT status, comment, created_at FROM appeal_statuses WHERE appeal_id = ? ORDER BY created_at ASC, id ASC');
$stmt->execute([$id]);
$statuses = $stmt->fetchAll();

$lastStatus = '';
if ($statuses) {
    $last = $statuses[count($statuses) - 1];
    $lastStatus = (string) ($last['status'] ?? '');
}
$isArchived = in_array($lastStatus, ['completed', 'rejected'], true);

$archiveCommentError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_comment'])) {
    if (!$isArchived) {
        $archiveCommentError = 'Комментарий можно оставить только для обращения в архиве (завершено или отклонено).';
    } else {
        $body = trim($_POST['archive_body'] ?? '');
        $len = function_exists('mb_strlen') ? mb_strlen($body, 'UTF-8') : strlen($body);
        if ($len < 10) {
            $archiveCommentError = 'Напишите хотя бы пару предложений (от 10 символов).';
        } elseif ($len > 2000) {
            $archiveCommentError = 'Текст не длиннее 2000 символов.';
        } else {
            $rating = isset($_POST['archive_rating']) ? (int) $_POST['archive_rating'] : 5;
            if ($rating < 1 || $rating > 5) {
                $rating = 5;
            }
            $isAnonymous = !empty($_POST['archive_anonymous']);
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO appeal_archive_comments (appeal_id, user_id, body, rating, is_anonymous) VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([$id, $userId, $body, $rating, $isAnonymous ? 1 : 0]);
                header('Location: cabinet_appeal.php?id=' . $id . '&archive_comment=1');
                exit;
            } catch (Throwable $e) {
                $archiveCommentError = 'Не удалось сохранить комментарий. Если не выполнялась миграция БД, выполните database_alter_appeal_archive_comments_rating.sql. Иначе сообщите администратору сайта.';
            }
        }
    }
}

$stmt = $pdo->prepare('
    SELECT c.comment, c.created_at, u.name AS admin_name
    FROM appeal_comments c
    JOIN users u ON u.id = c.admin_id
    WHERE c.appeal_id = ?
    ORDER BY c.created_at ASC, c.id ASC
');
$stmt->execute([$id]);
$comments = $stmt->fetchAll();

$archiveComments = [];
try {
    $stmt = $pdo->prepare(
        'SELECT id, body, status, created_at, rating, is_anonymous FROM appeal_archive_comments WHERE appeal_id = ? AND user_id = ? ORDER BY id DESC'
    );
    $stmt->execute([$id, $userId]);
    $archiveComments = $stmt->fetchAll();
} catch (Throwable $e) {
    try {
        $stmt = $pdo->prepare('SELECT id, body, status, created_at FROM appeal_archive_comments WHERE appeal_id = ? AND user_id = ? ORDER BY id DESC');
        $stmt->execute([$id, $userId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['rating'] = 5;
            $r['is_anonymous'] = 0;
        }
        unset($r);
        $archiveComments = $rows;
    } catch (Throwable $e2) {
        $archiveComments = [];
    }
}

$descSplit = appeal_split_description_for_display((string) ($appeal['description'] ?? ''));
$archiveCommentOk = isset($_GET['archive_comment']) && (string) $_GET['archive_comment'] === '1';

function topic_title(string $code): string {
    $map = [
        'bad_product' => 'Некачественный товар',
        'delay' => 'Нарушение сроков',
        'warranty_refusal' => 'Отказ в гарантийном ремонте',
        'housing' => 'Услуги ЖКХ',
        'other' => 'Другое',
    ];
    return $map[$code] ?? $code;
}

function status_title(string $code): string {
    $map = [
        'accepted' => 'Принято',
        'processing' => 'В работе',
        'answered' => 'Ответ сформирован',
        'completed' => 'Завершено',
        'rejected' => 'Отклонено',
    ];
    return $map[$code] ?? $code;
}

function archive_comment_status_label(string $status): string {
    return match ($status) {
        'pending' => 'На проверке у специалиста',
        'approved' => 'Опубликован в отзывах на странице «Услуги»',
        'rejected' => 'Не опубликован',
        default => $status,
    };
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Обращение №<?php echo (int) $appeal['id']; ?> — Личный кабинет</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <?php
    $headerCabinetSlimNav = true;
    require __DIR__ . '/includes/header.php';
    unset($headerCabinetSlimNav);
    ?>

    <main class="section section--light">
        <div class="container">
            <div class="section__head">
                <h2>Обращение №<?php echo (int) $appeal['id']; ?></h2>
                <p><?php echo htmlspecialchars(appeal_subject_line_from_description($appeal['description'] ?? null) ?? topic_title($appeal['topic']), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <div class="request request--cabinet-appeal">
                <div class="request__form request__form--stack">
                    <section class="appeal-card">
                        <h3>Описание обращения</h3>
                        <?php if ($descSplit['has_split'] && ($descSplit['services_block'] !== '' || $descSplit['user_text'] !== '')): ?>
                            <?php if ($descSplit['services_block'] !== ''): ?>
                                <?php
                                $servicesBlockTitle = (strpos($descSplit['services_block'], 'Тема по') === 0)
                                    ? 'Классификация обращения'
                                    : 'Услуги из заказа калькулятора';
                                ?>
                                <div class="appeal-detail__subsection">
                                    <p class="appeal-detail__kicker">Заказ и тема</p>
                                    <?php if (strpos($descSplit['services_block'], "\n") !== false): ?>
                                        <h4><?php echo htmlspecialchars($servicesBlockTitle, ENT_QUOTES, 'UTF-8'); ?></h4>
                                        <ul class="appeal-detail__list">
                                            <?php
                                            foreach (preg_split('/\R/u', $descSplit['services_block']) as $ln) {
                                                $ln = trim((string) $ln);
                                                if ($ln === '') {
                                                    continue;
                                                }
                                                $ln = preg_replace('/^[•\-\*]\s*/u', '', $ln);
                                                if ($ln !== '') {
                                                    echo '<li>' . htmlspecialchars($ln, ENT_QUOTES, 'UTF-8') . '</li>';
                                                }
                                            }
                                            ?>
                                        </ul>
                                    <?php else: ?>
                                        <h4><?php echo htmlspecialchars($servicesBlockTitle, ENT_QUOTES, 'UTF-8'); ?></h4>
                                        <p class="appeal-card__text" style="margin:0;"><?php echo htmlspecialchars($descSplit['services_block'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($descSplit['user_text'] !== ''): ?>
                                <div class="appeal-detail__subsection appeal-detail__subsection--plain">
                                    <p class="appeal-detail__kicker">Ситуация</p>
                                    <h4>Ваше описание</h4>
                                    <p class="appeal-card__text" style="margin:0;"><?php echo htmlspecialchars($descSplit['user_text'], ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="appeal-card__text"><?php echo htmlspecialchars((string) $appeal['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>

                        <?php if ($appeal['attachment_path']): ?>
                            <p class="request__note" style="margin-top:14px;">
                                Прикреплённый файл:
                                <a href="<?php echo htmlspecialchars($appeal['attachment_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                    Скачать
                                </a>
                            </p>
                        <?php endif; ?>
                        <?php if ($appeal['generated_doc_path']): ?>
                            <p class="request__note" style="margin-top:8px;">
                                Подготовленный документ:
                                <a href="<?php echo htmlspecialchars($appeal['generated_doc_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                    Скачать документ
                                </a>
                            </p>
                        <?php endif; ?>
                    </section>

                    <?php if ($isArchived): ?>
                        <section class="appeal-card">
                            <h3>Комментарий после завершения</h3>
                            <p class="request__hint" style="margin-top:0;">
                                Если обращение уже в архиве, вы можете написать отзыв о работе союза. Он уйдёт специалисту на проверку.
                                После одобрения текст может появиться среди отзывов в соответствующем разделе на странице «Услуги». Укажите оценку звёздами; при желании отметьте анонимность — на сайте будет показано «Анонимный клиент» вместо имени из профиля.
                            </p>
                            <?php if ($archiveCommentOk): ?>
                                <p class="request__note" role="status">Комментарий отправлен.</p>
                            <?php endif; ?>
                            <?php if ($archiveCommentError): ?>
                                <div class="auth-error" style="max-width:none;"><?php echo htmlspecialchars($archiveCommentError, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                            <?php if ($archiveComments): ?>
                                <?php foreach ($archiveComments as $ac): ?>
                                    <?php
                                    $st = (string) ($ac['status'] ?? '');
                                    $modClass = 'appeal-archive-comment--pending';
                                    if ($st === 'approved') {
                                        $modClass = 'appeal-archive-comment--approved';
                                    } elseif ($st === 'rejected') {
                                        $modClass = 'appeal-archive-comment--rejected';
                                    }
                                    ?>
                                    <div class="appeal-archive-comment <?php echo htmlspecialchars($modClass, ENT_QUOTES, 'UTF-8'); ?>">
                                        <div class="appeal-archive-comment__meta">
                                            <?php echo htmlspecialchars((string) $ac['created_at'], ENT_QUOTES, 'UTF-8'); ?>
                                            — <?php echo htmlspecialchars(archive_comment_status_label($st), ENT_QUOTES, 'UTF-8'); ?>
                                            <?php
                                            $acRating = review_normalize_stars($ac['rating'] ?? 5);
                                            $acAnon = !empty($ac['is_anonymous']);
                                            ?>
                                            <span class="appeal-archive-comment__rating" title="Ваша оценка"><?php echo htmlspecialchars(review_stars_text($acRating), ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php if ($acAnon): ?>
                                                <span class="appeal-archive-comment__anon">· на сайте без имени</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="appeal-card__text" style="margin:0;"><?php echo nl2br(htmlspecialchars((string) $ac['body'], ENT_QUOTES, 'UTF-8')); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <form method="post" class="cabinet-appeal-form" style="margin-top:12px;">
                                <input type="hidden" name="archive_comment" value="1" />
                                <div class="field">
                                    <label id="archive_rating_label">Оценка</label>
                                    <input type="hidden" name="archive_rating" id="archive_rating_value" value="5" />
                                    <div class="archive-rating-picker" role="group" aria-labelledby="archive_rating_label">
                                        <?php for ($si = 1; $si <= 5; ++$si): ?>
                                            <button type="button" class="archive-rating-picker__btn archive-rating-picker__btn--on" data-archive-star="<?php echo $si; ?>" aria-pressed="<?php echo $si <= 5 ? 'true' : 'false'; ?>" aria-label="<?php echo $si; ?> из 5">★</button>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="request__hint" style="margin-top:6px;">Нажмите на звезду, чтобы выставить оценку от 1 до 5.</p>
                                </div>
                                <label class="checkbox" style="margin-bottom:14px;">
                                    <input type="checkbox" name="archive_anonymous" value="1" />
                                    <span>Не показывать моё имя на странице «Услуги» (анонимный отзыв)</span>
                                </label>
                                <div class="field">
                                    <label for="archive_body">Новый комментарий для специалиста</label>
                                    <textarea id="archive_body" name="archive_body" rows="5" maxlength="2000" required placeholder="Например: как прошла консультация, что было полезно, что хотелось бы улучшить"></textarea>
                                </div>
                                <div class="cabinet-appeal-form__actions">
                                    <button type="submit" class="btn btn--secondary">Отправить на проверку</button>
                                </div>
                            </form>
                        </section>
                    <?php endif; ?>
                </div>

                <aside class="request__sidebar request__sidebar--stack">
                    <section class="appeal-card appeal-card--chat">
                        <h3>Чат по обращению</h3>
                        <p class="appeal-chat-intro">Сообщения видны вам и специалисту в реальном времени (без перезагрузки страницы).</p>
                        <div class="appeal-chat-shell">
                            <div id="chatScroll" class="appeal-chat-scroll">
                                <div id="chatEmpty" class="appeal-chat-empty" role="status">
                                    <div class="appeal-chat-empty__bubble" aria-hidden="true"></div>
                                    <p class="appeal-chat-empty__title">Пока нет сообщений</p>
                                    <p class="appeal-chat-empty__text">Напишите ниже — когда специалист ответит, переписка отобразится здесь.</p>
                                </div>
                                <div id="chatStream" class="appeal-chat-stream" aria-live="polite"></div>
                            </div>
                        </div>
                        <form id="chatForm" class="appeal-chat-form">
                            <div class="field" style="margin-bottom:0;">
                                <label for="chatMessage">Ваше сообщение</label>
                                <textarea id="chatMessage" rows="2" placeholder="Вопрос или уточнение по обращению…"></textarea>
                            </div>
                            <button type="submit" class="btn btn--secondary appeal-chat-form__submit">
                                Отправить
                            </button>
                        </form>
                    </section>

                    <section class="appeal-card">
                        <h3>Статусы обращения</h3>
                        <ol class="request__steps">
                            <?php if (!$statuses): ?>
                                <li>Информация о статусе пока отсутствует.</li>
                            <?php else: ?>
                                <?php foreach ($statuses as $s): ?>
                                    <li>
                                        <strong><?php echo htmlspecialchars(status_title($s['status']), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="request__hint">
                                            <?php echo htmlspecialchars($s['created_at'], ENT_QUOTES, 'UTF-8'); ?>
                                            <?php if ($s['comment']): ?>
                                                <br /><?php echo htmlspecialchars($s['comment'], ENT_QUOTES, 'UTF-8'); ?>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ol>
                    </section>

                    <section class="appeal-card">
                        <h3>Комментарии специалиста</h3>
                        <?php if (!$comments): ?>
                            <p class="request__hint">Пока нет комментариев.</p>
                        <?php else: ?>
                            <?php foreach ($comments as $c): ?>
                                <div class="request-row">
                                    <div class="request-row__title">
                                        <?php echo htmlspecialchars($c['admin_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="request-row__meta">
                                        <?php echo htmlspecialchars($c['created_at'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="request-row__comment">
                                        <?php echo nl2br(htmlspecialchars($c['comment'], ENT_QUOTES, 'UTF-8')); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </section>

                    <div class="appeal-card appeal-card--actions">
                        <a href="cabinet.php" class="btn btn--ghost">К списку обращений</a>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/includes/footer.php'; ?>

    <script src="script.js"></script>
    <script>
        window.APPEAL_CHAT_CONFIG = {
            appealId: <?php echo (int) $appeal['id']; ?>,
            tokenUrl: 'appeal_chat_ws_token.php',
            historyUrl: 'appeal_chat.php',
            labels: { user: 'Вы', admin: 'Специалист' }
        };
    </script>
    <script src="js/appeal-chat.js"></script>
    <script>
            (function () {
                var wrap = document.querySelector('.archive-rating-picker');
                var input = document.getElementById('archive_rating_value');
                if (!wrap || !input) return;
                var btns = wrap.querySelectorAll('[data-archive-star]');
                function apply(val) {
                    if (val < 1) val = 1;
                    if (val > 5) val = 5;
                    input.value = String(val);
                    btns.forEach(function (b) {
                        var n = parseInt(b.getAttribute('data-archive-star'), 10);
                        var on = n <= val;
                        b.classList.toggle('archive-rating-picker__btn--on', on);
                        b.classList.toggle('archive-rating-picker__btn--off', !on);
                        b.setAttribute('aria-pressed', on ? 'true' : 'false');
                    });
                }
                apply(parseInt(input.value, 10) || 5);
                wrap.addEventListener('click', function (e) {
                    var t = e.target.closest('[data-archive-star]');
                    if (!t) return;
                    apply(parseInt(t.getAttribute('data-archive-star'), 10));
                });
            })();
    </script>
</body>
</html>
