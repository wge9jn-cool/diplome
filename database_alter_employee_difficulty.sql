ALTER TABLE users
    MODIFY role ENUM('client', 'admin', 'employee') NOT NULL DEFAULT 'client';

ALTER TABLE appeals
    ADD COLUMN difficulty ENUM('easy', 'medium', 'hard') NULL DEFAULT NULL AFTER topic;
