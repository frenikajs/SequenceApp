-- Group hint sync: when the host opens a hint in group play, broadcast that hint
-- to every joiner. The current step token (e.g. 'clue:2', 'finale', 'intro') is
-- stored here; '' means no hint is currently shared. See GroupModel::setHint()
-- and SequenceController::groupHint().
ALTER TABLE play_groups
  ADD COLUMN hint_shown VARCHAR(24) NOT NULL DEFAULT '' AFTER solution_shown;
