-- New puzzle type: "Fill in the Blank". The text and its blanks live in
-- puzzle_pages.data_json as:
--   { "text": "The quick {brown} fox jumps over the {lazy} dog.",
--     "difficulty": "easy" | "hard",
--     "clues": ["...", "..."] }
-- Words wrapped in `{}` in the text are the blanks (limit 10). On Easy the
-- player drags from a shuffled bank into the blanks; on Hard they type the
-- missing words with no bank shown.
ALTER TABLE puzzle_pages
  MODIFY COLUMN puzzle_type
    ENUM('order','caesar','phone','access','elim','wordsearch','match','hotspot','fillblank')
    NOT NULL;
