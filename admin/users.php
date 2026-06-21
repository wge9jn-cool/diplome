<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

admin_ensure_role($pdo);
admin_require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
    if ($userId > 0) {
        if (isset($_POST['confirm_phone'])) {
            $stmt = $pdo->prepare('UPDATE users SET is_phone_confirmed = 1 WHERE id = ?');
            $stmt->execute([$userId]);
        } elseif (isset($_POST['block'])) {
            $stmt = $pdo->prepare('UPDATE users SET is_blocked = 1 WHERE id = ?');
            $stmt->execute([$userId]);
        } elseif (isset($_POST['unblock'])) {
            $stmt = $pdo->prepare('UPDATE users SET is_blocked = 0 WHERE id = ?');
            $stmt->execute([$userId]);
        } elseif (isset($_POST['change_role'])) {
            $newRole = (string) ($_POST['role'] ?? '');
            if (in_array($newRole, ['client', 'admin', 'employee'], true)) {
                $stmt = $pdo->prepare('SELECT id, role FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([$userId]);
                $target = $stmt->fetch();
                if ($target && (int) $target['id'] !== admin_staff_id()) {
                    $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
                    $stmt->execute([$newRole, $userId]);
                }
            }
        }
    }
    header('Location: users.php');
    exit;
}

$stmt = $pdo->query('SELECT id, name, phone, email, is_phone_confirmed, is_blocked, role, created_at FROM users ORDER BY created_at DESC');
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Пользователи — Админпанель КОСП</title>
    <link rel="stylesheet" href="../styles.css" />
</head>
<body>
    <header class="header">
        <div class="container header__inner">
            <?php
            $logoBase = '../';
            $logoHref = 'index.php';
            $logoAdmin = true;
            require __DIR__ . '/../includes/logo.php';
            ?>
            <?php require __DIR__ . '/includes/nav.php'; ?>
        </div>
    </header>

    <main class="section section--light">
        <div class="container">
            <div class="section__head">
                <h2>Пользователи</h2>
            </div>

            <?php if (!$users): ?>
                <p>Пользователи отсутствуют.</p>
            <?php else: ?>
                <div class="requests-table">
                    <div class="requests-row requests-row--head">
                        <div>№</div>
                        <div>ФИО</div>
                        <div>Телефон</div>
                        <div>E-mail</div>
                        <div>Телефон</div>
                        <div>Роль</div>
                        <div>Создан</div>
                        <div>Действия</div>
                    </div>
                    <?php foreach ($users as $u): ?>
                        <div class="requests-row">
                            <div>#<?php echo (int) $u['id']; ?></div>
                            <div><?php echo htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><?php echo htmlspecialchars($u['phone'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div>
                                <?php if ((int) $u['is_phone_confirmed'] === 1): ?>
                                    Подтверждён
                                <?php else: ?>
                                    Не подтверждён
                                <?php endif; ?>
                            </div>
                            <div><?php echo htmlspecialchars(admin_role_label((string) $u['role']), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><?php echo htmlspecialchars($u['created_at'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div>
                                <form method="post" style="display:flex; flex-direction:column; gap:4px; margin-bottom:4px;">
                                    <input type="hidden" name="user_id" value="<?php echo (int) $u['id']; ?>" />
                                    <?php if ((int) $u['is_phone_confirmed'] === 0): ?>
                                        <button type="submit" name="confirm_phone" value="1" class="btn btn--secondary" style="font-size:11px; padding:4px 8px;">
                                            Подтвердить телефон
                                        </button>
                                    <?php endif; ?>
                                    <?php if ((int) $u['is_blocked'] === 0): ?>
                                        <button type="submit" name="block" value="1" class="btn btn--secondary" style="font-size:11px; padding:4px 8px;">
                                            Заблокировать
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="unblock" value="1" class="btn btn--secondary" style="font-size:11px; padding:4px 8px;">
                                            Разблокировать
                                        </button>
                                    <?php endif; ?>
                                </form>
                                <?php if ((int) $u['id'] !== admin_staff_id()): ?>
                                    <form method="post" style="display:flex; gap:4px; align-items:center; margin-bottom:4px;">
                                        <input type="hidden" name="user_id" value="<?php echo (int) $u['id']; ?>" />
                                        <select name="role" style="font-size:11px; padding:4px;">
                                            <option value="client"<?php echo $u['role'] === 'client' ? ' selected' : ''; ?>>Клиент</option>
                                            <option value="employee"<?php echo $u['role'] === 'employee' ? ' selected' : ''; ?>>Сотрудник</option>
                                            <option value="admin"<?php echo $u['role'] === 'admin' ? ' selected' : ''; ?>>Администратор</option>
                                        </select>
                                        <button type="submit" name="change_role" value="1" class="btn btn--secondary" style="font-size:11px; padding:4px 8px;">
                                            Роль
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <a href="appeals.php?user_id=<?php echo (int) $u['id']; ?>" class="btn btn--link" style="font-size:11px; padding:0;">
                                    История обращений
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php $footerPrefix = '../'; require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

