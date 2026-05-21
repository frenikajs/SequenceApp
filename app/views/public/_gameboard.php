<?php
/**
 * Game Board view — Candy Land–style tile path.
 *
 * Inherits from sequence.php:
 *   $sequence, $clues, $progress, $started, $unlocked, $ready_to_solve,
 *   $solution_shown, $completed, $total, $slug, $flashMsg, $flashType
 *
 * State semantics map onto the existing sequential state machine:
 *   Tile 0       — Start / Introduction. Holds start_code (when !started) and
 *                  intro_access_code (when started && unlocked === 0).
 *   Tile 1..N    — One per clue (1-indexed). Tile K is "current" when
 *                  $unlocked === K and not yet ready_to_solve; it accepts the
 *                  K-th clue's access_code to advance to tile K+1.
 *   Tile N+1     — Finale. Active when ready_to_solve && !solution_shown;
 *                  accepts finale_code, then reveals solution + fireworks.
 */

/** Build the modal body HTML for a given tile. */
$_gbCodeForm = function (string $btnLabel, ?string $errorMsg = null) use ($slug): string {
    $action = htmlspecialchars(url('s/' . $slug), ENT_QUOTES, 'UTF-8');
    $csrf   = csrf_field();
    $errHtml = '';
    if ($errorMsg !== null && $errorMsg !== '') {
        $errHtml = '<div class="code-error" role="alert" style="margin-top:.75rem"><span class="alert-icon">&#9888;</span> '
                 . htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') . '</div>';
    }
    return <<<HTML
<form method="POST" action="$action" class="code-form gb-code-form">
  $csrf
  <div class="code-input-wrap">
    <label for="gb-code-input" class="sr-only">Access code</label>
    <input type="text" name="code" id="gb-code-input" class="code-input" placeholder="Enter access code…"
           autocomplete="off" autocorrect="off" autocapitalize="characters" spellcheck="false" required>
    <button type="submit" class="btn-primary code-submit">$btnLabel</button>
  </div>
  $errHtml
</form>
HTML;
};

$_gbMediaBlock = function (?string $path, ?string $type, ?string $name, ?string $caption): string {
    if (empty($path)) {
        return '';
    }
    $html = '<div class="media-block">'
          . renderMedia((string)$type, UPLOAD_URL . '/' . $path, (string)$name);
    if (!empty($caption)) {
        $html .= '<div class="media-caption">' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '</div>';
    }
    return $html . '</div>';
};

$_gbHintBlock = function (?string $html, ?string $path, ?string $type, ?string $name): string {
    return renderHint($html, $path, $type, $name);
};

// Build per-tile panels --------------------------------------------------------

$tiles = [];

// ── Tile 0: Start / Introduction ──
$introHtml = '';
if (!empty($sequence['introduction_content'])) {
    $introHtml .= '<div class="rich-content">' . $sequence['introduction_content'] . '</div>';
}
$introHtml .= $_gbMediaBlock(
    $sequence['intro_file_path']         ?? null,
    $sequence['intro_file_type']         ?? null,
    $sequence['intro_original_filename'] ?? null,
    $sequence['intro_caption']           ?? null
);

$tile0State = 'locked';
$tile0Body  = '';
if (!$started) {
    $tile0State = 'current';
    $err = ($flashType === 'error') ? $flashMsg : null;
    $tile0Body = '<h2 class="gb-modal-title">Start the Mystery</h2>'
               . '<p class="gb-modal-sub">Enter the start code to begin.</p>'
               . $_gbCodeForm('Reveal', $err);
} elseif ($unlocked === 0) {
    $tile0State = 'current';
    $tile0Body  = '<h2 class="gb-modal-title">Introduction</h2>'
                . $introHtml;
    if (trim((string)($sequence['intro_instruction'] ?? '')) !== '') {
        $tile0Body .= '<p class="gb-modal-sub" style="margin-top:1rem">'
                    . nl2br(htmlspecialchars((string)$sequence['intro_instruction'], ENT_QUOTES, 'UTF-8'))
                    . '</p>';
    }
    $err = ($flashType === 'error') ? $flashMsg : null;
    $tile0Body .= $_gbCodeForm('Reveal', $err);
    $tile0Body .= $_gbHintBlock(
        $sequence['intro_hint_text']              ?? null,
        $sequence['intro_hint_file_path']         ?? null,
        $sequence['intro_hint_file_type']         ?? null,
        $sequence['intro_hint_original_filename'] ?? null
    );
} else {
    $tile0State = 'done';
    $tile0Body  = '<h2 class="gb-modal-title">Introduction</h2>'
                . ($introHtml !== '' ? $introHtml : '<p class="muted">The investigation has begun.</p>');
}
$tiles[] = ['idx' => 0, 'label' => 'Start', 'kind' => 'start', 'state' => $tile0State, 'body' => $tile0Body];

// ── Tile 1..N: one per clue ──
foreach ($clues as $i => $clue) {
    $k = $i + 1;
    if ($solution_shown || $ready_to_solve || $unlocked > $k) {
        $state = 'done';
    } elseif ($unlocked === $k) {
        $state = 'current';
    } else {
        $state = 'locked';
    }

    $body = '';
    if ($state === 'locked') {
        $body = '<h2 class="gb-modal-title">Locked</h2>'
              . '<p class="gb-modal-sub">Unlock the previous tiles first to reveal this clue.</p>';
    } elseif ($state === 'current') {
        $body  = '<h2 class="gb-modal-title">' . htmlspecialchars($clue['title'] ?: ('Clue ' . $k), ENT_QUOTES, 'UTF-8') . '</h2>';
        $body .= '<div class="card-label">Clue ' . $k . '</div>';
        if (!empty($clue['content'])) {
            $body .= '<div class="rich-content">' . $clue['content'] . '</div>';
        }
        $body .= $_gbMediaBlock(
            $clue['file_path']         ?? null,
            $clue['file_type']         ?? null,
            $clue['original_filename'] ?? null,
            $clue['file_caption']      ?? null
        );
        if (!empty($clue['page_slug'])) {
            $body .= '<div class="media-block"><a href="' . htmlspecialchars(url('p/' . $clue['page_slug']), ENT_QUOTES, 'UTF-8')
                  . '" target="_blank" class="site-link-card"><span class="site-link-icon">&#127760;</span>'
                  . '<span class="site-link-text"><strong>Open Site</strong><span>Opens in a new tab</span></span>'
                  . '<span class="site-link-arrow">&#8599;</span></a></div>';
        }
        if (!empty($clue['puzzle_slug'])) {
            $body .= '<div class="media-block"><a href="' . htmlspecialchars(url('z/' . $clue['puzzle_slug']), ENT_QUOTES, 'UTF-8')
                  . '" target="_blank" class="site-link-card"><span class="site-link-icon">&#129513;</span>'
                  . '<span class="site-link-text"><strong>Solve Puzzle</strong><span>Opens in a new tab</span></span>'
                  . '<span class="site-link-arrow">&#8599;</span></a></div>';
        }
        if (trim((string)($clue['instruction'] ?? '')) !== '') {
            $body .= '<p class="gb-modal-sub" style="margin-top:1rem">'
                   . nl2br(htmlspecialchars((string)$clue['instruction'], ENT_QUOTES, 'UTF-8'))
                   . '</p>';
        }
        $btn = 'Reveal';
        $err = ($flashType === 'error') ? $flashMsg : null;
        $body .= $_gbCodeForm($btn, $err);
        $body .= $_gbHintBlock(
            $clue['hint_text']              ?? null,
            $clue['hint_file_path']         ?? null,
            $clue['hint_file_type']         ?? null,
            $clue['hint_original_filename'] ?? null
        );
    } else { // done
        $body  = '<h2 class="gb-modal-title">' . htmlspecialchars($clue['title'] ?: ('Tile ' . $k), ENT_QUOTES, 'UTF-8') . '</h2>';
        $body .= '<div class="card-label accent">Reward</div>';
        $rewardHtml = (string)($clue['reward_content'] ?? '');
        if (richHasContent($rewardHtml)) {
            $body .= '<div class="rich-content">' . $rewardHtml . '</div>';
        } else {
            $body .= '<p class="muted">No reward has been added to this clue yet.</p>';
        }
    }
    $tiles[] = ['idx' => $k, 'label' => 'Tile ' . $k, 'kind' => 'clue', 'state' => $state, 'body' => $body];
}

// ── Finale tile (N+1) ──
$finaleIdx   = $total + 1;
$finaleState = 'locked';
$finaleBody  = '';
if ($solution_shown) {
    $finaleState = 'done';
    $finaleBody  = '<h2 class="gb-modal-title">Mystery Solved &#127881;</h2>';
    if (richHasContent($sequence['solution_content'] ?? null)) {
        $finaleBody .= '<div class="rich-content">' . $sequence['solution_content'] . '</div>';
    }
    $finaleBody .= $_gbMediaBlock(
        $sequence['solution_file_path']         ?? null,
        $sequence['solution_file_type']         ?? null,
        $sequence['solution_original_filename'] ?? null,
        $sequence['solution_caption']           ?? null
    );
    if (richHasContent($sequence['thank_you_content'] ?? null)) {
        $finaleBody .= '<div class="rich-content" style="margin-top:1.25rem;border-top:1px solid rgba(255,255,255,.15);padding-top:1.25rem">'
                     . $sequence['thank_you_content'] . '</div>';
    }
} elseif ($ready_to_solve) {
    $finaleState = 'current';
    $finaleBody  = '<h2 class="gb-modal-title">The Finale</h2>';
    if (richHasContent($sequence['finale_content'] ?? null)) {
        $finaleBody .= '<div class="rich-content">' . $sequence['finale_content'] . '</div>';
    }
    if (trim((string)($sequence['finale_instruction'] ?? '')) !== '') {
        $finaleBody .= '<p class="gb-modal-sub" style="margin-top:1rem">'
                     . nl2br(htmlspecialchars((string)$sequence['finale_instruction'], ENT_QUOTES, 'UTF-8'))
                     . '</p>';
    }
    $err = ($flashType === 'error') ? $flashMsg : null;
    $finaleBody .= $_gbCodeForm('Reveal', $err);
    $finaleBody .= $_gbHintBlock(
        $sequence['finale_hint_text']              ?? null,
        $sequence['finale_hint_file_path']         ?? null,
        $sequence['finale_hint_file_type']         ?? null,
        $sequence['finale_hint_original_filename'] ?? null
    );
} else {
    $finaleBody = '<h2 class="gb-modal-title">Locked</h2>'
                . '<p class="gb-modal-sub">Unlock every tile first to reach the finale.</p>';
}
$tiles[] = ['idx' => $finaleIdx, 'label' => 'Finale', 'kind' => 'finale', 'state' => $finaleState, 'body' => $finaleBody];

// Determine which tile to auto-open after a redirect.
//
// Player-facing rules:
//  - First visit (!started): open the "Start the Mystery" modal automatically.
//  - Correct start code: modal closes; player clicks Start tile to begin.
//  - Any other correct access code: show the just-unlocked tile's reward in
//    the same modal. It stays open until the player closes it; they must then
//    click the next tile manually to continue.
//  - Wrong code: keep the current gate's modal open so the error is visible.
$autoOpen = -1;
if ($flashType === 'error') {
    foreach ($tiles as $t) {
        if ($t['state'] === 'current') { $autoOpen = (int)$t['idx']; break; }
    }
} elseif ($flashMsg === 'start_code_accepted') {
    // Close the modal after the start code so the player sees the unlocked board.
    $autoOpen = -1;
} elseif ($flashMsg === 'clue_unlocked') {
    // The state machine bumped $unlocked; the just-completed tile is one behind.
    $autoOpen = max(0, (int)$unlocked - 1);
} elseif ($flashMsg === 'ready_to_solve') {
    // Last clue's code: $unlocked stays at $total, so open that tile directly.
    $autoOpen = $total;
} elseif ($flashMsg === 'solution_unlocked') {
    $autoOpen = $finaleIdx;
} elseif (!$started) {
    $autoOpen = 0;
}
?>

<?php
// Assign each tile a candy color class by global index so colors don't
// reset per row, then chunk the path into serpentine rows of 6.
foreach ($tiles as $globalIdx => &$_t) {
    $_t['colorClass'] = 'gb-c-' . ((($globalIdx % 6) + 1));
}
unset($_t);
$gbRowSize = 6;
$gbRows    = array_chunk($tiles, $gbRowSize);
?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&display=swap">

<div class="gb-board" id="gb-board">
  <?php foreach ($gbRows as $rIdx => $rowTiles):
    $dirCls = ($rIdx % 2 === 0) ? 'gb-row-ltr' : 'gb-row-rtl';
  ?>
  <div class="gb-row <?= $dirCls ?>">
    <?php foreach ($rowTiles as $t):
      $kind  = $t['kind'];
      $state = $t['state'];
      $idx   = $t['idx'];
      $cls   = 'gb-tile gb-' . $kind . ' gb-' . $state . ' ' . $t['colorClass'];
    ?>
    <button type="button" class="<?= $cls ?>" data-tile="<?= $idx ?>"
            aria-label="<?= htmlspecialchars($t['label'] . ' — ' . $state, ENT_QUOTES, 'UTF-8') ?>">
      <?php if ($kind === 'clue'): ?>
      <span class="gb-tile-num"><?= $idx ?></span>
      <?php endif; ?>
      <?php if ($state === 'locked'): ?>
      <span class="gb-tile-lock" aria-hidden="true">&#128274;</span>
      <?php elseif ($state === 'done'): ?>
      <span class="gb-tile-check" aria-hidden="true">&#10003;</span>
      <?php endif; ?>
    </button>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>
</div>

<!-- Tile panels (hidden; injected into modal on click) -->
<div id="gb-panels" hidden>
  <?php foreach ($tiles as $t): ?>
  <template id="gb-panel-<?= (int)$t['idx'] ?>"><?= $t['body'] ?></template>
  <?php endforeach; ?>
</div>

<!-- Modal shell -->
<div class="gb-modal-overlay hidden" id="gb-modal" role="dialog" aria-modal="true" aria-labelledby="gb-modal-title">
  <div class="gb-modal-box">
    <button type="button" class="gb-modal-close" id="gb-modal-close" aria-label="Close">&times;</button>
    <div class="gb-modal-body" id="gb-modal-body"></div>
  </div>
</div>

<?php if ($flashMsg === 'solution_unlocked'): ?>
<!-- Fireworks: triggered on first render after solving -->
<div class="gb-fireworks" id="gb-fireworks" aria-hidden="true">
  <?php for ($f = 0; $f < 6; $f++): ?>
  <span class="gb-firework" style="--gb-fw-x:<?= 8 + ($f * 14) ?>%;--gb-fw-y:<?= 18 + ($f % 3) * 22 ?>%;--gb-fw-delay:<?= $f * 0.35 ?>s;--gb-fw-hue:<?= ($f * 57) % 360 ?>"></span>
  <?php endfor; ?>
</div>
<?php endif; ?>

<script>
(function () {
  var board = document.getElementById('gb-board');
  var modal = document.getElementById('gb-modal');
  var body  = document.getElementById('gb-modal-body');
  var closer = document.getElementById('gb-modal-close');
  if (!board || !modal || !body) return;

  function open(idx) {
    var tpl = document.getElementById('gb-panel-' + idx);
    if (!tpl) return;
    body.innerHTML = '';
    body.appendChild(tpl.content.cloneNode(true));
    modal.classList.remove('hidden');
    document.documentElement.classList.add('gb-modal-open');
    var firstInput = body.querySelector('input[name="code"]');
    if (firstInput) setTimeout(function () { firstInput.focus(); }, 30);
  }
  function close() {
    modal.classList.add('hidden');
    document.documentElement.classList.remove('gb-modal-open');
  }

  board.addEventListener('click', function (e) {
    var btn = e.target.closest('.gb-tile');
    if (!btn) return;
    if (btn.classList.contains('gb-locked')) {
      btn.classList.remove('gb-shake');
      void btn.offsetWidth;
      btn.classList.add('gb-shake');
      return;
    }
    open(btn.getAttribute('data-tile'));
  });
  closer.addEventListener('click', close);
  modal.addEventListener('click', function (e) {
    if (e.target === modal) close();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !modal.classList.contains('hidden')) close();
  });

  // Responsive row chunking: 6 tiles per row on wide screens, 3 on narrow
  // (mobile or any device in portrait up to ~1024px). The serpentine
  // direction always alternates LTR/RTL so the path stays continuous.
  var allTiles = Array.from(board.querySelectorAll('.gb-tile'));
  var lastRowSize = 0;
  var mqNarrow = window.matchMedia('(max-width: 768px), (orientation: portrait) and (max-width: 1024px)');

  function chunkRows() {
    var rowSize = mqNarrow.matches ? 3 : 6;
    if (rowSize === lastRowSize) return;
    lastRowSize = rowSize;

    Array.from(board.querySelectorAll('.gb-row')).forEach(function (r) { r.remove(); });
    for (var i = 0; i < allTiles.length; i += rowSize) {
      var row = document.createElement('div');
      var rowIdx = Math.floor(i / rowSize);
      row.className = 'gb-row ' + (rowIdx % 2 === 0 ? 'gb-row-ltr' : 'gb-row-rtl');
      allTiles.slice(i, i + rowSize).forEach(function (t) { row.appendChild(t); });
      board.appendChild(row);
    }
    board.classList.add('gb-ready');
  }

  chunkRows();
  if (mqNarrow.addEventListener) mqNarrow.addEventListener('change', chunkRows);
  else if (mqNarrow.addListener) mqNarrow.addListener(chunkRows);

  // Auto-open the relevant tile after a server redirect
  var auto = <?= (int)$autoOpen ?>;
  if (auto >= 0) {
    setTimeout(function () { open(auto); }, 60);
  }

  // Fireworks: dismiss on click outside / after a few seconds
  var fw = document.getElementById('gb-fireworks');
  if (fw) {
    setTimeout(function () { fw.classList.add('gb-fade'); }, 5500);
  }
})();
</script>
