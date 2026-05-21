-- Reworked unlock flow: intro access code, finale hint, Solution + Thank You.
-- Idempotent (MariaDB 10.4+ supports ADD COLUMN IF NOT EXISTS).
USE sequence_db;

ALTER TABLE sequences
  ADD COLUMN IF NOT EXISTS intro_access_code            VARCHAR(255)      NULL AFTER start_code,
  ADD COLUMN IF NOT EXISTS finale_hint_text             MEDIUMTEXT        NULL,
  ADD COLUMN IF NOT EXISTS finale_hint_file_path        VARCHAR(500)      NULL,
  ADD COLUMN IF NOT EXISTS finale_hint_file_type        VARCHAR(20)       NULL,
  ADD COLUMN IF NOT EXISTS finale_hint_original_filename VARCHAR(255)     NULL,
  ADD COLUMN IF NOT EXISTS finale_hint_file_size        INT UNSIGNED      NULL,
  ADD COLUMN IF NOT EXISTS finale_hint_mime_type        VARCHAR(100)      NULL,
  ADD COLUMN IF NOT EXISTS finale_hint_caption          VARCHAR(255)      NULL,
  ADD COLUMN IF NOT EXISTS solution_content             TEXT              NULL,
  ADD COLUMN IF NOT EXISTS solution_file_path           VARCHAR(500)      NULL,
  ADD COLUMN IF NOT EXISTS solution_file_type           VARCHAR(20)       NULL,
  ADD COLUMN IF NOT EXISTS solution_original_filename   VARCHAR(255)      NULL,
  ADD COLUMN IF NOT EXISTS solution_file_size           INT UNSIGNED      NULL,
  ADD COLUMN IF NOT EXISTS solution_mime_type           VARCHAR(100)      NULL,
  ADD COLUMN IF NOT EXISTS solution_caption             VARCHAR(255)      NULL,
  ADD COLUMN IF NOT EXISTS thank_you_content            TEXT              NULL;
