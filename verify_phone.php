<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['pending_user_id'])) {
    header('Location: register.php');
    exit;
}

$userId = (int) $_SESSION['pending_user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codeInput = trim($_POST['code'] ?? '');

    if ($codeInput === '') {
        $error = 'Введите код подтверждения из SMS.';
    } else {
        $stmt = $pdo->prepare('SELECT id, code, expires_at, used FROM phone_verification_codes WHERE user_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row) {
            $error = 'Код подтверждения не найден. Попробуйте зарегистрироваться заново.';
        } elseif ((int) $row['used'] === 1) {
            $error = 'Код уже был использован. При необходимости начните регистрацию заново.';
        } else {
            $now = new DateTime();
            $expiresAt = new DateTime($row['expires_at']);

            if ($now > $expiresAt) {
                $error = 'Срок действия кода истёк. Пройдите регистрацию заново.';
            } elseif ($codeInput !== $row['code']) {
                $error = 'Неверный код подтверждения.';
            } else {
                // помечаем код использованным и подтверждаем телефон (без транзакций, чтобы избежать ошибок)
                $stmt = $pdo->prepare('UPDATE phone_verification_codes SET used = 1 WHERE id = ?');
                $stmt->execute([(int) $row['id']]);

                $stmt = $pdo->prepare('UPDATE users SET is_phone_confirmed = 1 WHERE id = ?');
                $stmt->execute([$userId]);

                unset($_SESSION['pending_user_id']);
                $_SESSION['user_id'] = $userId;
                header('Location: index.php');
                exit;
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
    <title>Подтверждение телефона — Курганский областной союз потребителей</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <h1>Подтверждение телефона</h1>
            <p class="auth-note">
                На указанный при регистрации номер был отправлен SMS-код. Введите его для активации учётной записи.
            </p>
            <?php if (!empty($_SESSION['debug_last_code'])): ?>
                <p class="auth-note">
                    Тестовый код (для отладки, вместо SMS): <strong><?php echo (int) $_SESSION['debug_last_code']; ?></strong>
                </p>
            <?php endif; ?>
            <?php if (!empty($_SESSION['sms_last_error'])): ?>
                <p class="auth-note" style="color:#b91c1c;">
                    Ошибка отправки SMS: <?php echo htmlspecialchars((string)$_SESSION['sms_last_error'], ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="auth-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="field">
                    <label for="code">Код из SMS</label>
                    <input type="text" id="code" name="code" required />
                </div>
                <button type="submit" class="btn btn--primary" style="width: 100%; margin-top: 8px;">
                    Подтвердить
                </button>
                <p class="auth-note">
                    Если SMS не приходит, проверьте правильность номера и при необходимости пройдите регистрацию заново.
                </p>
                <p class="auth-note">
                    <a href="register.php">Вернуться к регистрации</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>

