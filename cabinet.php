<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/appeal_topic_from_calc.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

// данные пользователя
$stmt = $pdo->prepare('SELECT id, name, phone, email FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    header('Location: logout.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, created_at, is_paid, payment_method, appeal_topic_preset, appeal_service_summary, comment FROM requests WHERE user_id = ? AND service = 'calculator' ORDER BY id DESC LIMIT 1");
$stmt->execute([$userId]);
$cabinetLatestCalc = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

$cabinetOrderComplete = false;
$cabinetHasAppealForLatestOrder = false;
if (is_array($cabinetLatestCalc)) {
    $cabinetIsPaid = (int) ($cabinetLatestCalc['is_paid'] ?? 0) === 1;
    $cabinetPaymentLater = ($cabinetLatestCalc['payment_method'] ?? '') === 'later';
    $cabinetOrderComplete = $cabinetIsPaid || $cabinetPaymentLater;
    if ($cabinetOrderComplete) {
        $stmt = $pdo->prepare('SELECT 1 FROM appeals WHERE user_id = ? AND created_at >= ? LIMIT 1');
        $stmt->execute([$userId, $cabinetLatestCalc['created_at']]);
        $cabinetHasAppealForLatestOrder = (bool) $stmt->fetchColumn();
    }
}
$cabinetNeedsAppealStep = $cabinetOrderComplete && !$cabinetHasAppealForLatestOrder;

$cabinetAppealTopicPreset = 'other';
$cabinetAppealServiceSummary = '';
if ($cabinetOrderComplete && is_array($cabinetLatestCalc)) {
    $rawT = $cabinetLatestCalc['appeal_topic_preset'] ?? '';
    if (is_string($rawT) && $rawT !== '') {
        $cabinetAppealTopicPreset = appeal_topic_normalize($rawT);
    }
    $cabinetAppealServiceSummary = appeal_service_summary_for_display($cabinetLatestCalc);
}

if (isset($_GET['open_appeal']) && (string) $_GET['open_appeal'] === '1') {
    if ($cabinetNeedsAppealStep) {
        header('Location: cabinet.php#cabinet-appeal');
        exit;
    }
    header('Location: cabinet.php');
    exit;
}

// обработка создания нового обращения
$error = '';

// удаление обращения пользователем
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_appeal'])) {
    $appealId = isset($_POST['appeal_id']) ? (int) $_POST['appeal_id'] : 0;
    if ($appealId > 0) {
        $stmt = $pdo->prepare('DELETE FROM appeals WHERE id = ? AND user_id = ?');
        $stmt->execute([$appealId, $userId]);
    }
    header('Location: cabinet.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_appeal'])) {
    if (!$cabinetNeedsAppealStep) {
        if (!$cabinetOrderComplete) {
            $error = 'Обращение можно отправить после оплаты заказа через ЮKassa или после выбора «Оплатить позже» в калькуляторе. Завершите оформление заказа ниже.';
        } else {
            $error = 'Обращение по этому заказу уже отправлено. Список обращений — ниже; новый заказ оформите в калькуляторе.';
        }
    } elseif (!isset($_POST['appeal_gate']) || (string) $_POST['appeal_gate'] !== '1') {
        $error = 'Откройте раздел «Обращение» в меню и отправьте форму с этой страницы.';
    } else {
    $topic = appeal_topic_normalize($cabinetAppealTopicPreset);
    $descriptionUser = trim($_POST['description'] ?? '');

    if ($descriptionUser === '') {
        $error = 'Опишите, пожалуйста, проблему.';
    } else {
        $serviceBlock = $cabinetAppealServiceSummary !== ''
            ? "Услуги из заказа калькулятора:\n" . $cabinetAppealServiceSummary . "\n\n"
            : ("Тема по классификатору: " . topic_title($cabinetAppealTopicPreset) . "\n\n");
        $description = $serviceBlock . $descriptionUser;

        $attachmentPath = null;

        if (!empty($_FILES['attachment']['name'])) {
            $file = $_FILES['attachment'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                if ($file['size'] > 5 * 1024 * 1024) {
                    $error = 'Файл слишком большой. Максимальный размер — 5 МБ.';
                } else {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($file['tmp_name']);
                    $allowedMime = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'application/pdf' => 'pdf',
                    ];
                    if (!array_key_exists($mime, $allowedMime)) {
                        $error = 'Разрешены только файлы JPG, PNG или PDF.';
                    } else {
                        $ext = $allowedMime[$mime];
                        $uploadsDir = __DIR__ . '/uploads/appeals';
                        if (!is_dir($uploadsDir)) {
                            mkdir($uploadsDir, 0777, true);
                        }
                        $filename = sprintf('%s_%s.%s', $userId, uniqid('', true), $ext);
                        $target = $uploadsDir . '/' . $filename;
                        if (!move_uploaded_file($file['tmp_name'], $target)) {
                            $error = 'Не удалось сохранить файл.';
                        } else {
                            $attachmentPath = 'uploads/appeals/' . $filename;
                        }
                    }
                }
            } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                $error = 'Ошибка загрузки файла.';
            }
        }

        if ($error === '') {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('INSERT INTO appeals (user_id, topic, description, attachment_path) VALUES (?, ?, ?, ?)');
                $stmt->execute([$userId, $topic, $description, $attachmentPath]);
                $appealId = (int) $pdo->lastInsertId();

                $stmt = $pdo->prepare('INSERT INTO appeal_statuses (appeal_id, status, comment) VALUES (?, ?, ?)');
                $stmt->execute([$appealId, 'accepted', 'Обращение принято к рассмотрению.']);

                $pdo->commit();
                header('Location: cabinet_appeal.php?id=' . $appealId);
                exit;
            } catch (Throwable $e) {
                $pdo->rollBack();
                $error = 'Не удалось сохранить обращение. Попробуйте позже.';
            }
        }
    }
    }
}

// статистика и список обращений
$stmt = $pdo->prepare('
    SELECT a.id, a.topic, a.created_at,
           LEFT(a.description, 4000) AS description_preview,
           (SELECT status FROM appeal_statuses s WHERE s.appeal_id = a.id ORDER BY s.created_at DESC, s.id DESC LIMIT 1) AS last_status
    FROM appeals a
    WHERE a.user_id = ?
    ORDER BY a.created_at DESC
');
$stmt->execute([$userId]);
$allAppeals = $stmt->fetchAll();

$appealsActive = [];
$appealsArchived = [];
foreach ($allAppeals as $row) {
    if (in_array($row['last_status'], ['completed', 'rejected'], true)) {
        $appealsArchived[] = $row;
    } else {
        $appealsActive[] = $row;
    }
}

function topic_title(string $code): string {
    $map = [
        'bad_product' => 'Некачественный товар',
        'delay' => 'Нарушение сроков',
        'warranty_refusal' => 'Отказ в гарантийном ремонте',
        'housing' => 'Услуги ЖКХ',
        'other' => 'Другое',
    ];
    return $map[$code] ?? 'Обращение';
}

function appeal_table_topic_label(array $row): string {
    $fromDesc = appeal_subject_line_from_description(isset($row['description_preview']) ? (string) $row['description_preview'] : null);

    return $fromDesc ?? topic_title((string) ($row['topic'] ?? 'other'));
}

function status_title(?string $code): string {
    $map = [
        'accepted' => 'Принято',
        'processing' => 'В работе',
        'answered' => 'Ответ сформирован',
        'completed' => 'Завершено',
        'rejected' => 'Отклонено',
    ];
    return $code !== null && isset($map[$code]) ? $map[$code] : '—';
}

$cabinetPaidReturn = isset($_GET['paid']) && (string) $_GET['paid'] === '1' && $cabinetNeedsAppealStep;

$cabinetAppealServicesDisplay = $cabinetAppealServiceSummary !== ''
    ? $cabinetAppealServiceSummary
    : topic_title($cabinetAppealTopicPreset);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Личный кабинет — Курганский областной союз потребителей</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <header class="header">
        <div class="container header__inner">
            <?php
            $logoHref = 'index.php';
            require __DIR__ . '/includes/logo.php';
            ?>
            <nav class="nav" aria-label="Меню личного кабинета">
                <a href="index.php" class="nav__link">Главная</a>
                <a href="profile.php" class="nav__link">Профиль</a>
                <a href="logout.php" class="nav__link nav__link--outlined">Выйти</a>
            </nav>
            <button class="header__burger" aria-label="Меню">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <main class="section section--light">
        <div class="container">
            <div class="section__head">
                <h2>Личный кабинет</h2>
                <p>Здравствуйте, <?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>.</p>
            </div>

            <?php if ($error): ?>
                <div class="auth-error" style="max-width:640px;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <section class="cabinet-appeals-strip" aria-label="Обращения к специалисту">
                <div class="cabinet-appeals-strip__media" aria-hidden="true">
                    <img
                        src="assets/cabinet_appeals_illustration.svg"
                        alt=""
                        width="120"
                        height="90"
                        decoding="async"
                        class="cabinet-appeals-strip__art"
                    />
                </div>
                <div class="cabinet-appeals-strip__head">
                    <h3 class="cabinet-appeals-strip__title">Обращения</h3>
                    <p class="cabinet-appeals-strip__hint">Списки открываются в отдельном окне.</p>
                </div>
                <div class="cabinet-appeals-strip__pills">
                    <button type="button" class="cabinet-appeals-pill" data-modal-open="#cabinetAppealsActiveModal">
                        Текущие обращения<?php echo $appealsActive ? ' (' . count($appealsActive) . ')' : ''; ?>
                    </button>
                    <button type="button" class="cabinet-appeals-pill" data-modal-open="#cabinetAppealsArchiveModal">
                        Архив обращений<?php echo $appealsArchived ? ' (' . count($appealsArchived) . ')' : ''; ?>
                    </button>
                </div>
            </section>

            <div class="section__head" style="margin-top:8px;">
                <h2><?php echo $cabinetNeedsAppealStep ? 'Обращение к специалисту' : 'Услуги и оплата'; ?></h2>
                <?php if (!$cabinetNeedsAppealStep && $cabinetLatestCalc !== null && !$cabinetOrderComplete): ?>
                    <p>
                        Последний заказ из калькулятора ещё не оплачен — шаг с текстом обращения к специалисту откроется после успешной оплаты через ЮKassa. Если вы выберете «Оплатить позже», форма обращения откроется сразу после оформления заказа.
                        <a href="requests.php">Мои заявки</a> — перейти к оплате.
                    </p>
                <?php endif; ?>
            </div>

            <?php if ($cabinetPaidReturn): ?>
                <div class="cabinet-paid-banner" role="status">
                    Оплата прошла успешно. Услуги из заказа уже подставлены в форму — допишите подробности ситуации для специалиста.
                </div>
            <?php endif; ?>

            <div class="request cabinet-workbench<?php echo $cabinetNeedsAppealStep ? ' cabinet-workbench--appeal-phase' : ''; ?>" style="margin-bottom:24px;">
                <?php if ($cabinetNeedsAppealStep): ?>
                    <div class="cabinet-workbench-card cabinet-workbench-card--appeal-step" id="cabinet-appeal">
                        <section class="cabinet-step" aria-labelledby="cabinet-step-appeal-title">
                            <div class="cabinet-step__head">
                                <div class="cabinet-step__titlewrap">
                                    <h3 class="cabinet-step__title" id="cabinet-step-appeal-title">Текст обращения</h3>
                                    <p class="cabinet-step__lead">Калькулятор на этом шаге скрыт. Ниже — услуги из вашего заказа; опишите ситуацию для юриста. Это не заменяет и не дублирует автоматически комментарий к заказу в корзине — при необходимости повторите важное здесь.</p>
                                </div>
                            </div>
                            <form class="cabinet-appeal-form" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="create_appeal" value="1" />
                                <input type="hidden" name="appeal_gate" value="1" />
                                <div class="field">
                                    <div class="field__label">Услуги из вашего заказа</div>
                                    <p class="cabinet-appeal-topic-readonly cabinet-appeal-services" id="cabinet-appeal-topic"><?php echo htmlspecialchars($cabinetAppealServicesDisplay, ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p class="request__hint" style="margin-top:4px;">Берётся из последнего завершённого заказа калькулятора (после оплаты или «позже»). После нового заказа список обновится.</p>
                                </div>
                                <div class="field">
                                    <label for="description">Что произошло</label>
                                    <textarea id="description" name="description" rows="12" required placeholder="Опишите ситуацию полностью: что купили или заказали, что пошло не так, что уже предпринимали"></textarea>
                                </div>
                                <div class="field">
                                    <span class="field__label" id="attachment-label">Файл (jpg, png, pdf, до 5 МБ)</span>
                                    <div class="file-upload" role="group" aria-labelledby="attachment-label">
                                        <input
                                            type="file"
                                            id="attachment"
                                            name="attachment"
                                            class="file-upload__input"
                                            accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"
                                        />
                                        <label for="attachment" class="file-upload__btn">Выбрать файл</label>
                                        <span class="file-upload__name" data-empty="Файл не выбран">Файл не выбран</span>
                                    </div>
                                </div>
                                <div class="cabinet-appeal-form__actions">
                                    <button type="submit" class="btn btn--primary">Отправить обращение</button>
                                </div>
                            </form>
                        </section>
                    </div>
                <?php else: ?>
                    <div class="cabinet-workbench-card cabinet-workbench-card--calc-only" id="calculator">
                        <p class="cabinet-paywall-note">
                            <?php if ($cabinetLatestCalc !== null && $cabinetOrderComplete && !$cabinetNeedsAppealStep): ?>
                                Обращение по последнему заказу уже отправлено — ниже можно оформить новый заказ в калькуляторе. Текущие обращения — в списке под калькулятором.
                            <?php elseif ($cabinetLatestCalc !== null && !$cabinetOrderComplete): ?>
                                Шаг с формой обращения к специалисту откроется после <strong>успешной оплаты</strong> последнего заказа (ЮKassa) или сразу после оформления с вариантом <strong>«Оплатить позже»</strong>. Оплату можно завершить в <a href="requests.php">Моих заявках</a> или ниже.
                            <?php else: ?>
                                Сначала оформите заказ в калькуляторе. После оплаты или при выборе «Оплатить позже» откроется отдельный экран только с формой обращения — без калькулятора, чтобы не запутаться в шагах.
                            <?php endif; ?>
                        </p>
                        <?php
                        $calcLayout = 'embed';
                        require __DIR__ . '/includes/calc_section.php';
                        unset($calcLayout);
                        ?>
                    </div>
                <?php endif; ?>
            </div>

            <script>
            (function () {
                function scrollCabinetAnchor() {
                    var h = location.hash;
                    var appeal = document.getElementById('cabinet-appeal');
                    var calc = document.getElementById('calculator');
                    if (h === '#appeal' || h === '#cabinet-appeal') {
                        if (appeal) {
                            requestAnimationFrame(function () {
                                appeal.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            });
                        }
                    } else if (h === '#calculator') {
                        if (calc) {
                            requestAnimationFrame(function () {
                                calc.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            });
                        }
                    }
                }
                window.addEventListener('hashchange', scrollCabinetAnchor);
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', scrollCabinetAnchor);
                } else {
                    scrollCabinetAnchor();
                }
            })();
            </script>
        </div>
    </main>

    <div class="modal" id="cabinetAppealsActiveModal" role="dialog" aria-modal="true" aria-labelledby="cabinetAppealsActiveTitle" aria-hidden="true">
        <div class="modal__overlay" data-modal-close></div>
        <div class="modal__content modal__content--wide">
            <button type="button" class="modal__close" aria-label="Закрыть" data-modal-close>✕</button>
            <h3 id="cabinetAppealsActiveTitle" style="margin:0;">Текущие обращения</h3>
            <div class="modal__body modal__body--tall">
                <p class="request__hint" style="margin:0 0 12px;">Обращения к специалисту союза по вашим заявкам из калькулятора. Статусы обновляются по мере работы.</p>
                <?php if (!$appealsActive): ?>
                    <p style="margin:0;">У вас нет активных обращений.</p>
                <?php else: ?>
                    <div class="requests-table requests-table--cabinet-appeals">
                        <div class="requests-row requests-row--head">
                            <div>№</div>
                            <div>Тема</div>
                            <div>Статус</div>
                            <div>Создано</div>
                            <div></div>
                        </div>
                        <?php foreach ($appealsActive as $a): ?>
                            <div class="requests-row">
                                <div>#<?php echo (int) $a['id']; ?></div>
                                <div><?php echo htmlspecialchars(appeal_table_topic_label($a), ENT_QUOTES, 'UTF-8'); ?></div>
                                <div><?php echo htmlspecialchars(status_title($a['last_status']), ENT_QUOTES, 'UTF-8'); ?></div>
                                <div><?php echo htmlspecialchars($a['created_at'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div>
                                    <a href="cabinet_appeal.php?id=<?php echo (int) $a['id']; ?>" class="btn btn--link" style="margin-right:8px;">Подробнее</a>
                                    <form action="cabinet.php" method="post" style="display:inline;" onsubmit="return confirm('Удалить это обращение без возможности восстановления?');">
                                        <input type="hidden" name="delete_appeal" value="1" />
                                        <input type="hidden" name="appeal_id" value="<?php echo (int) $a['id']; ?>" />
                                        <button type="submit" class="btn btn--ghost" style="padding:4px 10px; font-size:13px;">
                                            Удалить
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal" id="cabinetAppealsArchiveModal" role="dialog" aria-modal="true" aria-labelledby="cabinetAppealsArchiveTitle" aria-hidden="true">
        <div class="modal__overlay" data-modal-close></div>
        <div class="modal__content modal__content--wide">
            <button type="button" class="modal__close" aria-label="Закрыть" data-modal-close>✕</button>
            <h3 id="cabinetAppealsArchiveTitle" style="margin:0;">Архив обращений</h3>
            <div class="modal__body modal__body--tall">
                <p class="request__hint" style="margin:0 0 12px;">Завершённые и отклонённые обращения хранятся здесь для просмотра.</p>
                <?php if (!$appealsArchived): ?>
                    <p style="margin:0;">В архиве пока нет обращений (завершённые и отклонённые обращения попадут сюда автоматически).</p>
                <?php else: ?>
                    <div class="requests-table requests-table--cabinet-appeals">
                        <div class="requests-row requests-row--head">
                            <div>№</div>
                            <div>Тема</div>
                            <div>Статус</div>
                            <div>Создано</div>
                            <div></div>
                        </div>
                        <?php foreach ($appealsArchived as $a): ?>
                            <div class="requests-row">
                                <div>#<?php echo (int) $a['id']; ?></div>
                                <div><?php echo htmlspecialchars(appeal_table_topic_label($a), ENT_QUOTES, 'UTF-8'); ?></div>
                                <div><?php echo htmlspecialchars(status_title($a['last_status']), ENT_QUOTES, 'UTF-8'); ?></div>
                                <div><?php echo htmlspecialchars($a['created_at'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div>
                                    <a href="cabinet_appeal.php?id=<?php echo (int) $a['id']; ?>" class="btn btn--link" style="margin-right:8px;">Подробнее</a>
                                    <form action="cabinet.php" method="post" style="display:inline;" onsubmit="return confirm('Удалить это обращение без возможности восстановления?');">
                                        <input type="hidden" name="delete_appeal" value="1" />
                                        <input type="hidden" name="appeal_id" value="<?php echo (int) $a['id']; ?>" />
                                        <button type="submit" class="btn btn--ghost" style="padding:4px 10px; font-size:13px;">
                                            Удалить
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php require __DIR__ . '/includes/footer.php'; ?>

    <script src="script.js"></script>
</body>
</html>

