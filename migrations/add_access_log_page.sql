-- New clue/decoy page type: "Access Log" — a sign-in/sign-out style log
-- (equipment maintenance log, checkout register, visitor sign-in, etc.).
-- Stored entries live in clue_pages.nav_json as
--   { "type": "access_log", "entries": [{ "time": "...", "initials": "...", "desc": "..." }] }
-- The site_name field holds the log title; the publish_date field is shown at
-- the top of the log.
ALTER TABLE clue_pages
  MODIFY COLUMN site_type
    ENUM('news','corporate','blog','archive','calendar','inbox','sms','invoice','receipt','map','access_log')
    NOT NULL;
