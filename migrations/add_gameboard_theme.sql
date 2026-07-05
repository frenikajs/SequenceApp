-- Selectable visual theme for Game Board sequences. Only meaningful when
-- sequences.type = 'gameboard'; ignored for every other type.
-- Allowed values map to .gb-theme-* CSS variants in app.css:
--   candyland | winter | spooky | pool | birthday
ALTER TABLE sequences
    ADD COLUMN gameboard_theme varchar(32) NOT NULL DEFAULT 'candyland' AFTER type;

-- Existing game boards keep the original Candy Land look.
UPDATE sequences SET gameboard_theme = 'candyland' WHERE gameboard_theme IS NULL OR gameboard_theme = '';
