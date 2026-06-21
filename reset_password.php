<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['reset_user_id'])) {
    header('Location: forgot_password.php');
    exit;
}

$userId = (int) $_SESSION['reset_user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codeInput = trim($_POST['code'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($codeInput === '' || $password === '' || $password2 === '') {
        $error = 'Заполните все поля.';
    } elseif ($password !== $password2) {
        $error = 'Пароли не совпадают.';
    } else {
        $stmt = $pdo->prepare('SELECT id, code, expires_at, used FROM phone_verification_codes WHERE user_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row) {
            $error = 'Код не найден. Попробуйте запросить его заново.';
        } elseif ((int) $row['used'] === 1) {
            $error = 'Код уже использован. Запросите новый код.';
        } else {
            $now = new DateTime();
            $expiresAt = new DateTime($row['expires_at']);

            if ($now > $expiresAt) {
                $error = 'Срок действия кода истёк. Запросите новый код.';
            } elseif ($codeInput !== $row['code']) {
                $error = 'Неверный код.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $stmt->execute([$hash, $userId]);

                    $stmt = $pdo->prepare('UPDATE phone_verification_codes SET used = 1 WHERE id = ?');
                    $stmt->execute([(int) $row['id']]);

                    $pdo->commit();
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    $error = 'Не удалось сохранить пароль. Попробуйте позже.';
                }

                if ($error === '') {
                    unset($_SESSION['reset_user_id'], $_SESSION['reset_debug_code']);
                    header('Location: login.php');
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Смена пароля — Курганский областной союз потребителей</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <h1>Смена пароля</h1>
            <?php if (!empty($_SESSION['reset_debug_code'])): ?>
                <p class="auth-note">
                    Тестовый код (для отладки, вместо SMS): <strong><?php echo (int) $_SESSION['reset_debug_code']; ?></strong>
                </p>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="auth-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="field">
                    <label for="code">Код подтверждения</label>
                    <input type="text" id="code" name="code" required />
                </div>
                <div class="field">
                    <label for="password">Новый пароль</label>
                    <input type="password" id="password" name="password" required />
                </div>
                <div class="field">
                    <label for="password2">Повторите новый пароль</label>
                    <input type="password" id="password2" name="password2" required />
                </div>
                <button type="submit" class="btn btn--primary" style="width: 100%; margin-top: 8px;">
                    Сохранить пароль
                </button>
                <p class="auth-note">
                    <a href="login.php">Вернуться ко входу</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>

