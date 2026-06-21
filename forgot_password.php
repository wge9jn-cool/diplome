<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/sms.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = preg_replace('/\D+/', '', $_POST['phone'] ?? '');

    if ($phone === '') {
        $error = 'Укажите номер телефона.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = ? LIMIT 1');
        $stmt->execute([$phone]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Пользователь с таким номером не найден.';
        } else {
            $userId = (int) $user['id'];
            $code = random_int(100000, 999999);
            $expiresAt = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');
            $stmt = $pdo->prepare('INSERT INTO phone_verification_codes (user_id, code, expires_at) VALUES (?, ?, ?)');
            $stmt->execute([$userId, (string) $code, $expiresAt]);

            // отправляем код через SMS Aero
            $smsText = "Код для смены пароля КОСП: {$code}";
            if (!sendSms($phone, $smsText)) {
                // если не удалось отправить, оставляем код на странице для отладки
                $_SESSION['reset_debug_code'] = $code;
                $_SESSION['sms_last_error'] = sms_last_error();
            } else {
                unset($_SESSION['reset_debug_code']);
                unset($_SESSION['sms_last_error']);
            }

            $_SESSION['reset_user_id'] = $userId;

            header('Location: reset_password.php');
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
    <title>Восстановление пароля — Курганский областной союз потребителей</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <h1>Восстановление пароля</h1>
            <p class="auth-note">
                Укажите номер телефона, который вы использовали при регистрации. На него будет отправлен код для смены пароля.
            </p>
            <?php if ($error): ?>
                <div class="auth-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="field">
                    <label for="phone">Номер телефона</label>
                    <input type="tel" id="phone" name="phone" required />
                </div>
                <button type="submit" class="btn btn--primary" style="width: 100%; margin-top: 8px;">
                    Отправить код
                </button>
                <p class="auth-note">
                    <a href="login.php">Вернуться ко входу</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>

