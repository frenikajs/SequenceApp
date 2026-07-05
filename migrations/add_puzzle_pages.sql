-- Puzzle pages linked to clues (interactive puzzles). First type: Order Puzzle.
USE sequence_db;

CREATE TABLE IF NOT EXISTS puzzle_pages (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    clue_id      INT UNSIGNED NULL,
    slug         VARCHAR(120) NOT NULL,
    puzzle_type  ENUM('order','caesar','phone','access','elim','wordsearch','match','hotspot') NOT NULL DEFAULT 'order',
    title        VARCHAR(500) NOT NULL DEFAULT '',
    prompt       TEXT NULL,
    data_json    TEXT NULL,
    reward_content            LONGTEXT     NULL,
    reward_file_path          VARCHAR(500) NULL,
    reward_file_type          VARCHAR(20)  NULL,
    reward_original_filename  VARCHAR(255) NULL,
    reward_file_size          INT UNSIGNED NULL,
    reward_mime_type          VARCHAR(100) NULL,
    reward_file_caption       VARCHAR(255) NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_puzzle_slug (slug),
    UNIQUE KEY uq_puzzle_clue (clue_id),
    CONSTRAINT fk_puzzle_clue FOREIGN KEY (clue_id) REFERENCES clues (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Idempotent: bring databases that ran an earlier version of this migration
-- up to the full set of puzzle types. Safe to re-run; no-op on fresh installs.
ALTER TABLE puzzle_pages
    MODIFY COLUMN puzzle_type ENUM('order','caesar','phone','access','elim','wordsearch','match','hotspot') NOT NULL DEFAULT 'order';
