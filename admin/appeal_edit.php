<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/appeal_topic_from_calc.php';
require_once __DIR__ . '/../includes/calc_catalog.php';
require_once __DIR__ . '/../includes/review_stars.php';

admin_ensure_role($pdo);
admin_require_staff();

$adminId = admin_staff_id();
$isAdminUser = admin_is_admin();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare('
    SELECT a.id, a.user_id, a.topic, a.difficulty, a.description, a.attachment_path, a.generated_doc_path, a.created_at,
           u.name, u.phone, u.email
    FROM appeals a
    JOIN users u ON u.id = a.user_id
    WHERE a.id = ?
    LIMIT 1
');
$stmt->execute([$id]);
$appeal = $stmt->fetch();

if (!$appeal) {
    header('Location: appeals.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_status'])) {
        $status = $_POST['status'] ?? 'processing';
        $comment = trim($_POST['status_comment'] ?? '');
        $allowed = ['accepted', 'processing', 'answered', 'completed', 'rejected'];
        if (!in_array($status, $allowed, true)) {
            $status = 'processing';
        }
        $stmt = $pdo->prepare('INSERT INTO appeal_statuses (appeal_id, status, comment) VALUES (?, ?, ?)');
        $stmt->execute([$id, $status, $comment !== '' ? $comment : null]);
        header('Location: appeal_edit.php?id=' . $id);
        exit;
    } elseif (isset($_POST['add_comment'])) {
        $comment = trim($_POST['admin_comment'] ?? '');
        if ($comment !== '') {
            $stmt = $pdo->prepare('INSERT INTO appeal_comments (appeal_id, admin_id, comment) VALUES (?, ?, ?)');
            $stmt->execute([$id, $adminId, $comment]);
        }
        header('Location: appeal_edit.php?id=' . $id);
        exit;
    } elseif (isset($_POST['change_difficulty'])) {
        $difficulty = $_POST['difficulty'] ?? '';
        $allowedDifficulty = ['easy', 'medium', 'hard', ''];
        if (!in_array($difficulty, $allowedDifficulty, true)) {
            $difficulty = '';
        }
        $stmt = $pdo->prepare('UPDATE appeals SET difficulty = ? WHERE id = ?');
        $stmt->execute([$difficulty !== '' ? $difficulty : null, $id]);
        header('Location: appeal_edit.php?id=' . $id);
        exit;
    } elseif (isset($_POST['upload_doc'])) {
        if (!empty($_FILES['doc']['name'])) {
            $file = $_FILES['doc'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                if ($file['size'] > 5 * 1024 * 1024) {
                    $error = 'Файл слишком большой. Максимальный размер — 5 МБ.';
                } else {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($file['tmp_name']);
                    $allowedMime = [
                        'application/pdf' => 'pdf',
                        'application/msword' => 'doc',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                    ];
                    if (!array_key_exists($mime, $allowedMime)) {
                        $error = 'Разрешены только файлы DOC, DOCX или PDF.';
                    } else {
                        $ext = $allowedMime[$mime];
                        $uploadsDir = __DIR__ . '/../uploads/docs';
                        if (!is_dir($uploadsDir)) {
                            mkdir($uploadsDir, 0777, true);
                        }
                        $filename = sprintf('%s_%s.%s', $id, uniqid('', true), $ext);
                        $target = $uploadsDir . '/' . $filename;
                        if (!move_uploaded_file($file['tmp_name'], $target)) {
                            $error = 'Не удалось сохранить файл.';
                        } else {
                            $relPath = 'uploads/docs/' . $filename;
                            $stmt = $pdo->prepare('UPDATE appeals SET generated_doc_path = ? WHERE id = ?');
                            $stmt->execute([$relPath, $id]);
                        }
                    }
                }
            } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                $error = 'Ошибка загрузки файла.';
            }
        }

        if ($error === '') {
            header('Location: appeal_edit.php?id=' . $id);
            exit;
        }
    } elseif (isset($_POST['moderate_archive_comment'])) {
        if (!$isAdminUser) {
            header('Location: appeal_edit.php?id=' . $id);
            exit;
        }
        $cid = (int) ($_POST['comment_id'] ?? 0);
        $action = (string) ($_POST['moderation_action'] ?? '');
        $svcPick = trim((string) ($_POST['calc_service_id'] ?? ''));
        $validSvc = calc_catalog_valid_service_ids();
        if ($cid > 0 && ($action === 'approve' || $action === 'reject')) {
            try {
                $stmt = $pdo->prepare('SELECT id, appeal_id, status FROM appeal_archive_comments WHERE id = ? LIMIT 1');
                $stmt->execute([$cid]);
                $row = $stmt->fetch();
                if ($row && (int) $row['appeal_id'] === $id && (string) $row['status'] === 'pending') {
                    if ($action === 'approve') {
                        if (!in_array($svcPick, $validSvc, true)) {
                            $error = 'Выберите раздел услуг (куда вывести отзыв на странице «Услуги»).';
                        } else {
                            $stmt = $pdo->prepare('UPDATE appeal_archive_comments SET status = ?, calc_service_id = ?, moderated_at = NOW(), moderator_id = ? WHERE id = ? AND appeal_id = ?');
                            $stmt->execute(['approved', $svcPick, $adminId, $cid, $id]);
                        }
                    } else {
                        $stmt = $pdo->prepare('UPDATE appeal_archive_comments SET status = ?, calc_service_id = NULL, moderated_at = NOW(), moderator_id = ? WHERE id = ? AND appeal_id = ?');
                        $stmt->execute(['rejected', $adminId, $cid, $id]);
                    }
                }
            } catch (Throwable $e) {
                $error = 'Таблица комментариев не найдена. Выполните SQL-миграцию database_alter_appeal_archive_comments.sql.';
            }
        }
        if ($error === '') {
            header('Location: appeal_edit.php?id=' . $id);
            exit;
        }
    }
}

$stmt = $pdo->prepare('SELECT status, comment, created_at FROM appeal_statuses WHERE appeal_id = ? ORDER BY created_at ASC, id ASC');
$stmt->execute([$id]);
$statuses = $stmt->fetchAll();

$stmt = $pdo->prepare('
    SELECT c.comment, c.created_at, u.name AS admin_name
    FROM appeal_comments c
    JOIN users u ON u.id = c.admin_id
    WHERE c.appeal_id = ?
    ORDER BY c.created_at ASC, c.id ASC
');
$stmt->execute([$id]);
$comments = $stmt->fetchAll();

$userArchiveComments = [];
try {
    $stmt = $pdo->prepare('
        SELECT c.id, c.body, c.status, c.created_at, c.calc_service_id, c.rating, c.is_anonymous, u.name AS user_name
        FROM appeal_archive_comments c
        JOIN users u ON u.id = c.user_id
        WHERE c.appeal_id = ?
        ORDER BY c.id DESC
    ');
    $stmt->execute([$id]);
    $userArchiveComments = $stmt->fetchAll();
} catch (Throwable $e) {
    try {
        $stmt = $pdo->prepare('
            SELECT c.id, c.body, c.status, c.created_at, c.calc_service_id, u.name AS user_name
            FROM appeal_archive_comments c
            JOIN users u ON u.id = c.user_id
            WHERE c.appeal_id = ?
            ORDER BY c.id DESC
        ');
        $stmt->execute([$id]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['rating'] = 5;
            $r['is_anonymous'] = 0;
        }
        unset($r);
        $userArchiveComments = $rows;
    } catch (Throwable $e2) {
        $userArchiveComments = [];
    }
}

$descParts = appeal_split_description_for_display((string) ($appeal['description'] ?? ''));
$defaultSvcGuess = appeal_guess_calc_service_id_from_text($descParts['services_block'], $descParts['user_text']);
$calcSvcOptions = [];
foreach (calc_catalog_list() as $svc) {
    $calcSvcOptions[] = [
        'id' => (string) $svc['id'],
        'label' => calc_catalog_service_title_plain((string) $svc['title']),
    ];
}

function topic_title_admin(string $code): string {
    $map = [
        'bad_product' => 'Некачественный товар',
        'delay' => 'Нарушение сроков',
        'warranty_refusal' => 'Отказ в гарантийном ремонте',
        'housing' => 'Услуги ЖКХ',
        'other' => 'Другое',
    ];
    return $map[$code] ?? 'Обращение';
}

function status_title_admin(string $code): string {
    $map = [
        'accepted' => 'Принято',
        'processing' => 'В работе',
        'answered' => 'Ответ сформирован',
        'completed' => 'Завершено',
        'rejected' => 'Отклонено',
    ];
    return $map[$code] ?? $code;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Обращение №<?php echo (int) $appeal['id']; ?> — Админпанель</title>
    <link rel="stylesheet" href="../styles.css" />
</head>
<body>
    <header class="header">
        <div class="container header__inner">
            <?php
            $logoBase = '../';
            $logoHref = admin_home_href();
            $logoAdmin = true;
            require __DIR__ . '/../includes/logo.php';
            ?>
            <?php require __DIR__ . '/includes/nav.php'; ?>
        </div>
    </header>

    <main class="section section--light">
        <div class="container">
            <div class="section__head">
                <h2>Обращение №<?php echo (int) $appeal['id']; ?></h2>
                <p><?php echo htmlspecialchars(topic_title_admin($appeal['topic']), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <?php if ($error): ?>
                <div class="auth-error" style="max-width:720px;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <div class="request">
                <div class="request__form request__form--stack">
                    <section class="appeal-card appeal-card--admin-brief">
                        <div class="admin-appeal-brief__head">
                            <h3 class="admin-appeal-brief__title">Обращение</h3>
                            <div class="admin-appeal-brief__chips">
                                <span class="admin-appeal-chip admin-appeal-chip--topic"><?php echo htmlspecialchars(topic_title_admin($appeal['topic']), ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="admin-appeal-chip admin-appeal-chip--muted">Сложность: <?php echo htmlspecialchars(appeal_difficulty_label($appeal['difficulty'] ?? null), ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="admin-appeal-chip admin-appeal-chip--muted">Создано <?php echo htmlspecialchars((string) $appeal['created_at'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        </div>

                        <div class="admin-appeal-contact">
                            <div class="admin-appeal-contact__item">
                                <span class="admin-appeal-contact__label">Заявитель</span>
                                <span class="admin-appeal-contact__value"><?php echo htmlspecialchars((string) $appeal['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <div class="admin-appeal-contact__item">
                                <span class="admin-appeal-contact__label">Телефон</span>
                                <a class="admin-appeal-contact__value admin-appeal-contact__link" href="tel:<?php echo htmlspecialchars(preg_replace('/[^\d+]/', '', (string) $appeal['phone']), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars((string) $appeal['phone'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </div>
                            <div class="admin-appeal-contact__item">
                                <span class="admin-appeal-contact__label">E-mail</span>
                                <a class="admin-appeal-contact__value admin-appeal-contact__link" href="mailto:<?php echo htmlspecialchars((string) ($appeal['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars((string) ($appeal['email'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </div>
                        </div>

                        <div class="admin-appeal-brief__body">
                            <?php if ($descParts['has_split'] && ($descParts['services_block'] !== '' || $descParts['user_text'] !== '')): ?>
                                <?php if ($descParts['services_block'] !== ''): ?>
                                    <?php
                                    $admSvcTitle = (strpos($descParts['services_block'], 'Тема по') === 0)
                                        ? 'Классификация'
                                        : 'Заказ калькулятора';
                                    ?>
                                    <div class="admin-appeal-block">
                                        <div class="admin-appeal-block__label"><?php echo htmlspecialchars($admSvcTitle, ENT_QUOTES, 'UTF-8'); ?></div>
                                        <?php if (strpos($descParts['services_block'], "\n") !== false): ?>
                                            <ul class="admin-appeal-block__list">
                                                <?php
                                                foreach (preg_split('/\R/u', $descParts['services_block']) as $ln) {
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
                                            <p class="admin-appeal-block__text"><?php echo nl2br(htmlspecialchars($descParts['services_block'], ENT_QUOTES, 'UTF-8')); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($descParts['user_text'] !== ''): ?>
                                    <div class="admin-appeal-block admin-appeal-block--story">
                                        <div class="admin-appeal-block__label">Описание ситуации</div>
                                        <p class="admin-appeal-block__text"><?php echo nl2br(htmlspecialchars($descParts['user_text'], ENT_QUOTES, 'UTF-8')); ?></p>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="admin-appeal-block admin-appeal-block--story">
                                    <div class="admin-appeal-block__label">Текст обращения</div>
                                    <p class="admin-appeal-block__text"><?php echo nl2br(htmlspecialchars((string) $appeal['description'], ENT_QUOTES, 'UTF-8')); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="admin-appeal-files">
                            <?php if ($appeal['attachment_path']): ?>
                                <a class="admin-appeal-file-pill" href="../<?php echo htmlspecialchars($appeal['attachment_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                    Файл от клиента
                                </a>
                            <?php endif; ?>
                            <?php if ($appeal['generated_doc_path']): ?>
                                <a class="admin-appeal-file-pill admin-appeal-file-pill--doc" href="../<?php echo htmlspecialchars($appeal['generated_doc_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                    Документ для клиента
                                </a>
                            <?php endif; ?>
                            <?php if (!$appeal['attachment_path'] && !$appeal['generated_doc_path']): ?>
                                <span class="admin-appeal-files__empty">Нет прикреплённых файлов</span>
                            <?php endif; ?>
                        </div>
                    </section>

                    <?php if ($isAdminUser): ?>
                    <section class="appeal-card">
                        <h3>Комментарии пользователя (после архивации)</h3>
                        <p class="request__hint" style="margin-top:0;">
                            Пользователь может оставить отзыв, когда обращение завершено или отклонено. После одобрения текст появляется среди отзывов на странице «Услуги». Модерация — здесь или в разделе
                            <a href="reviews.php">«Отзывы»</a>.
                        </p>
                        <?php if (!$userArchiveComments): ?>
                            <p class="request__hint">Пока нет таких комментариев.</p>
                        <?php else: ?>
                            <?php foreach ($userArchiveComments as $uac): ?>
                                <?php
                                $uacStatus = (string) ($uac['status'] ?? '');
                                $uacId = (int) ($uac['id'] ?? 0);
                                ?>
                                <div class="appeal-archive-comment<?php
                                    echo $uacStatus === 'pending' ? ' appeal-archive-comment--pending' : '';
                                    echo $uacStatus === 'approved' ? ' appeal-archive-comment--approved' : '';
                                    echo $uacStatus === 'rejected' ? ' appeal-archive-comment--rejected' : '';
                                ?>" style="margin-bottom:12px;">
                                    <div class="appeal-archive-comment__meta">
                                        <?php echo htmlspecialchars((string) ($uac['user_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                        · <?php echo htmlspecialchars(review_stars_text(review_normalize_stars($uac['rating'] ?? 5)), ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if (!empty($uac['is_anonymous'])): ?>
                                            · <span class="appeal-archive-comment__anon">анонимно на сайте</span>
                                        <?php endif; ?>
                                        · <?php echo htmlspecialchars((string) ($uac['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if ($uacStatus === 'approved' && !empty($uac['calc_service_id'])): ?>
                                            · раздел: <?php echo htmlspecialchars((string) $uac['calc_service_id'], ENT_QUOTES, 'UTF-8'); ?>
                                        <?php endif; ?>
                                    </div>
                                    <p class="appeal-card__text" style="margin:0 0 10px;"><?php echo nl2br(htmlspecialchars((string) ($uac['body'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></p>
                                    <?php if ($uacStatus === 'pending'): ?>
                                        <form method="post" class="btn-row" style="flex-wrap:wrap; gap:8px; align-items:flex-end;">
                                            <input type="hidden" name="moderate_archive_comment" value="1" />
                                            <input type="hidden" name="comment_id" value="<?php echo $uacId; ?>" />
                                            <input type="hidden" name="moderation_action" value="approve" />
                                            <div class="field" style="margin:0; min-width:220px;">
                                                <label for="calc_service_id_<?php echo $uacId; ?>">Раздел на «Услуги»</label>
                                                <select id="calc_service_id_<?php echo $uacId; ?>" name="calc_service_id" required>
                                                    <?php foreach ($calcSvcOptions as $opt): ?>
                                                        <option value="<?php echo htmlspecialchars($opt['id'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $opt['id'] === $defaultSvcGuess ? ' selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($opt['label'], ENT_QUOTES, 'UTF-8'); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn--primary">
                                                Опубликовать в отзывах
                                            </button>
                                        </form>
                                        <form method="post" style="margin-top:8px;" onsubmit="return confirm('Отклонить этот комментарий?');">
                                            <input type="hidden" name="moderate_archive_comment" value="1" />
                                            <input type="hidden" name="comment_id" value="<?php echo $uacId; ?>" />
                                            <input type="hidden" name="moderation_action" value="reject" />
                                            <button type="submit" class="btn btn--ghost">Отклонить</button>
                                        </form>
                                    <?php else: ?>
                                        <p class="request__hint" style="margin:0;">
                                            <?php echo $uacStatus === 'approved' ? 'Опубликовано на сайте.' : 'Отклонено.'; ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </section>
                    <?php endif; ?>

                    <section class="appeal-card appeal-card--chat">
                        <h3>Чат по обращению</h3>
                        <p class="appeal-chat-intro">Переписка в реальном времени — у клиента в личном кабинете сообщения появляются сразу.</p>
                        <div class="appeal-chat-shell">
                            <div id="chatScroll" class="appeal-chat-scroll">
                                <div id="chatEmpty" class="appeal-chat-empty" role="status">
                                    <div class="appeal-chat-empty__bubble" aria-hidden="true"></div>
                                    <p class="appeal-chat-empty__title">Пока нет сообщений</p>
                                    <p class="appeal-chat-empty__text">Напишите первым — ответ появится здесь. У клиента отображается так же.</p>
                                </div>
                                <div id="chatStream" class="appeal-chat-stream" aria-live="polite"></div>
                            </div>
                        </div>
                        <form id="chatForm" class="appeal-chat-form">
                            <div class="field" style="margin-bottom:0;">
                                <label for="chatMessage">Новое сообщение</label>
                                <textarea id="chatMessage" rows="2" placeholder="Текст для клиента…"></textarea>
                            </div>
                            <button type="submit" class="btn btn--secondary appeal-chat-form__submit">
                                Отправить
                            </button>
                        </form>
                    </section>
                </div>

                <aside class="request__sidebar request__sidebar--stack">
                    <section class="appeal-card">
                        <form method="post">
                            <h3>Сложность обращения</h3>
                            <div class="field">
                                <label for="difficulty">Тип запроса</label>
                                <select id="difficulty" name="difficulty">
                                    <option value=""<?php echo empty($appeal['difficulty']) ? ' selected' : ''; ?>>Не указана</option>
                                    <option value="easy"<?php echo ($appeal['difficulty'] ?? '') === 'easy' ? ' selected' : ''; ?>>Лёгкий</option>
                                    <option value="medium"<?php echo ($appeal['difficulty'] ?? '') === 'medium' ? ' selected' : ''; ?>>Средний</option>
                                    <option value="hard"<?php echo ($appeal['difficulty'] ?? '') === 'hard' ? ' selected' : ''; ?>>Сложный</option>
                                </select>
                            </div>
                            <div class="btn-row">
                                <button type="submit" name="change_difficulty" value="1" class="btn btn--secondary">
                                    Сохранить сложность
                                </button>
                            </div>
                        </form>
                    </section>

                    <section class="appeal-card">
                        <form method="post">
                            <h3>Изменение статуса</h3>
                            <div class="field">
                                <label for="status">Новый статус</label>
                                <select id="status" name="status" required>
                                    <option value="accepted">Принято</option>
                                    <option value="processing">В работе</option>
                                    <option value="answered">Ответ сформирован</option>
                                    <option value="completed">Завершено</option>
                                    <option value="rejected">Отклонено</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="status_comment">Комментарий к статусу (отобразится пользователю)</label>
                                <textarea id="status_comment" name="status_comment" rows="2"></textarea>
                            </div>
                            <div class="btn-row">
                                <button type="submit" name="change_status" value="1" class="btn btn--secondary">
                                    Сохранить статус
                                </button>
                            </div>
                        </form>
                    </section>

                    <section class="appeal-card">
                        <form method="post" enctype="multipart/form-data">
                            <h3>Прикрепить готовый документ</h3>
                            <div class="field">
                                <span class="field__label" id="doc-label">Файл (DOC, DOCX, PDF, до 5 МБ)</span>
                                <div class="file-upload" role="group" aria-labelledby="doc-label">
                                    <input
                                        type="file"
                                        id="doc"
                                        name="doc"
                                        class="file-upload__input"
                                        accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                    />
                                    <label for="doc" class="file-upload__btn">Выбрать файл</label>
                                    <span class="file-upload__name" data-empty="Файл не выбран">Файл не выбран</span>
                                </div>
                            </div>
                            <div class="btn-row">
                                <button type="submit" name="upload_doc" value="1" class="btn btn--secondary">
                                    Загрузить документ
                                </button>
                            </div>
                        </form>
                    </section>

                    <section class="appeal-card">
                        <h3>История статусов</h3>
                        <ol class="request__steps">
                            <?php if (!$statuses): ?>
                                <li>Пока нет статусов.</li>
                            <?php else: ?>
                                <?php foreach ($statuses as $s): ?>
                                    <li>
                                        <strong><?php echo htmlspecialchars(status_title_admin($s['status']), ENT_QUOTES, 'UTF-8'); ?></strong>
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
                        <h3>Комментарии администратора</h3>
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

                    <section class="appeal-card">
                        <form method="post">
                            <h3>Новый комментарий</h3>
                            <div class="field">
                                <label for="admin_comment">Комментарий для пользователя</label>
                                <textarea id="admin_comment" name="admin_comment" rows="3"></textarea>
                            </div>
                            <div class="btn-row">
                                <button type="submit" name="add_comment" value="1" class="btn btn--secondary">
                                    Добавить комментарий
                                </button>
                            </div>
                        </form>
                    </section>

                    <div class="appeal-card appeal-card--actions">
                        <a href="appeals.php" class="btn btn--ghost">К списку обращений</a>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    <?php $footerPrefix = '../'; require __DIR__ . '/../includes/footer.php'; ?>

    <script>
        window.APPEAL_CHAT_CONFIG = {
            appealId: <?php echo (int) $appeal['id']; ?>,
            chatRole: 'admin',
            tokenUrl: '../appeal_chat_ws_token.php',
            historyUrl: '../appeal_chat.php',
            labels: { user: 'Клиент', admin: <?php echo $isAdminUser ? "'Вы (админ)'" : "'Вы (сотрудник)'"; ?> }
        };
    </script>
    <script src="../script.js"></script>
    <script src="../js/appeal-chat.js"></script>
</body>
</html>

