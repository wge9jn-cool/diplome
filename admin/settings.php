<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

admin_ensure_role($pdo);
admin_require_admin();

$keys = ['phone_main', 'phone_expert', 'email_main', 'address', 'vk_link'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($keys as $key) {
        $value = trim($_POST[$key] ?? '');
        if ($value === '') continue;
        $stmt = $pdo->prepare('INSERT INTO settings (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
        $stmt->execute([$key, $value]);
    }
    header('Location: settings.php');
    exit;
}

$values = array_fill_keys($keys, '');
$stmt = $pdo->query('SELECT key_name, value FROM settings');
foreach ($stmt->fetchAll() as $row) {
    if (in_array($row['key_name'], $keys, true)) {
        $values[$row['key_name']] = $row['value'];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Настройки — Админпанель КОСП</title>
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
                <h2>Основная информация сайта</h2>
            </div>

            <form method="post" style="max-width:480px;">
                <div class="field">
                    <label for="phone_main">Основной телефон</label>
                    <input type="text" id="phone_main" name="phone_main" value="<?php echo htmlspecialchars($values['phone_main'], ENT_QUOTES, 'UTF-8'); ?>" />
                </div>
                <div class="field">
                    <label for="phone_expert">Телефон эксперта‑консультанта</label>
                    <input type="text" id="phone_expert" name="phone_expert" value="<?php echo htmlspecialchars($values['phone_expert'], ENT_QUOTES, 'UTF-8'); ?>" />
                </div>
                <div class="field">
                    <label for="email_main">E-mail</label>
                    <input type="email" id="email_main" name="email_main" value="<?php echo htmlspecialchars($values['email_main'], ENT_QUOTES, 'UTF-8'); ?>" />
                </div>
                <div class="field">
                    <label for="address">Адрес</label>
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($values['address'], ENT_QUOTES, 'UTF-8'); ?>" />
                </div>
                <div class="field">
                    <label for="vk_link">Ссылка на группу ВКонтакте</label>
                    <input type="text" id="vk_link" name="vk_link" value="<?php echo htmlspecialchars($values['vk_link'], ENT_QUOTES, 'UTF-8'); ?>" />
                </div>
                <div class="btn-row">
                    <button type="submit" class="btn btn--primary">Сохранить</button>
                </div>
            </form>
        </div>
    </main>

    <?php $footerPrefix = '../'; require __DIR__ . '/../includes/footer.php'; ?>
    <?php require __DIR__ . '/includes/scripts.php'; ?>
</body>
</html>

