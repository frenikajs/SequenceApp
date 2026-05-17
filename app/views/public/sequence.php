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

<?php
$seqId    = (int)$sequence['id'];
$slug     = $sequence['slug'];
$type     = $sequence['type'];
$prog     = $progress;
$started  = (bool)($prog['started'] ?? false);
$unlocked = (int)($prog['unlocked_clues'] ?? 0);
$finale_unlocked = (bool)($prog['finale_unlocked'] ?? false);
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
          <div class="progress-bar">
            <div class="progress-fill" style="width:<?= $total > 0 ? round(($unlocked / $total) * 100) : 0 ?>%"></div>
          </div>
        <?php else: ?>
          <span>Open mode</span>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php if ($started): ?>
    <form method="POST" action="<?= url('s/' . $slug . '/reset') ?>" class="reset-form">
      <?= csrf_field() ?>
      <button type="submit" class="btn-reset" onclick="return confirm('Reset your progress? You will need to start over.')">
        ↺ Reset
      </button>
    </form>
    <?php endif; ?>
  </header>

  <!-- Flash / feedback banner -->
  <?php if ($flashMsg && !in_array($flashMsg, ['start_code_accepted','clue_unlocked','finale_unlocked','success','Incorrect code. Keep searching for clues.'], true)): ?>
  <div class="seq-alert seq-alert-<?= $flashType === 'error' ? 'error' : 'info' ?>" id="seq-flash">
    <?php if ($flashType === 'error'): ?>
    <span class="alert-icon">⚠</span>
    <?php endif; ?>
    <?= e($flashMsg) ?>
  </div>
  <?php endif; ?>

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
        <input type="text" name="code" id="start-code-input" class="code-input"
               placeholder="Enter start code…" autocomplete="off" autocorrect="off"
               autocapitalize="characters" spellcheck="false" required>
        <button type="submit" class="btn-primary code-submit">Unlock</button>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <!-- STATE: Started — show intro, clues, code prompt, finale                -->
  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <?php if ($started): ?>

  <!-- Introduction card -->
  <?php if ($sequence['introduction_content'] || $sequence['intro_file_path']): ?>
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

  <!-- ── OPEN MODE: show all clues immediately ── -->
  <?php if ($type === 'open'): ?>

  <?php foreach ($clues as $i => $clue): ?>
  <?= renderClueCard($clue, $i + 1, true, false) ?>
  <?php endforeach; ?>

  <!-- Finale (open mode) -->
  <?php if ($sequence['finale_requires_code'] && !$finale_unlocked): ?>
  <div class="gate-card finale-gate">
    <div class="gate-icon">🔐</div>
    <h2>Finale Locked</h2>
    <p class="gate-sub">You've found all the clues. Enter the finale code to reveal the truth.</p>
    <form method="POST" action="<?= url('s/' . $slug) ?>" class="code-form">
      <?= csrf_field() ?>
      <div class="code-input-wrap">
        <input type="text" name="code" class="code-input" placeholder="Enter finale code…"
               autocomplete="off" autocorrect="off" autocapitalize="characters" spellcheck="false">
        <button type="submit" class="btn-primary code-submit">Reveal</button>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <?php endif; // end open mode ?>

  <!-- ── SEQUENTIAL MODE ── -->
  <?php if ($type === 'sequential'): ?>

  <?php foreach ($clues as $i => $clue): ?>
    <?php $clueNum = $i + 1; ?>
    <?php if ($i < $unlocked): ?>
    <!-- Unlocked clue -->
    <?= renderClueCard($clue, $clueNum, true, ($i === $unlocked - 1 && $flashMsg === 'clue_unlocked')) ?>
    <?php elseif ($i === $unlocked): ?>
    <!-- Next locked clue — show code prompt -->
    <div class="locked-gate" id="next-gate">
      <div class="gate-icon">🔒</div>
      <p class="gate-sub">Clue <?= $clueNum ?> is locked. Find the next access code.</p>
      <form method="POST" action="<?= url('s/' . $slug) ?>" class="code-form">
        <?= csrf_field() ?>
        <div class="code-input-wrap">
          <input type="text" name="code" class="code-input"
                 placeholder="Enter access code…"
                 autocomplete="off" autocorrect="off" autocapitalize="characters" spellcheck="false">
          <button type="submit" class="btn-primary code-submit">Unlock Clue <?= $clueNum ?></button>
        </div>
        <?php if ($flashType === 'error' && $flashMsg === 'Incorrect code. Keep searching for clues.'): ?>
        <div class="code-error"><span class="alert-icon">⚠</span> <?= e($flashMsg) ?></div>
        <?php endif; ?>
      </form>
      <?php if ($clue['hint_text'] || $clue['hint_file_path']): ?>
      <div class="hint-toggle">
        <button class="btn-hint" onclick="toggleHint(this)">💡 Need a hint?</button>
        <div class="hint-content hidden">
          <?php if ($clue['hint_text']): ?>
          <div class="rich-content hint-text"><?= $clue['hint_text'] ?></div>
          <?php endif; ?>
          <?php if ($clue['hint_file_path']): ?>
          <div class="media-block">
            <?= renderMedia($clue['hint_file_type'], UPLOAD_URL . '/' . $clue['hint_file_path'], $clue['hint_original_filename'] ?? '') ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <?php else: ?>
    <!-- Future locked clue (ghost indicator) -->
    <div class="clue-locked-indicator">
      <span class="lock-icon">🔒</span>
      <span>Clue <?= $clueNum ?></span>
    </div>
    <?php endif; ?>
  <?php endforeach; ?>

  <!-- Finale prompt (after all clues) -->
  <?php if ($unlocked >= $total && !$finale_unlocked): ?>
    <?php if ($sequence['finale_requires_code']): ?>
    <div class="gate-card finale-gate">
      <div class="gate-icon">🔐</div>
      <h2>Almost there…</h2>
      <p class="gate-sub">You've uncovered every clue. Enter the final code to reveal the truth.</p>
      <form method="POST" action="<?= url('s/' . $slug) ?>" class="code-form">
        <?= csrf_field() ?>
        <div class="code-input-wrap">
          <input type="text" name="code" class="code-input" placeholder="Final code…"
                 autocomplete="off" autocorrect="off" autocapitalize="characters" spellcheck="false">
          <button type="submit" class="btn-primary code-submit">Reveal</button>
        </div>
      </form>
    </div>
    <?php else: ?>
      <?php $finale_unlocked = true; ?>
    <?php endif; ?>
  <?php endif; ?>

  <?php endif; // end sequential mode ?>

  <!-- ── FINALE ── -->
  <?php if ($finale_unlocked || ($unlocked >= $total && !$sequence['finale_requires_code'])): ?>
  <div class="finale-card <?= $flashMsg === 'finale_unlocked' ? 'card-animate-in' : '' ?>">
    <div class="finale-glow"></div>
    <div class="card-label accent">Finale</div>
    <?php if ($sequence['finale_content']): ?>
    <div class="rich-content">
      <?= $sequence['finale_content'] ?>
    </div>
    <?php endif; ?>
    <?php if ($sequence['finale_file_path']): ?>
    <div class="media-block">
      <?= renderMedia(
        $sequence['finale_file_type'],
        UPLOAD_URL . '/' . $sequence['finale_file_path'],
        $sequence['finale_original_filename'] ?? ''
      ) ?>
      <?php if ($sequence['finale_caption']): ?>
      <div class="media-caption"><?= e($sequence['finale_caption']) ?></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="finale-badge">🏆 Mystery Solved</div>
  </div>
  <?php endif; ?>

  <?php endif; // end $started ?>

  <footer class="seq-footer">
    Powered by <span class="accent">Simply Creative Games</span>
  </footer>
</div>

<!-- Lightbox -->
<div id="lightbox" class="lightbox hidden" onclick="closeLightbox()">
  <button class="lightbox-close" onclick="closeLightbox()" aria-label="Close">✕</button>
  <img id="lightbox-img" src="" alt="" onclick="event.stopPropagation()">
</div>

<script src="<?= url('assets/js/app.js') ?>"></script>

<?php if ($flashMsg === 'start_code_accepted'): ?>
<script>window.addEventListener('load', () => { showNotification('✅ Code accepted! The investigation begins…', 'success'); });</script>
<?php elseif ($flashMsg === 'clue_unlocked'): ?>
<script>window.addEventListener('load', () => { showNotification('🔓 Clue unlocked!', 'success'); document.getElementById('next-gate')?.scrollIntoView({behavior:'smooth',block:'start'}); });</script>
<?php elseif ($flashMsg === 'finale_unlocked'): ?>
<script>window.addEventListener('load', () => { showNotification('🎉 Finale revealed! Congratulations!', 'success'); });</script>
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

    $html .= '</div>';
    return $html;
}

function renderMedia(string $type, string $url, string $name): string
{
    $safeUrl  = htmlspecialchars($url, ENT_QUOTES);
    $safeName = htmlspecialchars($name, ENT_QUOTES);

    return match ($type) {
        'image' => "<figure class=\"media-frame\">
                      <img src=\"$safeUrl\" alt=\"$safeName\" class=\"media-img\" loading=\"lazy\">
                      <figcaption class=\"media-frame-bar\">
                        <span class=\"media-frame-name\">$safeName</span>
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
                      <iframe src=\"$safeUrl\" title=\"$safeName\" loading=\"lazy\"></iframe>
                      <a href=\"$safeUrl\" target=\"_blank\" class=\"pdf-dl\">📄 Open PDF</a>
                    </div>",
        default => "<a href=\"$safeUrl\" class=\"file-link\" download>📎 $safeName</a>",
    };
}
