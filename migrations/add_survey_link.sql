ALTER TABLE sequences
  ADD COLUMN survey_link VARCHAR(2048) NULL DEFAULT NULL AFTER thank_you_content;
