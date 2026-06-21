<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/sms.php';

$error = '';
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = preg_replace('/\D+/', '', $_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    $agreePdn = isset($_POST['agree_pdn']);

    if ($name === '' || $phone === '' || $password === '' || $password2 === '') {
        $error = 'Заполните все обязательные поля (ФИО, телефон, пароль).';
    } elseif (!$agreePdn) {
        $error = 'Необходимо подтвердить согласие на обработку персональных данных.';
    } elseif (strlen($phone) < 10 || strlen($phone) > 15) {
        $error = 'Укажите корректный номер телефона.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный e-mail.';
    } elseif ($password !== $password2) {
        $error = 'Пароли не совпадают.';
    } else {
        // проверяем телефон:
        // если есть подтверждённый пользователь — не даём регистрироваться,
        // если телефон есть, но НЕ подтверждён — переиспользуем запись
        $stmt = $pdo->prepare('SELECT id, is_phone_confirmed FROM users WHERE phone = ? LIMIT 1');
        $stmt->execute([$phone]);
        $existing = $stmt->fetch();

        if ($existing && (int)$existing['is_phone_confirmed'] === 1) {
            $error = 'Пользователь с таким номером телефона уже зарегистрирован.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            if ($existing) {
                $userId = (int)$existing['id'];
                // обновляем незавершённый профиль, но телефон заново помечаем как неподтверждённый
                $stmt = $pdo->prepare('
                    UPDATE users
                    SET name = ?, email = ?, password_hash = ?, is_phone_confirmed = 0, created_at = NOW()
                    WHERE id = ?
                ');
                $stmt->execute([$name, $email, $hash, $userId]);
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO users (name, phone, email, password_hash, is_phone_confirmed, created_at)
                    VALUES (?, ?, ?, ?, 0, NOW())
                ');
                $stmt->execute([$name, $phone, $email, $hash]);
                $userId = (int)$pdo->lastInsertId();
            }

            // отправляем SMS-код для подтверждения номера
            $code = random_int(100000, 999999);
            $expiresAt = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');
            $stmt = $pdo->prepare('INSERT INTO phone_verification_codes (user_id, code, expires_at) VALUES (?, ?, ?)');
            $stmt->execute([$userId, (string) $code, $expiresAt]);

            $smsText = "Код подтверждения КОСП: {$code}";
            if (!sendSms($phone, $smsText)) {
                // если не удалось отправить, оставляем код на странице для отладки
                $_SESSION['debug_last_code'] = $code;
                $_SESSION['sms_last_error'] = sms_last_error();
            } else {
                unset($_SESSION['debug_last_code'], $_SESSION['sms_last_error']);
            }

            // переводим пользователя на страницу ввода SMS-кода
            unset($_SESSION['user_id']);
            $_SESSION['pending_user_id'] = $userId;
            header('Location: verify_phone.php');
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
    <title>Регистрация — Курганский областной союз потребителей</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <h1>Регистрация</h1>
            <?php if ($error): ?>
                <div class="auth-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="field">
                    <label for="name">ФИО</label>
                    <input type="text" id="name" name="name" required />
                </div>
                <div class="field">
                    <label for="phone">Номер телефона (логин)</label>
                    <input type="tel" id="phone" name="phone" placeholder="+7..." required />
                </div>
                <div class="field">
                    <label for="email">E-mail (необязательно)</label>
                    <input type="email" id="email" name="email" />
                </div>
                <div class="field">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required />
                </div>
                <div class="field">
                    <label for="password2">Повторите пароль</label>
                    <input type="password" id="password2" name="password2" required />
                </div>
                <label class="checkbox" style="margin-top: 8px; align-items: flex-start;">
                    <input type="checkbox" name="agree_pdn" value="1" required />
                    <span>Я согласен(на) на обработку персональных данных</span>
                </label>
                <button type="submit" class="btn btn--primary" style="width: 100%; margin-top: 8px;">
                    Зарегистрироваться
                </button>
                <p class="auth-note">
                    Уже есть аккаунт? <a href="login.php">Войти</a>
                </p>
                <p class="auth-note">
                    <a href="index.php">Вернуться на главную</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>

