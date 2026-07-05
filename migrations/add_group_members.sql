-- Per-player identity within a group (Interactive play). The existing play_groups
-- row tracks only a member count and shared progress; Interactive needs to know
-- WHICH suspect each player picked and HOW each player voted, so each player gets
-- one row here keyed by their session.
CREATE TABLE IF NOT EXISTS group_members (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_id INT UNSIGNED NOT NULL,
  session_id VARCHAR(128) NOT NULL,
  is_host TINYINT(1) NOT NULL DEFAULT 0,
  suspect_index INT NULL,            -- chosen suspect (index into the Suspects options)
  vote_index INT NULL,               -- voted culprit this round
  vote_round SMALLINT UNSIGNED NULL, -- which accuse_round the vote belongs to
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_group_session (group_id, session_id),
  INDEX (group_id),
  CONSTRAINT fk_gm_group FOREIGN KEY (group_id) REFERENCES play_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
