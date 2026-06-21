<?php
session_start();
require_once __DIR__ . '/../db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = preg_replace('/\D+/', '', $_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($phone === '' || $password === '') {
        $error = 'Введите номер телефона и пароль.';
    } else {
        $stmt = $pdo->prepare('SELECT id, password_hash, role FROM users WHERE phone = ? LIMIT 1');
        $stmt->execute([$phone]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash']) || !in_array($user['role'], ['admin', 'employee'], true)) {
            $error = 'Неверные данные для входа.';
        } else {
            $_SESSION['admin_id'] = (int) $user['id'];
            $_SESSION['admin_role'] = $user['role'];
            $redirect = $user['role'] === 'employee' ? 'appeals.php' : 'index.php';
            header('Location: ' . $redirect);
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
    <title>Вход в панель управления — КОСП</title>
    <link rel="stylesheet" href="../styles.css" />
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <h1>Панель управления</h1>
            <?php if ($error): ?>
                <div class="auth-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="field">
                    <label for="phone">Номер телефона</label>
                    <input type="tel" id="phone" name="phone" required />
                </div>
                <div class="field">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required />
                </div>
                <button type="submit" class="btn btn--primary" style="width: 100%; margin-top: 8px;">
                    Войти
                </button>
            </form>
        </div>
    </div>
</body>
</html>

