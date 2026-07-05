-- Who-dun-it / Deduction sequence type.
-- Plays like Open (evidence gathered freely), but the win condition is an
-- accusation: the player names who/where/why instead of entering a code.
ALTER TABLE sequences
    MODIFY COLUMN type enum('sequential','open','gameboard','whodunit') NOT NULL DEFAULT 'sequential';

-- Structured accusation config: {"prompt":"…","categories":[{"label":"Suspect","options":["…"],"answer":0}]}
ALTER TABLE sequences
    ADD COLUMN accusation_json longtext DEFAULT NULL AFTER thank_you_content;
