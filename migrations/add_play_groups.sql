-- Group (multiplayer) play. A host creates a group, players join with the code,
-- and the host drives shared progress; everyone in the group unlocks together.
CREATE TABLE IF NOT EXISTS play_groups (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(12) NOT NULL UNIQUE,
  sequence_id INT UNSIGNED NOT NULL,
  status ENUM('lobby','active') NOT NULL DEFAULT 'lobby',
  unlocked_clues INT UNSIGNED NOT NULL DEFAULT 0,
  ready_to_solve TINYINT(1) NOT NULL DEFAULT 0,
  solution_shown TINYINT(1) NOT NULL DEFAULT 0,
  hint_shown VARCHAR(24) NOT NULL DEFAULT '',
  member_count INT UNSIGNED NOT NULL DEFAULT 1,
  start_time INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (sequence_id), INDEX (status), INDEX (created_at),
  CONSTRAINT fk_pg_seq FOREIGN KEY (sequence_id) REFERENCES sequences(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
