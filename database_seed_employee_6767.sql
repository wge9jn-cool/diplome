INSERT INTO users (name, phone, email, password_hash, is_phone_confirmed, role, is_blocked, created_at)
VALUES (
    'Сотрудник 6767',
    '6767',
    'employee6767@kosp.local',
    '$2y$10$bU/tXeezniVwEzH38gRePeDbCZu5XqAspIsQsb5hFtrj.YnZg.57K',
    1,
    'employee',
    0,
    NOW()
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    email = VALUES(email),
    password_hash = VALUES(password_hash),
    is_phone_confirmed = 1,
    role = 'employee',
    is_blocked = 0;
