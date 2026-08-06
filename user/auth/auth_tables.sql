-- Run this in phpMyAdmin

USE paulines_kitchen;

ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) AFTER email;
ALTER TABLE users ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) AFTER id;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) AFTER first_name;

CREATE TABLE IF NOT EXISTS password_resets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(100) NOT NULL UNIQUE,
    token      VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL
);
