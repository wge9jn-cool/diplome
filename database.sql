CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_phone_confirmed TINYINT(1) NOT NULL DEFAULT 0,
    role ENUM('client', 'admin', 'employee') NOT NULL DEFAULT 'client',
    is_blocked TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- старый функционал заявок через requests можно оставить для совместимости
CREATE TABLE requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    service VARCHAR(50) NOT NULL,
    sum INT UNSIGNED NOT NULL DEFAULT 0,
    comment TEXT NULL,
    payment_method VARCHAR(20) NOT NULL DEFAULT 'yookassa',
    status VARCHAR(100) NOT NULL DEFAULT 'Новое',
    is_paid TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_requests_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- обращения (апелляции) по ТЗ
CREATE TABLE appeals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    topic ENUM(
        'bad_product',
        'delay',
        'warranty_refusal',
        'housing',
        'other'
    ) NOT NULL,
    difficulty ENUM('easy', 'medium', 'hard') NULL DEFAULT NULL,
    description TEXT NOT NULL,
    attachment_path VARCHAR(255) NULL,
    generated_doc_path VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_appeals_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE appeal_statuses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appeal_id INT UNSIGNED NOT NULL,
    status ENUM('accepted', 'processing', 'answered', 'completed', 'rejected') NOT NULL,
    comment VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_status_appeal FOREIGN KEY (appeal_id) REFERENCES appeals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE appeal_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appeal_id INT UNSIGNED NOT NULL,
    admin_id INT UNSIGNED NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comments_appeal FOREIGN KEY (appeal_id) REFERENCES appeals(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE news (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    body TEXT NOT NULL,
    image VARCHAR(255) NULL,
    published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Сообщения чата по обращению (клиент ↔ админ)
CREATE TABLE appeal_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appeal_id INT UNSIGNED NOT NULL,
    sender_type ENUM('user','admin') NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_msg_appeal FOREIGN KEY (appeal_id) REFERENCES appeals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE phone_verification_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_codes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

