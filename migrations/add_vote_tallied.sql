-- Interactive: let the host force-resolve a vote before everyone has voted.
-- vote_tallied stores the accuse_round that has been tallied (0 = none yet), so a
-- round counts as "closed" when either everyone voted or the host forced it.
ALTER TABLE play_groups
    ADD COLUMN vote_tallied SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER outcome;
