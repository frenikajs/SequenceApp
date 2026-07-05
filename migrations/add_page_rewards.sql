-- Evidence Locker: decoy/clue pages get their own reward field.
-- Only reward content (clues, decoy pages, puzzle pages) appears in the locker.
ALTER TABLE clue_pages
    ADD COLUMN reward_content longtext DEFAULT NULL AFTER content;
