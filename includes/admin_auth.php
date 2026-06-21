<?php
declare(strict_types=1);

function admin_ensure_role(PDO $pdo): void
{
    if (!isset($_SESSION['admin_id'])) {
        return;
    }
    if (isset($_SESSION['admin_role'])) {
        return;
    }
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['admin_id']]);
    $row = $stmt->fetch();
    if ($row && in_array($row['role'], ['admin', 'employee'], true)) {
        $_SESSION['admin_role'] = $row['role'];
        return;
    }
    unset($_SESSION['admin_id'], $_SESSION['admin_role']);
}

function admin_is_logged_in(): bool
{
    return isset($_SESSION['admin_id'])
        && isset($_SESSION['admin_role'])
        && in_array($_SESSION['admin_role'], ['admin', 'employee'], true);
}

function admin_is_admin(): bool
{
    return admin_is_logged_in() && $_SESSION['admin_role'] === 'admin';
}

function admin_require_staff(): void
{
    if (!admin_is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function admin_require_admin(): void
{
    admin_require_staff();
    if (!admin_is_admin()) {
        header('Location: appeals.php');
        exit;
    }
}

function admin_staff_id(): int
{
    return (int) $_SESSION['admin_id'];
}

function admin_home_href(): string
{
    return admin_is_admin() ? 'index.php' : 'appeals.php';
}

function admin_role_label(string $role): string
{
    $map = [
        'client' => 'Клиент',
        'admin' => 'Администратор',
        'employee' => 'Сотрудник',
    ];
    return $map[$role] ?? $role;
}

function appeal_difficulty_label(?string $code): string
{
    if ($code === null || $code === '') {
        return '—';
    }
    $map = [
        'easy' => 'Лёгкий',
        'medium' => 'Средний',
        'hard' => 'Сложный',
    ];
    return $map[$code] ?? $code;
}
