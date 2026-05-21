<?php
declare(strict_types=1);

class SequenceController
{
    private SequenceModel   $seqModel;
    private ClueModel       $clueModel;
    private CluePageModel   $pageModel;
    private PuzzlePageModel $puzzleModel;
    private ProgressModel   $progModel;

    public function __construct()
    {
        $this->seqModel    = new SequenceModel();
        $this->clueModel   = new ClueModel();
        $this->pageModel   = new CluePageModel();
        $this->puzzleModel = new PuzzlePageModel();
        $this->progModel   = new ProgressModel();
    }

    // ── Public sequence page ──────────────────────────────────────────────────

    public function show(string $slug): void
    {
        $sequence = $this->seqModel->findBySlug($slug);

        if (!$sequence || (!$sequence['published'] && !isAdmin())) {
            notFound('This sequence is not available.');
        }

        // Expiry check
        if ($sequence['expires_at'] && strtotime($sequence['expires_at']) < time()) {
            notFound('This sequence has expired.');
        }

        $theme  = $this->seqModel->getTheme((int)$sequence['id']);
        $css    = $this->seqModel->buildCss($theme);
        $clues   = $this->clueModel->getBySequenceId((int)$sequence['id']);
        $clueIds   = array_map('intval', array_column($clues, 'id'));
        $pageMap   = $this->pageModel->getMapForClues($clueIds);
        $puzzleMap = $this->puzzleModel->getMapForClues($clueIds);
        foreach ($clues as &$clue) {
            $p = $pageMap[(int)$clue['id']] ?? null;
            $clue['page_slug'] = $p ? $p['slug']      : null;
            $clue['page_type'] = $p ? $p['site_type'] : null;
            $z = $puzzleMap[(int)$clue['id']] ?? null;
            $clue['puzzle_slug'] = $z ? $z['slug'] : null;
        }
        unset($clue);
        $prog   = $this->getSessionProgress((int)$sequence['id']);

        // If no intro access code is configured, there's no gate before Clue 1 —
        // reveal it automatically so players aren't stuck on an empty box.
        if ($sequence['type'] !== 'open'
            && !empty($prog['started'])
            && (int)$prog['unlocked_clues'] === 0
            && !$prog['ready_to_solve']
            && !$prog['solution_shown']
            && trim((string)($sequence['intro_access_code'] ?? '')) === ''
            && count($clues) > 0) {
            $prog['unlocked_clues'] = 1;
            $this->saveSessionProgress((int)$sequence['id'], $prog);
        }

        $this->seqModel->incrementView((int)$sequence['id']);

        view('public.sequence', [
            'sequence' => $sequence,
            'clues'    => $clues,
            'progress' => $prog,
            'theme'    => $theme,
            'css'      => $css,
            'flash'    => getFlash(),
        ]);
    }

    // ── Code submission ───────────────────────────────────────────────────────

    public function submitCode(string $slug): void
    {
        validate_csrf();

        $sequence = $this->seqModel->findBySlug($slug);
        if (!$sequence || (!$sequence['published'] && !isAdmin())) {
            notFound();
        }

        $code  = Security::sanitizeString(strtolower(trim($_POST['code'] ?? '')));
        $prog  = $this->getSessionProgress((int)$sequence['id']);
        $clues = $this->clueModel->getBySequenceId((int)$sequence['id']);
        $total = count($clues);
        $id    = (int)$sequence['id'];

        // ── State machine ─────────────────────────────────────────────────────

        // Not started yet → validate start code
        if (!$prog['started']) {
            if ($this->codesMatch($code, $sequence['start_code'])) {
                $prog['started']   = true;
                $prog['intro_shown'] = false;
                $this->saveSessionProgress($id, $prog);
                $this->progModel->upsertProgress(session_id(), $id, [
                    'unlocked_clues' => 0,
                    'intro_shown'    => 0,
                ]);
                flash('success', 'start_code_accepted');
            } else {
                flash('error', 'Incorrect start code. Try again.');
            }
            redirect('/s/' . $slug);
        }

        // ── OPEN MODE: all clues visible → Solution Code reveals the Solution ──
        if ($sequence['type'] === 'open') {
            if (!$prog['solution_shown']) {
                if (!$sequence['finale_requires_code']
                    || $this->codesMatch($code, $sequence['finale_code'] ?? '')) {
                    $prog['ready_to_solve'] = true;
                    $prog['solution_shown'] = true;
                    $prog['finale_unlocked'] = true;
                    $prog['completed']       = true;
                    $this->saveSessionProgress($id, $prog);
                    $this->finalizeProgress($id, $prog);
                    flash('success', 'solution_unlocked');
                } else {
                    flash('error', 'Incorrect solution code. Keep trying.');
                }
            }
            redirect('/s/' . $slug);
        }

        // ── SEQUENTIAL MODE ──
        $unlocked = (int)$prog['unlocked_clues'];

        // Stage A — Introduction screen: enter the introduction access code to reveal Clue 1
        if (!$prog['ready_to_solve'] && !$prog['solution_shown'] && $unlocked === 0) {
            $introCode = trim((string)($sequence['intro_access_code'] ?? ''));
            if ($introCode === '' || $this->codesMatch($code, $introCode)) {
                $prog['unlocked_clues'] = 1;
                $this->saveSessionProgress($id, $prog);
                $this->progModel->upsertProgress(session_id(), $id, ['unlocked_clues' => 1]);
                flash('success', 'clue_unlocked');
            } else {
                flash('error', 'Incorrect code. Keep searching for clues.');
            }
            redirect('/s/' . $slug);
        }

        // Stage B — Viewing Clue N: enter THIS clue's access code to reveal the next step
        if (!$prog['ready_to_solve'] && !$prog['solution_shown']
            && $unlocked >= 1 && $unlocked <= $total) {
            $currentClue = $clues[$unlocked - 1] ?? null;
            if ($currentClue && $this->codesMatch($code, $currentClue['access_code'])) {
                if ($unlocked < $total) {
                    $prog['unlocked_clues'] = $unlocked + 1;
                    flash('success', 'clue_unlocked');
                } else {
                    $prog['ready_to_solve'] = true;
                    flash('success', 'ready_to_solve');
                }
                $this->saveSessionProgress($id, $prog);
                $this->progModel->upsertProgress(session_id(), $id, [
                    'unlocked_clues' => (int)$prog['unlocked_clues'],
                ]);
            } else {
                flash('error', 'Incorrect code. Keep searching for clues.');
            }
            redirect('/s/' . $slug);
        }

        // Stage C — "Ready to Solve?": enter the Solution Code to reveal the Solution
        if ($prog['ready_to_solve'] && !$prog['solution_shown']) {
            if (!$sequence['finale_requires_code']
                || $this->codesMatch($code, $sequence['finale_code'] ?? '')) {
                $prog['solution_shown']  = true;
                $prog['finale_unlocked'] = true;
                $prog['completed']       = true;
                $this->saveSessionProgress($id, $prog);
                $this->finalizeProgress($id, $prog);
                flash('success', 'solution_unlocked');
            } else {
                flash('error', 'Incorrect solution code. You\'re so close!');
            }
        }

        redirect('/s/' . $slug);
    }

    public function resetProgress(string $slug): void
    {
        validate_csrf();
        $sequence = $this->seqModel->findBySlug($slug);
        if ($sequence) {
            $id = (int)$sequence['id'];
            unset($_SESSION['seq'][$id]);
            $this->progModel->resetProgress(session_id(), $id);
        }
        redirect('/s/' . $slug);
    }

    // ── Session progress helpers ──────────────────────────────────────────────

    private function getSessionProgress(int $seqId): array
    {
        return $_SESSION['seq'][$seqId] ?? [
            'started'         => false,
            'unlocked_clues'  => 0,      // 0 = Introduction screen (needs intro access code)
            'ready_to_solve'  => false,  // all clues done → "Ready to Solve?" screen
            'solution_shown'  => false,  // solution code entered → Solution revealed
            'finale_unlocked' => false,  // kept for progress/analytics compatibility
            'completed'       => false,
            'start_time'      => time(),
        ];
    }

    private function saveSessionProgress(int $seqId, array $prog): void
    {
        if (!isset($_SESSION['seq'])) {
            $_SESSION['seq'] = [];
        }
        $_SESSION['seq'][$seqId] = $prog;
    }

    private function finalizeProgress(int $seqId, array $prog): void
    {
        $elapsed = time() - (int)($prog['start_time'] ?? time());
        $this->progModel->upsertProgress(session_id(), $seqId, [
            'finale_unlocked'    => 1,
            'completed'          => 1,
            'completed_at'       => date('Y-m-d H:i:s'),
            'time_spent_seconds' => $elapsed,
        ]);
        $this->seqModel->incrementCompletion($seqId);
    }

    private function codesMatch(string $submitted, string $expected): bool
    {
        return strtolower(trim($submitted)) === strtolower(trim($expected));
    }
}
