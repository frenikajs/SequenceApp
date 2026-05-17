-- Decoy pages linked to clues
USE sequence_db;

CREATE TABLE IF NOT EXISTS clue_pages (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    clue_id      INT UNSIGNED NULL,
    slug         VARCHAR(120) NOT NULL,
    site_type    ENUM('news','corporate','blog','archive','calendar','inbox','sms','invoice','receipt','map') NOT NULL DEFAULT 'news',
    site_name    VARCHAR(200) NOT NULL DEFAULT 'The Daily Record',
    page_title   VARCHAR(500) NOT NULL DEFAULT '',
    author       VARCHAR(200) NULL,
    publish_date DATE NULL,
    content      LONGTEXT NULL,
    nav_json     TEXT NULL,
    footer_text  VARCHAR(500) NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_page_slug (slug),
    UNIQUE KEY uq_page_clue (clue_id),
    CONSTRAINT fk_page_clue FOREIGN KEY (clue_id) REFERENCES clues (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Idempotent: bring databases that ran an earlier version of this migration
-- up to the full set of decoy page types. Safe to re-run; no-op on fresh installs.
ALTER TABLE clue_pages
    MODIFY COLUMN site_type
    ENUM('news','corporate','blog','archive','calendar','inbox','sms','invoice','receipt','map')
    NOT NULL DEFAULT 'news';
