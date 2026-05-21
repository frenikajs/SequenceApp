-- Game Board sequence type + per-clue Reward field.
-- Idempotent: safe to re-run.
USE sequence_db;

-- Allow 'gameboard' as a third sequence type alongside 'sequential' and 'open'.
ALTER TABLE sequences
    MODIFY COLUMN type ENUM('sequential','open','gameboard') NOT NULL DEFAULT 'sequential';

-- Rich-text reward shown after a player unlocks a clue tile on the game board.
ALTER TABLE clues
    ADD COLUMN IF NOT EXISTS reward_content LONGTEXT NULL AFTER content;
