-- Sequence Mystery System - Database Schema
-- Requires MySQL 8.0+ / MariaDB 10.5+

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO';

CREATE DATABASE IF NOT EXISTS sequence_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sequence_db;

DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS sequence_progress;
DROP TABLE IF EXISTS clues;
DROP TABLE IF EXISTS sequence_themes;
DROP TABLE IF EXISTS sequences;
DROP TABLE IF EXISTS admins;

CREATE TABLE admins (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username    VARCHAR(80)  NOT NULL,
    email       VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_super    TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login  DATETIME     NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_username (username),
    UNIQUE KEY uq_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sequences (
    id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title                   VARCHAR(255) NOT NULL,
    slug                    VARCHAR(255) NOT NULL,
    description             TEXT NULL,
    type                    ENUM('sequential','open') NOT NULL DEFAULT 'sequential',

    start_code              VARCHAR(255) NOT NULL,
    finale_code             VARCHAR(255) NULL,
    finale_requires_code    TINYINT(1)   NOT NULL DEFAULT 1,

    introduction_content    MEDIUMTEXT NULL,
    intro_file_path         VARCHAR(500) NULL,
    intro_file_type         VARCHAR(20)  NULL,
    intro_original_filename VARCHAR(255) NULL,
    intro_file_size         INT UNSIGNED NULL,
    intro_mime_type         VARCHAR(100) NULL,
    intro_caption           VARCHAR(255) NULL,

    finale_content          MEDIUMTEXT NULL,
    finale_file_path        VARCHAR(500) NULL,
    finale_file_type        VARCHAR(20)  NULL,
    finale_original_filename VARCHAR(255) NULL,
    finale_file_size        INT UNSIGNED NULL,
    finale_mime_type        VARCHAR(100) NULL,
    finale_caption          VARCHAR(255) NULL,

    published               TINYINT(1)   NOT NULL DEFAULT 0,
    expires_at              DATETIME     NULL,
    view_count              INT UNSIGNED NOT NULL DEFAULT 0,
    completion_count        INT UNSIGNED NOT NULL DEFAULT 0,

    created_at              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_sequence_slug (slug),
    KEY idx_sequence_published (published),
    KEY idx_sequence_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sequence_themes (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sequence_id     INT UNSIGNED NOT NULL,
    bg_color        VARCHAR(20)  NOT NULL DEFAULT '#0f0f1a',
    text_color      VARCHAR(20)  NOT NULL DEFAULT '#e0e0e0',
    button_color    VARCHAR(20)  NOT NULL DEFAULT '#6c63ff',
    btn_text_color  VARCHAR(20)  NOT NULL DEFAULT '#ffffff',
    accent_color    VARCHAR(20)  NOT NULL DEFAULT '#ff6b6b',
    font_family     VARCHAR(100) NOT NULL DEFAULT 'Inter, sans-serif',
    container_width VARCHAR(20)  NOT NULL DEFAULT '800px',
    bg_image        VARCHAR(500) NULL,
    custom_css      MEDIUMTEXT   NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_theme_sequence (sequence_id),
    CONSTRAINT fk_theme_sequence FOREIGN KEY (sequence_id) REFERENCES sequences (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clues (
    id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sequence_id             INT UNSIGNED NOT NULL,
    title                   VARCHAR(255) NOT NULL DEFAULT '',
    content                 MEDIUMTEXT NULL,
    access_code             VARCHAR(255) NOT NULL,

    file_path               VARCHAR(500) NULL,
    file_type               VARCHAR(20)  NULL,
    original_filename       VARCHAR(255) NULL,
    file_size               INT UNSIGNED NULL,
    mime_type               VARCHAR(100) NULL,
    file_caption            VARCHAR(255) NULL,

    hint_text               MEDIUMTEXT NULL,
    hint_file_path          VARCHAR(500) NULL,
    hint_file_type          VARCHAR(20)  NULL,
    hint_original_filename  VARCHAR(255) NULL,
    hint_file_size          INT UNSIGNED NULL,
    hint_mime_type          VARCHAR(100) NULL,
    hint_caption            VARCHAR(255) NULL,

    sort_order              INT UNSIGNED NOT NULL DEFAULT 0,
    created_at              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_clue_sequence (sequence_id),
    KEY idx_clue_order (sequence_id, sort_order),
    CONSTRAINT fk_clue_sequence FOREIGN KEY (sequence_id) REFERENCES sequences (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sequence_progress (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id          VARCHAR(128) NOT NULL,
    sequence_id         INT UNSIGNED NOT NULL,
    unlocked_clues      INT UNSIGNED NOT NULL DEFAULT 0,
    intro_shown         TINYINT(1)   NOT NULL DEFAULT 0,
    finale_unlocked     TINYINT(1)   NOT NULL DEFAULT 0,
    completed           TINYINT(1)   NOT NULL DEFAULT 0,
    started_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at        DATETIME     NULL,
    last_activity       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    time_spent_seconds  INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_progress (session_id, sequence_id),
    KEY idx_progress_sequence (sequence_id),
    CONSTRAINT fk_progress_sequence FOREIGN KEY (sequence_id) REFERENCES sequences (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip_address      VARCHAR(45)  NOT NULL,
    username        VARCHAR(80)  NULL,
    success         TINYINT(1)   NOT NULL DEFAULT 0,
    attempted_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_attempts_ip (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
