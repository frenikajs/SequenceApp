<?php
declare(strict_types=1);

class PuzzlePageModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): array|false
    {
        return $this->db->fetch('SELECT * FROM puzzle_pages WHERE id = ?', [$id]);
    }

    public function findBySlug(string $slug): array|false
    {
        return $this->db->fetch('SELECT * FROM puzzle_pages WHERE slug = ?', [$slug]);
    }

    public function findByClueId(int $clueId): array|false
    {
        return $this->db->fetch('SELECT * FROM puzzle_pages WHERE clue_id = ?', [$clueId]);
    }

    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO puzzle_pages
             (clue_id, slug, puzzle_type, title, prompt, data_json,
              reward_content, reward_file_path, reward_file_type,
              reward_original_filename, reward_file_size, reward_mime_type, reward_file_caption)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['clue_id']                  ?? null,
                $data['slug'],
                $data['puzzle_type']              ?? 'order',
                $data['title'],
                $data['prompt']                   ?? null,
                $data['data_json']                ?? null,
                $data['reward_content']           ?? null,
                $data['reward_file_path']         ?? null,
                $data['reward_file_type']         ?? null,
                $data['reward_original_filename'] ?? null,
                $data['reward_file_size']         ?? null,
                $data['reward_mime_type']         ?? null,
                $data['reward_file_caption']      ?? null,
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $this->db->execute(
            'UPDATE puzzle_pages
             SET slug=?, puzzle_type=?, title=?, prompt=?, data_json=?,
                 reward_content=?, reward_file_path=?, reward_file_type=?,
                 reward_original_filename=?, reward_file_size=?, reward_mime_type=?, reward_file_caption=?
             WHERE id = ?',
            [
                $data['slug'],
                $data['puzzle_type']              ?? 'order',
                $data['title'],
                $data['prompt']                   ?? null,
                $data['data_json']                ?? null,
                $data['reward_content']           ?? null,
                $data['reward_file_path']         ?? null,
                $data['reward_file_type']         ?? null,
                $data['reward_original_filename'] ?? null,
                $data['reward_file_size']         ?? null,
                $data['reward_mime_type']         ?? null,
                $data['reward_file_caption']      ?? null,
                $id,
            ]
        );
    }

    public function delete(int $id): void
    {
        $this->db->execute('DELETE FROM puzzle_pages WHERE id = ?', [$id]);
    }

    public function slugExists(string $slug, int $excludeId = 0): bool
    {
        $row = $this->db->fetch(
            'SELECT id FROM puzzle_pages WHERE slug = ? AND id != ?',
            [$slug, $excludeId]
        );
        return (bool)$row;
    }

    /** Returns [clue_id => puzzle_row] for the given clue IDs. */
    public function getMapForClues(array $clueIds): array
    {
        if (empty($clueIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($clueIds), '?'));
        $rows = $this->db->fetchAll(
            "SELECT * FROM puzzle_pages WHERE clue_id IN ($placeholders)",
            $clueIds
        );
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['clue_id']] = $row;
        }
        return $map;
    }
}
