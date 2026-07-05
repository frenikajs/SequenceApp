<?php
declare(strict_types=1);

/**
 * Group (multiplayer) play sessions. One row per group; the host drives the
 * shared progress and joiners poll it.
 */
class GroupModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByCode(string $code): array|false
    {
        return $this->db->fetch('SELECT * FROM play_groups WHERE code = ?', [strtoupper(trim($code))]);
    }

    public function findById(int $id): array|false
    {
        return $this->db->fetch('SELECT * FROM play_groups WHERE id = ?', [$id]);
    }

    /** Create a new lobby group with a unique, easy-to-read code. Returns the code. */
    public function create(int $sequenceId): string
    {
        // Ambiguous characters (0/O, 1/I) excluded so codes are easy to read aloud.
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while ($this->findByCode($code) !== false);

        $this->db->execute(
            'INSERT INTO play_groups (code, sequence_id, status, member_count) VALUES (?, ?, "lobby", 1)',
            [$code, $sequenceId]
        );
        return $code;
    }

    /** Move a group between stages: 'lobby' → 'select' (suspect picking) → 'active' (play). */
    public function setStatus(int $id, string $status): void
    {
        $this->db->execute(
            'UPDATE play_groups SET status = ? WHERE id = ?',
            [$status, $id]
        );
    }

    /** Stamp the shared start time (used when an Interactive group leaves the lobby). */
    public function stampStart(int $id): void
    {
        $this->db->execute(
            'UPDATE play_groups SET start_time = COALESCE(start_time, ?) WHERE id = ?',
            [time(), $id]
        );
    }

    /** Record the final Interactive outcome ('solved' | 'failed') and reveal the solution. */
    public function setOutcome(int $id, string $outcome): void
    {
        $this->db->execute(
            'UPDATE play_groups SET outcome = ?, solution_shown = 1 WHERE id = ?',
            [$outcome, $id]
        );
    }

    /** Mark a voting round as tallied (everyone voted, or the host forced it). */
    public function setVoteTallied(int $id, int $round): void
    {
        $this->db->execute(
            'UPDATE play_groups SET vote_tallied = ? WHERE id = ?',
            [$round, $id]
        );
    }

    /** Open a fresh voting round (host called "accuse again"). */
    public function bumpAccuseRound(int $id): void
    {
        $this->db->execute(
            'UPDATE play_groups SET accuse_round = accuse_round + 1, outcome = "pending", ready_to_solve = 1 WHERE id = ?',
            [$id]
        );
    }

    /** Mark the group ready to accuse (all clues unlocked → voting opens). */
    public function setReady(int $id): void
    {
        $this->db->execute(
            'UPDATE play_groups SET ready_to_solve = 1 WHERE id = ?',
            [$id]
        );
    }

    public function incrementMembers(int $id): void
    {
        $this->db->execute('UPDATE play_groups SET member_count = member_count + 1 WHERE id = ?', [$id]);
    }

    /**
     * Host starts the investigation: lobby → active, stamps the shared start time.
     * $initialUnlocked is the number of clues to reveal immediately (0 when the
     * mystery is gated behind an intro access code the host must still enter).
     */
    public function start(int $id, int $initialUnlocked = 1): void
    {
        $this->db->execute(
            'UPDATE play_groups SET status = "active", start_time = ?, unlocked_clues = GREATEST(unlocked_clues, ?) WHERE id = ? AND status = "lobby"',
            [time(), max(0, $initialUnlocked), $id]
        );
    }

    /** Host pushes the shared progress (unlocked clues / finale state). */
    public function setProgress(int $id, int $unlocked, bool $ready, bool $solved): void
    {
        $this->db->execute(
            'UPDATE play_groups SET unlocked_clues = ?, ready_to_solve = ?, solution_shown = ? WHERE id = ?',
            [$unlocked, $ready ? 1 : 0, $solved ? 1 : 0, $id]
        );
    }

    /**
     * Host reveals (or hides) a hint for the whole group. $token identifies the
     * current step (e.g. 'clue:2', 'finale', 'intro'); '' means no hint shown.
     */
    public function setHint(int $id, string $token): void
    {
        $this->db->execute(
            'UPDATE play_groups SET hint_shown = ? WHERE id = ?',
            [substr($token, 0, 24), $id]
        );
    }
}
