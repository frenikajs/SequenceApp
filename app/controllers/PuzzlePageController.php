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
        $puzzleType = in_array($rawType, ['caesar', 'phone', 'access', 'elim', 'wordsearch', 'match', 'hotspot', 'fillblank'], true) ? $rawType : 'order';
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
        $elimHeading = 'Eliminate the wrong items';
        $wsGrid       = [];
        $wsSize       = 0;
        $wsList       = [];
        $wsWordsFound = false;
        $matchLeft    = [];
        $matchRight   = [];
        $hsImage      = '';
        $hsCount      = 0;
        $hsClicks     = [];
        $fbText       = '';
        $fbDifficulty = 'easy';
        $fbPieces     = [];
        $fbBlanks     = [];
        $fbBank       = [];

        if ($puzzleType === 'hotspot') {
            $hsImage  = (string)($data['image'] ?? '');
            $hsSpots  = is_array($data['spots'] ?? null) ? $data['spots'] : [];
            $hsRadius = (float)($data['radius'] ?? 8);
            $hsCount  = count($hsSpots);
            $clues    = array_values(array_filter(array_map(
                static fn ($v) => trim((string)$v),
                $data['clues'] ?? []
            ), static fn ($v) => $v !== ''));
            if ($isPost) {
                validate_csrf();
                $clicksIn = json_decode($_POST['hotspot_clicks'] ?? '[]', true);
                $clicks = [];
                if (is_array($clicksIn)) {
                    foreach ($clicksIn as $c) {
                        $x = (float)($c['x'] ?? -1);
                        $y = (float)($c['y'] ?? -1);
                        if ($x >= 0 && $x <= 100 && $y >= 0 && $y <= 100) {
                            $clicks[] = ['x' => $x, 'y' => $y, 'hit' => false];
                        }
                        if (count($clicks) >= $hsCount) { break; } // never more clicks than spots
                    }
                }
                // Greedily match each click to an unclaimed spot within the radius.
                $spotMatched = array_fill(0, $hsCount, false);
                foreach ($clicks as &$cc) {
                    foreach ($hsSpots as $si => $sp) {
                        if ($spotMatched[$si]) { continue; }
                        $dx = $cc['x'] - (float)($sp['x'] ?? 0);
                        $dy = $cc['y'] - (float)($sp['y'] ?? 0);
                        if (sqrt($dx * $dx + $dy * $dy) <= $hsRadius) {
                            $spotMatched[$si] = true;
                            $cc['hit'] = true;
                            break;
                        }
                    }
                }
                unset($cc);
                $foundSpots = count(array_filter($spotMatched));
                if (!$solved && $hsCount > 0 && $foundSpots === $hsCount) {
                    $_SESSION['puzzle'][$slug] = true;
                    $solved = true;
                } elseif (!$solved) {
                    $wrong = true;
                    $hsClicks = $clicks; // re-render markers with hit/miss colours
                }
            }
        } elseif ($puzzleType === 'match') {
            $pairs = [];
            foreach (($data['pairs'] ?? []) as $p) {
                $l = trim((string)($p['l'] ?? ''));
                $r = trim((string)($p['r'] ?? ''));
                if ($l !== '' && $r !== '') { $pairs[] = ['l' => $l, 'r' => $r]; }
            }
            $clues = array_values(array_filter(array_map(
                static fn ($v) => trim((string)$v),
                $data['clues'] ?? []
            ), static fn ($v) => $v !== ''));

            $matchLeft = array_map(static fn ($p) => $p['l'], $pairs);

            // Right column carries each pair's original index, then shuffled
            // deterministically (stable across reloads, not the solution order).
            $rights = [];
            foreach ($pairs as $i => $p) { $rights[] = ['i' => $i, 'v' => $p['r']]; }
            if (count($rights) > 1) {
                $seed = (int)hexdec(substr(md5($slug . 'match'), 0, 8));
                for ($k = count($rights) - 1; $k > 0; $k--) {
                    $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
                    $j = $seed % ($k + 1);
                    [$rights[$k], $rights[$j]] = [$rights[$j], $rights[$k]];
                }
                $identity = true;
                foreach ($rights as $pos => $rr) { if ($rr['i'] !== $pos) { $identity = false; break; } }
                if ($identity) { [$rights[0], $rights[1]] = [$rights[1], $rights[0]]; }
            }
            $matchRight = $rights;

            if ($isPost) {
                validate_csrf();
                $conn = json_decode($_POST['connections'] ?? '[]', true);
                $conn = is_array($conn) ? $conn : [];
                $n = count($pairs);
                $allCorrect = $n > 0;
                for ($i = 0; $i < $n; $i++) {
                    if (!isset($conn[$i]) || (int)$conn[$i] !== $i) { $allCorrect = false; break; }
                }
                if (!$solved && $allCorrect) {
                    $_SESSION['puzzle'][$slug] = true;
                    $solved = true;
                } elseif (!$solved) {
                    $wrong = true;
                }
            }
        } elseif ($puzzleType === 'wordsearch') {
            $wsPhrase     = trim((string)($data['phrase'] ?? ''));
            $ws           = self::buildWordSearch($slug, $data['words'] ?? [], $wsPhrase);
            $wsGrid       = $ws['grid'];
            $wsSize       = $ws['size'];
            $wsList       = $ws['words']; // words actually placed = the list to find
            // Client sets this once every word is found; lets us re-show the
            // message input after an incorrect phrase reload.
            $wsWordsFound = !empty($_POST['wordsfound']);
            if ($isPost) {
                validate_csrf();
                // Compare letters only — the leftover grid cells hold no spaces
                // or punctuation, so the player types just the letters they read.
                $ansLetters    = strtoupper((string)preg_replace('/[^A-Za-z]/', '', (string)($_POST['answer'] ?? '')));
                $phraseLetters = strtoupper((string)preg_replace('/[^A-Za-z]/', '', $wsPhrase));
                if (!$solved && $phraseLetters !== '' && $ansLetters === $phraseLetters) {
                    $_SESSION['puzzle'][$slug] = true;
                    $solved = true;
                } elseif (!$solved) {
                    $wrong = true;
                }
            }
        } elseif ($puzzleType === 'elim') {
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
            $elimHeading = trim((string)($data['heading'] ?? '')) ?: 'Eliminate the wrong items';

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
        } elseif ($puzzleType === 'fillblank') {
            // Text with {word} markers; blanks derived from the markers.
            $fbText        = (string)($data['text'] ?? '');
            $fbDifficulty  = (($data['difficulty'] ?? '') === 'hard') ? 'hard' : 'easy';
            $clues         = array_values(array_filter(array_map(
                static fn ($v) => trim((string)$v),
                $data['clues'] ?? []
            ), static fn ($v) => $v !== ''));

            // Parse into [{text:...}, {blank:i}, ...] pieces; capture answers.
            $fbPieces = [];
            $fbBlanks = [];
            $parts    = preg_split('/\{([^{}]+)\}/', $fbText, -1, PREG_SPLIT_DELIM_CAPTURE);
            foreach ($parts as $idx => $part) {
                if ($idx % 2 === 0) {
                    if ($part !== '') { $fbPieces[] = ['text' => $part]; }
                } else {
                    $word = trim($part);
                    if ($word === '' || count($fbBlanks) >= 10) { continue; }
                    $fbPieces[] = ['blank' => count($fbBlanks)];
                    $fbBlanks[] = $word;
                }
            }

            // Easy mode: shuffled word bank (deterministic across reloads).
            $fbBank = $fbBlanks;
            if ($fbDifficulty === 'easy' && count($fbBank) > 1) {
                $seed = (int)hexdec(substr(md5($slug . 'fillblank'), 0, 8));
                for ($k = count($fbBank) - 1; $k > 0; $k--) {
                    $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
                    $j = $seed % ($k + 1);
                    [$fbBank[$k], $fbBank[$j]] = [$fbBank[$j], $fbBank[$k]];
                }
                if ($fbBank === $fbBlanks) {
                    [$fbBank[0], $fbBank[1]] = [$fbBank[1], $fbBank[0]];
                }
            }

            if ($isPost) {
                validate_csrf();
                $submitted = $_POST['blanks'] ?? [];
                if (!is_array($submitted)) { $submitted = []; }
                $allCorrect = !empty($fbBlanks);
                foreach ($fbBlanks as $i => $expected) {
                    $given = trim((string)($submitted[$i] ?? ''));
                    if (strcasecmp($given, $expected) !== 0) { $allCorrect = false; break; }
                }
                if (!$solved && $allCorrect) {
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

        // "Back to Puzzle" link (mobile): return to the parent sequence and focus the
        // exact clue this puzzle was opened from (#clue-N anchor on the sequence page).
        $backUrl = '';
        $clueId  = (int)($puzzle['clue_id'] ?? 0);
        if ($clueId > 0 && ($clue = $this->clueModel->findById($clueId))) {
            $seqId    = (int)$clue['sequence_id'];
            $sequence = (new SequenceModel())->findById($seqId);
            if ($sequence) {
                $allClues = $this->clueModel->getBySequenceId($seqId);
                $clueNum  = 0;
                foreach ($allClues as $i => $c) {
                    if ((int)$c['id'] === $clueId) { $clueNum = $i + 1; break; }
                }
                $backUrl = url('s/' . $sequence['slug']) . ($clueNum > 0 ? '#clue-' . $clueNum : '');
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

    /**
     * Build a deterministic word-search grid from the slug + word list.
     * Words are placed across (E), down (S) and diagonally (SE, NE) — forwards
     * only. Returns ['size' => int, 'grid' => char[][], 'words' => placed[]].
     * Empty cells are filled with deterministic random letters so the grid is
     * stable across reloads. Only successfully-placed words are returned, so the
     * puzzle is always solvable.
     */
    private static function buildWordSearch(string $slug, array $rawWords, string $phrase = ''): array
    {
        // Normalise: letters only, uppercase, 2–12 chars, de-duplicated, max 8.
        $words = [];
        foreach ($rawWords as $w) {
            $w = strtoupper((string)preg_replace('/[^A-Za-z]/', '', (string)$w));
            if (strlen($w) >= 2 && strlen($w) <= 12 && !in_array($w, $words, true)) {
                $words[] = $w;
            }
            if (count($words) >= 8) { break; }
        }
        if (empty($words)) {
            return ['size' => 0, 'grid' => [], 'words' => []];
        }

        // Letters of the hidden phrase used to fill the leftover cells (in
        // reading order). When set, the puzzle's answer is this phrase.
        $pLetters = strtoupper((string)preg_replace('/[^A-Za-z]/', '', $phrase));
        $pLen     = strlen($pLetters);

        $longest = 0; $totalLen = 0;
        foreach ($words as $w) { $longest = max($longest, strlen($w)); $totalLen += strlen($w); }

        // Size so words pack reliably while leaving room for the phrase. With a
        // phrase, the leftover cells are filled with its letters (repeating to
        // fill any extra space), so the phrase always reads from the top first.
        $need = $pLen > 0 ? (int)ceil(($totalLen + $pLen + 2) * 1.2) : $totalLen * 2;
        $size = max($longest, (int)ceil(sqrt($need)), 8);
        $size = min(max($size, $longest), 18);

        // Deterministic PRNG (same LCG used elsewhere), seeded from the slug.
        $seed = (int)hexdec(substr(md5($slug), 0, 8));
        $rand = static function (int $n) use (&$seed): int {
            $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
            return $n > 0 ? $seed % $n : 0;
        };

        $dirs = [[0, 1], [1, 0], [1, 1], [-1, 1]]; // E, S, SE, NE
        $grid = array_fill(0, $size, array_fill(0, $size, null));

        $placed = [];
        foreach ($words as $word) {
            $len = strlen($word);
            $done = false;
            for ($attempt = 0; $attempt < 300 && !$done; $attempt++) {
                [$dr, $dc] = $dirs[$rand(count($dirs))];
                $rMin = $dr < 0 ? $len - 1 : 0;
                $rMax = $dr > 0 ? $size - $len : $size - 1;
                $cMin = 0;
                $cMax = $dc > 0 ? $size - $len : $size - 1;
                if ($rMax < $rMin || $cMax < $cMin) { continue; }
                $r = $rMin + $rand($rMax - $rMin + 1);
                $c = $cMin + $rand($cMax - $cMin + 1);

                $ok = true;
                for ($i = 0; $i < $len; $i++) {
                    $cell = $grid[$r + $dr * $i][$c + $dc * $i];
                    if ($cell !== null && $cell !== $word[$i]) { $ok = false; break; }
                }
                if (!$ok) { continue; }

                for ($i = 0; $i < $len; $i++) {
                    $grid[$r + $dr * $i][$c + $dc * $i] = $word[$i];
                }
                $placed[] = $word;
                $done = true;
            }
        }

        // Fill the gaps. With a hidden phrase, lay its letters into the leftover
        // cells in reading order (left→right, top→bottom), followed by "ZZ" as an
        // end marker, repeating that unit to fill any extra space — so players can
        // tell exactly where the phrase ends. Without a phrase, use random letters.
        if ($pLen > 0) {
            $fill = $pLetters . 'ZZ';
            $fLen = strlen($fill);
            $pi = 0;
            for ($r = 0; $r < $size; $r++) {
                for ($c = 0; $c < $size; $c++) {
                    if ($grid[$r][$c] === null) {
                        $grid[$r][$c] = $fill[$pi % $fLen];
                        $pi++;
                    }
                }
            }
        } else {
            for ($r = 0; $r < $size; $r++) {
                for ($c = 0; $c < $size; $c++) {
                    if ($grid[$r][$c] === null) {
                        $grid[$r][$c] = chr(65 + $rand(26));
                    }
                }
            }
        }

        return ['size' => $size, 'grid' => $grid, 'words' => $placed];
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
            'sequence'  => (new SequenceModel())->findById((int)$clue['sequence_id']) ?: null,
            'flash'     => getFlash(),
            'errors'    => [],
            'input'     => [],
        ]);
    }

    /**
     * Admin-only, printer-friendly "player sheet" of the puzzle (no answers) —
     * the puzzle exactly as a player sees it, laid out for printing on paper.
     */
    public function printPuzzle(int $clueId): void
    {
        requireAdmin();
        $clue   = $this->clueModel->findById($clueId) ?: notFound();
        $puzzle = $this->puzzleModel->findByClueId($clueId) ?: notFound();

        $slug    = (string)$puzzle['slug'];
        $rawType = $puzzle['puzzle_type'] ?? 'order';
        $type    = in_array($rawType, ['caesar', 'phone', 'access', 'elim', 'wordsearch', 'match', 'hotspot', 'fillblank'], true) ? $rawType : 'order';
        $data    = json_decode($puzzle['data_json'] ?? '{}', true) ?: [];
        $title   = (string)($puzzle['title'] ?: 'Puzzle');
        $prompt  = trim((string)($puzzle['prompt'] ?? ''));

        // Player-facing display fields only — never the answers.
        $clues = []; $items = []; $cipher = ''; $phoneClue = ''; $accessLen = 0;
        $elimHeading = 'Eliminate the wrong items';
        $wsGrid = []; $wsSize = 0; $wsList = [];
        $matchLeft = []; $matchRight = [];
        $hsImage = ''; $hsCount = 0;

        $cleanList = static fn (array $a): array => array_values(array_filter(
            array_map(static fn ($v) => trim((string)$v), $a),
            static fn ($v) => $v !== ''
        ));
        $shuffle = static function (array $arr, string $seedStr): array {
            $n = count($arr);
            if ($n > 1) {
                $seed = (int)hexdec(substr(md5($seedStr), 0, 8));
                for ($i = $n - 1; $i > 0; $i--) {
                    $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
                    $j = $seed % ($i + 1);
                    [$arr[$i], $arr[$j]] = [$arr[$j], $arr[$i]];
                }
            }
            return $arr;
        };

        if ($type === 'caesar') {
            $cipher = self::caesar(trim((string)($data['phrase'] ?? '')), (int)($data['shift'] ?? 0));
        } elseif ($type === 'phone') {
            $phoneClue = self::phoneClue(trim((string)($data['code'] ?? '')));
        } elseif ($type === 'access') {
            $accessLen = strlen((string)(preg_replace('/\D+/', '', (string)($data['code'] ?? '')) ?? ''));
            $clues = $cleanList($data['clues'] ?? []);
        } elseif ($type === 'elim') {
            $items = $shuffle($cleanList($data['items'] ?? []), $slug);
            $clues = $cleanList($data['clues'] ?? []);
            $elimHeading = trim((string)($data['heading'] ?? '')) ?: 'Eliminate the wrong items';
        } elseif ($type === 'wordsearch') {
            $ws = self::buildWordSearch($slug, $data['words'] ?? [], (string)($data['phrase'] ?? ''));
            $wsGrid = $ws['grid']; $wsSize = $ws['size']; $wsList = $ws['words'];
        } elseif ($type === 'match') {
            $pairs = [];
            foreach (($data['pairs'] ?? []) as $p) {
                $l = trim((string)($p['l'] ?? '')); $r = trim((string)($p['r'] ?? ''));
                if ($l !== '' && $r !== '') { $pairs[] = ['l' => $l, 'r' => $r]; }
            }
            $matchLeft = array_map(static fn ($p) => $p['l'], $pairs);
            $rights = [];
            foreach ($pairs as $i => $p) { $rights[] = ['i' => $i, 'v' => $p['r']]; }
            $rights = $shuffle($rights, $slug . 'match');
            $identity = true;
            foreach ($rights as $pos => $rr) { if ($rr['i'] !== $pos) { $identity = false; break; } }
            if ($identity && count($rights) > 1) { [$rights[0], $rights[1]] = [$rights[1], $rights[0]]; }
            $matchRight = $rights;
            $clues = $cleanList($data['clues'] ?? []);
        } elseif ($type === 'hotspot') {
            $hsImage = (string)($data['image'] ?? '');
            $hsCount = count(is_array($data['spots'] ?? null) ? $data['spots'] : []);
            $clues = $cleanList($data['clues'] ?? []);
        } elseif ($type === 'fillblank') {
            $fbText       = (string)($data['text'] ?? '');
            $fbDifficulty = (($data['difficulty'] ?? '') === 'hard') ? 'hard' : 'easy';
            $clues        = $cleanList($data['clues'] ?? []);
            preg_match_all('/\{([^{}]+)\}/', $fbText, $__m);
            $fbBlanks = array_map('trim', $__m[1] ?? []);
        } else { // order
            $sol = $cleanList($data['items'] ?? []);
            $items = $shuffle($sol, $slug);
            if ($items === $sol && count($items) > 1) { [$items[0], $items[1]] = [$items[1], $items[0]]; }
            $clues = $cleanList($data['clues'] ?? []);
        }

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        require APP_ROOT . '/views/admin/pages/puzzle_print.php';
        exit;
    }

    public function savePage(int $clueId): void
    {
        requireAdmin();
        validate_csrf();
        $clue = $this->clueModel->findById($clueId) ?: notFound();
        $sequence = (new SequenceModel())->findById((int)$clue['sequence_id']) ?: null;

        $errors = [];
        $title  = Security::sanitizeString($_POST['title'] ?? '');
        // Default the slug to the puzzle title (spaces → dashes) when left blank.
        $slug   = slugify($_POST['slug'] ?? '') ?: slugify($title);
        if ($title === '') { $errors[] = 'Title is required.'; }
        if ($slug === '')  { $errors[] = 'Add a title so a URL slug can be generated.'; }

        $type = $_POST['puzzle_type'] ?? 'order';
        if (!in_array($type, ['order', 'caesar', 'phone', 'access', 'elim', 'wordsearch', 'match', 'hotspot', 'fillblank'], true)) {
            // Allowed types list — kept in sync above.
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
                $v = Security::sanitizeString($_POST['eitem' . $i] ?? '');
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
            $elimHeading = Security::sanitizeString($_POST['elim_heading'] ?? '');
            $dataJson = json_encode(
                ['items' => $elimItems, 'eliminate' => $marked, 'clues' => $elimClues, 'heading' => $elimHeading],
                JSON_UNESCAPED_UNICODE
            );
        } elseif ($type === 'wordsearch') {
            // Up to 8 words, letters only, 2–12 characters
            $wsWords = [];
            for ($i = 1; $i <= 8; $i++) {
                $w = strtoupper((string)preg_replace('/[^A-Za-z]/', '', (string)($_POST['wsword' . $i] ?? '')));
                if ($w === '') { continue; }
                if (strlen($w) < 2) {
                    $errors[] = 'Each word must be at least 2 letters (word ' . $i . ').';
                    continue;
                }
                if (strlen($w) > 12) { $w = substr($w, 0, 12); }
                if (!in_array($w, $wsWords, true)) { $wsWords[] = $w; }
            }
            if (count($wsWords) < 1) {
                $errors[] = 'Add at least one word (letters only).';
            }
            $wsPhrase = Security::sanitizeString($_POST['ws_phrase'] ?? '');
            $pLetters = (string)preg_replace('/[^A-Za-z]/', '', $wsPhrase);
            if (strlen($pLetters) < 1) {
                $errors[] = 'Enter a hidden phrase to fill the grid.';
            } elseif (strlen($pLetters) > 80) {
                $errors[] = 'Keep the hidden phrase to 80 letters or fewer.';
            }
            $dataJson = json_encode(['words' => $wsWords, 'phrase' => $wsPhrase], JSON_UNESCAPED_UNICODE);
        } elseif ($type === 'match') {
            // Up to 10 left↔right pairs, up to 10 clues
            $pairs = [];
            for ($i = 1; $i <= 10; $i++) {
                $l = Security::sanitizeString($_POST['mleft' . $i] ?? '');
                $r = Security::sanitizeString($_POST['mright' . $i] ?? '');
                if ($l !== '' && $r !== '') {
                    $pairs[] = ['l' => $l, 'r' => $r];
                } elseif (($l !== '') xor ($r !== '')) {
                    $errors[] = 'Pair ' . $i . ' needs both a left and a right item.';
                }
            }
            $mClues = [];
            for ($i = 1; $i <= 10; $i++) {
                $v = Security::sanitizeString($_POST['mclue' . $i] ?? '');
                if ($v !== '') { $mClues[] = $v; }
            }
            if (count($pairs) < 2) {
                $errors[] = 'Add at least 2 complete pairs.';
            }
            $dataJson = json_encode(['pairs' => $pairs, 'clues' => $mClues], JSON_UNESCAPED_UNICODE);
        } elseif ($type === 'hotspot') {
            // Carry the existing puzzle image forward unless a new one is uploaded.
            $existingPuzzle = $this->puzzleModel->findByClueId($clueId);
            $existingData = $existingPuzzle ? (json_decode($existingPuzzle['data_json'] ?? '{}', true) ?: []) : [];
            $imagePath = (string)($existingData['image'] ?? '');
            if (!empty($_FILES['hotspot_image']['name'])) {
                $uploader = new FileUpload();
                $info = $uploader->handle($_FILES['hotspot_image'], (string)$clueId);
                if ($info === false) {
                    $errors[] = 'Puzzle image not saved: ' . implode(' ', $uploader->getErrors());
                } elseif (($info['file_type'] ?? '') !== 'image') {
                    $errors[] = 'The hotspot puzzle file must be an image.';
                    FileUpload::delete($info['file_path']);
                } else {
                    if ($imagePath !== '') { FileUpload::delete($imagePath); }
                    $imagePath = $info['file_path'];
                }
            }
            if ($imagePath === '') {
                $errors[] = 'Upload an image for the hotspot puzzle.';
            }
            // Expose the just-uploaded path so the editor preview survives a re-render.
            $_POST['_hotspot_uploaded'] = $imagePath;

            $spotsIn = json_decode($_POST['hotspot_spots'] ?? '[]', true);
            $spots = [];
            if (is_array($spotsIn)) {
                foreach ($spotsIn as $sp) {
                    $x = (float)($sp['x'] ?? -1);
                    $y = (float)($sp['y'] ?? -1);
                    if ($x >= 0 && $x <= 100 && $y >= 0 && $y <= 100) {
                        $spots[] = ['x' => round($x, 2), 'y' => round($y, 2)];
                    }
                    if (count($spots) >= 5) { break; }
                }
            }
            if (count($spots) < 1) {
                $errors[] = 'Place at least one spot on the image.';
            }
            $radius = (float)($_POST['hotspot_radius'] ?? 8);
            $radius = max(2.0, min(25.0, $radius));
            $hsClues = [];
            for ($i = 1; $i <= 10; $i++) {
                $v = Security::sanitizeString($_POST['hsclue' . $i] ?? '');
                if ($v !== '') { $hsClues[] = $v; }
            }
            $dataJson = json_encode(['image' => $imagePath, 'radius' => $radius, 'spots' => $spots, 'clues' => $hsClues], JSON_UNESCAPED_UNICODE);
        } elseif ($type === 'fillblank') {
            // Fill-in-the-Blank: text uses {word} markers, up to 10 blanks, up to 10 clues.
            // We accept Security::sanitizeString on the raw text so braces survive.
            $fbText = Security::sanitizeString($_POST['fb_text'] ?? '');
            $fbDifficulty = (($_POST['fb_difficulty'] ?? 'easy') === 'hard') ? 'hard' : 'easy';
            // Count and cap blanks (drop extra markers beyond the 10th).
            preg_match_all('/\{([^{}]+)\}/', $fbText, $__fbMatches);
            $blanksFound = 0;
            $fbText = preg_replace_callback('/\{([^{}]+)\}/', function ($m) use (&$blanksFound) {
                if ($blanksFound >= 10 || trim($m[1]) === '') { return $m[1]; }
                $blanksFound++;
                return '{' . trim($m[1]) . '}';
            }, $fbText) ?? $fbText;
            if ($blanksFound < 1) {
                $errors[] = 'Mark at least one word as a blank by wrapping it in braces (e.g. {word}).';
            }
            $fbClues = [];
            for ($i = 1; $i <= 10; $i++) {
                $v = Security::sanitizeString($_POST['fbclue' . $i] ?? '');
                if ($v !== '') { $fbClues[] = $v; }
            }
            $dataJson = json_encode([
                'text'       => $fbText,
                'difficulty' => $fbDifficulty,
                'clues'      => $fbClues,
            ], JSON_UNESCAPED_UNICODE);
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
                'sequence'  => $sequence,
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
                'sequence'  => $sequence,
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
        redirect('/admin/sequences/' . (int)$clue['sequence_id'] . '/clues');
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
