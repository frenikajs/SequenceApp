-- Introduction hint (mirrors per-clue hints) for the sequence intro.
-- Idempotent: safe to re-run.
USE sequence_db;

ALTER TABLE sequences ADD COLUMN IF NOT EXISTS intro_hint_text              MEDIUMTEXT   NULL;
ALTER TABLE sequences ADD COLUMN IF NOT EXISTS intro_hint_file_path         VARCHAR(500) NULL;
ALTER TABLE sequences ADD COLUMN IF NOT EXISTS intro_hint_file_type         VARCHAR(20)  NULL;
ALTER TABLE sequences ADD COLUMN IF NOT EXISTS intro_hint_original_filename VARCHAR(255) NULL;
ALTER TABLE sequences ADD COLUMN IF NOT EXISTS intro_hint_file_size         INT UNSIGNED NULL;
ALTER TABLE sequences ADD COLUMN IF NOT EXISTS intro_hint_mime_type         VARCHAR(100) NULL;
ALTER TABLE sequences ADD COLUMN IF NOT EXISTS intro_hint_caption           VARCHAR(255) NULL;
