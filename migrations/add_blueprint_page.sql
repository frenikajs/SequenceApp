-- House Blueprint decoy/clue page type: a fixed 6-room floor plan drawn in
-- blueprint style. The admin supplies only the 6 room names (stored in nav_json
-- as {"rooms":[{"label":"…"}]}); pins are auto-centred in each room.
ALTER TABLE clue_pages
    MODIFY COLUMN site_type
    enum('news','corporate','blog','archive','calendar','inbox','sms','invoice','receipt','map','blueprint')
    NOT NULL DEFAULT 'news';
