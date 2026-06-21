<?php
session_start();
require_once __DIR__ . '/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = preg_replace('/\D+/', '', $_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($phone === '' || $password === '') {
        $error = 'Введите номер телефона и пароль.';
    } else {
        $stmt = $pdo->prepare('SELECT id, password_hash, is_phone_confirmed, is_blocked, role FROM users WHERE phone = ? LIMIT 1');
        $stmt->execute([$phone]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Неверный номер телефона или пароль.';
        } elseif (in_array($user['role'], ['admin', 'employee'], true)) {
            $error = 'Для сотрудников и администраторов вход через панель управления (/admin/).';
        } elseif ((int) $user['is_blocked'] === 1) {
            $error = 'Учетная запись заблокирована. Обратитесь в организацию.';
        } elseif ((int) $user['is_phone_confirmed'] === 0) {
            $_SESSION['pending_user_id'] = (int) $user['id'];
            header('Location: verify_phone.php');
            exit;
        } else {
            $_SESSION['user_id'] = $user['id'];
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Вход — Курганский областной союз потребителей</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <h1>Вход</h1>
            <?php if ($error): ?>
                <div class="auth-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="field">
                    <label for="phone">Номер телефона (логин)</label>
                    <input type="tel" id="phone" name="phone" required />
                </div>
                <div class="field">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required />
                </div>
                <button type="submit" class="btn btn--primary" style="width: 100%; margin-top: 8px;">
                    Войти
                </button>
                <p class="auth-note">
                    Нет аккаунта? <a href="register.php">Зарегистрироваться</a>
                </p>
                <p class="auth-note">
                    <a href="forgot_password.php">Забыли пароль?</a>
                </p>
                <p class="auth-note">
                    <a href="index.php">Вернуться на главную</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>

