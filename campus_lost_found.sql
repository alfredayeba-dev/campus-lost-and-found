CREATE DATABASE IF NOT EXISTS campus_lost_found;
USE campus_lost_found;

CREATE TABLE IF NOT EXISTS users (
  user_id            INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  student_id         VARCHAR(50)  NULL,
  first_name         VARCHAR(50)  NOT NULL,
  middle_name        VARCHAR(50)  NULL,
  last_name          VARCHAR(50)  NOT NULL,
  role               ENUM('student','admin') DEFAULT 'student',
  email              VARCHAR(100) NULL,
  pending_email      VARCHAR(100) NULL,
  password           VARCHAR(255) NULL,
  email_verified_at  DATETIME NULL,
  verify_token_hash  CHAR(64) NULL,
  verify_expires     DATETIME NULL,
  verify_sent_at     DATETIME NULL,
  reset_token_hash   CHAR(64) NULL,
  reset_expires      DATETIME NULL,
  reset_sent_at      DATETIME NULL,
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_email (email),
  UNIQUE KEY uq_student_id (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS items (
  item_id      INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(150) NULL,
  category     VARCHAR(100) NULL,
  description  TEXT NULL,
  status       ENUM('lost','found','claimed','returned') DEFAULT 'lost',
  location     VARCHAR(255) NULL,
  image        VARCHAR(255) NULL,
  user_id      INT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id            INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(100) NOT NULL,
  ip            VARCHAR(45)  NOT NULL,
  attempted_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ip_time (ip, attempted_at),
  KEY idx_email_time (email, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;