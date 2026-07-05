<?php
declare(strict_types=1);

/**
 * Suspect clues (Interactive play): a matrix of clue step x suspect. Each cell is
 * a private clue (text + optional media) shown to the player whose suspect matches
 * when the host unlocks that clue step.
 */
class SuspectClueModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** All rows for a sequence, indexed [clue_id][suspect_index] => row. */
    public function getMatrix(int $sequenceId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT * FROM suspect_clues WHERE sequence_id = ?',
            [$sequenceId]
        );
        $matrix = [];
        foreach ($rows as $r) {
            $matrix[(int)$r['clue_id']][(int)$r['suspect_index']] = $r;
        }
        return $matrix;
    }

    public function getForClueSuspect(int $clueId, int $suspectIndex): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM suspect_clues WHERE clue_id = ? AND suspect_index = ?',
            [$clueId, $suspectIndex]
        );
    }

    /**
     * Fully reconcile a cell to the given body + media (an already-uploaded path).
     * An empty body with no media removes the cell entirely. Returns an orphaned
     * file path the caller should delete from disk (the old file when it changed
     * or was removed), or null.
     */
    public function set(
        int $sequenceId, int $clueId, int $suspectIndex,
        ?string $body, ?string $filePath, ?string $fileType, ?string $fileName
    ): ?string {
        // Treat whitespace-only / empty-markup bodies as empty.
        $bodyEmpty = ($body === null || trim(strip_tags($body)) === '');
        $body      = $bodyEmpty ? null : $body;
        $filePath  = ($filePath !== null && trim($filePath) !== '') ? trim($filePath) : null;

        $existing = $this->getForClueSuspect($clueId, $suspectIndex);
        $oldPath  = $existing ? (string)($existing['file_path'] ?? '') : '';
        $orphan   = ($oldPath !== '' && $oldPath !== $filePath) ? $oldPath : null;

        // Nothing left in the cell → remove the row.
        if ($body === null && $filePath === null) {
            if ($existing) {
                $this->db->query('DELETE FROM suspect_clues WHERE id = ?', [(int)$existing['id']]);
            }
            return $orphan;
        }

        if ($existing) {
            $this->db->query(
                'UPDATE suspect_clues SET body = ?, file_path = ?, file_type = ?, original_filename = ? WHERE id = ?',
                [$body, $filePath, $filePath ? $fileType : null, $filePath ? $fileName : null, (int)$existing['id']]
            );
        } else {
            $this->db->query(
                'INSERT INTO suspect_clues
                 (sequence_id, clue_id, suspect_index, body, file_path, file_type, original_filename)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$sequenceId, $clueId, $suspectIndex, $body, $filePath, $filePath ? $fileType : null, $filePath ? $fileName : null]
            );
        }
        return $orphan;
    }

    /** Every stored file path for a sequence — used to clean up on delete. */
    public function filePathsForSequence(int $sequenceId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT file_path FROM suspect_clues WHERE sequence_id = ? AND file_path IS NOT NULL',
            [$sequenceId]
        );
        return array_map(static fn ($r) => (string)$r['file_path'], $rows);
    }
}
