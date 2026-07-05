-- Track when a sequence was (last) published, so the editor can show
-- "Draft created …" vs "Published …" next to the Publish/Unpublish button.
ALTER TABLE sequences
    ADD COLUMN published_at datetime DEFAULT NULL AFTER published;

-- Backfill: assume already-published sequences went live when they were created.
UPDATE sequences SET published_at = created_at WHERE published = 1 AND published_at IS NULL;
