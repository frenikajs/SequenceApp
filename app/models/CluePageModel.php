<?php
declare(strict_types=1);

class CluePageModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): array|false
    {
        return $this->db->fetch('SELECT * FROM clue_pages WHERE id = ?', [$id]);
    }

    public function findBySlug(string $slug): array|false
    {
        return $this->db->fetch('SELECT * FROM clue_pages WHERE slug = ?', [$slug]);
    }

    public function findByClueId(int $clueId): array|false
    {
        return $this->db->fetch('SELECT * FROM clue_pages WHERE clue_id = ?', [$clueId]);
    }

    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO clue_pages
             (clue_id, slug, site_type, site_name, page_title, author, publish_date, content, reward_content, nav_json, footer_text)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['clue_id'] ?? null,
                $data['slug'],
                $data['site_type'],
                $data['site_name'],
                $data['page_title'],
                $data['author']         ?? null,
                $data['publish_date']   ?? null,
                $data['content']        ?? null,
                $data['reward_content'] ?? null,
                $data['nav_json']       ?? null,
                $data['footer_text']    ?? null,
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $this->db->execute(
            'UPDATE clue_pages
             SET slug=?, site_type=?, site_name=?, page_title=?, author=?,
                 publish_date=?, content=?, reward_content=?, nav_json=?, footer_text=?
             WHERE id = ?',
            [
                $data['slug'],
                $data['site_type'],
                $data['site_name'],
                $data['page_title'],
                $data['author']         ?? null,
                $data['publish_date']   ?? null,
                $data['content']        ?? null,
                $data['reward_content'] ?? null,
                $data['nav_json']       ?? null,
                $data['footer_text']    ?? null,
                $id,
            ]
        );
    }

    public function delete(int $id): void
    {
        $this->db->execute('DELETE FROM clue_pages WHERE id = ?', [$id]);
    }

    public function slugExists(string $slug, int $excludeId = 0): bool
    {
        $row = $this->db->fetch(
            'SELECT id FROM clue_pages WHERE slug = ? AND id != ?',
            [$slug, $excludeId]
        );
        return (bool)$row;
    }

    /** Returns [clue_id => page_row] for the given clue IDs. */
    public function getMapForClues(array $clueIds): array
    {
        if (empty($clueIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($clueIds), '?'));
        $rows = $this->db->fetchAll(
            "SELECT * FROM clue_pages WHERE clue_id IN ($placeholders)",
            $clueIds
        );
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['clue_id']] = $row;
        }
        return $map;
    }
}
