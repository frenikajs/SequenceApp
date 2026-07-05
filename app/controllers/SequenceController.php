<?php
declare(strict_types=1);

class SequenceController
{
    private SequenceModel   $seqModel;
    private ClueModel       $clueModel;
    private CluePageModel   $pageModel;
    private PuzzlePageModel $puzzleModel;
    private ProgressModel   $progModel;
    private GroupModel      $groupModel;
    private GroupMemberModel $memberModel;
    private SuspectClueModel $suspectClueModel;

    public function __construct()
    {
        $this->seqModel    = new SequenceModel();
        $this->clueModel   = new ClueModel();
        $this->pageModel   = new CluePageModel();
        $this->puzzleModel = new PuzzlePageModel();
        $this->progModel   = new ProgressModel();
        $this->groupModel  = new GroupModel();
        $this->memberModel = new GroupMemberModel();
        $this->suspectClueModel = new SuspectClueModel();
    }

    // ── Public landing page (crime-file with a quick-access code box) ─────────

    public function landing(): void
    {
        view('public.landing', [
            'flash' => getFlash(),
        ]);
    }

    /**
     * Quick access: a player enters a start code on the landing page; we find the
     * matching mystery, mark it started (so it's already unlocked), and send them in.
     */
    public function enterCode(): void
    {
        validate_csrf();
        $code = Security::sanitizeString(trim($_POST['code'] ?? ''));
        if ($code === '') {
            flash('error', 'Enter your access code to open your case.');
            redirect('/');
        }

        // A group code lets a player join their host's investigation directly —
        // no need to enter the start code too. Check this first.
        $group = $this->groupModel->findByCode($code);
        if ($group) {
            $seq = $this->seqModel->findById((int)$group['sequence_id']);
            if ($seq && ($seq['published'] || isAdmin())) {
                if ($group['status'] !== 'lobby') {
                    flash('error', 'That group has already started — you can\'t join now.');
                    redirect('/');
                }
                $id   = (int)$seq['id'];
                $this->groupModel->incrementMembers((int)$group['id']);
                if (($seq['type'] ?? '') === 'interactive') {
                    $this->memberModel->ensure((int)$group['id'], session_id(), false);
                }
                $prog = $this->getSessionProgress($id);
                $prog['started']    = true;
                $prog['mode']       = 'group';
                $prog['is_host']    = false;
                $prog['group_code'] = $group['code'];
                $prog['start_time'] = time();
                $this->saveSessionProgress($id, $prog);
                redirect('/s/' . $seq['slug']); // → joiner "standing by" lobby
            }
        }

        // Otherwise treat it as a start code. Admins may open unpublished mysteries.
        $seq = $this->seqModel->findByStartCode($code, isAdmin());
        if (!$seq) {
            flash('error', 'No case found for that code. Double-check it and try again.');
            redirect('/');
        }

        // Start code accepted → land on the mystery page in the lobby chooser
        // (Start group / Join group / Start solo). Same as entering it on the page.
        // Interactive is group-only: skip the chooser and open the host lobby directly.
        $id   = (int)$seq['id'];
        $prog = $this->getSessionProgress($id);
        $prog['started']    = true;
        $prog['start_time'] = time();
        if (($seq['type'] ?? '') === 'interactive') {
            $this->startInteractiveHost($id, $prog);
        } else {
            $prog['mode']       = null;   // null → show the lobby chooser
            $prog['group_code'] = null;
            $prog['is_host']    = false;
        }
        $this->saveSessionProgress($id, $prog);
        redirect('/s/' . $seq['slug']);
    }

    /**
     * Interactive is group-only: entering the start code makes the player the host
     * of a fresh group and drops them straight into the host lobby (no chooser).
     * Mutates $prog in place; the caller saves it.
     */
    private function startInteractiveHost(int $seqId, array &$prog): void
    {
        $code  = $this->groupModel->create($seqId);
        $group = $this->groupModel->findByCode($code);
        if ($group) {
            $this->memberModel->ensure((int)$group['id'], session_id(), true);
        }
        $prog['mode']       = 'group';
        $prog['is_host']    = true;
        $prog['group_code'] = $code;
    }

    // ── Group (multiplayer) lobby ───────────────────────────────────────────────

    private function resolveSequence(string $slug): array
    {
        $sequence = $this->seqModel->findBySlug($slug);
        if (!$sequence || (!$sequence['published'] && !isAdmin())) {
            notFound();
        }
        return $sequence;
    }

    /** Lobby choice: play alone — behaves exactly like the original flow. */
    public function chooseSolo(string $slug): void
    {
        validate_csrf();
        $sequence = $this->resolveSequence($slug);
        $id   = (int)$sequence['id'];
        $prog = $this->getSessionProgress($id);
        if (!empty($prog['started'])) {
            $prog['mode']       = 'solo';
            $prog['group_code'] = null;
            $prog['is_host']    = false;
            $prog['start_time'] = time(); // the game actually begins now — start the clock here
            $this->saveSessionProgress($id, $prog);
            $this->progUpsert($id, ['unlocked_clues' => (int)$prog['unlocked_clues']]);
        }
        flash('success', 'opened_case'); // load at the top of the mystery
        redirect('/s/' . $slug);
    }

    /** Lobby choice: start a group — become the host of a new lobby. */
    public function groupCreate(string $slug): void
    {
        validate_csrf();
        $sequence = $this->resolveSequence($slug);
        $id   = (int)$sequence['id'];
        $prog = $this->getSessionProgress($id);
        if (empty($prog['started'])) { redirect('/s/' . $slug); }

        $code = $this->groupModel->create($id);
        $prog['mode']       = 'group';
        $prog['is_host']    = true;
        $prog['group_code'] = $code;
        $this->saveSessionProgress($id, $prog);
        redirect('/s/' . $slug);
    }

    /** Lobby choice: join a group with the host's code. */
    public function groupJoin(string $slug): void
    {
        validate_csrf();
        $sequence = $this->resolveSequence($slug);
        $id   = (int)$sequence['id'];
        $prog = $this->getSessionProgress($id);
        if (empty($prog['started'])) { redirect('/s/' . $slug); }

        $code  = strtoupper(trim((string)($_POST['group_code'] ?? '')));
        $group = $code !== '' ? $this->groupModel->findByCode($code) : false;
        if (!$group || (int)$group['sequence_id'] !== $id) {
            flash('error', 'No group found for that code. Check it with your host.');
            redirect('/s/' . $slug);
        }
        if ($group['status'] !== 'lobby') {
            flash('error', 'That group has already started — you can\'t join now.');
            redirect('/s/' . $slug);
        }
        $this->groupModel->incrementMembers((int)$group['id']);
        $prog['mode']       = 'group';
        $prog['is_host']    = false;
        $prog['group_code'] = $group['code'];
        $this->saveSessionProgress($id, $prog);
        redirect('/s/' . $slug);
    }

    /** Host starts the investigation for the whole group. */
    public function groupStart(string $slug): void
    {
        validate_csrf();
        $sequence = $this->resolveSequence($slug);
        $id   = (int)$sequence['id'];
        $prog = $this->getSessionProgress($id);
        if (($prog['mode'] ?? null) !== 'group' || empty($prog['is_host']) || empty($prog['group_code'])) {
            redirect('/s/' . $slug);
        }
        $group = $this->groupModel->findByCode($prog['group_code']);
        if ($group && (int)$group['sequence_id'] === $id && $group['status'] === 'lobby') {
            if ($sequence['type'] === 'interactive') {
                // Interactive: the host opens the suspect-selection stage; play
                // begins only once everyone picks a suspect and the host enters
                // the crime scene.
                $this->groupModel->setStatus((int)$group['id'], 'select');
                $this->groupModel->stampStart((int)$group['id']);
                $group = $this->groupModel->findByCode($prog['group_code']);
                $prog['start_time'] = (int)($group['start_time'] ?? time());
                $this->saveSessionProgress($id, $prog);
                redirect('/s/' . $slug);
            }
            // A sequential mystery gated by an intro access code must NOT auto-jump
            // to Clue 1 — the host enters the intro code first, just like solo play.
            $introGated = $sequence['type'] === 'sequential'
                && trim((string)($sequence['intro_access_code'] ?? '')) !== '';
            $initialUnlocked = $introGated ? 0 : 1;
            $this->groupModel->start((int)$group['id'], $initialUnlocked);
            $group = $this->groupModel->findByCode($prog['group_code']);
            // Share the group's start time so everyone's timer matches.
            $prog['unlocked_clues'] = max($initialUnlocked, (int)$prog['unlocked_clues']);
            $prog['start_time']     = (int)($group['start_time'] ?? time());
            $this->saveSessionProgress($id, $prog);
        }
        flash('success', 'opened_case');
        redirect('/s/' . $slug);
    }

    /** AJAX: current group state, polled by the lobby and by joiners in play. */
    public function groupStatus(string $slug): void
    {
        $sequence = $this->seqModel->findBySlug($slug);
        if (!$sequence) { jsonResponse(['ok' => false], 404); }
        $prog  = $this->getSessionProgress((int)$sequence['id']);
        $code  = (string)($_GET['code'] ?? ($prog['group_code'] ?? ''));
        $group = $code !== '' ? $this->groupModel->findByCode($code) : false;
        if (!$group || (int)$group['sequence_id'] !== (int)$sequence['id']) {
            jsonResponse(['ok' => false]);
        }
        jsonResponse([
            'ok'       => true,
            'status'   => $group['status'],
            'unlocked' => (int)$group['unlocked_clues'],
            'ready'    => (int)$group['ready_to_solve'],
            'solved'   => (int)$group['solution_shown'],
            'hint'     => (string)($group['hint_shown'] ?? ''),
            'members'  => (int)$group['member_count'],
        ]);
    }

    /**
     * AJAX (host only): the host opened/closed a hint, so mirror it to the group
     * row. Joiners pick it up on their next poll and reveal the same hint.
     * $_POST['token'] = the current step token ('clue:N' | 'finale' | 'intro'),
     * or '' to clear the shared hint when the host closes it.
     */
    public function groupHint(string $slug): void
    {
        validate_csrf();
        $sequence = $this->seqModel->findBySlug($slug);
        if (!$sequence) { jsonResponse(['ok' => false], 404); }
        $id   = (int)$sequence['id'];
        $prog = $this->getSessionProgress($id);
        // Only the host of an active group may drive the shared hint.
        if (($prog['mode'] ?? null) !== 'group' || empty($prog['is_host']) || empty($prog['group_code'])) {
            jsonResponse(['ok' => false], 403);
        }
        $group = $this->groupModel->findByCode($prog['group_code']);
        if (!$group || (int)$group['sequence_id'] !== $id) {
            jsonResponse(['ok' => false], 404);
        }
        $token = trim((string)($_POST['token'] ?? ''));
        $this->groupModel->setHint((int)$group['id'], $token);
        jsonResponse(['ok' => true, 'hint' => substr($token, 0, 24)]);
    }

    // ── Interactive: suspect selection ──────────────────────────────────────────

    /** Number of suspects defined for an interactive sequence. */
    private function suspectCount(array $sequence): int
    {
        $acc  = json_decode($sequence['accusation_json'] ?? '', true);
        $cats = is_array($acc) ? ($acc['categories'] ?? []) : [];
        return !empty($cats) ? count($cats[0]['options'] ?? []) : 0;
    }

    /** A player claims a suspect during the selection stage. */
    public function pickSuspect(string $slug): void
    {
        validate_csrf();
        $sequence = $this->seqModel->findBySlug($slug);
        if (!$sequence || $sequence['type'] !== 'interactive') { redirect('/s/' . $slug); }
        $id   = (int)$sequence['id'];
        $prog = $this->getSessionProgress($id);
        $group = !empty($prog['group_code']) ? $this->groupModel->findByCode($prog['group_code']) : false;
        if (!$group || (int)$group['sequence_id'] !== $id || $group['status'] !== 'select') {
            redirect('/s/' . $slug);
        }
        $pick = isset($_POST['suspect']) && $_POST['suspect'] !== '' ? (int)$_POST['suspect'] : -1;
        $count = $this->suspectCount($sequence);
        if ($pick < 0 || $pick >= $count) {
            flash('error', 'Pick a suspect to continue.');
            redirect('/s/' . $slug);
        }
        $this->memberModel->ensure((int)$group['id'], session_id(), !empty($prog['is_host']));
        if (!$this->memberModel->setSuspect((int)$group['id'], session_id(), $pick)) {
            flash('error', 'Another player already took that suspect — pick a different one.');
        }
        redirect('/s/' . $slug);
    }

    /** AJAX: live selection-stage state (members, assigned, taken suspects). */
    public function selectStatus(string $slug): void
    {
        $sequence = $this->seqModel->findBySlug($slug);
        if (!$sequence) { jsonResponse(['ok' => false], 404); }
        $prog  = $this->getSessionProgress((int)$sequence['id']);
        $code  = (string)($_GET['code'] ?? ($prog['group_code'] ?? ''));
        $group = $code !== '' ? $this->groupModel->findByCode($code) : false;
        if (!$group || (int)$group['sequence_id'] !== (int)$sequence['id']) {
            jsonResponse(['ok' => false]);
        }
        [$assigned, $total] = $this->memberModel->assignedCounts((int)$group['id']);
        jsonResponse([
            'ok'       => true,
            'status'   => $group['status'],
            'members'  => $total,
            'assigned' => $assigned,
            'taken'    => $this->memberModel->takenSuspects((int)$group['id']),
        ]);
    }

    /** Host opens the crime scene once everyone has a suspect → play begins. */
    public function enterCrimeScene(string $slug): void
    {
        validate_csrf();
        $sequence = $this->seqModel->findBySlug($slug);
        if (!$sequence || $sequence['type'] !== 'interactive') { redirect('/s/' . $slug); }
        $id   = (int)$sequence['id'];
        $prog = $this->getSessionProgress($id);
        if (($prog['mode'] ?? null) !== 'group' || empty($prog['is_host']) || empty($prog['group_code'])) {
            redirect('/s/' . $slug);
        }
        $group = $this->groupModel->findByCode($prog['group_code']);
        if (!$group || (int)$group['sequence_id'] !== $id || $group['status'] !== 'select') {
            redirect('/s/' . $slug);
        }
        if (!$this->memberModel->allAssigned((int)$group['id'])) {
            flash('error', 'Everyone needs to pick a suspect before entering the crime scene.');
            redirect('/s/' . $slug);
        }
        // Like sequential: an intro access code gates Clue 1; otherwise reveal it.
        $introGated = trim((string)($sequence['intro_access_code'] ?? '')) !== '';
        $initial    = $introGated ? 0 : 1;
        $this->groupModel->setStatus((int)$group['id'], 'active');
        $this->groupModel->setProgress((int)$group['id'], $initial, false, false);

        $prog['unlocked_clues'] = $initial;
        $prog['ready_to_solve'] = false;
        $prog['solution_shown'] = false;
        $this->saveSessionProgress($id, $prog);
        flash('success', 'opened_case');
        redirect('/s/' . $slug);
    }

    // ── Interactive: group accusation vote ──────────────────────────────────────

    /** A player casts their vote for the culprit. Resolves the round when all vote. */
    public function castVote(string $slug): void
    {
        validate_csrf();
        $sequence = $this->seqModel->findBySlug($slug);
        if (!$sequence || $sequence['type'] !== 'interactive') { redirect('/s/' . $slug); }
        $id   = (int)$sequence['id'];
        $prog = $this->getSessionProgress($id);
        $group = !empty($prog['group_code']) ? $this->groupModel->findByCode($prog['group_code']) : false;
        if (!$group || (int)$group['sequence_id'] !== $id || $group['status'] !== 'active') {
            redirect('/s/' . $slug);
        }
        // Voting is only open after all clues, and before the case is resolved.
        if (empty($group['ready_to_solve']) || !empty($group['solution_shown']) || ($group['outcome'] ?? 'pending') !== 'pending') {
            redirect('/s/' . $slug);
        }
        $round = (int)($group['accuse_round'] ?? 1);
        // The host may have already force-tallied this round — no late votes then.
        if ((int)($group['vote_tallied'] ?? 0) === $round) {
            redirect('/s/' . $slug);
        }
        $pick  = isset($_POST['vote']) && $_POST['vote'] !== '' ? (int)$_POST['vote'] : -1;
        $count = $this->suspectCount($sequence);
        if ($pick < 0 || $pick >= $count) {
            flash('error', 'Choose a suspect to accuse.');
            redirect('/s/' . $slug);
        }
        $this->memberModel->ensure((int)$group['id'], session_id(), !empty($prog['is_host']));
        $this->memberModel->setVote((int)$group['id'], session_id(), $pick, $round);

        // Once everyone has voted, tally and resolve.
        if ($this->memberModel->allVoted((int)$group['id'], $round)) {
            $this->resolveVoteRound($sequence, $group, $round);
        }
        redirect('/s/' . $slug);
    }

    /** Host forces the vote to resolve with whatever votes are in (no need to wait). */
    public function forceVote(string $slug): void
    {
        validate_csrf();
        $group = $this->hostInteractiveGroup($slug);
        if (!$group) { redirect('/s/' . $slug); }
        // Only while voting is open and unresolved.
        if (empty($group['ready_to_solve']) || !empty($group['solution_shown']) || ($group['outcome'] ?? 'pending') !== 'pending') {
            redirect('/s/' . $slug);
        }
        $round = (int)($group['accuse_round'] ?? 1);
        if ((int)($group['vote_tallied'] ?? 0) === $round) {
            redirect('/s/' . $slug);   // already tallied this round
        }
        if ($this->memberModel->pluralityWinner((int)$group['id'], $round) === null) {
            flash('error', 'No one has voted yet — wait for at least one accusation.');
            redirect('/s/' . $slug);
        }
        $sequence = $this->seqModel->findBySlug($slug);
        $this->resolveVoteRound($sequence, $group, $round);
        redirect('/s/' . $slug);
    }

    /**
     * Tally a voting round: stamp it tallied, and if the group's plurality pick is
     * the culprit, reveal the solution. A wrong pick stays 'pending' so the host
     * can re-accuse or reveal. Shared by the all-voted path and host force-resolve.
     */
    private function resolveVoteRound(array $sequence, array $group, int $round): void
    {
        $this->groupModel->setVoteTallied((int)$group['id'], $round);
        $winner  = $this->memberModel->pluralityWinner((int)$group['id'], $round);
        $acc     = json_decode($sequence['accusation_json'] ?? '', true);
        $culprit = (int)($acc['categories'][0]['answer'] ?? 0);
        if ($winner !== null && $winner === $culprit) {
            $this->groupModel->setOutcome((int)$group['id'], 'solved');
            if (!isAdmin()) {
                $this->seqModel->incrementCompletion((int)$sequence['id']);
            }
        }
    }

    /** AJAX: live voting state. */
    public function voteStatus(string $slug): void
    {
        $sequence = $this->seqModel->findBySlug($slug);
        if (!$sequence) { jsonResponse(['ok' => false], 404); }
        $prog  = $this->getSessionProgress((int)$sequence['id']);
        $code  = (string)($_GET['code'] ?? ($prog['group_code'] ?? ''));
        $group = $code !== '' ? $this->groupModel->findByCode($code) : false;
        if (!$group || (int)$group['sequence_id'] !== (int)$sequence['id']) {
            jsonResponse(['ok' => false]);
        }
        $round = (int)($group['accuse_round'] ?? 1);
        [$voted, $total] = $this->memberModel->voteCounts((int)$group['id'], $round);
        $tallied = (int)($group['vote_tallied'] ?? 0) === $round;
        jsonResponse([
            'ok'      => true,
            'voted'   => $voted,
            'members' => $total,
            'complete' => ($total > 0 && $voted === $total) ? 1 : 0,
            // "closed" = everyone voted OR the host forced the tally.
            'closed'  => (($total > 0 && $voted === $total) || $tallied) ? 1 : 0,
            'outcome' => $group['outcome'] ?? 'pending',
            'round'   => $round,
            'solved'  => (int)$group['solution_shown'],
        ]);
    }

    /** Host clears the vote and opens a fresh accusation round after a wrong answer. */
    public function accuseAgain(string $slug): void
    {
        validate_csrf();
        $group = $this->hostInteractiveGroup($slug);
        if ($group) {
            $this->memberModel->clearVotes((int)$group['id']);
            $this->groupModel->bumpAccuseRound((int)$group['id']);
        }
        redirect('/s/' . $slug);
    }

    /** Host gives up and reveals the solution (failed outcome). */
    public function revealMystery(string $slug): void
    {
        validate_csrf();
        $group = $this->hostInteractiveGroup($slug);
        if ($group) {
            $this->groupModel->setOutcome((int)$group['id'], 'failed');
        }
        redirect('/s/' . $slug);
    }

    /** Resolve the host's active interactive group, or null (with a redirect-safe caller). */
    private function hostInteractiveGroup(string $slug): ?array
    {
        $sequence = $this->seqModel->findBySlug($slug);
        if (!$sequence || $sequence['type'] !== 'interactive') { return null; }
        $prog = $this->getSessionProgress((int)$sequence['id']);
        if (($prog['mode'] ?? null) !== 'group' || empty($prog['is_host']) || empty($prog['group_code'])) {
            return null;
        }
        $group = $this->groupModel->findByCode($prog['group_code']);
        if (!$group || (int)$group['sequence_id'] !== (int)$sequence['id'] || $group['status'] !== 'active') {
            return null;
        }
        return $group;
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
            $clue['page_reward'] = $p['reward_content'] ?? null;
            $z = $puzzleMap[(int)$clue['id']] ?? null;
            $clue['puzzle_slug'] = $z ? $z['slug'] : null;
            $clue['puzzle_reward'] = $z['reward_content'] ?? null;
            // A puzzle's reward is only "earned" once the puzzle is solved
            // ($_SESSION['puzzle'][slug] is set by PuzzlePageController on a correct solve).
            $clue['puzzle_solved'] = $z && !empty($_SESSION['puzzle'][$z['slug']]);
        }
        unset($clue);
        $prog   = $this->getSessionProgress((int)$sequence['id']);

        // If no intro access code is configured, there's no gate before Clue 1 —
        // reveal it automatically so players aren't stuck on an empty box.
        // Open and Who-dun-it both surface every clue at once, so they skip this.
        if (!in_array($sequence['type'], ['open', 'whodunit'], true)
            && ($prog['mode'] ?? null) !== 'group'   // group unlocking is host-driven
            && !empty($prog['started'])
            && (int)$prog['unlocked_clues'] === 0
            && !$prog['ready_to_solve']
            && !$prog['solution_shown']
            && trim((string)($sequence['intro_access_code'] ?? '')) === ''
            && count($clues) > 0) {
            $prog['unlocked_clues'] = 1;
            $this->saveSessionProgress((int)$sequence['id'], $prog);
        }

        // Don't count admin play-tests in the public stats.
        if (!isAdmin()) {
            $this->seqModel->incrementView((int)$sequence['id']);
        }

        // ── Group play: lobby state + shared progress ──────────────────────────
        $id       = (int)$sequence['id'];
        $mode     = $prog['mode'] ?? null;
        $isGroup  = ($mode === 'group');
        $isHost   = $isGroup && !empty($prog['is_host']);
        $lobby    = null;          // 'choose' | 'host' | 'join' | null (in play)
        $groupCode = null;
        $groupMembers = 0;
        $groupHint = '';           // step token of the hint the host has shared, if any

        // Interactive (suspects) play state — populated below for that type only.
        $interactiveStage = null;  // 'select' | 'play' | null
        $suspects         = [];
        $mySuspectIndex   = null;
        $mySuspect        = null;
        $suspectClues     = [];    // clue step number => the player's suspect clue row
        $selAssigned = 0; $selTotal = 0; $selTaken = [];
        $voteOutcome = 'pending'; $accuseRound = 1; $myVote = null;
        $voteVoted = 0; $voteTotal = 0; $voteResultIndex = null; $voteClosed = false;

        if (!empty($prog['started']) && $mode === null) {
            $lobby = 'choose';
        } elseif ($isGroup) {
            $group = !empty($prog['group_code']) ? $this->groupModel->findByCode($prog['group_code']) : false;
            if (!$group || (int)$group['sequence_id'] !== $id) {
                $lobby = 'choose';            // group vanished → re-choose
                $isGroup = $isHost = false;
            } elseif ($group['status'] === 'lobby') {
                $lobby        = $isHost ? 'host' : 'join';
                $groupCode    = $group['code'];
                $groupMembers = (int)$group['member_count'];
            } elseif ($group['status'] === 'select') {
                // Interactive: suspect-selection stage (between lobby and play).
                $groupCode        = $group['code'];
                $interactiveStage = 'select';
                $this->memberModel->ensure((int)$group['id'], session_id(), $isHost);
                $mine = $this->memberModel->findMine((int)$group['id'], session_id());
                $mySuspectIndex = ($mine && $mine['suspect_index'] !== null) ? (int)$mine['suspect_index'] : null;
                [$selAssigned, $selTotal] = $this->memberModel->assignedCounts((int)$group['id']);
                $selTaken = $this->memberModel->takenSuspects((int)$group['id']);
            } else {
                // Active group play: shared progress lives in the group row.
                $groupCode = $group['code'];
                $groupHint = (string)($group['hint_shown'] ?? '');
                if ($sequence['type'] === 'interactive') {
                    // The group row is the source of truth for ready/solved/outcome;
                    // the host drives only the unlocked-clue count.
                    $interactiveStage = 'play';
                    $accuseRound = (int)($group['accuse_round'] ?? 1);
                    $voteOutcome = (string)($group['outcome'] ?? 'pending');
                    $prog['solution_shown'] = (bool)$group['solution_shown'];
                    if ($isHost) {
                        // Push unlocked + ready, but never clobber the solved flag.
                        $this->groupModel->setProgress(
                            (int)$group['id'],
                            (int)$prog['unlocked_clues'],
                            (bool)$prog['ready_to_solve'],
                            (bool)$group['solution_shown']
                        );
                        $prog['ready_to_solve'] = (bool)$prog['ready_to_solve'] || (bool)$group['ready_to_solve'];
                    } else {
                        $prog['started']        = true;
                        $prog['unlocked_clues'] = (int)$group['unlocked_clues'];
                        $prog['ready_to_solve'] = (bool)$group['ready_to_solve'];
                    }
                } elseif ($isHost) {
                    $this->groupModel->setProgress(
                        (int)$group['id'],
                        (int)$prog['unlocked_clues'],
                        (bool)$prog['ready_to_solve'],
                        (bool)$prog['solution_shown']
                    );
                } else {
                    $prog['started']        = true;
                    $prog['unlocked_clues'] = (int)$group['unlocked_clues'];
                    $prog['ready_to_solve'] = (bool)$group['ready_to_solve'];
                    $prog['solution_shown'] = (bool)$group['solution_shown'];
                }
                if (!empty($group['start_time'])) {
                    $prog['start_time'] = (int)$group['start_time'];
                    if (!empty($prog['solution_shown'])) {
                        $prog['elapsed'] = max(0, time() - (int)$group['start_time']);
                    }
                }
            }
        }

        // ── Interactive: resolve the player's suspect, their clues, and vote state ──
        if ($sequence['type'] === 'interactive' && $isGroup && isset($group)
            && $group && in_array($group['status'], ['select', 'active'], true)) {
            $accDecoded = json_decode($sequence['accusation_json'] ?? '', true);
            $suspects   = (is_array($accDecoded) && !empty($accDecoded['categories']))
                ? ($accDecoded['categories'][0]['options'] ?? []) : [];

            $mine = $this->memberModel->findMine((int)$group['id'], session_id());
            $mySuspectIndex = ($mine && $mine['suspect_index'] !== null) ? (int)$mine['suspect_index'] : null;
            if ($mySuspectIndex !== null && isset($suspects[$mySuspectIndex])) {
                $mySuspect = $suspects[$mySuspectIndex];
            }

            if ($interactiveStage === 'play') {
                // The player's private suspect clue for each unlocked step.
                if ($mySuspectIndex !== null) {
                    $matrix = $this->suspectClueModel->getMatrix($id);
                    $upTo   = (int)$prog['unlocked_clues'];
                    for ($k = 1; $k <= $upTo && $k <= count($clues); $k++) {
                        $cid = (int)$clues[$k - 1]['id'];
                        if (isset($matrix[$cid][$mySuspectIndex])) {
                            $suspectClues[$k] = $matrix[$cid][$mySuspectIndex];
                        }
                    }
                }
                // Voting state (only meaningful once the case is ready to accuse).
                $accuseRound = (int)($group['accuse_round'] ?? 1);
                [$voteVoted, $voteTotal] = $this->memberModel->voteCounts((int)$group['id'], $accuseRound);
                if ($mine && (int)($mine['vote_round'] ?? 0) === $accuseRound && $mine['vote_index'] !== null) {
                    $myVote = (int)$mine['vote_index'];
                }
                // The round is "closed" once everyone has voted OR the host forced it.
                $voteTallied = ((int)($group['vote_tallied'] ?? 0) === $accuseRound);
                $voteClosed  = ($voteTotal > 0 && $voteVoted === $voteTotal) || $voteTallied;
                if ($voteOutcome !== 'pending' || $voteClosed) {
                    $voteResultIndex = $this->memberModel->pluralityWinner((int)$group['id'], $accuseRound);
                }
            }
        }

        $accusation = null;
        if ($sequence['type'] === 'whodunit') {
            $decoded = json_decode($sequence['accusation_json'] ?? '', true);
            if (is_array($decoded) && !empty($decoded['categories'])) {
                $accusation = $decoded;
            }
        }

        // How-to-Play guide for this sequence type (falls back to the legacy single guide).
        $settings = new SettingsModel();
        $howto = $settings->get('howto_guide_' . $sequence['type'], '');
        if ($howto === '') {
            $howto = $settings->get('howto_guide', '');
        }

        view('public.sequence', [
            'sequence'     => $sequence,
            'clues'        => $clues,
            'progress'     => $prog,
            'theme'        => $theme,
            'css'          => $css,
            'accusation'   => $accusation,
            'howto'        => $howto,
            'lobby'        => $lobby,
            'isGroup'      => $isGroup,
            'isHost'       => $isHost,
            'groupCode'    => $groupCode,
            'groupMembers' => $groupMembers,
            'groupHint'    => $groupHint,
            // Interactive (suspects) play state
            'interactiveStage' => $interactiveStage,
            'suspects'         => $suspects,
            'mySuspectIndex'   => $mySuspectIndex,
            'mySuspect'        => $mySuspect,
            'suspectClues'     => $suspectClues,
            'selAssigned'      => $selAssigned,
            'selTotal'         => $selTotal,
            'selTaken'         => $selTaken,
            'voteOutcome'      => $voteOutcome,
            'accuseRound'      => $accuseRound,
            'myVote'           => $myVote,
            'voteVoted'        => $voteVoted,
            'voteTotal'        => $voteTotal,
            'voteResultIndex'  => $voteResultIndex,
            'voteClosed'       => $voteClosed,
            'flash'        => getFlash(),
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

        // Not started yet → accept either the sequence's start code OR a group code
        // for THIS sequence. A group code from a different sequence is ignored and
        // falls through to the start-code check (so the player just sees an error).
        if (!$prog['started']) {
            $group = $this->groupModel->findByCode($code);
            if ($group && (int)$group['sequence_id'] === $id) {
                if ($group['status'] !== 'lobby') {
                    flash('error', 'That group has already started — you can\'t join now.');
                } else {
                    $this->groupModel->incrementMembers((int)$group['id']);
                    if ($sequence['type'] === 'interactive') {
                        $this->memberModel->ensure((int)$group['id'], session_id(), false);
                    }
                    $prog['started']    = true;
                    $prog['mode']       = 'group';
                    $prog['is_host']    = false;
                    $prog['group_code'] = $group['code'];
                    $prog['start_time'] = time();
                    $this->saveSessionProgress($id, $prog);
                }
                redirect('/s/' . $slug);
            }
            if ($this->codesMatch($code, $sequence['start_code'])) {
                $prog['started']    = true;
                $prog['intro_shown'] = false;
                $prog['start_time'] = time();
                if ($sequence['type'] === 'interactive') {
                    $this->startInteractiveHost($id, $prog);   // group-only: straight to host lobby
                } else {
                    $prog['mode']       = null;   // null → lobby chooser (group / join / solo)
                    $prog['group_code'] = null;
                    $prog['is_host']    = false;
                }
                $this->saveSessionProgress($id, $prog);
            } else {
                flash('error', 'Incorrect code. Try again.');
            }
            redirect('/s/' . $slug);
        }

        // Joiners never submit codes (the host drives unlocking).
        if (($prog['mode'] ?? null) === 'group' && empty($prog['is_host'])) {
            redirect('/s/' . $slug);
        }

        // ── WHODUNIT: the accusation form (POST /accuse) is the only gate after start ──
        if ($sequence['type'] === 'whodunit') {
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
                    $prog['elapsed']         = max(0, time() - (int)($prog['start_time'] ?? time()));
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
                $this->progUpsert($id, ['unlocked_clues' => 1]);
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
                $this->progUpsert($id, [
                    'unlocked_clues' => (int)$prog['unlocked_clues'],
                ]);
            } else {
                flash('error', 'Incorrect code. Keep searching for clues.');
            }
            redirect('/s/' . $slug);
        }

        // Interactive has no solution code — the group vote (POST /vote) is the gate.
        if ($sequence['type'] === 'interactive') {
            redirect('/s/' . $slug);
        }

        // Stage C — "Ready to Solve?": enter the Solution Code to reveal the Solution
        if ($prog['ready_to_solve'] && !$prog['solution_shown']) {
            if (!$sequence['finale_requires_code']
                || $this->codesMatch($code, $sequence['finale_code'] ?? '')) {
                $prog['solution_shown']  = true;
                $prog['finale_unlocked'] = true;
                $prog['completed']       = true;
                $prog['elapsed']         = max(0, time() - (int)($prog['start_time'] ?? time()));
                $this->saveSessionProgress($id, $prog);
                $this->finalizeProgress($id, $prog);
                flash('success', 'solution_unlocked');
            } else {
                flash('error', 'Incorrect solution code. You\'re so close!');
            }
        }

        redirect('/s/' . $slug);
    }

    // ── Whodunit accusation submission ──────────────────────────────────────────

    public function accuse(string $slug): void
    {
        validate_csrf();

        $sequence = $this->seqModel->findBySlug($slug);
        if (!$sequence || (!$sequence['published'] && !isAdmin())) {
            notFound();
        }
        if ($sequence['type'] !== 'whodunit') {
            redirect('/s/' . $slug);
        }

        $id   = (int)$sequence['id'];
        $prog = $this->getSessionProgress($id);

        // In a group, only the host makes the accusation for everyone.
        if (($prog['mode'] ?? null) === 'group' && empty($prog['is_host'])) {
            redirect('/s/' . $slug);
        }

        // Must have started, and can't re-accuse once solved.
        if (empty($prog['started']) || !empty($prog['solution_shown'])) {
            redirect('/s/' . $slug);
        }

        $acc  = json_decode($sequence['accusation_json'] ?? '', true);
        $cats = is_array($acc) ? ($acc['categories'] ?? []) : [];
        if (empty($cats)) {
            flash('error', 'No accusation is set up for this mystery yet.');
            redirect('/s/' . $slug);
        }

        $picks   = (array)($_POST['acc'] ?? []);
        $results = [];
        $chosen  = [];
        $allRight = true;
        foreach ($cats as $i => $cat) {
            $pick = isset($picks[$i]) && $picks[$i] !== '' ? (int)$picks[$i] : -1;
            $right = ($pick === (int)($cat['answer'] ?? 0));
            $results[$i] = $right;
            $chosen[$i]  = $pick;
            if (!$right) {
                $allRight = false;
            }
        }

        $prog['acc_results']  = $results;
        $prog['acc_choices']  = $chosen;
        $prog['acc_attempts'] = (int)($prog['acc_attempts'] ?? 0) + 1;

        if ($allRight) {
            $prog['correct_accusation'] = true;
            $prog['ready_to_solve']     = true;
            $prog['solution_shown']     = true;
            $prog['finale_unlocked']    = true;
            $prog['completed']          = true;
            $prog['elapsed']            = max(0, time() - (int)($prog['start_time'] ?? time()));
            $this->saveSessionProgress($id, $prog);
            $this->finalizeProgress($id, $prog);
            flash('success', 'solution_unlocked');
        } else {
            $this->saveSessionProgress($id, $prog);
            flash('error', 'accusation_wrong');
        }

        redirect('/s/' . $slug);
    }

    public function resetProgress(string $slug): void
    {
        // Reset only clears the player's own session progress — low risk. If the CSRF
        // token is stale (e.g. the page sat open until the session expired), don't show
        // an error page; just send them back to a fresh start. A genuine cross-site
        // attempt simply gets redirected without clearing anything.
        if (Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $sequence = $this->seqModel->findBySlug($slug);
            if ($sequence) {
                $id = (int)$sequence['id'];
                unset($_SESSION['seq'][$id]);
                $this->progModel->resetProgress(session_id(), $id);

                // Also wipe puzzle "solved" flags for every puzzle attached to a
                // clue of this sequence, so the player has to re-solve them. If we
                // skip this, the puzzle page would still show its solved state on
                // a fresh playthrough.
                $clues      = $this->clueModel->getBySequenceId($id);
                $clueIds    = array_map('intval', array_column($clues, 'id'));
                $puzzleMap  = $clueIds ? $this->puzzleModel->getMapForClues($clueIds) : [];
                foreach ($puzzleMap as $pz) {
                    if (!empty($pz['slug']) && isset($_SESSION['puzzle'][$pz['slug']])) {
                        unset($_SESSION['puzzle'][$pz['slug']]);
                    }
                }
            }
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
            'mode'            => null,    // null = lobby chooser pending; 'solo' | 'group'
            'group_code'      => null,
            'is_host'         => false,
        ];
    }

    private function saveSessionProgress(int $seqId, array $prog): void
    {
        if (!isset($_SESSION['seq'])) {
            $_SESSION['seq'] = [];
        }
        $_SESSION['seq'][$seqId] = $prog;
    }

    /** Persist progress to the DB — skipped for admin play-tests. */
    private function progUpsert(int $seqId, array $data): void
    {
        if (isAdmin()) {
            return;
        }
        $this->progModel->upsertProgress(session_id(), $seqId, $data);
    }

    private function finalizeProgress(int $seqId, array $prog): void
    {
        if (isAdmin()) {
            return; // play-test: leave real completion stats untouched
        }
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
