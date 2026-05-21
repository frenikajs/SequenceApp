-- Editable gate instruction text for introduction, finale, and each clue.
-- Idempotent (MariaDB 10.4+).
USE sequence_db;

ALTER TABLE sequences
  ADD COLUMN IF NOT EXISTS intro_instruction  TEXT NULL,
  ADD COLUMN IF NOT EXISTS finale_instruction TEXT NULL;

ALTER TABLE clues
  ADD COLUMN IF NOT EXISTS instruction TEXT NULL;
