-- Per-sequence title styling: a custom font and colour for the page title
-- (`.seq-title`) on the public sequence page, edited from the admin Theme card.
-- Both columns are nullable; when NULL the title inherits the body font / text
-- colour (no override is emitted in SequenceModel::buildCss()).
ALTER TABLE sequence_themes
  ADD COLUMN title_font  VARCHAR(80) NULL AFTER font_family,
  ADD COLUMN title_color VARCHAR(20) NULL AFTER title_font;
