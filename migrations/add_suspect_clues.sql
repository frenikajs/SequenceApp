-- Suspect clues (Interactive play): a matrix of clue step x suspect.
-- For each clue the host can unlock, every suspect can have their own private
-- clue (text + optional media). When the host unlocks Clue N, each player is
-- shown the row matching (their suspect, Clue N).
CREATE TABLE IF NOT EXISTS suspect_clues (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sequence_id INT UNSIGNED NOT NULL,
  clue_id INT UNSIGNED NOT NULL,
  suspect_index INT NOT NULL,
  body MEDIUMTEXT NULL,
  file_path VARCHAR(255) NULL,
  file_type VARCHAR(20) NULL,
  original_filename VARCHAR(255) NULL,
  file_size INT NULL,
  mime_type VARCHAR(100) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_clue_suspect (clue_id, suspect_index),
  INDEX (sequence_id),
  CONSTRAINT fk_sc_clue FOREIGN KEY (clue_id) REFERENCES clues(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
