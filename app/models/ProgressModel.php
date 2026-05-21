<?php
declare(strict_types=1);

class ProgressModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getProgress(string $sessionId, int $sequenceId): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM sequence_progress WHERE session_id = ? AND sequence_id = ?',
            [$sessionId, $sequenceId]
        );
    }

    public function upsertProgress(string $sessionId, int $sequenceId, array $data): bool
    {
        $existing = $this->getProgress($sessionId, $sequenceId);

        $cols   = ['unlocked_clues', 'intro_shown', 'finale_unlocked', 'completed',
                   'completed_at', 'time_spent_seconds'];
        $fields = [];
        $params = [];
        foreach ($cols as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = $col;
                $params[]  = $data[$col];
            }
        }

        if ($existing) {
            if (empty($fields)) {
                return false;
            }
            $set      = implode(' = ?, ', $fields) . ' = ?';
            $params[] = $sessionId;
            $params[] = $sequenceId;
            return $this->db->execute(
                "UPDATE sequence_progress SET $set WHERE session_id = ? AND sequence_id = ?",
                $params
            );
        }

        // Insert
        $fields[] = 'session_id';
        $params[]  = $sessionId;
        $fields[] = 'sequence_id';
        $params[]  = $sequenceId;

        $cols    = implode(', ', $fields);
        $holders = implode(', ', array_fill(0, count($params), '?'));
        return $this->db->execute(
            "INSERT INTO sequence_progress ($cols) VALUES ($holders)",
            $params
        );
    }

    public function resetProgress(string $sessionId, int $sequenceId): bool
    {
        return $this->db->execute(
            'DELETE FROM sequence_progress WHERE session_id = ? AND sequence_id = ?',
            [$sessionId, $sequenceId]
        );
    }

    public function resetAllForSequence(int $sequenceId): bool
    {
        return $this->db->execute(
            'DELETE FROM sequence_progress WHERE sequence_id = ?',
            [$sequenceId]
        );
    }

    public function getAnalytics(int $sequenceId): array
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS total_starts,
                    SUM(completed) AS total_completions,
                    AVG(time_spent_seconds) AS avg_time
             FROM sequence_progress WHERE sequence_id = ?',
            [$sequenceId]
        );
        return [
            'total_starts'      => (int)($row['total_starts'] ?? 0),
            'total_completions' => (int)($row['total_completions'] ?? 0),
            'avg_time'          => (int)($row['avg_time'] ?? 0),
        ];
    }
}
