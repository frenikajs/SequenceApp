<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($sequence['title']) ?></title>
<meta name="description" content="<?= e(truncate($sequence['description'] ?? '', 160)) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Special+Elite&family=Cinzel:wght@400;600&display=swap">
<link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
<style>
<?= $css ?>
</style>
</head>
<body class="seq-body">
<a href="#seq-main" class="skip-link">Skip to main content</a>

<?php
$seqId    = (int)$sequence['id'];
$slug     = $sequence['slug'];
$type     = $sequence['type'];
$prog     = $progress;
$started  = (bool)($prog['started'] ?? false);
$unlocked = (int)($prog['unlocked_clues'] ?? 0);
$finale_unlocked = (bool)($prog['finale_unlocked'] ?? false);
$ready_to_solve  = (bool)($prog['ready_to_solve'] ?? false);
$solution_shown  = (bool)($prog['solution_shown'] ?? false);
$completed = (bool)($prog['completed'] ?? false);
$total    = count($clues);

// Determine flash intent
$flashMsg = $flash ? $flash['message'] : null;
$flashType = $flash ? $flash['type'] : null;
?>

<div class="seq-container">
  <!-- Header -->
  <header class="seq-header">
    <div class="seq-title-wrap">
      <h1 class="seq-title"><?= e($sequence['title']) ?></h1>
      <?php if ($started && !$completed): ?>
      <div class="progress-indicator">
        <?php if ($type === 'sequential'): ?>
          <span><?= $unlocked ?>/<?= $total ?> clues</span>
          <div class="progress-bar" role="progressbar" aria-label="Clues unlocked"
               aria-valuenow="<?= $unlocked ?>" aria-valuemin="0" aria-valuemax="<?= $total ?>">
            <div class="progress-fill" style="width:<?= $total > 0 ? round(($unlocked / $total) * 100) : 0 ?>%"></div>
          </div>
        <?php elseif ($type === 'gameboard'): ?>
          <span><?= $unlocked ?>/<?= $total ?> tiles</span>
          <div class="progress-bar" role="progressbar" aria-label="Tiles unlocked"
               aria-valuenow="<?= $unlocked ?>" aria-valuemin="0" aria-valuemax="<?= $total ?>">
            <div class="progress-fill" style="width:<?= $total > 0 ? round(($unlocked / $total) * 100) : 0 ?>%"></div>
          </div>
        <?php else: ?>
          <span>Open mode</span>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <div class="seq-toolbar">
      <a href="<?= url('how-to-play') ?>" target="_blank" rel="noopener" class="btn-reset btn-howto">
        ❓ How to Play
      </a>
      <?php if ($started): ?>
      <form method="POST" action="<?= url('s/' . $slug . '/reset') ?>" class="reset-form">
        <?= csrf_field() ?>
        <button type="submit" class="btn-reset" onclick="return confirm('Reset your progress? You will need to start over.')">
          ↺ Reset
        </button>
      </form>
      <?php endif; ?>
    </div>
  </header>

  <main id="seq-main" class="seq-main" tabindex="-1">

  <!-- Flash / feedback banner -->
  <?php if ($flashMsg && $flashType !== 'error' && !in_array($flashMsg, ['start_code_accepted','clue_unlocked','finale_unlocked','ready_to_solve','solution_unlocked','success'], true)): ?>
  <div class="seq-alert seq-alert-<?= $flashType === 'error' ? 'error' : 'info' ?>" id="seq-flash" role="status">
    <?php if ($flashType === 'error'): ?>
    <span class="alert-icon">⚠</span>
    <?php endif; ?>
    <?= e($flashMsg) ?>
  </div>
  <?php endif; ?>

  <?php if ($type === 'gameboard'): ?>
    <?php require __DIR__ . '/_gameboard.php'; ?>
  <?php else: ?>

  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <!-- STATE: Not started — show start code form                              -->
  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <?php if (!$started): ?>
  <div class="gate-card" id="start-gate">
    <div class="gate-icon">🔒</div>
    <h2>This mystery is locked</h2>
    <p class="gate-sub">Enter the start code to begin your investigation.</p>
    <form method="POST" action="<?= url('s/' . $slug) ?>" class="code-form" id="start-form">
      <?= csrf_field() ?>
      <div class="code-input-wrap">
        <label for="start-code-input" class="sr-only">Start code</label>
        <input type="text" name="code" id="start-code-input" class="code-input"
               placeholder="Enter start code…" autocomplete="off" autocorrect="off"
               autocapitalize="characters" spellcheck="false" required>
        <button type="submit" class="btn-primary code-submit">Unlock</button>
      </div>
      <?php if ($flashType === 'error'): ?>
      <div class="code-error" role="alert"><span class="alert-icon">⚠</span> <?= e($flashMsg) ?></div>
      <?php endif; ?>
    </form>
  </div>
  <?php endif; ?>

  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <!-- STATE: Started — show intro, clues, code prompt, finale                -->
  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <?php if ($started): ?>

  <!-- Introduction card -->
  <?php if (richHasContent($sequence['introduction_content'] ?? null) || $sequence['intro_file_path']): ?>
  <div class="content-card intro-card <?= ($flashMsg === 'start_code_accepted') ? 'card-animate-in' : '' ?>">
    <div class="card-accent-bar"></div>
    <div class="card-label">Introduction</div>
    <?php if ($sequence['introduction_content']): ?>
    <div class="rich-content">
      <?= $sequence['introduction_content'] ?>
    </div>
    <?php endif; ?>
    <?php if ($sequence['intro_file_path']): ?>
    <div class="media-block">
      <?= renderMedia(
        $sequence['intro_file_type'],
        UPLOAD_URL . '/' . $sequence['intro_file_path'],
        $sequence['intro_original_filename'] ?? ''
      ) ?>
      <?php if ($sequence['intro_caption']): ?>
      <div class="media-caption"><?= e($sequence['intro_caption']) ?></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php $atIntroGate = ($type === 'sequential' && $unlocked === 0 && !$ready_to_solve && !$solution_shown); ?>

  <!-- ── OPEN MODE: all clues visible immediately ── -->
  <?php if ($type === 'open'): ?>
  <?php foreach ($clues as $i => $clue): ?>
  <?= renderClueCard($clue, $i + 1, true, false) ?>
  <?php endforeach; ?>
  <?php endif; ?>

  <!-- ── SEQUENTIAL MODE ── -->
  <?php if ($type === 'sequential'): ?>

    <?php if ($atIntroGate): ?>
    <!-- Introduction screen — enter the introduction access code to unlock Clue 1 -->
    <div class="locked-gate" id="next-gate">
      <div class="gate-icon">🔒</div>
      <?php if (trim((string)($sequence['intro_instruction'] ?? '')) !== ''): ?>
      <p class="gate-sub"><?= nl2br(e($sequence['intro_instruction'])) ?></p>
      <?php endif; ?>
      <form method="POST" action="<?= url('s/' . $slug) ?>" class="code-form">
        <?= csrf_field() ?>
        <div class="code-input-wrap">
          <input type="text" name="code" class="code-input" placeholder="Enter access code…"
                 aria-label="Access code"
                 autocomplete="off" autocorrect="off" autocapitalize="characters" spellcheck="false">
          <button type="submit" class="btn-primary code-submit">Unlock Clue 1</button>
        </div>
        <?php if ($flashType === 'error'): ?>
        <div class="code-error" role="alert"><span class="alert-icon">⚠</span> <?= e($flashMsg) ?></div>
        <?php endif; ?>
      </form>
      <?= renderHint(
            $sequence['intro_hint_text'] ?? null,
            $sequence['intro_hint_file_path'] ?? null,
            $sequence['intro_hint_file_type'] ?? null,
            $sequence['intro_hint_original_filename'] ?? null
          ) ?>
    </div>
    <?php else: ?>

      <?php for ($k = 1; $k <= $unlocked && $k <= $total; $k++):
        $clue  = $clues[$k - 1];
        $fresh = ($k === $unlocked && $flashMsg === 'clue_unlocked');
      ?>
      <?= renderClueCard($clue, $k, true, $fresh) ?>
      <?php endfor; ?>

      <?php if (!$ready_to_solve && !$solution_shown && $unlocked >= 1 && $unlocked <= $total):
        $curClue = $clues[$unlocked - 1] ?? null;
        $isLast  = ($unlocked >= $total);
      ?>
      <div class="locked-gate" id="next-gate">
        <div class="gate-icon">🔒</div>
        <?php if ($curClue && trim((string)($curClue['instruction'] ?? '')) !== ''): ?>
        <p class="gate-sub"><?= nl2br(e($curClue['instruction'])) ?></p>
        <?php endif; ?>
        <form method="POST" action="<?= url('s/' . $slug) ?>" class="code-form">
          <?= csrf_field() ?>
          <div class="code-input-wrap">
            <input type="text" name="code" class="code-input" placeholder="Enter access code…"
                   autocomplete="off" autocorrect="off" autocapitalize="characters" spellcheck="false">
            <button type="submit" class="btn-primary code-submit"><?= $isLast ? 'Continue' : 'Unlock Clue ' . ($unlocked + 1) ?></button>
          </div>
          <?php if ($flashType === 'error'): ?>
          <div class="code-error" role="alert"><span class="alert-icon">⚠</span> <?= e($flashMsg) ?></div>
          <?php endif; ?>
        </form>
        <?php if ($curClue) {
            echo renderHint(
                $curClue['hint_text'] ?? null,
                $curClue['hint_file_path'] ?? null,
                $curClue['hint_file_type'] ?? null,
                $curClue['hint_original_filename'] ?? null
            );
        } ?>
      </div>
      <?php endif; ?>

      <?php if (!$ready_to_solve && !$solution_shown):
        for ($g = $unlocked + 1; $g <= $total; $g++): ?>
      <div class="clue-locked-indicator"><span class="lock-icon">🔒</span><span>Clue <?= $g ?></span></div>
      <?php endfor; endif; ?>

    <?php endif; ?>
  <?php endif; // end sequential ?>

  <!-- ── READY TO SOLVE (sequential, or open after clues) ── -->
  <?php if ((($type === 'sequential' && $ready_to_solve) || ($type === 'open')) && !$solution_shown): ?>
  <div class="gate-card finale-gate">
    <div class="gate-icon">🔐</div>
    <h2>Ready to Solve?</h2>
    <?php if (richHasContent($sequence['finale_content'] ?? null)): ?>
    <div class="rich-content" style="margin-bottom:1.5rem"><?= $sequence['finale_content'] ?></div>
    <?php endif; ?>
    <?php if (trim((string)($sequence['finale_instruction'] ?? '')) !== ''): ?>
    <p class="gate-sub"><?= nl2br(e($sequence['finale_instruction'])) ?></p>
    <?php endif; ?>
    <form method="POST" action="<?= url('s/' . $slug) ?>" class="code-form">
      <?= csrf_field() ?>
      <div class="code-input-wrap">
        <input type="text" name="code" class="code-input" placeholder="Enter solution code…"
               aria-label="Solution code"
               autocomplete="off" autocorrect="off" autocapitalize="characters" spellcheck="false">
        <button type="submit" class="btn-primary code-submit">Solve</button>
      </div>
      <?php if ($flashType === 'error'): ?>
      <div class="code-error" role="alert"><span class="alert-icon">⚠</span> <?= e($flashMsg) ?></div>
      <?php endif; ?>
    </form>
    <?= renderHint(
          $sequence['finale_hint_text'] ?? null,
          $sequence['finale_hint_file_path'] ?? null,
          $sequence['finale_hint_file_type'] ?? null,
          $sequence['finale_hint_original_filename'] ?? null
        ) ?>
  </div>
  <?php endif; ?>

  <!-- ── SOLUTION ── -->
  <?php if ($solution_shown):
    $hasThankYou = richHasContent($sequence['thank_you_content'] ?? null);
  ?>
  <div class="finale-card <?= $flashMsg === 'solution_unlocked' ? 'card-animate-in' : '' ?>">
    <div class="finale-glow"></div>
    <div class="card-label accent">Solution</div>
    <?php if (richHasContent($sequence['solution_content'] ?? null)): ?>
    <div class="rich-content"><?= $sequence['solution_content'] ?></div>
    <?php endif; ?>
    <?php if (!empty($sequence['solution_file_path'])): ?>
    <div class="media-block">
      <?= renderMedia($sequence['solution_file_type'], UPLOAD_URL . '/' . $sequence['solution_file_path'], $sequence['solution_original_filename'] ?? '') ?>
      <?php if (!empty($sequence['solution_caption'])): ?>
      <div class="media-caption"><?= e($sequence['solution_caption']) ?></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="finale-badge">🏆 Mystery Solved</div>
    <?php if ($hasThankYou): ?>
    <div id="thankyou-inline" class="rich-content hidden" style="margin-top:1.5rem;border-top:1px solid rgba(255,255,255,.15);padding-top:1.25rem">
      <?= $sequence['thank_you_content'] ?>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($hasThankYou): ?>
  <div id="thankyou-modal" class="ty-modal hidden" role="dialog" aria-modal="true" aria-label="A message for you">
    <div class="ty-modal-box">
      <button type="button" class="ty-modal-close" onclick="closeThankYou()" aria-label="Close message">✕</button>
      <div class="rich-content"><?= $sequence['thank_you_content'] ?></div>
      <button type="button" class="btn-primary code-submit" onclick="closeThankYou()" style="margin-top:1.25rem">Close</button>
    </div>
  </div>
  <script>
  (function () {
    var shown = false;
    var returnFocus = null;
    var modal = document.getElementById('thankyou-modal');
    function onKeydown(e) {
      if (e.key === 'Escape') { window.closeThankYou(); }
      else if (typeof trapFocus === 'function') { trapFocus(modal, e); }
    }
    function showTY() {
      if (shown || !modal) return; shown = true;
      returnFocus = document.activeElement;
      modal.classList.remove('hidden');
      var closeBtn = modal.querySelector('.ty-modal-close');
      if (closeBtn) closeBtn.focus();
      document.addEventListener('keydown', onKeydown);
    }
    window.closeThankYou = function () {
      if (modal) modal.classList.add('hidden');
      document.removeEventListener('keydown', onKeydown);
      var inl = document.getElementById('thankyou-inline');
      if (inl) inl.classList.remove('hidden');
      if (returnFocus && typeof returnFocus.focus === 'function') { returnFocus.focus(); }
    };
    window.addEventListener('load', function () { setTimeout(showTY, 10000); });
  })();
  </script>
  <?php endif; ?>
  <?php endif; ?>

  <?php endif; // end $started ?>

  <?php endif; // end gameboard / else branch ?>

  </main>

  <footer class="seq-footer">
    Powered by <a href="https://simplycreativegames.etsy.com" target="_blank" rel="noopener" class="accent">Simply Creative Games</a>
  </footer>
</div>

<!-- Lightbox -->
<div id="lightbox" class="lightbox hidden" role="dialog" aria-modal="true" aria-label="Enlarged image" onclick="closeLightbox()">
  <button class="lightbox-close" onclick="closeLightbox()" aria-label="Close image">✕</button>
  <img id="lightbox-img" src="" alt="" onclick="event.stopPropagation()">
</div>

<script src="<?= url('assets/js/app.js') ?>"></script>

<?php if ($flashMsg === 'start_code_accepted'): ?>
<script>window.addEventListener('load', () => { showNotification('✅ Code accepted! The investigation begins…', 'success'); });</script>
<?php elseif ($flashMsg === 'clue_unlocked'): ?>
<script>window.addEventListener('load', () => { showNotification('🔓 Clue unlocked!', 'success'); document.getElementById('next-gate')?.scrollIntoView({behavior:'smooth',block:'start'}); });</script>
<?php elseif ($flashMsg === 'ready_to_solve'): ?>
<script>window.addEventListener('load', () => { showNotification('🧩 All clues found — ready to solve!', 'success'); document.querySelector('.finale-gate')?.scrollIntoView({behavior:'smooth',block:'start'}); });</script>
<?php elseif ($flashMsg === 'solution_unlocked'): ?>
<script>window.addEventListener('load', () => { showNotification('🎉 Solved! Congratulations!', 'success'); });</script>
<?php endif; ?>

</body>
</html>

<?php

function renderClueCard(array $clue, int $num, bool $unlocked, bool $fresh): string
{
    $title = $clue['title'] ? e($clue['title']) : 'Clue ' . $num;
    $anim  = $fresh ? 'card-animate-in' : '';
    $html  = "<div class=\"content-card clue-card $anim\">";
    $html .= '<div class="card-accent-bar"></div>';
    $html .= "<div class=\"card-label\">Clue $num</div>";
    $html .= "<h2 class=\"clue-title\">$title</h2>";

    if ($clue['content']) {
        $html .= '<div class="rich-content">' . $clue['content'] . '</div>';
    }

    if ($clue['file_path']) {
        $url  = UPLOAD_URL . '/' . $clue['file_path'];
        $html .= '<div class="media-block">';
        $html .= renderMedia($clue['file_type'], $url, $clue['original_filename'] ?? '');
        if ($clue['file_caption']) {
            $html .= '<div class="media-caption">' . e($clue['file_caption']) . '</div>';
        }
        $html .= '</div>';
    }

    if (!empty($clue['page_slug'])) {
        $pageUrl   = htmlspecialchars(url('p/' . $clue['page_slug']), ENT_QUOTES, 'UTF-8');
        $pageType  = $clue['page_type'] ?? '';
        $linkLabel = match ($pageType) {
            'inbox'   => 'View Email',
            'sms'     => 'View Texts',
            'invoice' => 'View Invoice',
            'receipt' => 'View Receipt',
            'map'     => 'View Map',
            'calendar' => 'View Calendar',
            default   => 'View Site',
        };
        $linkIcon  = match ($pageType) {
            'inbox'   => '&#9993;',
            'sms'     => '&#128172;',
            'invoice' => '&#129534;',
            'receipt' => '&#129534;',
            'map'     => '&#128506;',
            'calendar' => '&#128197;',
            default   => '&#127760;',
        };
        $html .= '<div class="media-block">'
               . '<a href="' . $pageUrl . '" target="_blank" class="site-link-card">'
               . '<span class="site-link-icon">' . $linkIcon . '</span>'
               . '<span class="site-link-text"><strong>' . $linkLabel . '</strong><span>Opens in a new tab</span></span>'
               . '<span class="site-link-arrow">&#8599;</span>'
               . '</a></div>';
    }

    if (!empty($clue['puzzle_slug'])) {
        $puzzleUrl = htmlspecialchars(url('z/' . $clue['puzzle_slug']), ENT_QUOTES, 'UTF-8');
        $html .= '<div class="media-block">'
               . '<a href="' . $puzzleUrl . '" target="_blank" class="site-link-card">'
               . '<span class="site-link-icon">&#129513;</span>'
               . '<span class="site-link-text"><strong>Solve Puzzle</strong><span>Opens in a new tab</span></span>'
               . '<span class="site-link-arrow">&#8599;</span>'
               . '</a></div>';
    }

    $html .= '</div>';
    return $html;
}

function renderMedia(string $type, string $url, string $name): string
{
    $safeUrl  = htmlspecialchars($url, ENT_QUOTES);

    return match ($type) {
        'image' => "<figure class=\"media-frame\">
                      <img src=\"$safeUrl\" alt=\"\" class=\"media-img\" loading=\"lazy\">
                      <figcaption class=\"media-frame-bar\">
                        <button type=\"button\" class=\"btn-enlarge\" onclick=\"openLightbox('$safeUrl')\">⤢ Enlarge</button>
                      </figcaption>
                    </figure>",
        'audio' => "<div class=\"audio-player\">
                      <audio controls preload=\"metadata\"><source src=\"$safeUrl\">
                      Your browser doesn't support audio.</audio>
                    </div>",
        'video' => "<div class=\"video-player\">
                      <video controls preload=\"metadata\" playsinline>
                        <source src=\"$safeUrl\">
                        Your browser doesn't support video.
                      </video>
                    </div>",
        'pdf'   => "<div class=\"pdf-viewer\">
                      <iframe src=\"$safeUrl\" title=\"Document\" loading=\"lazy\"></iframe>
                      <a href=\"$safeUrl\" target=\"_blank\" class=\"pdf-dl\">📄 Open PDF</a>
                    </div>",
        default => "<a href=\"$safeUrl\" class=\"file-link\" download>📎 Download file</a>",
    };
}

/**
 * Renders the "Need a hint?" toggle for the hint that helps unlock the
 * CURRENT gate. The hint source is the previous step (the intro for the
 * first clue, or the previously-unlocked clue for later clues/finale).
 */
function renderHint(?string $html, ?string $filePath, ?string $fileType, ?string $fileName): string
{
    static $hintSeq = 0;
    $hasText = richHasContent($html);
    $hasFile = !empty($filePath);
    if (!$hasText && !$hasFile) {
        return '';
    }
    $hintSeq++;
    $hintId = 'hint-content-' . $hintSeq;
    $out  = '<div class="hint-toggle">';
    $out .= '<button type="button" class="btn-hint" onclick="toggleHint(this)"'
          . ' aria-expanded="false" aria-controls="' . $hintId . '">&#128161; Need a hint?</button>';
    $out .= '<div class="hint-content hidden" id="' . $hintId . '">';
    if ($hasText) {
        $out .= '<div class="rich-content hint-text">' . $html . '</div>';
    }
    if ($hasFile) {
        $out .= '<div class="media-block">'
              . renderMedia((string)$fileType, UPLOAD_URL . '/' . $filePath, (string)$fileName)
              . '</div>';
    }
    $out .= '</div></div>';
    return $out;
}
