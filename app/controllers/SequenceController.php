<?php
declare(strict_types=1);

class SequenceController
{
    private SequenceModel   $seqModel;
    private ClueModel       $clueModel;
    private CluePageModel   $pageModel;
    private ProgressModel   $progModel;

    public function __construct()
    {
        $this->seqModel  = new SequenceModel();
        $this->clueModel = new ClueModel();
        $this->pageModel = new CluePageModel();
        $this->progModel = new ProgressModel();
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
        $pageMap = $this->pageModel->getMapForClues(array_map('intval', array_column($clues, 'id')));
        foreach ($clues as &$clue) {
            $p = $pageMap[(int)$clue['id']] ?? null;
            $clue['page_slug'] = $p ? $p['slug']      : null;
            $clue['page_type'] = $p ? $p['site_type'] : null;
        }
        unset($clue);
        $prog   = $this->getSessionProgress((int)$sequence['id']);

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

        // Intro shown, in open mode → all clues visible already, check finale
        if ($sequence['type'] === 'open') {
            if (!$prog['finale_unlocked']) {
                if ($sequence['finale_requires_code']) {
                    if ($this->codesMatch($code, $sequence['finale_code'] ?? '')) {
                        $prog['finale_unlocked'] = true;
                        $prog['completed']       = true;
                        $this->saveSessionProgress($id, $prog);
                        $this->finalizeProgress($id, $prog);
                        flash('success', 'finale_unlocked');
                    } else {
                        flash('error', 'Incorrect finale code. Keep searching.');
                    }
                }
            }
            redirect('/s/' . $slug);
        }

        // Sequential mode: unlock next clue or finale
        $unlocked = (int)$prog['unlocked_clues'];

        if ($unlocked < $total) {
            // Expect the access_code for the next locked clue
            $nextClue = $clues[$unlocked] ?? null;
            if ($nextClue && $this->codesMatch($code, $nextClue['access_code'])) {
                $prog['unlocked_clues'] = $unlocked + 1;
                $this->saveSessionProgress($id, $prog);
                $this->progModel->upsertProgress(session_id(), $id, [
                    'unlocked_clues' => $unlocked + 1,
                ]);
                flash('success', 'clue_unlocked');
            } else {
                flash('error', 'Incorrect code. Keep searching for clues.');
            }
            redirect('/s/' . $slug);
        }

        // All clues unlocked → finale code
        if (!$prog['finale_unlocked'] && $sequence['finale_requires_code']) {
            if ($this->codesMatch($code, $sequence['finale_code'] ?? '')) {
                $prog['finale_unlocked'] = true;
                $prog['completed']       = true;
                $this->saveSessionProgress($id, $prog);
                $this->finalizeProgress($id, $prog);
                flash('success', 'finale_unlocked');
            } else {
                flash('error', 'Incorrect finale code. You\'re so close!');
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
            'intro_shown'     => false,
            'unlocked_clues'  => 0,
            'finale_unlocked' => false,
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
