<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT id, name, phone, email FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    header('Location: logout.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        if ($name === '') {
            $error = 'ФИО не может быть пустым.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Некорректный e-mail.';
        } else {
            $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
            $stmt->execute([$name, $email, $userId]);
            $success = 'Данные профиля обновлены.';
            $user['name'] = $name;
            $user['email'] = $email;
        }
    } elseif (isset($_POST['change_password'])) {
        $old = $_POST['old_password'] ?? '';
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($old, $row['password_hash'])) {
            $error = 'Текущий пароль указан неверно.';
        } elseif ($password === '' || $password2 === '') {
            $error = 'Введите новый пароль и его подтверждение.';
        } elseif ($password !== $password2) {
            $error = 'Новые пароли не совпадают.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->execute([$hash, $userId]);
            $success = 'Пароль успешно изменён.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Профиль — Курганский областной союз потребителей</title>
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
                <h2>Профиль</h2>
                <p>Здесь вы можете отредактировать свои данные и сменить пароль.</p>
            </div>

            <?php if ($error): ?>
                <div class="auth-error" style="max-width:640px;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php elseif ($success): ?>
                <div class="auth-error" style="max-width:640px; background:#dcfce7; color:#166534;">
                    <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <div class="request" style="margin-bottom:24px;">
                <form class="request__form" method="post">
                    <h3>Личные данные</h3>
                    <input type="hidden" name="update_profile" value="1" />
                    <div class="field">
                        <label for="name">ФИО</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>" required />
                    </div>
                    <div class="field">
                        <label for="phone">Телефон (логин)</label>
                        <input type="tel" id="phone" value="<?php echo htmlspecialchars($user['phone'], ENT_QUOTES, 'UTF-8'); ?>" disabled />
                    </div>
                    <div class="field">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" />
                    </div>
                    <button type="submit" class="btn btn--primary">
                        Сохранить
                    </button>
                </form>

                <aside class="request__sidebar">
                    <h3>Смена пароля</h3>
                    <form method="post" class="request__form">
                        <input type="hidden" name="change_password" value="1" />
                        <div class="field">
                            <label for="old_password">Текущий пароль</label>
                            <input type="password" id="old_password" name="old_password" required />
                        </div>
                        <div class="field">
                            <label for="password">Новый пароль</label>
                            <input type="password" id="password" name="password" required />
                        </div>
                        <div class="field">
                            <label for="password2">Повторите новый пароль</label>
                            <input type="password" id="password2" name="password2" required />
                        </div>
                        <button type="submit" class="btn btn--secondary" style="margin-top: 8px;">
                            Изменить пароль
                        </button>
                    </form>
                </aside>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/includes/footer.php'; ?>

    <script src="script.js"></script>
</body>
</html>

