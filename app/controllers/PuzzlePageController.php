<?php
declare(strict_types=1);

class PuzzlePageController
{
    private PuzzlePageModel $puzzleModel;
    private ClueModel       $clueModel;

    public function __construct()
    {
        $this->puzzleModel = new PuzzlePageModel();
        $this->clueModel   = new ClueModel();
    }

    // ── Public: render / solve the puzzle ─────────────────────────────────────

    public function show(string $slug): void
    {
        $puzzle = $this->puzzleModel->findBySlug($slug) ?: notFound();
        $rawType = $puzzle['puzzle_type'] ?? 'order';
        $puzzleType = in_array($rawType, ['caesar', 'phone', 'access', 'elim'], true) ? $rawType : 'order';
        $data   = json_decode($puzzle['data_json'] ?? '{}', true) ?: [];

        if (!isset($_SESSION['puzzle']) || !is_array($_SESSION['puzzle'])) {
            $_SESSION['puzzle'] = [];
        }
        $solved = !empty($_SESSION['puzzle'][$slug]);
        $wrong  = false;
        $isPost = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');

        // Per-type view variables
        $items      = [];
        $clues      = [];
        $cipher     = '';
        $phoneClue  = '';
        $accessLen  = 0;

        if ($puzzleType === 'elim') {
            $allItems = array_values(array_filter(array_map(
                static fn ($v) => trim((string)$v),
                $data['items'] ?? []
            ), static fn ($v) => $v !== ''));
            $elimSet = array_values(array_filter(array_map(
                static fn ($v) => trim((string)$v),
                $data['eliminate'] ?? []
            ), static fn ($v) => $v !== ''));
            // Only count items that still exist
            $elimSet = array_values(array_intersect($elimSet, $allItems));
            $clues = array_values(array_filter(array_map(
                static fn ($v) => trim((string)$v),
                $data['clues'] ?? []
            ), static fn ($v) => $v !== ''));

            if ($isPost) {
                validate_csrf();
                $submitted = json_decode($_POST['eliminated'] ?? '[]', true);
                $submitted = is_array($submitted)
                    ? array_values(array_filter(array_map(static fn ($v) => trim((string)$v), $submitted), static fn ($v) => $v !== ''))
                    : [];
                $a = $submitted; sort($a);
                $b = $elimSet;   sort($b);
                if (!$solved && count($allItems) > 0 && $a === $b) {
                    $_SESSION['puzzle'][$slug] = true;
                    $solved = true;
                } elseif (!$solved) {
                    $wrong = true;
                }
            }

            // Deterministic shuffle (stable across reloads).
            $items = $allItems;
            if (count($items) > 1) {
                $seed = (int)hexdec(substr(md5($slug), 0, 8));
                for ($i = count($items) - 1; $i > 0; $i--) {
                    $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
                    $j = $seed % ($i + 1);
                    [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
                }
            }
        } elseif ($puzzleType === 'access') {
            $code      = preg_replace('/\D+/', '', (string)($data['code'] ?? '')) ?? '';
            $accessLen = strlen($code);
            $clues     = array_values(array_filter(array_map(
                static fn ($v) => trim((string)$v),
                $data['clues'] ?? []
            ), static fn ($v) => $v !== ''));
            if ($isPost) {
                validate_csrf();
                $ans = preg_replace('/\D+/', '', (string)($_POST['answer'] ?? '')) ?? '';
                if (!$solved && $code !== '' && $ans === $code) {
                    $_SESSION['puzzle'][$slug] = true;
                    $solved = true;
                } elseif (!$solved) {
                    $wrong = true;
                }
            }
        } elseif ($puzzleType === 'phone') {
            $code      = trim((string)($data['code'] ?? ''));
            $phoneClue = self::phoneClue($code);
            if ($isPost) {
                validate_csrf();
                $ans = trim((string)($_POST['answer'] ?? ''));
                if (!$solved && $code !== '' && $this->norm($ans) === $this->norm($code)) {
                    $_SESSION['puzzle'][$slug] = true;
                    $solved = true;
                } elseif (!$solved) {
                    $wrong = true;
                }
            }
        } elseif ($puzzleType === 'caesar') {
            $shift  = (int)($data['shift'] ?? 0);
            $phrase = trim((string)($data['phrase'] ?? ''));
            $cipher = self::caesar($phrase, $shift);
            if ($isPost) {
                validate_csrf();
                $ans = trim((string)($_POST['answer'] ?? ''));
                if (!$solved && $phrase !== '' && $this->norm($ans) === $this->norm($phrase)) {
                    $_SESSION['puzzle'][$slug] = true;
                    $solved = true;
                } elseif (!$solved) {
                    $wrong = true;
                }
            }
        } else {
            $solItems = array_values(array_filter(array_map(
                static fn ($v) => trim((string)$v),
                $data['items'] ?? []
            ), static fn ($v) => $v !== ''));
            $clues = array_values(array_filter(array_map(
                static fn ($v) => trim((string)$v),
                $data['clues'] ?? []
            ), static fn ($v) => $v !== ''));

            if ($isPost) {
                validate_csrf();
                $submitted = json_decode($_POST['order'] ?? '[]', true);
                $submitted = is_array($submitted)
                    ? array_map(static fn ($v) => trim((string)$v), $submitted)
                    : [];
                if (!$solved && count($solItems) > 0 && $submitted === $solItems) {
                    $_SESSION['puzzle'][$slug] = true;
                    $solved = true;
                } elseif (!$solved) {
                    $wrong = true;
                }
            }

            // Deterministic shuffle (stable across reloads, not the solution).
            $items = $solItems;
            if (count($items) > 1) {
                $seed = (int)hexdec(substr(md5($slug), 0, 8));
                for ($i = count($items) - 1; $i > 0; $i--) {
                    $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
                    $j = $seed % ($i + 1);
                    [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
                }
                if ($items === $solItems) {
                    [$items[0], $items[1]] = [$items[1], $items[0]];
                }
            }
        }

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        require APP_ROOT . '/views/public/puzzle.php';
        exit;
    }

    /** Case-insensitive, whitespace-normalised comparison key. */
    private function norm(string $s): string
    {
        return strtoupper(trim((string)preg_replace('/\s+/', ' ', $s)));
    }

    /** Caesar-shift only A–Z / a–z; everything else passes through. */
    private static function caesar(string $text, int $shift): string
    {
        $shift = (($shift % 26) + 26) % 26;
        $out = '';
        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            $o = ord($text[$i]);
            if ($o >= 65 && $o <= 90) {
                $out .= chr((($o - 65 + $shift) % 26) + 65);
            } elseif ($o >= 97 && $o <= 122) {
                $out .= chr((($o - 97 + $shift) % 26) + 97);
            } else {
                $out .= $text[$i];
            }
        }
        return $out;
    }

    /**
     * Build an old-school multi-tap (T9) digit clue from a code.
     * 2=ABC 3=DEF 4=GHI 5=JKL 6=MNO 7=PQRS 8=TUV 9=WXYZ, space=0.
     * Each letter becomes its digit repeated (position+1) times; groups
     * are space-separated. Unknown characters are skipped.
     */
    private static function phoneClue(string $code): string
    {
        $map = [
            'A' => '2',  'B' => '22', 'C' => '222',
            'D' => '3',  'E' => '33', 'F' => '333',
            'G' => '4',  'H' => '44', 'I' => '444',
            'J' => '5',  'K' => '55', 'L' => '555',
            'M' => '6',  'N' => '66', 'O' => '666',
            'P' => '7',  'Q' => '77', 'R' => '777', 'S' => '7777',
            'T' => '8',  'U' => '88', 'V' => '888',
            'W' => '9',  'X' => '99', 'Y' => '999', 'Z' => '9999',
            ' ' => '0',
        ];
        $groups = [];
        $code   = strtoupper(trim($code));
        $len    = strlen($code);
        for ($i = 0; $i < $len; $i++) {
            $ch = $code[$i];
            if (isset($map[$ch])) {
                $groups[] = $map[$ch];
            }
        }
        return implode(' ', $groups);
    }

    // ── Admin: edit / create puzzle linked to a clue ──────────────────────────

    public function editPage(int $clueId): void
    {
        requireAdmin();
        $clue   = $this->clueModel->findById($clueId) ?: notFound();
        $puzzle = $this->puzzleModel->findByClueId($clueId);

        view('admin.pages.puzzle', [
            'pageTitle' => 'Puzzle Page',
            'activeNav' => 'sequences',
            'clue'      => $clue,
            'puzzle'    => $puzzle ?: null,
            'flash'     => getFlash(),
            'errors'    => [],
            'input'     => [],
        ]);
    }

    public function savePage(int $clueId): void
    {
        requireAdmin();
        validate_csrf();
        $clue = $this->clueModel->findById($clueId) ?: notFound();

        $errors = [];
        $slug   = slugify($_POST['slug'] ?? '');
        $title  = Security::sanitizeString($_POST['title'] ?? '');
        if ($slug === '')  { $errors[] = 'URL slug is required.'; }
        if ($title === '') { $errors[] = 'Title is required.'; }

        $type = $_POST['puzzle_type'] ?? 'order';
        if (!in_array($type, ['order', 'caesar', 'phone', 'access', 'elim'], true)) {
            $type = 'order';
        }

        if ($type === 'caesar') {
            $shift  = (int)($_POST['caesar_shift'] ?? 0);
            $phrase = Security::sanitizeString($_POST['caesar_phrase'] ?? '');
            if ($shift < 1 || $shift > 25) {
                $errors[] = 'Choose a shift between 1 and 25.';
                $shift = max(1, min(25, $shift));
            }
            if ($phrase === '') {
                $errors[] = 'Enter the phrase to encode.';
            }
            $dataJson = json_encode(['shift' => $shift, 'phrase' => $phrase], JSON_UNESCAPED_UNICODE);
        } elseif ($type === 'phone') {
            $code = Security::sanitizeString($_POST['phone_code'] ?? '');
            if ($code === '') {
                $errors[] = 'Enter the code (e.g. CAT).';
            }
            $dataJson = json_encode(['code' => $code], JSON_UNESCAPED_UNICODE);
        } elseif ($type === 'access') {
            $rawCode = (string)($_POST['access_code'] ?? '');
            $code    = preg_replace('/\D+/', '', $rawCode) ?? '';
            if ($code === '') {
                $errors[] = 'Enter the access code (digits only, up to 7).';
            } elseif (strlen($code) > 7) {
                $errors[] = 'Access code must be 7 digits or fewer.';
                $code = substr($code, 0, 7);
            }
            $accessClues = [];
            for ($i = 1; $i <= 10; $i++) {
                $v = Security::sanitizeString($_POST['aclue' . $i] ?? '');
                if ($v !== '') { $accessClues[] = $v; }
            }
            $dataJson = json_encode(['code' => $code, 'clues' => $accessClues], JSON_UNESCAPED_UNICODE);
        } elseif ($type === 'elim') {
            // Up to 6 items; up to 5 may be marked for elimination; up to 10 clues
            $elimItems = [];
            $elimMark  = $_POST['elim_mark'] ?? [];
            if (!is_array($elimMark)) { $elimMark = []; }
            $marked = [];
            for ($i = 1; $i <= 6; $i++) {
                $v = Security::sanitizeString($_POST['item' . $i] ?? '');
                if ($v !== '') {
                    $elimItems[] = $v;
                    if (!empty($elimMark[$i])) { $marked[] = $v; }
                }
            }
            $elimClues = [];
            for ($i = 1; $i <= 10; $i++) {
                $v = Security::sanitizeString($_POST['eclue' . $i] ?? '');
                if ($v !== '') { $elimClues[] = $v; }
            }
            if (count($elimItems) < 2) {
                $errors[] = 'Add at least 2 items.';
            }
            if (count($marked) > 5) {
                $errors[] = 'Mark at most 5 items for elimination.';
                $marked = array_slice($marked, 0, 5);
            }
            $dataJson = json_encode(
                ['items' => $elimItems, 'eliminate' => $marked, 'clues' => $elimClues],
                JSON_UNESCAPED_UNICODE
            );
        } else {
            // Order puzzle data: up to 6 line items (in correct order), up to 10 clues
            $items = [];
            for ($i = 1; $i <= 6; $i++) {
                $v = Security::sanitizeString($_POST['item' . $i] ?? '');
                if ($v !== '') { $items[] = $v; }
            }
            $clues = [];
            for ($i = 1; $i <= 10; $i++) {
                $v = Security::sanitizeString($_POST['oclue' . $i] ?? '');
                if ($v !== '') { $clues[] = $v; }
            }
            if (count($items) < 2) {
                $errors[] = 'Add at least 2 line items (in the correct order).';
            }
            $dataJson = json_encode(['items' => $items, 'clues' => $clues], JSON_UNESCAPED_UNICODE);
        }

        $existing  = $this->puzzleModel->findByClueId($clueId);
        $excludeId = $existing ? (int)$existing['id'] : 0;
        if (empty($errors) && $this->puzzleModel->slugExists($slug, $excludeId)) {
            $errors[] = 'That URL slug is already in use by another puzzle.';
        }

        if (!empty($errors)) {
            view('admin.pages.puzzle', [
                'pageTitle' => 'Puzzle Page',
                'activeNav' => 'sequences',
                'clue'      => $clue,
                'puzzle'    => $existing ?: null,
                'flash'     => null,
                'errors'    => $errors,
                'input'     => $_POST,
            ]);
            return;
        }

        $data = [
            'clue_id'        => $clueId,
            'slug'           => $slug,
            'puzzle_type'    => $type,
            'title'          => $title,
            'prompt'         => Security::sanitizeString($_POST['prompt'] ?? '') ?: null,
            'data_json'      => $dataJson,
            'reward_content' => $_POST['reward_content'] ?? null,
        ];

        // Carry existing reward media forward by default
        foreach (['file_path', 'file_type', 'original_filename', 'file_size', 'mime_type', 'file_caption'] as $k) {
            $data['reward_' . $k] = $existing['reward_' . $k] ?? null;
        }
        $data['reward_file_caption'] = Security::sanitizeString($_POST['reward_file_caption'] ?? '') ?: null;

        // Remove existing reward media
        if (!empty($_POST['reward_file_remove']) && $existing) {
            FileUpload::delete($existing['reward_file_path'] ?? '');
            $data['reward_file_path'] = $data['reward_file_type'] = null;
            $data['reward_original_filename'] = $data['reward_mime_type'] = null;
            $data['reward_file_size'] = null;
        }

        // New reward media upload
        if (!empty($_FILES['reward_file']['name'])) {
            $uploader = new FileUpload();
            $info = $uploader->handle($_FILES['reward_file'], (string)$clueId);
            if ($info === false) {
                $errors[] = 'Reward file not saved: ' . implode(' ', $uploader->getErrors());
            } else {
                if (!empty($existing['reward_file_path'])) {
                    FileUpload::delete($existing['reward_file_path']);
                }
                $data['reward_file_path']         = $info['file_path'];
                $data['reward_file_type']         = $info['file_type'];
                $data['reward_original_filename'] = $info['original_filename'];
                $data['reward_file_size']         = $info['file_size'];
                $data['reward_mime_type']         = $info['mime_type'];
            }
        }

        if (!empty($errors)) {
            view('admin.pages.puzzle', [
                'pageTitle' => 'Puzzle Page',
                'activeNav' => 'sequences',
                'clue'      => $clue,
                'puzzle'    => $existing ?: null,
                'flash'     => null,
                'errors'    => $errors,
                'input'     => $_POST,
            ]);
            return;
        }

        if ($existing) {
            $this->puzzleModel->update((int)$existing['id'], $data);
        } else {
            $this->puzzleModel->create($data);
        }

        flash('success', 'Puzzle page saved.');
        redirect('/admin/clues/' . $clueId . '/puzzle');
    }

    public function deletePage(int $clueId): void
    {
        requireAdmin();
        validate_csrf();
        $puzzle = $this->puzzleModel->findByClueId($clueId) ?: notFound();
        if (!empty($puzzle['reward_file_path'])) {
            FileUpload::delete($puzzle['reward_file_path']);
        }
        $this->puzzleModel->delete((int)$puzzle['id']);

        $clue  = $this->clueModel->findById($clueId);
        $seqId = $clue ? (int)$clue['sequence_id'] : 0;
        flash('success', 'Puzzle page deleted.');
        redirect('/admin/sequences/' . $seqId . '/clues');
    }
}
