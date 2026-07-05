-- Interactive sequence type.
-- A host-led, group-only experience: each player adopts a distinct suspect,
-- receives suspect-specific clues as the host unlocks each step (plays like
-- Sequential underneath), and the whole group votes on the culprit at the end.
--
-- The Suspects list, the culprit, and each suspect's game card reuse the existing
-- accusation_json column (one "Suspects" category whose options carry the new
-- {card, card_type} fields). See add_group_members.sql + add_suspect_clues.sql
-- for the per-player and per-suspect-clue tables.

ALTER TABLE sequences
    MODIFY COLUMN type enum('sequential','open','gameboard','whodunit','interactive') NOT NULL DEFAULT 'sequential';

-- Group play gains a "select" stage (suspect selection, between lobby and play),
-- a voting round counter, and a final outcome flag.
ALTER TABLE play_groups
    MODIFY COLUMN status enum('lobby','select','active') NOT NULL DEFAULT 'lobby';

ALTER TABLE play_groups
    ADD COLUMN accuse_round SMALLINT UNSIGNED NOT NULL DEFAULT 1 AFTER hint_shown;

ALTER TABLE play_groups
    ADD COLUMN outcome ENUM('pending','solved','failed') NOT NULL DEFAULT 'pending' AFTER accuse_round;
