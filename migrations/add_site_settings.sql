-- Site-wide settings (key/value). First use: built-in "How to Play" guide.
USE sequence_db;

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key   VARCHAR(64)  NOT NULL PRIMARY KEY,
    setting_value LONGTEXT     NULL,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed the How to Play guide on fresh installs; never overwrites an existing edit.
INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES
('howto_guide',
 '<h2>How to Play</h2><p>Welcome to the game! Each sequence is a series of clues you unlock one at a time. Read each clue, follow what it tells you, and find the next access code to keep moving forward.</p><h3>Getting started</h3><ul><li>Enter the starting access code on the sequence page to begin.</li><li>Each clue may reveal hints, media, or a link to a puzzle page.</li><li>When you discover a new access code, type it into the next gate to unlock the next clue.</li></ul><h3>Tips</h3><ul><li>If you get stuck, re-read earlier clues &mdash; the answer is usually hiding in plain sight.</li><li>Use the Reset button if you want to start a sequence over.</li></ul>');
