<?php
session_start();
require_once __DIR__ . '/db.php';

function contacts_tel_href(string $phone): string
{
    $digits = preg_replace('/[^\d+]/u', '', $phone);

    return $digits !== '' ? 'tel:' . $digits : '#';
}

$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT id, name FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;
}

// базовые значения по умолчанию
$defaults = [
    'phone_main' => '+7 (3522) 650-191',
    'phone_expert' => '+7 (3522) 241-720',
    'email_main' => 'kosp1994@mail.ru',
    'address' => 'г. Курган, ул. М.-Горького, 209-23',
    'vk_link' => 'https://vk.com/',
];

$settings = $defaults;
$stmt = $pdo->query('SELECT key_name, value FROM settings');
foreach ($stmt->fetchAll() as $row) {
    if (array_key_exists($row['key_name'], $settings)) {
        $settings[$row['key_name']] = $row['value'];
    }
}

$contactsCards = [
    [
        'mod' => 'contacts-card--accent',
        'kicker' => 'Приёмная',
        'title' => 'Общий телефон',
        'body_html' => '<a class="contacts-card__link" href="' . htmlspecialchars(contacts_tel_href($settings['phone_main']), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($settings['phone_main'], ENT_QUOTES, 'UTF-8') . '</a>',
        'hint' => 'Пн–пт, консультации и запись',
    ],
    [
        'mod' => '',
        'kicker' => 'Эксперт',
        'title' => 'Консультант по сложным вопросам',
        'body_html' => '<a class="contacts-card__link" href="' . htmlspecialchars(contacts_tel_href($settings['phone_expert']), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($settings['phone_expert'], ENT_QUOTES, 'UTF-8') . '</a>',
        'hint' => 'По договорённости',
    ],
    [
        'mod' => '',
        'kicker' => 'Электронная почта',
        'title' => 'E-mail',
        'body_html' => '<a class="contacts-card__link" href="mailto:' . htmlspecialchars($settings['email_main'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($settings['email_main'], ENT_QUOTES, 'UTF-8') . '</a>',
        'hint' => 'Ответ в течение рабочего дня',
    ],
    [
        'mod' => '',
        'kicker' => 'Офис',
        'title' => 'Адрес',
        'body_html' => '<span class="contacts-card__text">' . htmlspecialchars($settings['address'], ENT_QUOTES, 'UTF-8') . '</span>',
        'hint' => 'Предварительный звонок приветствуется',
    ],
    [
        'mod' => 'contacts-card--full',
        'kicker' => 'Соцсети',
        'title' => 'ВКонтакте',
        'body_html' => '<a class="contacts-card__link" href="' . htmlspecialchars($settings['vk_link'], ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">Официальная группа</a>',
        'hint' => 'Новости и полезные материалы',
    ],
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Контакты — Курганский областной союз потребителей</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <?php
    $headerActive = 'contacts';
    require __DIR__ . '/includes/header.php';
    ?>

    <div class="page-hero">
        <div class="container">
            <p class="page-hero__kicker">КОСП — Курган</p>
            <h1 class="page-hero__title">Контакты</h1>
            <p class="page-hero__sub">Свяжитесь с нами удобным способом — подскажем по защите прав потребителей и запишем на консультацию.</p>
        </div>
    </div>

    <main class="section section--light contacts-section">
        <div class="container contacts-page">
            <div class="contacts-layout">
                <div class="contacts-main">
                    <p class="contacts-lead">
                        Курганский областной союз потребителей принимает обращения по телефону, почте и в офисе. Ниже — основные каналы; карта поможет добраться до приёмной.
                    </p>
                    <div class="contacts-cards" role="list">
                        <?php foreach ($contactsCards as $card): ?>
                            <article class="contacts-card <?php echo htmlspecialchars($card['mod'], ENT_QUOTES, 'UTF-8'); ?>" role="listitem">
                                <p class="contacts-card__kicker"><?php echo htmlspecialchars($card['kicker'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <h3 class="contacts-card__title"><?php echo htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <div class="contacts-card__body"><?php echo $card['body_html']; ?></div>
                                <?php if ($card['hint'] !== ''): ?>
                                    <p class="contacts-card__hint"><?php echo htmlspecialchars($card['hint'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <div class="contacts-footnote">
                        <strong>Режим работы.</strong> Уточняйте по телефону приёмной — график может меняться в праздничные дни. Онлайн-заявки и калькулятор на сайте доступны круглосуточно.
                    </div>
                </div>
                <aside class="contacts-map-col" aria-label="Карта проезда">
                    <div class="contacts-map-panel">
                        <h2 class="contacts-map-panel__title">Как нас найти</h2>
                        <p class="contacts-map-panel__lead"><?php echo htmlspecialchars($settings['address'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <div class="contacts-map-panel__frame-wrap">
                            <iframe
                                class="contacts-map-panel__frame"
                                src="<?php echo htmlspecialchars(YANDEX_MAP_OFFICE_WIDGET, ENT_QUOTES, 'UTF-8'); ?>"
                                width="560"
                                height="360"
                                allowfullscreen
                                title="Карта: офис КОСП, <?php echo htmlspecialchars($settings['address'], ENT_QUOTES, 'UTF-8'); ?>"
                                referrerpolicy="no-referrer-when-downgrade"
                            ></iframe>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/includes/footer.php'; ?>

    <script src="script.js"></script>
</body>
</html>
