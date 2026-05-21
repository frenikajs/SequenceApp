<?php
declare(strict_types=1);

class ClueModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getBySequenceId(int $sequenceId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM clues WHERE sequence_id = ? ORDER BY sort_order ASC, id ASC',
            [$sequenceId]
        );
    }

    public function findById(int $id): array|false
    {
        return $this->db->fetch('SELECT * FROM clues WHERE id = ?', [$id]);
    }

    public function countBySequenceId(int $sequenceId): int
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS cnt FROM clues WHERE sequence_id = ?',
            [$sequenceId]
        );
        return (int)($row['cnt'] ?? 0);
    }

    public function create(array $data): int
    {
        // Auto-assign sort_order as max + 1
        $maxRow   = $this->db->fetch(
            'SELECT COALESCE(MAX(sort_order), -1) AS mx FROM clues WHERE sequence_id = ?',
            [$data['sequence_id']]
        );
        $sortOrder = (int)($maxRow['mx'] ?? -1) + 1;

        $this->db->execute(
            'INSERT INTO clues
             (sequence_id, title, content, reward_content, access_code, instruction,
              file_path, file_type, original_filename, file_size, mime_type, file_caption,
              hint_text, hint_file_path, hint_file_type, hint_original_filename,
              hint_file_size, hint_mime_type, hint_caption, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['sequence_id'],
                $data['title']             ?? '',
                $data['content']           ?? null,
                $data['reward_content']    ?? null,
                $data['access_code'],
                $data['instruction']       ?? null,
                $data['file_path']         ?? null,
                $data['file_type']         ?? null,
                $data['original_filename'] ?? null,
                $data['file_size']         ?? null,
                $data['mime_type']         ?? null,
                $data['file_caption']      ?? null,
                $data['hint_text']         ?? null,
                $data['hint_file_path']         ?? null,
                $data['hint_file_type']         ?? null,
                $data['hint_original_filename'] ?? null,
                $data['hint_file_size']         ?? null,
                $data['hint_mime_type']         ?? null,
                $data['hint_caption']           ?? null,
                $sortOrder,
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $cols = [
            'title','content','reward_content','access_code','instruction',
            'file_path','file_type','original_filename','file_size','mime_type','file_caption',
            'hint_text','hint_file_path','hint_file_type','hint_original_filename',
            'hint_file_size','hint_mime_type','hint_caption',
        ];
        $fields = [];
        $params = [];
        foreach ($cols as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = ?";
                $params[]  = $data[$col];
            }
        }
        if (empty($fields)) {
            return false;
        }
        $params[] = $id;
        return $this->db->execute(
            'UPDATE clues SET ' . implode(', ', $fields) . ' WHERE id = ?',
            $params
        );
    }

    public function delete(int $id): bool
    {
        return $this->db->execute('DELETE FROM clues WHERE id = ?', [$id]);
    }

    /** Accepts ordered array of IDs and re-assigns sort_order 0,1,2,… */
    public function updateOrder(array $orderedIds): bool
    {
        if (empty($orderedIds)) {
            return false;
        }
        $this->db->beginTransaction();
        try {
            foreach ($orderedIds as $order => $clueId) {
                $this->db->execute(
                    'UPDATE clues SET sort_order = ? WHERE id = ?',
                    [(int)$order, (int)$clueId]
                );
            }
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('ClueModel::updateOrder failed: ' . $e->getMessage());
            return false;
        }
    }
}
