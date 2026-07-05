<?php
declare(strict_types=1);

/**
 * Per-player identity within an Interactive group. One row per player (keyed by
 * session), tracking which suspect they adopted and how they voted on the culprit.
 */
class GroupMemberModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** Ensure this session has a member row in the group (idempotent). */
    public function ensure(int $groupId, string $sessionId, bool $isHost = false): void
    {
        $this->db->query(
            'INSERT INTO group_members (group_id, session_id, is_host)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE is_host = GREATEST(is_host, VALUES(is_host))',
            [$groupId, $sessionId, $isHost ? 1 : 0]
        );
    }

    public function findMine(int $groupId, string $sessionId): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM group_members WHERE group_id = ? AND session_id = ?',
            [$groupId, $sessionId]
        );
    }

    /** All member rows for a group, oldest first. */
    public function allForGroup(int $groupId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM group_members WHERE group_id = ? ORDER BY created_at, id',
            [$groupId]
        );
    }

    /** Suspect indices already claimed by someone in the group. */
    public function takenSuspects(int $groupId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT suspect_index FROM group_members WHERE group_id = ? AND suspect_index IS NOT NULL',
            [$groupId]
        );
        return array_map(static fn ($r) => (int)$r['suspect_index'], $rows);
    }

    /**
     * Claim a suspect for this session. Rejects (returns false) if another member
     * already took it. Passing null clears the player's pick.
     */
    public function setSuspect(int $groupId, string $sessionId, ?int $suspectIndex): bool
    {
        if ($suspectIndex !== null) {
            $clash = $this->db->fetch(
                'SELECT id FROM group_members
                 WHERE group_id = ? AND suspect_index = ? AND session_id != ?',
                [$groupId, $suspectIndex, $sessionId]
            );
            if ($clash) {
                return false;
            }
        }
        $this->db->query(
            'UPDATE group_members SET suspect_index = ? WHERE group_id = ? AND session_id = ?',
            [$suspectIndex, $groupId, $sessionId]
        );
        return true;
    }

    /** [assigned, total] — how many members have picked a suspect, out of all members. */
    public function assignedCounts(int $groupId): array
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS total, SUM(suspect_index IS NOT NULL) AS assigned
             FROM group_members WHERE group_id = ?',
            [$groupId]
        );
        return [(int)($row['assigned'] ?? 0), (int)($row['total'] ?? 0)];
    }

    /** True once every member has picked a distinct suspect (and there is at least one). */
    public function allAssigned(int $groupId): bool
    {
        [$assigned, $total] = $this->assignedCounts($groupId);
        return $total > 0 && $assigned === $total;
    }

    // ── Voting ────────────────────────────────────────────────────────────────

    public function setVote(int $groupId, string $sessionId, int $voteIndex, int $round): void
    {
        $this->db->query(
            'UPDATE group_members SET vote_index = ?, vote_round = ?
             WHERE group_id = ? AND session_id = ?',
            [$voteIndex, $round, $groupId, $sessionId]
        );
    }

    /** [voted, total] for the given round. */
    public function voteCounts(int $groupId, int $round): array
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS total, SUM(vote_round = ?) AS voted
             FROM group_members WHERE group_id = ?',
            [$round, $groupId]
        );
        return [(int)($row['voted'] ?? 0), (int)($row['total'] ?? 0)];
    }

    public function allVoted(int $groupId, int $round): bool
    {
        [$voted, $total] = $this->voteCounts($groupId, $round);
        return $total > 0 && $voted === $total;
    }

    /** Clear every member's vote (used when the host calls for another round). */
    public function clearVotes(int $groupId): void
    {
        $this->db->query(
            'UPDATE group_members SET vote_index = NULL, vote_round = NULL WHERE group_id = ?',
            [$groupId]
        );
    }

    /**
     * The group's collective pick for a round: the most-voted suspect index.
     * Ties are broken by the lowest suspect index. Returns null if no votes.
     */
    public function pluralityWinner(int $groupId, int $round): ?int
    {
        $rows = $this->db->fetchAll(
            'SELECT vote_index, COUNT(*) AS n
             FROM group_members
             WHERE group_id = ? AND vote_round = ? AND vote_index IS NOT NULL
             GROUP BY vote_index
             ORDER BY n DESC, vote_index ASC
             LIMIT 1',
            [$groupId, $round]
        );
        return $rows ? (int)$rows[0]['vote_index'] : null;
    }
}
