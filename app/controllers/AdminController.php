<?php
declare(strict_types=1);

class AdminController
{
    private SequenceModel   $seqModel;
    private ClueModel       $clueModel;
    private CluePageModel   $pageModel;
    private PuzzlePageModel $puzzleModel;

    public function __construct()
    {
        $this->seqModel    = new SequenceModel();
        $this->clueModel   = new ClueModel();
        $this->pageModel   = new CluePageModel();
        $this->puzzleModel = new PuzzlePageModel();
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────

    public function dashboard(): void
    {
        requireAdmin();
        $stats    = $this->seqModel->getStats();
        $recent   = $this->seqModel->getAll(5, 0);
        view('admin.dashboard', [
            'stats'  => $stats,
            'recent' => $recent,
            'flash'  => getFlash(),
        ]);
    }

    // ── Diagnostic ──────────────────────────────────────────────────────────────

    public function diagnostic(): void
    {
        requireAdmin();
        view('admin.diagnostic', [
            'pageTitle' => 'Diagnostic',
            'activeNav' => 'diagnostic',
            'flash'     => getFlash(),
        ]);
    }

    /** AJAX: verify every required table, column and enum value is present. */
    public function checkSql(): never
    {
        requireAdmin();

        // What the application code requires (incl. all migration-added pieces).
        $requiredColumns = [
            'sequences' => [
                'id', 'title', 'slug', 'description', 'type', 'start_code', 'intro_access_code',
                'finale_code', 'finale_requires_code', 'introduction_content', 'intro_instruction',
                'intro_hint_text', 'finale_content', 'finale_instruction', 'finale_hint_text',
                'solution_content', 'thank_you_content', 'survey_link', 'accusation_json', 'published', 'published_at',
                'expires_at', 'view_count', 'completion_count', 'created_at', 'updated_at',
            ],
            'sequence_themes' => [
                'id', 'sequence_id', 'bg_color', 'text_color', 'button_color', 'btn_text_color',
                'accent_color', 'font_family', 'title_font', 'title_color', 'container_width', 'bg_image', 'custom_css',
            ],
            'clues' => [
                'id', 'sequence_id', 'title', 'content', 'reward_content', 'access_code',
                'instruction', 'file_path', 'hint_text', 'sort_order',
            ],
            'clue_pages' => [
                'id', 'clue_id', 'slug', 'site_type', 'site_name', 'page_title',
                'content', 'reward_content', 'nav_json', 'footer_text',
            ],
            'puzzle_pages' => [
                'id', 'clue_id', 'slug', 'puzzle_type', 'title', 'prompt',
                'data_json', 'reward_content', 'reward_file_path',
            ],
            'sequence_progress' => [
                'id', 'session_id', 'sequence_id', 'unlocked_clues', 'finale_unlocked',
                'completed', 'time_spent_seconds',
            ],
            'play_groups' => [
                'id', 'code', 'sequence_id', 'status', 'unlocked_clues', 'ready_to_solve',
                'solution_shown', 'hint_shown', 'accuse_round', 'outcome', 'vote_tallied', 'member_count', 'start_time',
            ],
            'group_members' => [
                'id', 'group_id', 'session_id', 'is_host', 'suspect_index',
                'vote_index', 'vote_round',
            ],
            'suspect_clues' => [
                'id', 'sequence_id', 'clue_id', 'suspect_index', 'body',
                'file_path', 'file_type', 'original_filename',
            ],
            'site_settings' => ['setting_key', 'setting_value'],
            'admins'        => ['id', 'username', 'password_hash'],
            'login_attempts' => ['id', 'ip_address', 'username', 'success'],
        ];
        $requiredEnums = [
            'sequences.type'           => ['sequential', 'open', 'gameboard', 'whodunit', 'interactive'],
            'clue_pages.site_type'     => ['news', 'corporate', 'blog', 'archive', 'calendar', 'inbox', 'sms', 'invoice', 'receipt', 'map', 'access_log'],
            'puzzle_pages.puzzle_type' => ['order', 'caesar', 'phone', 'access', 'elim', 'wordsearch', 'match', 'hotspot', 'fillblank'],
            'play_groups.status'       => ['lobby', 'select', 'active'],
        ];

        $db      = Database::getInstance();
        $missing = [];

        $tableRows = $db->fetchAll("SELECT TABLE_NAME AS t FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()");
        $tables    = array_map(static fn ($r) => $r['t'], $tableRows);

        foreach ($requiredColumns as $table => $cols) {
            if (!in_array($table, $tables, true)) {
                $missing[] = "table {$table}";
                continue;
            }
            $colRows = $db->fetchAll(
                "SELECT COLUMN_NAME AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
                [$table]
            );
            $have = array_map(static fn ($r) => $r['c'], $colRows);
            foreach ($cols as $col) {
                if (!in_array($col, $have, true)) {
                    $missing[] = "{$table}.{$col}";
                }
            }
        }

        foreach ($requiredEnums as $key => $vals) {
            [$table, $col] = explode('.', $key);
            if (!in_array($table, $tables, true)) {
                continue; // already reported as a missing table
            }
            $row  = $db->fetch(
                "SELECT COLUMN_TYPE AS t FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
                [$table, $col]
            );
            $type = (string)($row['t'] ?? '');
            foreach ($vals as $v) {
                if (stripos($type, "'" . $v . "'") === false) {
                    $missing[] = "{$key} value '{$v}'";
                }
            }
        }

        jsonResponse(['ok' => empty($missing), 'missing' => $missing]);
    }

    // ── Sequence list ─────────────────────────────────────────────────────────

    public function sequences(): void
    {
        requireAdmin();
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $search = Security::sanitizeString($_GET['search'] ?? '');
        $offset = ($page - 1) * ITEMS_PER_PAGE;
        $total  = $this->seqModel->count($search);
        $items  = $this->seqModel->getAll(ITEMS_PER_PAGE, $offset, $search);

        view('admin.sequences.index', [
            'sequences'   => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => ITEMS_PER_PAGE,
            'search'      => $search,
            'flash'       => getFlash(),
        ]);
    }

    // ── Create sequence ───────────────────────────────────────────────────────

    public function createSequence(): void
    {
        requireAdmin();
        view('admin.sequences.create', ['flash' => getFlash(), 'errors' => []]);
    }

    public function storeSequence(): void
    {
        requireAdmin();
        validate_csrf();

        [$data, $errors] = $this->validateSequenceInput($_POST);
        if (!empty($errors)) {
            view('admin.sequences.create', ['flash' => null, 'errors' => $errors, 'input' => $_POST]);
            return;
        }

        // Unique slug
        $baseSlug = slugify($data['title']);
        $data['slug'] = $this->uniqueSlug($baseSlug);

        $id = $this->seqModel->create($data);
        $this->seqModel->saveTheme($id, $this->extractTheme($_POST));
        $this->processSequenceFiles($id);

        flash('success', 'Sequence created successfully.');
        redirect('/admin/sequences/' . $id . '/clues');
    }

    // ── Edit sequence ─────────────────────────────────────────────────────────

    public function editSequence(int $id): void
    {
        requireAdmin();
        $seq   = $this->seqModel->findById($id) ?: notFound();
        $theme = $this->seqModel->getTheme($id);
        $analytics = (new ProgressModel())->getAnalytics($id);

        $clues   = $this->clueModel->getBySequenceId($id);
        $puzzles = $this->puzzleModel->getMapForClues(array_map('intval', array_column($clues, 'id')));
        $health  = $this->sequenceHealth($seq, $clues, $puzzles);

        view('admin.sequences.edit', [
            'sequence'  => $seq,
            'theme'     => $theme,
            'analytics' => $analytics,
            'health'    => $health,
            'flash'     => getFlash(),
            'errors'    => [],
        ]);
    }

    /**
     * Inspect a sequence for common publish-blocking problems.
     * Returns a list of ['level' => 'error'|'warn', 'msg' => string].
     */
    private function sequenceHealth(array $seq, array $clues, array $puzzles): array
    {
        $issues = [];
        $add = static function (string $level, string $msg) use (&$issues) {
            $issues[] = ['level' => $level, 'msg' => $msg];
        };
        $checkMedia = static function (?string $path, string $label) use ($add) {
            if (!empty($path) && !file_exists(UPLOAD_PATH . '/' . ltrim($path, '/'))) {
                $add('error', "Missing media file for {$label}.");
            }
        };

        $isWhodunit = ($seq['type'] ?? '') === 'whodunit';

        if (trim((string)($seq['start_code'] ?? '')) === '') {
            $add('error', "No start code is set — players can't begin.");
        }
        if (!$isWhodunit && !empty($seq['finale_requires_code']) && trim((string)($seq['finale_code'] ?? '')) === '') {
            $add('error', 'The finale requires a code, but no finale code is set.');
        }
        if ($isWhodunit) {
            $acc  = json_decode($seq['accusation_json'] ?? '', true);
            $cats = is_array($acc) ? ($acc['categories'] ?? []) : [];
            if (empty($cats)) {
                $add('error', 'This Who-dun-it has no accusation set — add at least one category with a correct answer.');
            } else {
                if (count($cats) < 2) {
                    $add('error', 'Who-dun-it accusations need at least 2 categories (e.g., suspect, weapon, location).');
                }
                foreach ($cats as $i => $cat) {
                    $name = trim((string)($cat['label'] ?? '')) !== '' ? $cat['label'] : ('Category ' . ($i + 1));
                    $opts = is_array($cat['options'] ?? null) ? $cat['options'] : [];
                    if (count($opts) < 2) {
                        $add('warn', "Who-dun-it category \"{$name}\" only has " . count($opts) . ' option' . (count($opts) === 1 ? '' : 's') . ' — add at least 2 for it to be a real choice.');
                    }
                }
            }
        }
        $isInteractive = ($seq['type'] ?? '') === 'interactive';
        if ($isInteractive) {
            $acc      = json_decode($seq['accusation_json'] ?? '', true);
            $cats     = is_array($acc) ? ($acc['categories'] ?? []) : [];
            $suspects = !empty($cats) ? ($cats[0]['options'] ?? []) : [];
            $count    = count($suspects);
            if ($count < 2) {
                $add('error', 'Interactive mysteries need at least 2 suspects — add them in Interactive Setup.');
            }
            // Suspect clues form a matrix: once any suspect has a clue for a step,
            // every suspect must have one for that step (so no player is left blank).
            if ($count >= 1 && !empty($clues)) {
                $matrix = (new SuspectClueModel())->getMatrix((int)$seq['id']);
                foreach ($clues as $i => $clue) {
                    $row  = $matrix[(int)$clue['id']] ?? [];
                    $have = count($row);
                    if ($have > 0 && $have < $count) {
                        $clabel  = $clue['title'] ?: ('Clue ' . ($i + 1));
                        $missing = [];
                        for ($s = 0; $s < $count; $s++) {
                            if (!isset($row[$s])) {
                                $missing[] = trim((string)($suspects[$s]['name'] ?? '')) !== '' ? $suspects[$s]['name'] : ('Suspect ' . ($s + 1));
                            }
                        }
                        $add('error', "\"{$clabel}\": some suspects have a clue but these don't — " . implode(', ', $missing) . '. Give every suspect a clue for this step, or clear them all.');
                    }
                }
            }
        }
        if (count($clues) === 0) {
            $add('warn', 'This sequence has no clues yet.');
        }
        $checkMedia($seq['intro_file_path'] ?? null, 'the introduction');
        $checkMedia($seq['finale_file_path'] ?? null, 'the finale');
        $checkMedia($seq['solution_file_path'] ?? null, 'the solution');

        $isGameboard = ($seq['type'] ?? '') === 'gameboard';
        foreach ($clues as $i => $clue) {
            $label = $clue['title'] ?: ('Clue ' . ($i + 1));
            if (!$isWhodunit && trim((string)($clue['access_code'] ?? '')) === '') {
                $add('error', "\"{$label}\" has no access code.");
            }
            $checkMedia($clue['file_path'] ?? null, "\"{$label}\"");
            if (!richHasContent($clue['reward_content'] ?? null)) {
                $add('warn', ($isGameboard ? "Game-board tile" : "Clue") . " \"{$label}\" has no reward.");
            }
            $hasHint = richHasContent($clue['hint_text'] ?? null) || !empty($clue['hint_file_path']);
            if (!$hasHint) {
                $add('warn', ($isGameboard ? "Game-board tile" : "Clue") . " \"{$label}\" has no hint.");
            }
            $pz = $puzzles[(int)$clue['id']] ?? null;
            if ($pz) {
                foreach ($this->puzzleHealth($pz) as $msg) {
                    $add('warn', "Puzzle on \"{$label}\": {$msg}.");
                }
            }
        }
        return $issues;
    }

    /** Per-puzzle config sanity checks; returns short problem strings. */
    private function puzzleHealth(array $pz): array
    {
        $out = [];
        $d = json_decode($pz['data_json'] ?? '{}', true) ?: [];
        switch ($pz['puzzle_type'] ?? '') {
            case 'wordsearch':
                if (empty($d['words'])) { $out[] = 'no words set'; }
                if (trim((string)($d['phrase'] ?? '')) === '') { $out[] = 'no hidden phrase set'; }
                break;
            case 'match':
                if (count($d['pairs'] ?? []) < 2) { $out[] = 'fewer than 2 pairs'; }
                break;
            case 'hotspot':
                if (empty($d['image'])) {
                    $out[] = 'no image uploaded';
                } elseif (!file_exists(UPLOAD_PATH . '/' . ltrim((string)$d['image'], '/'))) {
                    $out[] = 'image file is missing';
                }
                if (empty($d['spots'])) { $out[] = 'no spots placed'; }
                break;
            case 'access':
                if (trim((string)($d['code'] ?? '')) === '') { $out[] = 'no code set'; }
                break;
            case 'caesar':
                if (trim((string)($d['phrase'] ?? '')) === '') { $out[] = 'no phrase set'; }
                break;
            case 'phone':
                if (trim((string)($d['code'] ?? '')) === '') { $out[] = 'no code set'; }
                break;
            case 'elim':
                if (count($d['items'] ?? []) < 2) { $out[] = 'fewer than 2 items'; }
                if (empty($d['eliminate'])) { $out[] = 'no items marked to eliminate'; }
                break;
            case 'order':
                if (count($d['items'] ?? []) < 2) { $out[] = 'fewer than 2 items'; }
                break;
        }
        return $out;
    }

    /** Command-center overview: the whole mystery flow at a glance. */
    public function sequenceOverview(int $id): void
    {
        requireAdmin();
        $seq     = $this->seqModel->findById($id) ?: notFound();
        $clues   = $this->clueModel->getBySequenceId($id);
        $clueIds = array_map('intval', array_column($clues, 'id'));
        $pages   = $this->pageModel->getMapForClues($clueIds);
        $puzzles = $this->puzzleModel->getMapForClues($clueIds);

        view('admin.sequences.overview', [
            'sequence' => $seq,
            'clues'    => $clues,
            'pages'    => $pages,
            'puzzles'  => $puzzles,
            'flash'    => getFlash(),
        ]);
    }

    /** Printable QR-code kit: start link + every puzzle and decoy-page link. */
    public function printKit(int $id): void
    {
        requireAdmin();
        $seq     = $this->seqModel->findById($id) ?: notFound();
        $clues   = $this->clueModel->getBySequenceId($id);
        $clueIds = array_map('intval', array_column($clues, 'id'));
        $pages   = $this->pageModel->getMapForClues($clueIds);
        $puzzles = $this->puzzleModel->getMapForClues($clueIds);

        $targets = [];
        $targets[] = ['kind' => 'Start Here', 'label' => $seq['title'] ?: 'Sequence', 'url' => url('s/' . $seq['slug'])];
        foreach ($clues as $i => $clue) {
            $label = $clue['title'] ?: ('Clue ' . ($i + 1));
            if ($z = ($puzzles[(int)$clue['id']] ?? null)) {
                $targets[] = ['kind' => 'Puzzle', 'label' => $label, 'url' => url('z/' . $z['slug'])];
            }
            if ($p = ($pages[(int)$clue['id']] ?? null)) {
                $targets[] = ['kind' => 'Page', 'label' => $label, 'url' => url('p/' . $p['slug'])];
            }
        }

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        require APP_ROOT . '/views/admin/sequences/print.php';
        exit;
    }

    /**
     * Answer Guide: a downloadable PDF (built client-side with jsPDF) listing every
     * code and puzzle answer in play order — start code, introduction code, then each
     * clue's code followed by its puzzle answers, and finally the solution code (or,
     * for Who-dun-it / Interactive, the correct accusation).
     */
    public function answerGuide(int $id): void
    {
        requireAdmin();
        $seq     = $this->seqModel->findById($id) ?: notFound();
        $clues   = $this->clueModel->getBySequenceId($id);
        $clueIds = array_map('intval', array_column($clues, 'id'));
        $puzzles = $this->puzzleModel->getMapForClues($clueIds);

        $type       = $seq['type'] ?? 'sequential';
        $isWhodunit = $type === 'whodunit';
        $noSolCode  = in_array($type, ['whodunit', 'interactive'], true);

        $guide = [];

        // 1. Start code — every mystery has one.
        $guide[] = [
            'label' => 'Start Code',
            'lines' => [['k' => 'Code', 'v' => trim((string)($seq['start_code'] ?? '')) ?: '(not set)']],
        ];

        // 2. Introduction code — the gate before Clue 1 (only when one is configured).
        $introCode = trim((string)($seq['intro_access_code'] ?? ''));
        if ($introCode !== '') {
            $guide[] = [
                'label' => 'Introduction Code',
                'lines' => [['k' => 'Code', 'v' => $introCode]],
            ];
        }

        // 3. Each clue: its access code, then the answers to its puzzle (if any).
        foreach ($clues as $i => $clue) {
            $n     = $i + 1;
            $title = trim((string)($clue['title'] ?? ''));
            $name  = $title !== '' ? $title : ('Clue ' . $n);

            // Who-dun-it clues are evidence, not gates — they carry no access code.
            if (!$isWhodunit) {
                $guide[] = [
                    'label' => 'Clue ' . $n . ' Code' . ($title !== '' ? ' — ' . $name : ''),
                    'lines' => [['k' => 'Code', 'v' => trim((string)($clue['access_code'] ?? '')) ?: '(not set)']],
                ];
            }

            $pz = $puzzles[(int)$clue['id']] ?? null;
            if ($pz) {
                $ans = $this->puzzleAnswer($pz);
                $pzTitle = trim((string)($pz['title'] ?? ''));
                $guide[] = [
                    'label' => 'Clue ' . $n . ' Puzzle Answers — ' . ($pzTitle !== '' ? $pzTitle : $ans['type']) . ' (' . $ans['type'] . ')',
                    'lines' => $ans['lines'],
                ];
            }
        }

        // 4. Finale — a solution code, or the correct accusation for deduction games.
        if ($noSolCode) {
            $acc   = json_decode($seq['accusation_json'] ?? '', true);
            $cats  = is_array($acc) ? ($acc['categories'] ?? []) : [];
            $lines = [];
            foreach ($cats as $j => $cat) {
                $opts   = is_array($cat['options'] ?? null) ? $cat['options'] : [];
                $ansIdx = (int)($cat['answer'] ?? 0);
                $optName = $opts[$ansIdx]['name'] ?? ('Option ' . ($ansIdx + 1));
                $catLbl  = trim((string)($cat['label'] ?? '')) !== '' ? $cat['label'] : ('Category ' . ($j + 1));
                $lines[] = ['k' => $catLbl, 'v' => $optName];
            }
            if (empty($lines)) {
                $lines[] = ['k' => 'Answer', 'v' => '(no accusation set)'];
            }
            $guide[] = ['label' => 'Solution — Correct Accusation', 'lines' => $lines];
        } else {
            $finaleCode = trim((string)($seq['finale_code'] ?? ''));
            if ($finaleCode !== '') {
                $code = $finaleCode;
            } elseif (!empty($seq['finale_requires_code'])) {
                $code = '(required but not set)';
            } else {
                $code = '(no code required)';
            }
            $guide[] = ['label' => 'Solution Code', 'lines' => [['k' => 'Code', 'v' => $code]]];
        }

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        require APP_ROOT . '/views/admin/sequences/answer_guide.php';
        exit;
    }

    /**
     * Extract a puzzle's answer(s) for the Answer Guide, as a list of key/value
     * lines plus a human label for the puzzle type. Mirrors how each puzzle type
     * stores its solution in data_json (see PuzzlePageController).
     *
     * @return array{type:string, lines:array<int,array{k:string,v:string}>}
     */
    private function puzzleAnswer(array $puzzle): array
    {
        $rawType = $puzzle['puzzle_type'] ?? 'order';
        $type    = in_array($rawType, ['order', 'caesar', 'phone', 'access', 'elim', 'wordsearch', 'match', 'hotspot', 'fillblank'], true)
                   ? $rawType : 'order';
        $d = json_decode($puzzle['data_json'] ?? '{}', true) ?: [];

        $clean = static fn (array $a): array => array_values(array_filter(
            array_map(static fn ($v) => trim((string)$v), $a),
            static fn ($v) => $v !== ''
        ));

        $labels = [
            'order'      => 'Put in Order',
            'caesar'     => 'Caesar Cipher',
            'phone'      => 'Phone Keypad',
            'access'     => 'Access Code',
            'elim'       => 'Elimination',
            'wordsearch' => 'Word Search',
            'match'      => 'Matching',
            'hotspot'    => 'Hotspot',
            'fillblank'  => 'Fill in the Blank',
        ];

        $lines = [];
        switch ($type) {
            case 'caesar':
                $lines[] = ['k' => 'Shift', 'v' => (string)(int)($d['shift'] ?? 0)];
                $lines[] = ['k' => 'Answer (decoded)', 'v' => trim((string)($d['phrase'] ?? '')) ?: '(none)'];
                break;

            case 'phone':
                $lines[] = ['k' => 'Answer', 'v' => trim((string)($d['code'] ?? '')) ?: '(none)'];
                break;

            case 'access':
                $code = (string)(preg_replace('/\D+/', '', (string)($d['code'] ?? '')) ?? '');
                $lines[] = ['k' => 'Code', 'v' => $code !== '' ? $code : '(none)'];
                break;

            case 'elim':
                $items = $clean($d['items'] ?? []);
                $elim  = array_values(array_intersect($clean($d['eliminate'] ?? []), $items));
                $keep  = array_values(array_diff($items, $elim));
                $lines[] = ['k' => 'Eliminate', 'v' => $elim ? implode(', ', $elim) : '(none)'];
                $lines[] = ['k' => 'Keep', 'v' => $keep ? implode(', ', $keep) : '(none)'];
                break;

            case 'wordsearch':
                $words = $clean($d['words'] ?? []);
                $lines[] = ['k' => 'Words', 'v' => $words ? implode(', ', array_map('strtoupper', $words)) : '(none)'];
                $lines[] = ['k' => 'Hidden phrase', 'v' => trim((string)($d['phrase'] ?? '')) ?: '(none)'];
                break;

            case 'match':
                foreach (($d['pairs'] ?? []) as $p) {
                    $l = trim((string)($p['l'] ?? ''));
                    $r = trim((string)($p['r'] ?? ''));
                    if ($l !== '' && $r !== '') {
                        $lines[] = ['k' => $l, 'v' => $r];
                    }
                }
                if (empty($lines)) {
                    $lines[] = ['k' => 'Matches', 'v' => '(none)'];
                }
                break;

            case 'hotspot':
                $spots = is_array($d['spots'] ?? null) ? $d['spots'] : [];
                $lines[] = ['k' => 'Spots to find', 'v' => (string)count($spots)];
                foreach ($spots as $idx => $sp) {
                    $x = (int)round((float)($sp['x'] ?? 0));
                    $y = (int)round((float)($sp['y'] ?? 0));
                    $lines[] = ['k' => 'Spot ' . ($idx + 1), 'v' => $x . '% across, ' . $y . '% down'];
                }
                break;

            case 'fillblank':
                preg_match_all('/\{([^{}]+)\}/', (string)($d['text'] ?? ''), $m);
                $blanks = array_map('trim', $m[1] ?? []);
                foreach ($blanks as $idx => $b) {
                    if ($b === '') { continue; }
                    $lines[] = ['k' => 'Blank ' . ($idx + 1), 'v' => $b];
                }
                if (empty($lines)) {
                    $lines[] = ['k' => 'Blanks', 'v' => '(none)'];
                }
                break;

            case 'order':
            default:
                $items = $clean($d['items'] ?? []);
                foreach ($items as $idx => $it) {
                    $lines[] = ['k' => (string)($idx + 1), 'v' => $it];
                }
                if (empty($items)) {
                    $lines[] = ['k' => 'Order', 'v' => '(none)'];
                }
                break;
        }

        return ['type' => $labels[$type] ?? ucfirst($type), 'lines' => $lines];
    }

    /** Printable, styled promo flyer: title, description, and a start-here QR. */
    public function promoPage(int $id): void
    {
        requireAdmin();
        $seq      = $this->seqModel->findById($id) ?: notFound();
        $theme    = $this->seqModel->getTheme($id);
        $startUrl = url('s/' . $seq['slug']);

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        require APP_ROOT . '/views/admin/sequences/promo.php';
        exit;
    }

    public function updateSequence(int $id): void
    {
        requireAdmin();
        validate_csrf();
        $seq = $this->seqModel->findById($id) ?: notFound();

        [$data, $errors] = $this->validateSequenceInput($_POST, $id);
        if (!empty($errors)) {
            $theme     = $this->seqModel->getTheme($id);
            $analytics = (new ProgressModel())->getAnalytics($id);
            $clues     = $this->clueModel->getBySequenceId($id);
            $puzzles   = $this->puzzleModel->getMapForClues(array_map('intval', array_column($clues, 'id')));
            view('admin.sequences.edit', [
                'sequence'  => array_merge($seq, $_POST),
                'theme'     => $theme,
                'analytics' => $analytics,
                'health'    => $this->sequenceHealth($seq, $clues, $puzzles),
                'flash'     => null,
                'errors'    => $errors,
            ]);
            return;
        }

        // If the slug field was left blank, default it from the title
        // (spaces → dashes), kept unique across other sequences.
        if (empty($data['slug'])) {
            $base = slugify($data['title'] ?? '');
            $slug = $base;
            $i = 1;
            while ($base !== '' && ($x = $this->seqModel->findBySlug($slug)) && (int)$x['id'] !== $id) {
                $slug = $base . '-' . $i++;
            }
            $data['slug'] = $slug ?: $seq['slug'];
        }

        $this->seqModel->update($id, $data);
        $this->seqModel->saveTheme($id, $this->extractTheme($_POST));
        $this->processSequenceFiles($id, $seq);

        flash('success', 'Sequence updated successfully.');
        redirect('/admin/sequences/' . $id . '/edit');
    }

    // ── Whodunnit setup (dedicated page) ────────────────────────────────────────

    public function whodunnitSetup(int $id): void
    {
        requireAdmin();
        $seq = $this->seqModel->findById($id) ?: notFound();
        view('admin.sequences.whodunnit', [
            'sequence' => $seq,
            'flash'    => getFlash(),
        ]);
    }

    public function saveWhodunnit(int $id): void
    {
        requireAdmin();
        validate_csrf();
        $this->seqModel->findById($id) ?: notFound();

        $this->seqModel->update($id, [
            'accusation_json' => $this->normalizeAccusation($_POST['accusation_json'] ?? ''),
        ]);

        flash('success', 'Whodunnit setup saved.');
        redirect('/admin/sequences/' . $id . '/whodunnit');
    }

    // ── Interactive setup (suspects + game cards, dedicated page) ────────────────

    public function interactiveSetup(int $id): void
    {
        requireAdmin();
        $seq = $this->seqModel->findById($id) ?: notFound();
        view('admin.sequences.interactive', [
            'sequence' => $seq,
            'flash'    => getFlash(),
        ]);
    }

    public function saveInteractive(int $id): void
    {
        requireAdmin();
        validate_csrf();
        $this->seqModel->findById($id) ?: notFound();

        // Reuse the whodunit normaliser, then force exactly one category labelled
        // "Suspects" — Interactive only ever has the single suspect list.
        $json = $this->normalizeAccusation($_POST['accusation_json'] ?? '');
        if ($json !== null) {
            $decoded = json_decode($json, true);
            $cats    = $decoded['categories'] ?? [];
            if (!empty($cats)) {
                $cats = [array_merge($cats[0], ['label' => 'Suspects'])];
                $json = json_encode(['prompt' => $decoded['prompt'] ?? '', 'categories' => $cats]);
            }
        }

        $this->seqModel->update($id, ['accusation_json' => $json]);

        flash('success', 'Interactive setup saved.');
        redirect('/admin/sequences/' . $id . '/interactive');
    }

    // ── Suspect clues matrix (Interactive) ──────────────────────────────────────

    public function suspectCluesEditor(int $id): void
    {
        requireAdmin();
        $seq   = $this->seqModel->findById($id) ?: notFound();
        $clues = $this->clueModel->getBySequenceId($id);
        $acc   = json_decode($seq['accusation_json'] ?? '', true);
        $cats  = is_array($acc) ? ($acc['categories'] ?? []) : [];
        $suspects = !empty($cats) ? ($cats[0]['options'] ?? []) : [];
        $matrix   = (new SuspectClueModel())->getMatrix($id);

        view('admin.sequences.suspect_clues', [
            'sequence' => $seq,
            'clues'    => $clues,
            'suspects' => $suspects,
            'matrix'   => $matrix,
            'flash'    => getFlash(),
        ]);
    }

    public function saveSuspectClues(int $id): void
    {
        requireAdmin();
        validate_csrf();
        $seq      = $this->seqModel->findById($id) ?: notFound();
        $clueIds  = array_map('intval', array_column($this->clueModel->getBySequenceId($id), 'id'));
        $acc      = json_decode($seq['accusation_json'] ?? '', true);
        $cats     = is_array($acc) ? ($acc['categories'] ?? []) : [];
        $suspectCount = !empty($cats) ? count($cats[0]['options'] ?? []) : 0;

        $model    = new SuspectClueModel();
        $bodies   = (array)($_POST['sc_body']  ?? []);   // [clueId][suspectIndex] => text
        $paths    = (array)($_POST['sc_path']  ?? []);   // [clueId][suspectIndex] => uploads path
        $types    = (array)($_POST['sc_type']  ?? []);
        $names    = (array)($_POST['sc_name']  ?? []);

        foreach ($clueIds as $clueId) {
            for ($s = 0; $s < $suspectCount; $s++) {
                $body = Security::sanitizeString((string)($bodies[$clueId][$s] ?? '')) ?: null;
                $path = $this->safeUploadPath((string)($paths[$clueId][$s] ?? ''));
                $type = preg_match('/^[a-z]+$/', (string)($types[$clueId][$s] ?? '')) ? (string)$types[$clueId][$s] : null;
                $name = Security::sanitizeString((string)($names[$clueId][$s] ?? '')) ?: null;
                $orphan = $model->set($id, $clueId, $s, $body, $path ?: null, $type, $name);
                if ($orphan) {
                    FileUpload::delete($orphan);
                }
            }
        }

        flash('success', 'Suspect clues saved.');
        redirect('/admin/sequences/' . $id . '/suspect-clues');
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function deleteSequence(int $id): void
    {
        requireAdmin();
        validate_csrf();
        $seq = $this->seqModel->findById($id) ?: notFound();

        // Remove media files
        foreach (['intro_file_path', 'intro_hint_file_path', 'finale_file_path'] as $col) {
            if ($seq[$col]) {
                FileUpload::delete($seq[$col]);
            }
        }
        $clues = $this->clueModel->getBySequenceId($id);
        foreach ($clues as $c) {
            FileUpload::delete($c['file_path'] ?? '');
            FileUpload::delete($c['hint_file_path'] ?? '');
        }

        // Remove whodunit/interactive character-card + game-card images
        // (stored only inside accusation_json).
        $acc = json_decode($seq['accusation_json'] ?? '', true);
        if (is_array($acc)) {
            foreach ($acc['categories'] ?? [] as $cat) {
                foreach ($cat['options'] ?? [] as $opt) {
                    foreach (['image', 'card'] as $imgKey) {
                        $img = is_array($opt) ? (string)($opt[$imgKey] ?? '') : '';
                        if ($img !== '' && strpos($img, '..') === false) {
                            FileUpload::delete($img);
                        }
                    }
                }
            }
        }

        // Remove suspect-clue media (Interactive).
        foreach ((new SuspectClueModel())->filePathsForSequence($id) as $p) {
            if ($p !== '' && strpos($p, '..') === false) {
                FileUpload::delete($p);
            }
        }

        $this->seqModel->delete($id);
        flash('success', 'Sequence "' . e($seq['title']) . '" deleted.');
        redirect('/admin/sequences');
    }

    // ── Publish toggle ────────────────────────────────────────────────────────

    public function togglePublish(int $id): void
    {
        requireAdmin();
        validate_csrf();
        $this->seqModel->findById($id) ?: notFound();
        $this->seqModel->togglePublish($id);
        if (($_POST['redirect_to'] ?? '') === 'edit') {
            redirect('/admin/sequences/' . $id . '/edit');
        }
        redirect('/admin/sequences');
    }

    // ── Reset statistics ──────────────────────────────────────────────────────

    public function resetStats(int $id): void
    {
        requireAdmin();
        validate_csrf();
        if (!$this->seqModel->findById($id)) {
            jsonResponse(['success' => false, 'error' => 'Sequence not found.'], 404);
        }
        $this->seqModel->resetStats($id);
        (new ProgressModel())->resetAllForSequence($id);
        jsonResponse(['success' => true]);
    }

    // ── Duplicate ─────────────────────────────────────────────────────────────

    public function duplicateSequence(int $id): void
    {
        requireAdmin();
        validate_csrf();
        $newId = $this->seqModel->duplicate($id);
        if ($newId) {
            flash('success', 'Sequence duplicated successfully.');
            redirect('/admin/sequences/' . $newId . '/edit');
        } else {
            flash('error', 'Failed to duplicate sequence.');
            redirect('/admin/sequences');
        }
    }

    // ── Clue management ───────────────────────────────────────────────────────

    public function manageClues(int $seqId): void
    {
        requireAdmin();
        $seq    = $this->seqModel->findById($seqId) ?: notFound();
        $clues  = $this->clueModel->getBySequenceId($seqId);
        $clueIds = array_column($clues, 'id');
        $pages   = $this->pageModel->getMapForClues(array_map('intval', $clueIds));
        $puzzles = $this->puzzleModel->getMapForClues(array_map('intval', $clueIds));

        view('admin.sequences.clues', [
            'sequence' => $seq,
            'clues'    => $clues,
            'pages'    => $pages,
            'puzzles'  => $puzzles,
            'flash'    => getFlash(),
        ]);
    }

    public function addClue(int $seqId): void
    {
        requireAdmin();
        validate_csrf();
        $seq = $this->seqModel->findById($seqId) ?: notFound();

        // Who-dun-it clues are evidence, not gates — they don't use access codes.
        $needsCode = ($seq['type'] ?? '') !== 'whodunit';
        if ($needsCode && empty($_POST['access_code'])) {
            flash('error', 'Access code is required.');
            redirect('/admin/sequences/' . $seqId . '/clues');
        }

        $data = [
            'sequence_id'    => $seqId,
            'title'          => Security::sanitizeString($_POST['title'] ?? ''),
            'content'        => $_POST['content'] ?? null,
            'reward_content' => $_POST['reward_content'] ?? null,
            'access_code'    => Security::sanitizeString($_POST['access_code'] ?? ''),
            'instruction'    => Security::sanitizeString($_POST['clue_instruction'] ?? '') ?: null,
            'hint_text'      => $_POST['hint_text'] ?? null,
        ];

        $clueId = $this->clueModel->create($data);

        // Handle file upload if provided
        $this->processClueFiles($clueId, (string)$seqId);

        flash('success', 'Clue added.');
        redirect('/admin/sequences/' . $seqId . '/clues');
    }

    public function editClue(int $clueId): void
    {
        requireAdmin();
        validate_csrf();
        $clue = $this->clueModel->findById($clueId) ?: notFound();
        $seq  = $this->seqModel->findById((int)$clue['sequence_id']);

        // Who-dun-it clues are evidence, not gates — they don't use access codes.
        $needsCode = ($seq['type'] ?? '') !== 'whodunit';
        if ($needsCode && empty($_POST['access_code'])) {
            flash('error', 'Access code is required.');
            redirect('/admin/sequences/' . $clue['sequence_id'] . '/clues');
        }

        $data = [
            'title'          => Security::sanitizeString($_POST['title'] ?? ''),
            'content'        => $_POST['content'] ?? null,
            'reward_content' => $_POST['reward_content'] ?? null,
            'access_code'    => Security::sanitizeString($_POST['access_code'] ?? ''),
            'instruction'    => Security::sanitizeString($_POST['clue_instruction'] ?? '') ?: null,
            'hint_text'      => $_POST['hint_text'] ?? null,
            'file_caption'   => Security::sanitizeString($_POST['file_caption'] ?? ''),
            'hint_caption'   => Security::sanitizeString($_POST['hint_caption'] ?? ''),
        ];

        $this->clueModel->update($clueId, $data);
        $this->processClueFiles($clueId, (string)$clue['sequence_id']);

        flash('success', 'Clue updated.');
        redirect('/admin/sequences/' . $clue['sequence_id'] . '/clues');
    }

    public function deleteClue(int $clueId): void
    {
        requireAdmin();
        validate_csrf();
        $clue = $this->clueModel->findById($clueId) ?: notFound();

        FileUpload::delete($clue['file_path'] ?? '');
        FileUpload::delete($clue['hint_file_path'] ?? '');
        $this->clueModel->delete($clueId);

        flash('success', 'Clue deleted.');
        redirect('/admin/sequences/' . $clue['sequence_id'] . '/clues');
    }

    public function reorderClues(): never
    {
        requireAdmin();
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['csrf_token']) || !Security::validateCsrfToken($data['csrf_token'])) {
            jsonResponse(['success' => false, 'error' => 'CSRF mismatch.'], 403);
        }
        $ids = array_map('intval', $data['order'] ?? []);
        if (empty($ids)) {
            jsonResponse(['success' => false, 'error' => 'No order provided.'], 400);
        }
        $ok = $this->clueModel->updateOrder($ids);
        jsonResponse(['success' => $ok]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validateSequenceInput(array $post, int $excludeId = 0): array
    {
        $errors = [];
        $data   = [];

        $data['title'] = Security::sanitizeString($post['title'] ?? '');
        if (empty($data['title'])) {
            $errors[] = 'Title is required.';
        }

        $data['description']          = $post['description'] ?? null;
        $data['type']                 = in_array($post['type'] ?? '', ['sequential', 'open', 'gameboard', 'whodunit', 'interactive'], true)
                                        ? $post['type'] : 'sequential';
        // Game Board visual theme (ignored for other types, but always stored).
        $data['gameboard_theme']      = in_array($post['gameboard_theme'] ?? '', ['candyland', 'winter', 'spooky', 'pool', 'birthday'], true)
                                        ? $post['gameboard_theme'] : 'candyland';
        // Accusation config is edited on its own page (/whodunnit), not here, so the
        // main sequence form never touches accusation_json.
        $data['start_code']           = Security::sanitizeString($post['start_code'] ?? '');
        $data['intro_access_code']    = Security::sanitizeString($post['intro_access_code'] ?? '') ?: null;
        $data['finale_code']          = Security::sanitizeString($post['finale_code'] ?? '') ?: null;
        $data['finale_requires_code'] = !empty($post['finale_requires_code']) ? 1 : 0;
        $data['introduction_content'] = $post['introduction_content'] ?? null;
        $data['intro_instruction']    = Security::sanitizeString($post['intro_instruction'] ?? '') ?: null;
        $data['intro_hint_text']      = $post['intro_hint_text'] ?? null;
        $data['intro_hint_caption']   = Security::sanitizeString($post['intro_hint_caption'] ?? '') ?: null;
        $data['finale_content']       = $post['finale_content'] ?? null;
        $data['finale_instruction']   = Security::sanitizeString($post['finale_instruction'] ?? '') ?: null;
        $data['finale_hint_text']     = $post['finale_hint_text'] ?? null;
        $data['finale_hint_caption']  = Security::sanitizeString($post['finale_hint_caption'] ?? '') ?: null;
        $data['solution_content']     = $post['solution_content'] ?? null;
        $data['solution_caption']     = Security::sanitizeString($post['solution_caption'] ?? '') ?: null;
        $data['thank_you_content']    = $post['thank_you_content'] ?? null;
        $rawSurveyLink = trim(strip_tags($post['survey_link'] ?? ''));
        $data['survey_link']          = $rawSurveyLink !== '' ? filter_var($rawSurveyLink, FILTER_SANITIZE_URL) ?: null : null;
        // Publish state is toggled via the dedicated Publish button (/publish),
        // never through the main sequence form, so it isn't set here.
        $data['expires_at']           = !empty($post['expires_at']) ? $post['expires_at'] : null;

        if (empty($data['start_code'])) {
            $errors[] = 'Start code is required.';
        } elseif ($this->seqModel->startCodeExists($data['start_code'], $excludeId)) {
            $errors[] = 'That start code is already in use by another mystery. Pick a unique one — players use it to open their case.';
        }

        // Slug handling
        if (!empty($post['slug'])) {
            $slug = slugify($post['slug']);
            $existing = $this->seqModel->findBySlug($slug);
            if ($existing && (int)$existing['id'] !== $excludeId) {
                $errors[] = 'That slug is already in use.';
            }
            $data['slug'] = $slug;
        }

        return [$data, $errors];
    }

    /**
     * Validate & normalise the Whodunnit accusation config posted as JSON.
     * Returns a clean JSON string, or null if there's nothing usable.
     * Shape: {
     *   "prompt":"…",
     *   "categories":[{"label":"Suspects","answer":0,
     *      "options":[{"name":"…","image":"<uploads path>","desc":"…"}]}]
     * }
     */
    private function normalizeAccusation(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        $cats = [];
        foreach (($decoded['categories'] ?? []) as $c) {
            if (!is_array($c)) {
                continue;
            }
            $label = Security::sanitizeString((string)($c['label'] ?? ''));
            $opts  = [];
            foreach ((array)($c['options'] ?? []) as $o) {
                // Accept rich {name,image,desc} objects or legacy plain strings.
                if (is_array($o)) {
                    $name  = Security::sanitizeString((string)($o['name'] ?? ''));
                    $image = $this->safeUploadPath((string)($o['image'] ?? ''));
                    $desc  = Security::sanitizeString((string)($o['desc'] ?? ''));
                    // Interactive game card (png/jpg/pdf) attached to the suspect.
                    $card  = $this->safeUploadPath((string)($o['card'] ?? ''));
                    $cardType = preg_match('/^[a-z]+$/', (string)($o['card_type'] ?? '')) ? (string)$o['card_type'] : '';
                } else {
                    $name  = Security::sanitizeString((string)$o);
                    $image = '';
                    $desc  = '';
                    $card  = '';
                    $cardType = '';
                }
                if ($name === '') {
                    continue;
                }
                $opts[] = ['name' => $name, 'image' => $image, 'desc' => $desc, 'card' => $card, 'card_type' => $cardType];
                if (count($opts) >= 12) {
                    break; // cap options per category
                }
            }
            // Allow saving in-progress work: a category just needs a label and at
             // least one option. The Health Check warns about <2 options.
            if ($label === '' || count($opts) < 1) {
                continue;
            }
            $ans = (int)($c['answer'] ?? 0);
            if ($ans < 0 || $ans >= count($opts)) {
                $ans = 0;
            }
            $cats[] = ['label' => $label, 'options' => $opts, 'answer' => $ans];
            if (count($cats) >= 5) {
                break; // cap at 5 categories (suspects/weapons/rooms/motive/…)
            }
        }
        if (empty($cats)) {
            return null;
        }

        return json_encode([
            'prompt'     => Security::sanitizeString((string)($decoded['prompt'] ?? '')),
            'categories' => $cats,
        ]);
    }

    /** Whitelist an uploads-relative path; '' if invalid or missing on disk. */
    private function safeUploadPath(string $path): string
    {
        $path = ltrim(trim($path), '/');
        if ($path === '' || strpos($path, '..') !== false) {
            return '';
        }
        if (!preg_match('#^[A-Za-z0-9._/\-]+$#', $path)) {
            return '';
        }
        return file_exists(UPLOAD_PATH . '/' . $path) ? $path : '';
    }

    private function extractTheme(array $post): array
    {
        return [
            'bg_color'        => $post['bg_color']        ?? '#0f0f1a',
            'text_color'      => $post['text_color']      ?? '#e0e0e0',
            'button_color'    => $post['button_color']    ?? '#6c63ff',
            'btn_text_color'  => $post['btn_text_color']  ?? '#ffffff',
            'accent_color'    => $post['accent_color']    ?? '#ff6b6b',
            'font_family'     => $post['font_family']     ?? 'Inter, sans-serif',
            // Empty string = "use defaults"; stored as NULL so the public page falls
            // back to the body font / accent color naturally.
            'title_font'      => trim((string)($post['title_font']  ?? '')) !== '' ? $post['title_font']  : null,
            'title_color'     => trim((string)($post['title_color'] ?? '')) !== '' ? $post['title_color'] : null,
            'container_width' => $post['container_width'] ?? '800px',
            'custom_css'      => $post['custom_css']      ?? null,
        ];
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i    = 1;
        while ($this->seqModel->findBySlug($slug)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    private function processSequenceFiles(int $seqId, array $existing = []): void
    {
        $uploader = new FileUpload();

        foreach (['intro' => 'intro_file', 'intro_hint' => 'intro_hint_file', 'finale' => 'finale_file', 'finale_hint' => 'finale_hint_file', 'solution' => 'solution_file'] as $slot => $fileKey) {
            if (empty($_FILES[$fileKey]['name'])) {
                continue;
            }

            $info = $uploader->handle($_FILES[$fileKey], (string)$seqId);
            if ($info === false) {
                continue;
            }

            // Delete the old file before saving the new one
            $oldPath = $existing[$slot . '_file_path'] ?? null;
            if ($oldPath) {
                FileUpload::delete($oldPath);
            }

            $this->seqModel->update($seqId, [
                $slot . '_file_path'         => $info['file_path'],
                $slot . '_file_type'         => $info['file_type'],
                $slot . '_original_filename' => $info['original_filename'],
                $slot . '_file_size'         => $info['file_size'],
                $slot . '_mime_type'         => $info['mime_type'],
            ]);
        }
    }

    private function processClueFiles(int $clueId, string $seqId): void
    {
        $uploader = new FileUpload();

        // Main clue file
        if (!empty($_FILES['clue_file']['name'])) {
            $info = $uploader->handle($_FILES['clue_file'], $seqId);
            if ($info) {
                $this->clueModel->update($clueId, [
                    'file_path'         => $info['file_path'],
                    'file_type'         => $info['file_type'],
                    'original_filename' => $info['original_filename'],
                    'file_size'         => $info['file_size'],
                    'mime_type'         => $info['mime_type'],
                ]);
            } elseif ($uploader->hasErrors()) {
                flash('error', 'Clue file not saved: ' . implode(' ', $uploader->getErrors()));
            }
        }

        // Hint file
        if (!empty($_FILES['hint_file']['name'])) {
            $info = $uploader->handle($_FILES['hint_file'], $seqId);
            if ($info) {
                $this->clueModel->update($clueId, [
                    'hint_file_path'         => $info['file_path'],
                    'hint_file_type'         => $info['file_type'],
                    'hint_original_filename' => $info['original_filename'],
                    'hint_file_size'         => $info['file_size'],
                    'hint_mime_type'         => $info['mime_type'],
                ]);
            } elseif ($uploader->hasErrors()) {
                flash('error', 'Hint file not saved: ' . implode(' ', $uploader->getErrors()));
            }
        }
    }
}
