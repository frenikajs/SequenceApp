<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($sequence['title']) ?></title>
<meta name="description" content="<?= e(truncate(strip_tags($sequence['description'] ?? ''), 160)) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Special+Elite&family=Cinzel:wght@400;600&family=Bangers&family=Permanent+Marker&family=Knewave&display=swap">
<link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
<style>
<?= $css ?>
</style>
</head>
<?php
// Game Board sequences carry a page-level theme class so the WHOLE page (not
// just the board) can be restyled — e.g. a full Winter Wonderland scene.
$gbThemes  = ['candyland', 'winter', 'spooky', 'pool', 'birthday'];
$gbTheme   = ($sequence['type'] === 'gameboard' && in_array($sequence['gameboard_theme'] ?? '', $gbThemes, true))
           ? $sequence['gameboard_theme'] : '';
$bodyClass = 'seq-body' . ($gbTheme !== '' ? ' gb-page gb-page-' . $gbTheme : '');
?>
<body class="<?= $bodyClass ?>" data-seq="<?= e($sequence['slug']) ?>" data-started="<?= ($progress['started'] ?? false) ? '1' : '0' ?>">
<a href="#seq-main" class="skip-link">Skip to main content</a>

<?php if ($gbTheme !== ''): ?>
<!-- Game Board scene: a full-page decorative backdrop for the chosen theme.
     Purely decorative (aria-hidden) and sits behind all page content. -->
<?php if ($gbTheme === 'candyland'): ?>
<!-- Candy Land: a rainbow arch, cotton-candy clouds, a candy forest (lollipop
     trees, gumdrops, candy canes, a candy house) and drifting sweets. -->
<div class="gb-scene gb-scene-candyland" aria-hidden="true">
  <div class="gb-rainbow"></div>
  <div class="gb-clouds"></div>
  <div class="gb-sprinkle gb-sprinkle-far"></div>
  <div class="gb-sprinkle gb-sprinkle-mid"></div>
  <div class="gb-sprinkle gb-sprinkle-near"></div>
  <div class="gb-skyline"></div>
</div>
<?php elseif ($gbTheme === 'winter'): ?>
<div class="gb-scene gb-scene-winter" aria-hidden="true">
  <div class="gb-stars"></div>
  <div class="gb-moon"></div>
  <div class="gb-snow gb-snow-far"></div>
  <div class="gb-snow gb-snow-mid"></div>
  <div class="gb-snow gb-snow-near"></div>
  <div class="gb-skyline"></div>
  <div class="gb-snowground"></div>
</div>
<?php elseif ($gbTheme === 'spooky'): ?>
<!-- Haunted graveyard: eerie moon, twinkling stars, drifting bats, a spooky
     silhouette skyline and rolling ground fog. -->
<div class="gb-scene gb-scene-spooky" aria-hidden="true">
  <div class="gb-stars"></div>
  <div class="gb-moon"></div>
  <div class="gb-bats"></div>
  <div class="gb-skyline"></div>
  <div class="gb-fog gb-fog-1"></div>
  <div class="gb-fog gb-fog-2"></div>
</div>
<?php elseif ($gbTheme === 'pool'): ?>
<!-- Summer poolside: blazing sun, drifting clouds, palm-tree skyline and a
     shimmering pool of water with animated caustics along the bottom. -->
<div class="gb-scene gb-scene-pool" aria-hidden="true">
  <div class="gb-sun"></div>
  <div class="gb-clouds"></div>
  <div class="gb-skyline"></div>
  <div class="gb-water"><div class="gb-caustics"></div></div>
</div>
<?php elseif ($gbTheme === 'birthday'): ?>
<!-- Birthday party: triangular bunting across the top, balloons floating up
     and three layers of falling confetti. -->
<div class="gb-scene gb-scene-birthday" aria-hidden="true">
  <div class="gb-bunting"></div>
  <div class="gb-balloons"></div>
  <div class="gb-confetti gb-confetti-far"></div>
  <div class="gb-confetti gb-confetti-mid"></div>
  <div class="gb-confetti gb-confetti-near"></div>
</div>
<?php endif; ?>
<?php endif; ?>

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

// Group (multiplayer) play state
$lobby        = $lobby        ?? null;   // 'choose' | 'host' | 'join' | null (in play)
$isGroup      = $isGroup      ?? false;
$isHost       = $isHost       ?? false;
$groupCode    = $groupCode    ?? '';
$groupMembers = $groupMembers ?? 0;
$groupHint    = $groupHint    ?? ''; // step token of the hint the host has shared
$isJoiner     = ($isGroup && !$isHost && $lobby === null); // group player, host drives unlocks

// Interactive (suspects) play state
$interactiveStage = $interactiveStage ?? null;   // 'select' | 'play' | null
$suspects         = $suspects         ?? [];
$mySuspectIndex   = $mySuspectIndex   ?? null;
$mySuspect        = $mySuspect        ?? null;
$suspectClues     = $suspectClues     ?? [];
$selAssigned      = $selAssigned      ?? 0;
$selTotal         = $selTotal         ?? 0;
$selTaken         = $selTaken         ?? [];
$voteOutcome      = $voteOutcome      ?? 'pending';
$accuseRound      = $accuseRound      ?? 1;
$myVote           = $myVote           ?? null;
$voteVoted        = $voteVoted        ?? 0;
$voteTotal        = $voteTotal        ?? 0;
$voteResultIndex  = $voteResultIndex  ?? null;
$voteClosed       = $voteClosed       ?? false;
$isInteractive    = ($type === 'interactive');
// Interactive plays through clues exactly like Sequential (host-driven unlocks).
$isSequentialLike = in_array($type, ['sequential', 'interactive'], true);

// Determine flash intent
$flashMsg = $flash ? $flash['message'] : null;
$flashType = $flash ? $flash['type'] : null;

// ── Evidence Locker: only the rewards the player has banked so far ──
// Each clue can carry up to three rewards — its own, its puzzle's, and its
// decoy page's — but ONLY reward content is filed here, never the raw clues.
$evidence = [];
if ($started) {
    $allUnlocked = ($ready_to_solve || $solution_shown);
    $upTo = ($type === 'open') ? $total : ($allUnlocked ? $total : (int)$unlocked);
    $tileWord = ($type === 'gameboard') ? 'Tile ' : 'Clue ';

    for ($k = 1; $k <= $upTo && $k <= $total; $k++) {
        $clue = $clues[$k - 1];
        $sources = [
            $clue['reward_content'] ?? null,  // the clue's own reward
            $clue['page_reward']    ?? null,  // its decoy page's reward
        ];
        // A puzzle's reward only joins the locker once that puzzle is solved.
        if (!empty($clue['puzzle_solved'])) {
            $sources[] = $clue['puzzle_reward'] ?? null;
        }
        $rewards = [];
        foreach ($sources as $rw) {
            if (richHasContent($rw)) {
                $rewards[] = (string)$rw;
            }
        }
        if (empty($rewards)) {
            continue;
        }
        $title = (($clue['title'] ?? '') !== '') ? $clue['title'] : ($tileWord . $k);
        $body  = '';
        foreach ($rewards as $ri => $rw) {
            if ($ri > 0) {
                $body .= '<hr class="ev-sep">';
            }
            $body .= '<div class="rich-content">' . $rw . '</div>';
        }
        // Exhibit label: A, B, C … then fall back to numbers past Z.
        $n       = count($evidence) + 1;
        $exhibit = $n <= 26 ? chr(64 + $n) : (string)$n;
        $evidence[] = '<div class="ev-card">'
            . '<div class="ev-tag"><span class="ev-tag-no">' . e($exhibit) . '</span>'
            . '<span class="ev-tag-text">EVIDENCE</span></div>'
            . '<div class="ev-card-title">' . e($title) . '</div>'
            . '<div class="ev-card-body">' . $body . '</div>'
            . '</div>';
    }

    // Interactive: the player's own suspect clues for each unlocked step also file
    // into their evidence locker (private to them — keyed by their chosen suspect).
    if ($isInteractive && !empty($suspectClues)) {
        foreach ($suspectClues as $k => $sc) {
            $scBody = '';
            if (trim(strip_tags((string)($sc['body'] ?? ''))) !== '') {
                $scBody .= '<div class="rich-content">' . nl2br(e($sc['body'])) . '</div>';
            }
            if (!empty($sc['file_path'])) {
                $scBody .= '<div class="media-block">' . renderMedia(
                    (string)($sc['file_type'] ?? ''),
                    UPLOAD_URL . '/' . $sc['file_path'],
                    (string)($sc['original_filename'] ?? '')
                ) . '</div>';
            }
            if ($scBody === '') { continue; }
            $n       = count($evidence) + 1;
            $exhibit = $n <= 26 ? chr(64 + $n) : (string)$n;
            $evidence[] = '<div class="ev-card ev-card-suspect">'
                . '<div class="ev-tag"><span class="ev-tag-no">' . e($exhibit) . '</span>'
                . '<span class="ev-tag-text">SUSPECT CLUE</span></div>'
                . '<div class="ev-card-title">Clue ' . (int)$k . '</div>'
                . '<div class="ev-card-body">' . $scBody . '</div>'
                . '</div>';
        }
    }
}
?>

<div class="seq-container">
  <!-- Header -->
  <header class="seq-header">
    <div class="seq-title-wrap">
      <h1 class="seq-title"><?= e($sequence['title']) ?></h1>
      <?php
        $showTimer = ($started && !$solution_shown && $lobby === null && $interactiveStage !== 'select');
        $hasProgress = in_array($type, ['sequential', 'gameboard', 'interactive'], true);
      ?>
      <?php if ($showTimer && $hasProgress && $interactiveStage !== 'select'): ?>
      <div class="progress-indicator">
        <?php if ($type !== 'gameboard'): ?>
          <span><?= $unlocked ?>/<?= $total ?> clues</span>
          <div class="progress-bar" role="progressbar" aria-label="Clues unlocked"
               aria-valuenow="<?= $unlocked ?>" aria-valuemin="0" aria-valuemax="<?= $total ?>">
            <div class="progress-fill" style="width:<?= $total > 0 ? round(($unlocked / $total) * 100) : 0 ?>%"></div>
          </div>
        <?php else: // gameboard ?>
          <span><?= $unlocked ?>/<?= $total ?> tiles</span>
          <div class="progress-bar" role="progressbar" aria-label="Tiles unlocked"
               aria-valuenow="<?= $unlocked ?>" aria-valuemin="0" aria-valuemax="<?= $total ?>">
            <div class="progress-fill" style="width:<?= $total > 0 ? round(($unlocked / $total) * 100) : 0 ?>%"></div>
          </div>
        <?php endif; ?>
        <div class="seq-timer" id="seq-timer" title="Time elapsed">⏱ 0:00</div>
      </div>
      <?php endif; ?>
    </div>
    <div class="seq-toolbar">
      <?php if ($showTimer && !$hasProgress): ?>
      <div class="seq-timer seq-timer-standalone" id="seq-timer" title="Time elapsed">⏱ 0:00</div>
      <?php endif; ?>
      <?php if ($isInteractive && $interactiveStage === 'play' && $mySuspect): ?>
      <div class="seq-suspect-chip" title="Your suspect">
        🕵️ <span class="seq-suspect-name"><?= e($mySuspect['name'] ?? 'Your suspect') ?></span>
        <?php if (!empty($mySuspect['card'])): ?>
        <button type="button" class="seq-card-btn" onclick="openGameCard()">🎴 Game Card</button>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <?php if (!empty($evidence)): ?>
      <button type="button" class="btn-reset" onclick="openEvidence()">
        🗂️ Evidence
      </button>
      <?php endif; ?>
      <button type="button" class="btn-reset btn-howto" onclick="openHowTo()">
        ❓ How to Play
      </button>
      <?php if ($started && !$isJoiner): // group members can't reset — only the host drives the game ?>
      <form method="POST" action="<?= url('s/' . $slug . '/reset') ?>" class="reset-form" id="reset-form">
        <?= csrf_field() ?>
        <button type="button" class="btn-reset" onclick="openResetConfirm()">
          ↺ Reset
        </button>
      </form>
      <?php endif; ?>
    </div>
  </header>

  <main id="seq-main" class="seq-main" tabindex="-1">

  <?php if (isAdmin()): ?>
  <!-- Admin play-test panel (only visible to logged-in admins; stats not counted) -->
  <details class="pt-panel">
    <summary>🛠️ Play-test mode — tap to reveal codes</summary>
    <div class="pt-body">
      <p class="pt-note">You're logged in as admin, so your views and completion aren't counted in the stats. Codes for this sequence:</p>
      <ul class="pt-codes">
        <li><strong>Start code:</strong> <code><?= e($sequence['start_code'] ?: '(none)') ?></code></li>
        <?php if (trim((string)($sequence['intro_access_code'] ?? '')) !== ''): ?>
        <li><strong>Intro access code:</strong> <code><?= e($sequence['intro_access_code']) ?></code></li>
        <?php endif; ?>
        <?php if ($type !== 'whodunit'): ?>
        <?php foreach ($clues as $i => $clue): ?>
        <li><strong><?= e($clue['title'] ?: 'Clue ' . ($i + 1)) ?>:</strong> <code><?= e($clue['access_code']) ?></code></li>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php if ($type !== 'whodunit' && !empty($sequence['finale_requires_code'])): ?>
        <li><strong>Finale / solution code:</strong> <code><?= e($sequence['finale_code'] ?: '(none set)') ?></code></li>
        <?php endif; ?>
        <?php if ($type === 'whodunit' && !empty($accusation['categories'])): ?>
        <li><strong>Accusation answers:</strong>
          <?php foreach ($accusation['categories'] as $cat):
            $ansOpt = $cat['options'][$cat['answer']] ?? null; ?>
          <code><?= e($cat['label']) ?>: <?= e(is_array($ansOpt) ? ($ansOpt['name'] ?? '?') : ($ansOpt ?? '?')) ?></code>
          <?php endforeach; ?>
        </li>
        <?php endif; ?>
        <?php if ($isInteractive):
          $ptAcc = json_decode($sequence['accusation_json'] ?? '', true);
          $ptSus = (is_array($ptAcc) && !empty($ptAcc['categories'])) ? ($ptAcc['categories'][0]['options'] ?? []) : [];
          $ptCulprit = $ptSus[(int)($ptAcc['categories'][0]['answer'] ?? 0)]['name'] ?? '(not set)';
        ?>
        <li><strong>Culprit:</strong> <code><?= e($ptCulprit) ?></code></li>
        <?php endif; ?>
      </ul>
    </div>
  </details>
  <?php endif; ?>

  <!-- Flash / feedback banner -->
  <?php if ($flashMsg && $flashType !== 'error' && !in_array($flashMsg, ['start_code_accepted','clue_unlocked','finale_unlocked','ready_to_solve','solution_unlocked','opened_case','success'], true)): ?>
  <div class="seq-alert seq-alert-<?= $flashType === 'error' ? 'error' : 'info' ?>" id="seq-flash" role="status">
    <?php if ($flashType === 'error'): ?>
    <span class="alert-icon">⚠</span>
    <?php endif; ?>
    <?= e($flashMsg) ?>
  </div>
  <?php endif; ?>

  <?php if ($lobby !== null): ?>
    <?php require __DIR__ . '/_lobby.php'; ?>
  <?php elseif ($interactiveStage === 'select'): ?>
    <?php require __DIR__ . '/_suspect_select.php'; ?>
  <?php elseif ($type === 'gameboard' && $started): ?>
    <?php require __DIR__ . '/_gameboard.php'; ?>
  <?php else: ?>

  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <!-- STATE: Not started — show start code form                              -->
  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <?php if (!$started): ?>
  <div class="lobby-file" id="start-gate">
    <div class="lobby-file-head"><span>🗂️ CASE FILE</span><span class="lobby-stamp">CONFIDENTIAL</span></div>
    <div class="lobby-file-body">
      <h2>🔒 This mystery is locked</h2>
      <p class="gate-sub">Enter the start code for this mystery &mdash; or a group code from your host &mdash; to begin the investigation.</p>
      <form method="POST" action="<?= url('s/' . $slug) ?>" class="code-form" id="start-form">
        <?= csrf_field() ?>
        <label for="start-code-input" class="sr-only">Start or group code</label>
        <input type="text" name="code" id="start-code-input" class="code-input"
               placeholder="ENTER CODE" autocomplete="off" autocorrect="off"
               autocapitalize="characters" spellcheck="false" required>
        <button type="submit" class="lobby-btn">🔓 Open Case</button>
        <?php if ($flashType === 'error'): ?>
        <div class="code-error" role="alert"><span class="alert-icon">⚠</span> <?= e($flashMsg) ?></div>
        <?php endif; ?>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <!-- STATE: Started — show intro, clues, code prompt, finale                -->
  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <?php if ($started): ?>

  <!-- Introduction card -->
  <?php if (richHasContent($sequence['introduction_content'] ?? null) || $sequence['intro_file_path']): ?>
  <?php if ($type === 'whodunit'): ?>
  <section class="wd-intro">
    <h3 class="wd-cat-title">📁 Introduction</h3>
    <div class="content-card intro-card <?= ($flashMsg === 'start_code_accepted') ? 'card-animate-in' : '' ?>" id="intro-card">
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
  </section>
  <?php else: ?>
  <div class="content-card intro-card <?= ($flashMsg === 'start_code_accepted') ? 'card-animate-in' : '' ?>" id="intro-card">
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
  <?php endif; ?>

  <?php $atIntroGate = ($isSequentialLike && $unlocked === 0 && !$ready_to_solve && !$solution_shown); ?>

  <!-- ── WHODUNIT: character cards ── -->
  <?php if ($type === 'whodunit' && $accusation): ?>
    <?php foreach ($accusation['categories'] as $cat): ?>
    <section class="wd-cat">
      <h3 class="wd-cat-title">📁 <?= e($cat['label']) ?></h3>
      <div class="wd-cards">
        <?php foreach ($cat['options'] as $opt):
          $imgUrl = !empty($opt['image']) ? htmlspecialchars(UPLOAD_URL . '/' . $opt['image'], ENT_QUOTES, 'UTF-8') : '';
        ?>
        <?php if ($imgUrl !== ''): ?>
        <div class="wd-card wd-card-clickable" role="button" tabindex="0"
             onclick="openLightbox('<?= $imgUrl ?>')"
             onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openLightbox('<?= $imgUrl ?>');}"
             aria-label="Enlarge <?= e($opt['name']) ?>">
          <div class="wd-card-img"><img src="<?= $imgUrl ?>" alt="<?= e($opt['name']) ?>" loading="lazy"></div>
          <div class="wd-card-name"><?= e($opt['name']) ?></div>
          <?php if (!empty($opt['desc'])): ?>
          <div class="wd-card-desc"><?= e($opt['desc']) ?></div>
          <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="wd-card">
          <div class="wd-card-img wd-card-noimg">🔍</div>
          <div class="wd-card-name"><?= e($opt['name']) ?></div>
          <?php if (!empty($opt['desc'])): ?>
          <div class="wd-card-desc"><?= e($opt['desc']) ?></div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- ── WHODUNIT: evidence presented as case files ── -->
  <?php if ($type === 'whodunit'): ?>
    <?php if (!empty($clues)): ?>
    <section class="wd-evidence">
      <h3 class="wd-cat-title">📁 Case Files</h3>
      <?php foreach ($clues as $i => $clue): ?>
      <?= renderClueCard($clue, $i + 1, true, false, true, false, true) ?>
      <?php endforeach; ?>
    </section>
    <?php endif; ?>
  <?php elseif ($type === 'open'): ?>
  <!-- ── OPEN MODE: all clues visible immediately ── -->
  <?php foreach ($clues as $i => $clue): ?>
  <?= renderClueCard($clue, $i + 1, true, false, true) ?>
  <?php endforeach; ?>
  <?php endif; ?>

  <!-- ── SEQUENTIAL MODE (also drives Interactive clue unlocks) ── -->
  <?php if ($isSequentialLike): ?>

    <?php if ($atIntroGate): ?>
    <?php if ($isJoiner): ?>
    <!-- Group joiner: the host enters the introduction access code for everyone. -->
    <div class="locked-gate group-wait">
      <div class="gate-icon">🔎</div>
      <p class="gate-sub">The host is leading the investigation — Clue 1 unlocks for everyone once the host enters the access code.</p>
      <?php if ($groupHint === 'intro') {
          echo '<p class="gate-sub group-hint-note">💡 Your host shared a hint:</p>';
          echo renderHint(
              $sequence['intro_hint_text'] ?? null,
              $sequence['intro_hint_file_path'] ?? null,
              $sequence['intro_hint_file_type'] ?? null,
              $sequence['intro_hint_original_filename'] ?? null,
              'intro',
              true
          );
      } ?>
    </div>
    <?php else: ?>
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
            $sequence['intro_hint_original_filename'] ?? null,
            'intro'
          ) ?>
    </div>
    <?php endif; ?>
    <?php else: ?>

      <?php for ($k = 1; $k <= $unlocked && $k <= $total; $k++):
        $clue  = $clues[$k - 1];
        $fresh = ($k === $unlocked && in_array($flashMsg, ['clue_unlocked', 'start_code_accepted'], true));
      ?>
      <?= renderClueCard($clue, $k, true, $fresh) ?>
      <?php endfor; ?>

      <?php if (!$ready_to_solve && !$solution_shown && $unlocked >= 1 && $unlocked <= $total):
        $curClue = $clues[$unlocked - 1] ?? null;
        $isLast  = ($unlocked >= $total);
      ?>
      <?php if ($isJoiner): ?>
      <div class="locked-gate group-wait">
        <div class="gate-icon">🔎</div>
        <p class="gate-sub">The host is leading the investigation — the next clue unlocks for everyone at once.</p>
        <?php if ($curClue && $groupHint === 'clue:' . $unlocked) {
            echo '<p class="gate-sub group-hint-note">💡 Your host shared a hint:</p>';
            echo renderHint(
                $curClue['hint_text'] ?? null,
                $curClue['hint_file_path'] ?? null,
                $curClue['hint_file_type'] ?? null,
                $curClue['hint_original_filename'] ?? null,
                'clue:' . $unlocked,
                true
            );
        } ?>
      </div>
      <?php else: ?>
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
                $curClue['hint_original_filename'] ?? null,
                'clue:' . $unlocked
            );
        } ?>
      </div>
      <?php endif; ?>
      <?php endif; ?>

      <?php if (!$ready_to_solve && !$solution_shown):
        for ($g = $unlocked + 1; $g <= $total; $g++): ?>
      <div class="clue-locked-indicator"><span class="lock-icon">🔒</span><span>Clue <?= $g ?></span></div>
      <?php endfor; endif; ?>

    <?php endif; ?>
  <?php endif; // end sequential ?>

  <!-- ── INTERACTIVE: game card, suspect-clue pop-up, and group vote ── -->
  <?php if ($isInteractive && $interactiveStage === 'play'): ?>

    <?php if ($mySuspect && !empty($mySuspect['card'])):
      $cardUrl  = htmlspecialchars(UPLOAD_URL . '/' . $mySuspect['card'], ENT_QUOTES, 'UTF-8');
      $cardType = $mySuspect['card_type'] ?? '';
    ?>
    <!-- Game card pop-up (opened from the header) -->
    <div id="gamecard-modal" class="sol-modal hidden" role="dialog" aria-modal="true" aria-label="Your game card">
      <div class="sol-box">
        <div class="sol-body">
          <?php if ($cardType === 'pdf'): ?>
          <iframe src="<?= $cardUrl ?>" title="Game card" style="width:100%;height:70vh;border:none;border-radius:8px"></iframe>
          <?php else: ?>
          <img src="<?= $cardUrl ?>" alt="Game card for <?= e($mySuspect['name'] ?? '') ?>" style="width:100%;border-radius:8px">
          <?php endif; ?>
        </div>
        <div class="sol-foot">
          <button type="button" class="btn-primary code-submit" onclick="closeGameCard()">Close</button>
        </div>
      </div>
    </div>
    <script>
    (function () {
      var modal = document.getElementById('gamecard-modal');
      if (!modal) return;
      var ret = null;
      function onKey(e) { if (e.key === 'Escape') window.closeGameCard(); else if (typeof trapFocus === 'function') trapFocus(modal, e); }
      window.openGameCard = function () {
        ret = document.activeElement;
        modal.classList.remove('hidden'); document.documentElement.classList.add('ev-open');
        var b = modal.querySelector('.sol-foot button'); if (b) b.focus();
        document.addEventListener('keydown', onKey);
      };
      window.closeGameCard = function () {
        modal.classList.add('hidden'); document.documentElement.classList.remove('ev-open');
        document.removeEventListener('keydown', onKey);
        if (ret && ret.focus) ret.focus();
      };
      modal.addEventListener('click', function (e) { if (e.target === modal) window.closeGameCard(); });
    })();
    </script>
    <?php endif; ?>

    <?php
      // Newest suspect clue available (for the just-unlocked step), if any.
      $latestScKey = null;
      foreach (array_keys($suspectClues) as $kk) { if ($kk <= $unlocked) { $latestScKey = $kk; } }
    ?>
    <?php if ($latestScKey !== null && !$solution_shown):
      $sc = $suspectClues[$latestScKey];
    ?>
    <!-- Suspect-clue pop-up: the player's private clue for the latest unlocked step -->
    <div id="suspect-clue-modal" class="sol-modal hidden" role="dialog" aria-modal="true" aria-label="Your suspect clue"
         data-clue="<?= (int)$latestScKey ?>" data-suspect="<?= (int)($mySuspectIndex ?? -1) ?>">
      <div class="sol-box">
        <div class="sol-head sc-pop-head">🕵️ <?= e($mySuspect['name'] ?? 'Your suspect') ?> — Clue <?= (int)$latestScKey ?></div>
        <div class="sol-body">
          <?php if (trim(strip_tags((string)($sc['body'] ?? ''))) !== ''): ?>
          <div class="rich-content"><?= nl2br(e($sc['body'])) ?></div>
          <?php endif; ?>
          <?php if (!empty($sc['file_path'])): ?>
          <div class="media-block"><?= renderMedia(
            (string)($sc['file_type'] ?? ''),
            UPLOAD_URL . '/' . $sc['file_path'],
            (string)($sc['original_filename'] ?? '')
          ) ?></div>
          <?php endif; ?>
        </div>
        <div class="sol-foot">
          <button type="button" class="btn-primary code-submit" onclick="closeSuspectClue()">Got it</button>
        </div>
      </div>
    </div>
    <script>
    (function () {
      var modal = document.getElementById('suspect-clue-modal');
      if (!modal) return;
      var slug = document.body.dataset.seq || '';
      var sus  = modal.dataset.suspect || '';
      var KEY  = 'seqIxSeen:' + slug + ':' + sus;
      var clueNo = parseInt(modal.dataset.clue, 10) || 0;
      var seen = 0;
      try { seen = parseInt(localStorage.getItem(KEY) || '0', 10) || 0; } catch (e) {}
      var ret = null;
      function onKey(e) { if (e.key === 'Escape') window.closeSuspectClue(); else if (typeof trapFocus === 'function') trapFocus(modal, e); }
      window.openSuspectClue = function () {
        ret = document.activeElement;
        modal.classList.remove('hidden'); document.documentElement.classList.add('ev-open');
        var b = modal.querySelector('.sol-foot button'); if (b) b.focus();
        document.addEventListener('keydown', onKey);
      };
      window.closeSuspectClue = function () {
        modal.classList.add('hidden'); document.documentElement.classList.remove('ev-open');
        document.removeEventListener('keydown', onKey);
        modal.querySelectorAll('audio, video').forEach(function (m) { try { m.pause(); } catch (e) {} });
        if (ret && ret.focus) ret.focus();
      };
      modal.addEventListener('click', function (e) { if (e.target === modal) window.closeSuspectClue(); });
      // Auto-pop only when this step's clue hasn't been shown to this player yet.
      if (clueNo > seen) {
        try { localStorage.setItem(KEY, String(clueNo)); } catch (e) {}
        setTimeout(window.openSuspectClue, 600);
      }
    })();
    </script>
    <?php endif; ?>

    <?php if ($ready_to_solve && !$solution_shown): ?>
    <?php require __DIR__ . '/_interactive_vote.php'; ?>
    <?php endif; ?>

  <?php endif; // end interactive play ?>

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
    <?php if ($isJoiner): ?>
    <p class="gate-sub">The host will enter the solution when the group is ready.</p>
    <?php if ($groupHint === 'finale') {
        echo '<p class="gate-sub group-hint-note">💡 Your host shared a hint:</p>';
        echo renderHint(
            $sequence['finale_hint_text'] ?? null,
            $sequence['finale_hint_file_path'] ?? null,
            $sequence['finale_hint_file_type'] ?? null,
            $sequence['finale_hint_original_filename'] ?? null,
            'finale',
            true
        );
    } ?>
    <?php else: ?>
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
          $sequence['finale_hint_original_filename'] ?? null,
          'finale'
        ) ?>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- ── WHODUNIT: Make Your Accusation ── -->
  <?php if ($type === 'whodunit' && !$solution_shown): ?>
  <section class="wd-accusation">
    <h3 class="wd-cat-title">📁 Accusation</h3>
    <?php if ($isJoiner): ?>
    <div class="gate-card accusation-gate">
      <div class="gate-icon">🕵️</div>
      <h2>Make Your Accusation</h2>
      <p class="gate-sub">The host makes the accusation for the group — review the evidence together and tell them your answer.</p>
    </div>
    <?php elseif (!empty($accusation['categories'])):
      $accCats    = $accusation['categories'];
      $accResults = $prog['acc_results'] ?? [];
      $accChoices = $prog['acc_choices'] ?? [];
      $accWrong   = ($flashType === 'error' && $flashMsg === 'accusation_wrong');
    ?>
    <div class="gate-card accusation-gate" id="accusation">
      <div class="gate-icon">🕵️</div>
      <h2>Make Your Accusation</h2>
      <?php if (trim((string)($accusation['prompt'] ?? '')) !== ''): ?>
      <p class="gate-sub"><?= nl2br(e($accusation['prompt'])) ?></p>
      <?php else: ?>
      <p class="gate-sub">Weigh the evidence and lock in your answer for each part of the case.</p>
      <?php endif; ?>
      <form method="POST" action="<?= url('s/' . $slug . '/accuse') ?>" class="accusation-form">
        <?= csrf_field() ?>
        <?php foreach ($accCats as $i => $cat):
          $res    = $accResults[$i] ?? null;          // true | false | null
          $chosen = isset($accChoices[$i]) ? (int)$accChoices[$i] : -1;
          $locked = ($res === true);
        ?>
        <div class="acc-cat <?= $res === true ? 'acc-correct' : ($res === false ? 'acc-wrong' : '') ?>">
          <label class="acc-label">
            <span><?= e($cat['label']) ?></span>
            <?php if ($res === true): ?><span class="acc-mark ok">✓</span>
            <?php elseif ($res === false): ?><span class="acc-mark no">✗</span><?php endif; ?>
          </label>
          <select name="acc[<?= $i ?>]" class="acc-select" <?= $locked ? 'disabled' : 'required' ?>>
            <option value="">— choose —</option>
            <?php foreach ($cat['options'] as $oi => $opt): ?>
            <option value="<?= $oi ?>" <?= ($chosen === $oi) ? 'selected' : '' ?>><?= e(is_array($opt) ? ($opt['name'] ?? '') : $opt) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if ($locked): ?>
          <input type="hidden" name="acc[<?= $i ?>]" value="<?= (int)$chosen ?>">
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php if ($accWrong): ?>
        <div class="code-error" role="alert"><span class="alert-icon">⚠</span> Not quite. A ✓ means that part is right — rethink the rest and try again.</div>
        <?php endif; ?>
        <button type="submit" class="btn-primary code-submit acc-submit">⚖️ Submit Accusation</button>
        <?php if (!empty($prog['acc_attempts'])): ?>
        <div class="acc-attempts muted small"><?= (int)$prog['acc_attempts'] ?> attempt<?= (int)$prog['acc_attempts'] === 1 ? '' : 's' ?> so far</div>
        <?php endif; ?>
      </form>
    </div>
      <?php if ($accWrong): ?>
      <script>
      (function () {
        var panel = document.getElementById('accusation');
        if (!panel) return;
        // After a wrong accusation, keep the player on the accusation panel
        // instead of snapping back to the top of the page.
        function focusPanel() {
          panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
          var firstOpen = panel.querySelector('.acc-select:not([disabled])');
          if (firstOpen) { try { firstOpen.focus({ preventScroll: true }); } catch (e) { firstOpen.focus(); } }
        }
        if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', focusPanel);
        } else {
          focusPanel();
        }
      })();
      </script>
      <?php endif; ?>
    <?php else: ?>
    <div class="gate-card">
      <div class="gate-icon">🕵️</div>
      <h2>Accusation coming soon</h2>
      <p class="gate-sub">This mystery's accusation hasn't been set up yet.</p>
    </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <!-- ── WHODUNIT: pop-out detective's notepad ── -->
  <?php if ($type === 'whodunit' && $accusation): ?>
  <button type="button" class="wd-pad-toggle" id="wd-pad-toggle" onclick="wdTogglePad()" aria-controls="wd-pad" aria-expanded="false">
    <span class="wd-pad-toggle-icon">📋</span><span class="wd-pad-toggle-label">Notepad</span>
  </button>
  <aside class="wd-pad" id="wd-pad" aria-label="Detective's notepad">
    <div class="wd-pad-head">
      <h2>📋 Detective&rsquo;s Notepad</h2>
      <button type="button" class="wd-pad-close" onclick="wdTogglePad(false)" aria-label="Close notepad">✕</button>
    </div>
    <div class="wd-pad-body">
      <p class="wd-pad-hint">Tap a cell to cycle <span class="wd-leg wd-leg-no">✗ ruled out</span> <span class="wd-leg wd-leg-yes">● confirmed</span></p>
      <?php
        $cats = $accusation['categories'];
        $k    = count($cats);
        if ($k >= 2):
          // Classic logic-grid layout (triangular):
          //   columns = categories 1..k-1;  rows = category 0, then k-1 down to 2.
          $colCatIdx = range(1, $k - 1);
          $rowCatIdx = [0];
          for ($x = $k - 1; $x >= 2; $x--) { $rowCatIdx[] = $x; }
          $gridCols = [];
          foreach ($colCatIdx as $cp => $ci) {
              foreach ($cats[$ci]['options'] as $oi => $opt) {
                  $gridCols[] = ['ci' => $ci, 'cp' => $cp, 'oi' => $oi, 'name' => $opt['name'], 'first' => ($oi === 0)];
              }
          }
          $gridRows = [];
          foreach ($rowCatIdx as $rp => $ci) {
              foreach ($cats[$ci]['options'] as $oi => $opt) {
                  $gridRows[] = ['ci' => $ci, 'rp' => $rp, 'oi' => $oi, 'name' => $opt['name'], 'first' => ($oi === 0)];
              }
          }
      ?>
      <div class="wd-grid-wrap">
        <table class="wd-grid">
          <thead>
            <tr>
              <th class="wd-grid-corner"></th>
              <?php foreach ($gridCols as $col): ?>
              <th class="wd-grid-colh<?= $col['first'] ? ' cat-edge' : '' ?>"><span><?= e($col['name']) ?></span></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($gridRows as $row): ?>
            <tr>
              <th class="wd-grid-rowh<?= $row['first'] ? ' cat-edge' : '' ?>"><?= e($row['name']) ?></th>
              <?php foreach ($gridCols as $col):
                  $visible = ($row['rp'] + $col['cp']) <= ($k - 2);
                  $cls = 'wd-grid-cell' . ($col['first'] ? ' cat-edge' : '') . ($row['first'] ? ' row-edge' : '');
              ?>
                <?php if ($visible): ?>
                <td class="<?= $cls ?>"><button type="button" class="wd-gcell"
                    data-key="<?= $row['ci'] ?>-<?= $row['oi'] ?>_<?= $col['ci'] ?>-<?= $col['oi'] ?>"
                    aria-label="<?= e($row['name'] . ' with ' . $col['name']) ?>"></button></td>
                <?php else: ?>
                <td class="<?= $cls ?> wd-grid-blank"></td>
                <?php endif; ?>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: // single category — simple checklist fallback ?>
      <?php foreach ($cats as $ci => $cat): ?>
      <div class="wd-pad-cat">
        <h3><?= e($cat['label']) ?></h3>
        <?php foreach ($cat['options'] as $oi => $opt): ?>
        <div class="wd-pad-row">
          <span class="wd-pad-opt"><?= e($opt['name']) ?></span>
          <button type="button" class="wd-gcell" data-key="<?= $ci ?>-<?= $oi ?>_solo" aria-label="Mark <?= e($opt['name']) ?>"></button>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
      <button type="button" class="wd-pad-reset" onclick="wdResetPad()">Clear notepad</button>
    </div>
  </aside>
  <script>
  (function () {
    var slug = document.body.dataset.seq || '';
    var KEY  = 'seqgrid:' + slug;
    var MARKS = ['', '✗', '●'];
    var CLS   = ['', 'is-no', 'is-yes'];
    var data = {};
    try { data = JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (e) {}
    function persist() { try { localStorage.setItem(KEY, JSON.stringify(data)); } catch (e) {} }
    function paint(btn) {
      var s = data[btn.dataset.key] || 0;
      btn.textContent = MARKS[s];
      btn.className = 'wd-gcell ' + CLS[s];
    }
    var cells = document.querySelectorAll('#wd-pad .wd-gcell');
    cells.forEach(function (btn) {
      paint(btn);
      btn.addEventListener('click', function () {
        var k = btn.dataset.key;
        var s = ((data[k] || 0) + 1) % MARKS.length;
        if (s === 0) { delete data[k]; } else { data[k] = s; }
        persist(); paint(btn);
      });
    });
    window.wdTogglePad = function (force) {
      var pad = document.getElementById('wd-pad');
      var tgl = document.getElementById('wd-pad-toggle');
      var open = (typeof force === 'boolean') ? force : !pad.classList.contains('open');
      pad.classList.toggle('open', open);
      document.documentElement.classList.toggle('wd-pad-open', open);
      if (tgl) tgl.setAttribute('aria-expanded', open ? 'true' : 'false');
    };
    window.wdResetPad = function () {
      if (!confirm('Clear all notepad marks?')) return;
      data = {}; persist();
      cells.forEach(paint);
    };
  })();
  </script>
  <?php endif; ?>

  <!-- ── SOLUTION ── -->
  <?php if ($solution_shown):
    $hasThankYou = richHasContent($sequence['thank_you_content'] ?? null);
  ?>
  <?php $ixSolved = ($isInteractive && $voteOutcome === 'solved'); $ixFailed = ($isInteractive && $voteOutcome === 'failed'); ?>
  <div class="finale-card <?= $flashMsg === 'solution_unlocked' ? 'card-animate-in' : '' ?> <?= $ixFailed ? 'finale-card-failed' : '' ?>" id="solution-reveal" tabindex="-1">
    <div class="finale-glow"></div>
    <div class="card-label accent"><?= ($type === 'whodunit' || $ixSolved) ? 'Case Closed' : ($ixFailed ? 'Case Unsolved' : 'Solution') ?></div>
    <?php if ($isInteractive): ?>
      <?php
        $accDec = json_decode($sequence['accusation_json'] ?? '', true);
        $culprit = (is_array($accDec) && !empty($accDec['categories'])) ? ($accDec['categories'][0]['options'][(int)($accDec['categories'][0]['answer'] ?? 0)] ?? null) : null;
        $groupPick = ($voteResultIndex !== null && !empty($suspects[$voteResultIndex])) ? $suspects[$voteResultIndex] : null;
      ?>
      <?php if ($ixSolved): ?>
      <p class="wd-verdict-lead ix-result ix-result-win">✅ Correct! Your team solved the mystery.</p>
      <?php else: ?>
      <p class="wd-verdict-lead ix-result ix-result-lose">🚔 The culprit got away — your team didn&rsquo;t solve this one. Better luck next time!</p>
      <?php endif; ?>
      <?php if ($groupPick): ?>
      <p class="gate-sub">Your group accused <strong><?= e($groupPick['name'] ?? '') ?></strong>.</p>
      <?php endif; ?>
      <?php if ($culprit): ?>
      <div class="wd-verdict">
        <div class="wd-verdict-item">
          <?php if (!empty($culprit['image'])): ?>
          <div class="wd-verdict-img"><img src="<?= e(UPLOAD_URL . '/' . $culprit['image']) ?>" alt="<?= e($culprit['name']) ?>"></div>
          <?php else: ?>
          <div class="wd-verdict-img wd-verdict-noimg">🔍</div>
          <?php endif; ?>
          <div class="wd-verdict-cat">The Culprit</div>
          <div class="wd-verdict-name"><?= e($culprit['name']) ?></div>
        </div>
      </div>
      <?php endif; ?>
    <?php endif; ?>
    <?php if ($type === 'whodunit' && $accusation): ?>
    <p class="wd-verdict-lead">The case is solved. The culprit&rsquo;s file is closed:</p>
    <div class="wd-verdict">
      <?php foreach ($accusation['categories'] as $cat):
        $ans = $cat['options'][$cat['answer']] ?? null;
        if (!$ans) { continue; }
      ?>
      <div class="wd-verdict-item">
        <?php if (!empty($ans['image'])): ?>
        <div class="wd-verdict-img"><img src="<?= e(UPLOAD_URL . '/' . $ans['image']) ?>" alt="<?= e($ans['name']) ?>"></div>
        <?php else: ?>
        <div class="wd-verdict-img wd-verdict-noimg">🔍</div>
        <?php endif; ?>
        <div class="wd-verdict-cat"><?= e($cat['label']) ?></div>
        <div class="wd-verdict-name"><?= e($ans['name']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if (richHasContent($sequence['solution_content'] ?? null)): ?>
    <div class="rich-content"><?= $sequence['solution_content'] ?></div>
    <?php endif; ?>
    <?php if (!empty($sequence['solution_file_path'])): ?>
    <div class="media-block">
      <button type="button" class="btn-summary" onclick="openSolutionMedia()">📄 View Solution Details</button>
      <?php if (!empty($sequence['solution_caption'])): ?>
      <div class="media-caption"><?= e($sequence['solution_caption']) ?></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="finale-badge">🏆 Mystery Solved</div>
  </div>

  <?php if (!empty($sequence['solution_file_path'])): ?>
  <!-- Solution media pop-up -->
  <div id="sol-modal" class="sol-modal hidden" role="dialog" aria-modal="true" aria-label="Solution details">
    <div class="sol-box">
      <div class="sol-body">
        <?= renderMedia(
              (string)($sequence['solution_file_type'] ?? ''),
              UPLOAD_URL . '/' . $sequence['solution_file_path'],
              (string)($sequence['solution_original_filename'] ?? '')
            ) ?>
        <?php if (!empty($sequence['solution_caption'])): ?>
        <div class="media-caption" style="margin-top:1rem"><?= e($sequence['solution_caption']) ?></div>
        <?php endif; ?>
      </div>
      <div class="sol-foot">
        <button type="button" class="btn-primary code-submit" onclick="closeSolutionMedia()">Close</button>
      </div>
    </div>
  </div>
  <script>
  (function () {
    var modal = document.getElementById('sol-modal');
    if (!modal) return;
    var returnFocus = null;
    function onKey(e) {
      if (e.key === 'Escape') { window.closeSolutionMedia(); }
      else if (typeof trapFocus === 'function') { trapFocus(modal, e); }
    }
    window.openSolutionMedia = function () {
      returnFocus = document.activeElement;
      modal.classList.remove('hidden');
      document.documentElement.classList.add('ev-open');
      var b = modal.querySelector('.sol-foot button');
      if (b) b.focus();
      document.addEventListener('keydown', onKey);
    };
    window.closeSolutionMedia = function () {
      modal.classList.add('hidden');
      document.documentElement.classList.remove('ev-open');
      document.removeEventListener('keydown', onKey);
      modal.querySelectorAll('audio, video').forEach(function (m) { try { m.pause(); } catch (e) {} });
      if (returnFocus && typeof returnFocus.focus === 'function') { returnFocus.focus(); }
    };
    modal.addEventListener('click', function (e) { if (e.target === modal) window.closeSolutionMedia(); });
  })();
  </script>
  <?php endif; ?>
  <?php endif; ?>

  <?php endif; // end $started ?>

  <?php endif; // end gameboard / else branch ?>

  <?php if ($isJoiner && $groupCode !== ''): ?>
  <!-- Group joiner: live-sync with the host. Reload when the shared progress moves. -->
  <div class="group-badge" title="Playing in a group">🤝 Group <?= e($groupCode) ?></div>
  <script>
  (function () {
    var url = <?= json_encode(url('s/' . $slug . '/group/status') . '?code=' . urlencode($groupCode)) ?>;
    var cur = { unlocked: <?= (int)$unlocked ?>, ready: <?= $ready_to_solve ? 1 : 0 ?>, solved: <?= $solution_shown ? 1 : 0 ?>, hint: <?= json_encode($groupHint) ?> };
    var _pollInterval = setInterval(function () {
      fetch(url, { credentials: 'same-origin' }).then(function (r) { return r.json(); })
        .then(function (d) {
          if (!d || !d.ok) return;
          // Reload when the host moves the group on, or reveals/hides a hint.
          if (d.unlocked !== cur.unlocked || d.ready !== cur.ready || d.solved !== cur.solved || d.hint !== cur.hint) {
            clearInterval(_pollInterval);
            location.reload();
          }
        }).catch(function () {});
    }, 3500);
    window.addEventListener('pagehide', function () { clearInterval(_pollInterval); });
  })();
  </script>
  <?php endif; ?>

  <?php if ($isHost && $lobby === null && $groupCode !== ''): ?>
  <!-- Group host: broadcast hint open/close to the group so joiners see the same hint. -->
  <script>
  window.SEQ_GROUP_HINT = {
    url: <?= json_encode(url('s/' . $slug . '/group/hint')) ?>,
    csrf: <?= json_encode(Security::generateCsrfToken()) ?>
  };
  </script>
  <?php endif; ?>

  <?php if ($started && $solution_shown):
    $elapsed = max(0, (int)($progress['elapsed'] ?? 0));
    $timeStr = sprintf('%d:%02d', intdiv($elapsed, 60), $elapsed % 60);
  ?>
  <!-- Completion timer + shareable result card -->
  <div class="result-share">
    <?php $hasTY = richHasContent($sequence['thank_you_content'] ?? null); ?>
    <?php if ($hasTY): ?>
    <div id="thankyou-inline" class="rc-thankyou hidden">
      <div class="rich-content"><?= $sequence['thank_you_content'] ?></div>
      <div class="ty-qr">
        <div class="ty-qr-box" id="ty-qr-inline" aria-hidden="true"></div>
        <a class="ty-qr-cap" href="https://simplycreativegames.etsy.com" target="_blank" rel="noopener">Scan for more mysteries</a>
      </div>
    </div>
    <?php endif; ?>
    <!-- A PNG snapshot of the card is overlaid as a real <img> so a long-press on
         mobile shows the actual image with the native Save to Photos / Share menu.
         It is intentionally NOT wrapped in a link — a linked image makes iOS show
         the URL preview instead of the image, hiding "Save to Photos". -->
    <div class="rc-photo-wrap">
    <div class="result-card <?= $ixFailed ? 'result-card-failed' : '' ?>" id="result-card">
      <span class="rc-seal" aria-hidden="true"><?= $ixFailed ? '🗄️' : '🔍' ?></span>
      <div class="rc-badge"><?= $ixFailed ? 'CASE UNSOLVED' : 'CASE CLOSED' ?></div>
      <div class="rc-title"><?= e($sequence['title']) ?></div>
      <div class="rc-sub"><?php
        if ($ixFailed) { echo 'The culprit got away…'; }
        else { echo $isGroup ? 'We solved the case!' : 'I solved the case!'; }
      ?></div>
      <div class="rc-rule"></div>
      <div class="rc-time"><?php
        if ($ixFailed) { echo '&#9201; Played for <strong>' . $timeStr . '</strong>'; }
        else { echo '&#9201; Solved in <strong>' . $timeStr . '</strong>'; }
      ?></div>
      <?php if (!empty($prog['correct_accusation'])): ?>
      <div class="rc-accuse">🎯 Correct accusation</div>
      <?php endif; ?>
      <div class="rc-stats" id="rc-stats"></div>
      <div class="rc-issuer">&#10022; &#10022; &#10022;</div>
      <div class="rc-master"><?php
        if ($ixFailed) {
          echo 'Simply Creative Games<br><span class="rc-rank">BETTER LUCK NEXT TIME!</span>';
        } else {
          echo ($isGroup ? 'We&rsquo;re' : 'I&rsquo;m a') . ' Simply Creative Games<br><span class="rc-rank">MASTER DETECTIVE' . ($isGroup ? 'S' : '') . '!</span>';
        }
      ?></div>
    </div>
    <!-- PNG snapshot of the card, set after load (see script below). -->
    <img id="rc-photo" class="rc-photo" alt="" aria-hidden="true">
    </div>
    <div class="result-actions">
      <button type="button" class="btn-primary code-submit" id="rc-share">📷 Save/Share</button>
    </div>
    <!-- Promo sits OUTSIDE #result-card, so it's never captured in the saved/shared image -->
    <div class="rc-promo">
      <?php if (!empty($sequence['survey_link'])): ?>
      <p class="rc-survey">Enjoyed the mystery? <a class="rc-link" href="<?= e($sequence['survey_link']) ?>" target="_blank" rel="noopener noreferrer">Take our survey</a> and tell us about your experience.</p>
      <?php endif; ?>
      <a class="rc-link" href="https://simplycreativegames.etsy.com" target="_blank" rel="noopener">Solve more mysteries at Simply Creative Games.</a>
    </div>
  </div>

  <?php if ($hasTY): ?>
  <!-- Thank-you message: pops up a few seconds after solving, then settles in above the result card -->
  <div id="thankyou-modal" class="ty-modal hidden" role="dialog" aria-modal="true" aria-label="A message for you">
    <div class="ty-modal-box">
      <button type="button" class="ty-modal-close" onclick="closeThankYou()" aria-label="Close message">✕</button>
      <div class="rich-content"><?= $sequence['thank_you_content'] ?></div>
      <div class="ty-qr">
        <div class="ty-qr-box" id="ty-qr-modal" aria-hidden="true"></div>
        <a class="ty-qr-cap" href="https://simplycreativegames.etsy.com" target="_blank" rel="noopener">Scan for more mysteries</a>
      </div>
      <button type="button" class="btn-primary code-submit" onclick="closeThankYou()" style="margin-top:1.25rem">Close</button>
    </div>
  </div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>
  (function () {
    if (typeof QRCode !== 'undefined') {
      ['ty-qr-modal', 'ty-qr-inline'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el && !el.hasChildNodes()) {
          new QRCode(el, { text: 'https://simplycreativegames.etsy.com', width: 132, height: 132, colorDark: '#000000', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });
        }
      });
    }
  })();
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
      // Reveal the static thank-you under the buttons, animating it in.
      var inl = document.getElementById('thankyou-inline');
      if (inl) { inl.classList.remove('hidden'); inl.classList.add('rc-reveal'); }
      if (returnFocus && typeof returnFocus.focus === 'function') { returnFocus.focus(); }
    };
    modal.addEventListener('click', function (e) { if (e.target === modal) window.closeThankYou(); });
    setTimeout(showTY, 4000);
  })();
  </script>
  <?php endif; ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script>
  (function () {
    var card  = document.getElementById('result-card');
    var title = <?= json_encode((string)$sequence['title']) ?>;
    var timeStr = <?= json_encode($timeStr) ?>;
    var shopUrl = 'https://simplycreativegames.etsy.com';
    var shareBtn = document.getElementById('rc-share');

    // Stamp a "No Hints!" badge (or hint count) from the per-mystery hint tally.
    var stats = document.getElementById('rc-stats');
    if (stats && card && !card.classList.contains('result-card-failed')) {
      var hints = 0;
      try { hints = parseInt(localStorage.getItem('seqHints:' + (document.body.dataset.seq || '')) || '0', 10) || 0; } catch (e) {}
      stats.innerHTML = hints === 0
        ? '<span class="rc-nohints">🎖️ Solved with No Hints!</span>'
        : '<span class="rc-hintcount">💡 ' + hints + ' hint' + (hints === 1 ? '' : 's') + ' used</span>';
    }

    // Settle a promise within `ms`, otherwise reject — so a stalled font load or
    // html2canvas render can never leave the UI hanging.
    function withTimeout(p, ms, label) {
      return new Promise(function (resolve, reject) {
        var done = false;
        var t = setTimeout(function () { if (!done) { done = true; reject(new Error((label || 'task') + ' timed out')); } }, ms);
        p.then(
          function (v) { if (!done) { done = true; clearTimeout(t); resolve(v); } },
          function (e) { if (!done) { done = true; clearTimeout(t); reject(e); } }
        );
      });
    }

    // Render the card to a canvas once and reuse it (save, share, photo overlay).
    // html2canvas clones the WHOLE page first; on iOS that stalls forever if other
    // clue media / the QR canvas are still loading. So we ignore everything that
    // isn't the card, skip media tags, wait (briefly) for fonts, and cap the time.
    var _cardCanvas = null;
    function renderCardCanvas() {
      if (_cardCanvas) { return Promise.resolve(_cardCanvas); }
      if (typeof html2canvas !== 'function') {
        return Promise.reject(new Error('image tool unavailable'));
      }
      var fontsReady = (document.fonts && document.fonts.ready) ? document.fonts.ready : Promise.resolve();
      return withTimeout(fontsReady, 2500, 'fonts').catch(function () {}).then(function () {
        return withTimeout(html2canvas(card, {
          // Solid (not transparent) background — iOS can't make a share-sheet
          // thumbnail from a transparent image, and it fills the rounded corners.
          backgroundColor: '#1b1430', scale: 2, useCORS: true, logging: false,
          ignoreElements: function (el) {
            if (!el || el.nodeType !== 1) { return false; }
            // Never skip the card itself (it must keep all its styling).
            if (card && (el === card || card.contains(el))) { return false; }
            // Skip only heavy/independent media elsewhere on the page (clue images,
            // audio/video, the QR canvas) that can stall the clone on iOS. Keep all
            // <link>/<style>/structure so the card renders WITH its CSS.
            var tag = el.tagName;
            return tag === 'VIDEO' || tag === 'AUDIO' || tag === 'IFRAME' || tag === 'IMG' || tag === 'CANVAS';
          }
        }), 8000, 'image');
      }).then(function (canvas) { _cardCanvas = canvas; return canvas; });
    }

    function cardImageFile() {
      return renderCardCanvas().then(function (canvas) {
        return new Promise(function (resolve, reject) {
          // JPEG so iOS shows the actual image as the share-sheet preview thumbnail.
          canvas.toBlob(function (blob) {
            blob ? resolve(new File([blob], 'mystery-solved.jpg', { type: 'image/jpeg' }))
                 : reject(new Error('could not encode image'));
          }, 'image/jpeg', 0.95);
        });
      });
    }

    // Pre-rendered file so sharing can run synchronously inside the tap (iOS rule).
    var shareFile = null;
    function prepShareFile() {
      if (shareFile) { return Promise.resolve(shareFile); }
      return cardImageFile().then(function (f) { shareFile = f; return f; });
    }

    // Swap the live CSS card for a real PNG of it, so a long-press on mobile shows
    // the actual image with the native Save to Photos / Share / Copy menu. Styles
    // are set inline so this works even if the stylesheet is cached/stale.
    function prepPhoto() {
      var img = document.getElementById('rc-photo');
      if (!img) { return; }
      renderCardCanvas().then(function (canvas) {
        img.src = canvas.toDataURL('image/jpeg', 0.95);
        img.alt = <?= json_encode('Case closed: ' . (string)$sequence['title']) ?>;
        img.removeAttribute('aria-hidden');
        img.style.cssText = 'display:block;width:100%;max-width:460px;height:auto;margin:0 auto;border-radius:16px;-webkit-touch-callout:default;';
        img.classList.add('ready');
        // Hide the live card so there's a single, long-pressable image (no duplicate).
        if (card) { card.style.display = 'none'; }
      }).catch(function () {});
    }

    // Brag text shared alongside the card image.
    var shareText = <?= json_encode($isGroup ? 'We solved "' : 'I solved "') ?> + title + '" in ' + timeStr
      + <?= json_encode($isGroup ? "! 🔍 We're Simply Creative Games MASTER DETECTIVES!" : "! 🔍 I'm a Simply Creative Games MASTER DETECTIVE!") ?>;

    // Share/save the card image via the native sheet (which offers "Save Image" →
    // Photos as well as posting). Share the file ALONE — adding `text` makes iOS show
    // the text as the preview instead of the image (the image already has the brag).
    function shareWin(file) {
      if (navigator.canShare && navigator.canShare({ files: [file] })) {
        return navigator.share({ files: [file] });
      }
      var url = URL.createObjectURL(file);
      var a = document.createElement('a');
      a.href = url; a.download = file.name || 'mystery-solved.jpg';
      document.body.appendChild(a); a.click(); a.remove();
      setTimeout(function () { URL.revokeObjectURL(url); }, 1500);
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(shareText + ' ' + shopUrl).catch(function () {});
      }
      return Promise.resolve();
    }

    // Defer the html2canvas render until the user first interacts with the page.
    // Rendering immediately on load clones the entire DOM in memory, which can
    // trigger Safari's "significant memory" warning on media-heavy mystery pages.
    function prepAll() { prepPhoto(); prepShareFile().catch(function () {}); }
    var _prepDone = false;
    function schedulePrep() {
      if (_prepDone) return;
      _prepDone = true;
      ['pointerdown', 'scroll', 'keydown'].forEach(function (ev) {
        document.removeEventListener(ev, schedulePrep, true);
      });
      setTimeout(prepAll, 200);
    }
    ['pointerdown', 'scroll', 'keydown'].forEach(function (ev) {
      document.addEventListener(ev, schedulePrep, true);
    });
    // Hard deadline: render even if the user never interacts (e.g. auto-scroll).
    window.addEventListener('load', function () { setTimeout(schedulePrep, 20000); });

    function flashShareBtn(msg) {
      var orig = shareBtn.textContent;
      shareBtn.textContent = msg;
      setTimeout(function () { shareBtn.textContent = orig; }, 1600);
    }

    shareBtn.addEventListener('click', function () {
      // Fast path: image ready → share synchronously (keeps the iOS user gesture).
      // Share the file alone so the card image is the share-sheet preview.
      if (shareFile && navigator.canShare && navigator.canShare({ files: [shareFile] })) {
        navigator.share({ files: [shareFile] }).catch(function () {});
        return;
      }
      var orig = shareBtn.textContent;
      shareBtn.disabled = true; shareBtn.textContent = 'Preparing…';
      prepShareFile().then(function (file) {
        shareBtn.disabled = false; shareBtn.textContent = orig;
        shareWin(file).catch(function () {});
      }).catch(function () {
        shareBtn.disabled = false; shareBtn.textContent = orig;
        // Render failed/unsupported → share or copy the brag text + link instead.
        if (navigator.share) {
          navigator.share({ text: shareText, url: shopUrl }).catch(function () {});
        } else if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(shareText + ' ' + shopUrl).then(function () {
            flashShareBtn('✓ Copied to clipboard!');
          }).catch(function () { flashShareBtn('Copy this: ' + shopUrl); });
        } else {
          flashShareBtn('Visit ' + shopUrl);
        }
      });
    });
  })();
  </script>
  <?php endif; ?>

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

<script>
// On mobile, open puzzles in the SAME tab (so the puzzle page's "Back to Mystery"
// link returns to this sequence at the right clue). Desktop keeps the new tab.
(function () {
  if (!window.matchMedia || !window.matchMedia('(max-width: 680px)').matches) { return; }
  document.querySelectorAll('a[data-puzzle-link]').forEach(function (a) {
    a.removeAttribute('target');
    a.removeAttribute('rel');
    var hint = a.querySelector('.site-link-hint');
    if (hint) { hint.textContent = 'Tap to solve'; }
  });
})();
</script>

<?php if ($flashMsg === 'start_code_accepted'): ?>
<script>if (window.Sound) Sound.chime(); window.addEventListener('load', () => { showNotification('✅ Code accepted! The investigation begins…', 'success'); });</script>
<?php elseif ($flashMsg === 'clue_unlocked'): ?>
<script>if (window.Sound) Sound.chime(); window.addEventListener('load', () => { showNotification('🔓 Clue unlocked!', 'success'); });</script>
<?php elseif ($flashMsg === 'ready_to_solve'): ?>
<script>if (window.Sound) Sound.chime(); window.addEventListener('load', () => { showNotification('🧩 All clues found — ready to solve!', 'success'); document.querySelector('.finale-gate')?.scrollIntoView({behavior:'smooth',block:'start'}); });</script>
<?php elseif ($flashMsg === 'solution_unlocked'): ?>
<script>
if (window.Sound) Sound.fanfare();
window.addEventListener('load', function () {
  showNotification('🎉 Solved! Congratulations!', 'success');
  var el = document.getElementById('solution-reveal');
  if (!el) return;
  function aim() {
    var y = 0, n = el;
    while (n) { y += n.offsetTop; n = n.offsetParent; }
    window.scrollTo({ top: Math.max(0, y - 12), behavior: 'smooth' });
  }
  aim();
  try { el.focus({ preventScroll: true }); } catch (e) {}
  // Mobile settles late — the result card's image/QR render and the address-bar
  // collapse shift layout after load, so re-aim a couple of times.
  setTimeout(aim, 300);
  setTimeout(aim, 800);
});
</script>
<?php endif; ?>
<?php if ($flashType === 'error'): ?>
<script>if (window.Sound) Sound.buzz();</script>
<?php endif; ?>

<?php if ($started && !$solution_shown && $flashMsg !== 'opened_case'): ?>
<!-- Post-load focus: a freshly-revealed clue wins; on the start screen the
     introduction wins; otherwise the next code box. Driven from the page (not
     from cached app.js) so it always takes effect. Skipped when arriving from the
     landing page (opened_case) so the mystery loads at the very top. -->
<script>
window.addEventListener('load', function () {
  function toTop(el) {
    if (!el) return false;
    var y = 0, n = el;
    while (n) { y += n.offsetTop; n = n.offsetParent; }
    window.scrollTo({ top: Math.max(0, y - 12), behavior: 'smooth' });
    return true;
  }
  // Arriving from a puzzle's "Back to Puzzle" link (#clue-N) → focus that clue.
  var hm = (location.hash || '').match(/^#clue-(\d+)$/);
  if (hm && toTop(document.getElementById('clue-' + hm[1]))) return;
  if (toTop(document.querySelector('.clue-fresh'))) return;
  <?php if ($flashMsg === 'start_code_accepted'): ?>
  if (toTop(document.getElementById('intro-card'))) return;
  <?php endif; ?>
  var gate = document.getElementById('next-gate');
  if (gate) { setTimeout(function () { gate.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 150); }
});
</script>
<?php endif; ?>

<?php if ($started && !$solution_shown && $lobby === null): ?>
<!-- Live elapsed timer -->
<script>
(function () {
  var el = document.getElementById('seq-timer');
  if (!el) return;
  var base = <?= max(0, time() - (int)($prog['start_time'] ?? time())) ?>;
  var t0 = Date.now();
  function fmt(s) { return Math.floor(s / 60) + ':' + ('0' + (s % 60)).slice(-2); }
  function tick() { el.textContent = '⏱ ' + fmt(base + Math.floor((Date.now() - t0) / 1000)); }
  tick();
  var _timerInterval = setInterval(tick, 1000);
  window.addEventListener('pagehide', function () { clearInterval(_timerInterval); });
})();
</script>
<?php endif; ?>

<?php if (!empty($evidence)): ?>
<!-- Evidence Locker -->
<div id="evidence-modal" class="ev-modal hidden" role="dialog" aria-modal="true" aria-label="Evidence locker">
  <div class="ev-box">
    <div class="ev-head">
      <h2>🗂️ Evidence Locker</h2>
      <button type="button" class="ev-close" onclick="closeEvidence()" aria-label="Close">✕</button>
    </div>
    <div class="ev-body">
      <?php foreach ($evidence as $item): ?>
      <?= $item ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<script>
(function () {
  var modal = document.getElementById('evidence-modal');
  if (!modal) return;
  var returnFocus = null;
  function onKey(e) {
    if (e.key === 'Escape') { window.closeEvidence(); }
    else if (typeof trapFocus === 'function') { trapFocus(modal, e); }
  }
  window.openEvidence = function () {
    returnFocus = document.activeElement;
    modal.classList.remove('hidden');
    document.documentElement.classList.add('ev-open');
    var c = modal.querySelector('.ev-close');
    if (c) c.focus();
    document.addEventListener('keydown', onKey);
  };
  window.closeEvidence = function () {
    modal.classList.add('hidden');
    document.documentElement.classList.remove('ev-open');
    document.removeEventListener('keydown', onKey);
    if (returnFocus && typeof returnFocus.focus === 'function') returnFocus.focus();
  };
  modal.addEventListener('click', function (e) { if (e.target === modal) window.closeEvidence(); });
})();
</script>
<?php endif; ?>

<!-- How to Play (pops up like the Evidence Locker) -->
<?php
$howto = $howto ?? '';
$howtoHas = trim(strip_tags(str_replace('&nbsp;', ' ', $howto))) !== ''
    || preg_match('/<(img|audio|video|iframe|svg|figure|table)/i', $howto);
?>
<div id="howto-modal" class="ev-modal hidden" role="dialog" aria-modal="true" aria-label="How to play">
  <div class="ev-box">
    <div class="ev-head">
      <h2>🗂️ How to Play</h2>
      <button type="button" class="ev-close" onclick="closeHowTo()" aria-label="Close">✕</button>
    </div>
    <div class="ev-body">
      <div class="ev-card">
        <div class="ev-card-title">Field Guide</div>
        <div class="ev-card-body">
          <?php if ($howtoHas): ?>
          <div class="rich-content"><?= $howto ?></div>
          <?php else: ?>
          <p style="font-style:italic">The how-to-play guide hasn&rsquo;t been written yet.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  var modal = document.getElementById('howto-modal');
  if (!modal) return;
  var returnFocus = null;
  function onKey(e) {
    if (e.key === 'Escape') { window.closeHowTo(); }
    else if (typeof trapFocus === 'function') { trapFocus(modal, e); }
  }
  window.openHowTo = function () {
    returnFocus = document.activeElement;
    modal.classList.remove('hidden');
    document.documentElement.classList.add('ev-open');
    var c = modal.querySelector('.ev-close');
    if (c) c.focus();
    document.addEventListener('keydown', onKey);
  };
  window.closeHowTo = function () {
    modal.classList.add('hidden');
    document.documentElement.classList.remove('ev-open');
    document.removeEventListener('keydown', onKey);
    if (returnFocus && typeof returnFocus.focus === 'function') returnFocus.focus();
  };
  modal.addEventListener('click', function (e) { if (e.target === modal) window.closeHowTo(); });
})();
</script>

<?php if ($started && !$isJoiner): ?>
<style>
.reset-actions { display:flex; gap:.75rem; flex-wrap:wrap; margin-top:1.25rem; }
.btn-keep { background:#15803d; color:#f0fdf4; border:1px solid #166534; border-radius:8px; padding:.7rem 1.3rem; font-weight:700; letter-spacing:.02em; cursor:pointer; font-family:inherit; box-shadow:0 4px 12px rgba(0,0,0,.4); }
.btn-keep:hover { filter:brightness(1.1); }
.btn-restart-confirm { background:#7f1d1d; color:#fde8e8; border:1px solid #b91c1c; border-radius:8px; padding:.7rem 1.3rem; font-weight:700; letter-spacing:.02em; cursor:pointer; font-family:inherit; box-shadow:0 4px 12px rgba(0,0,0,.4); }
.btn-restart-confirm:hover { filter:brightness(1.1); }
</style>
<div id="reset-modal" class="ev-modal hidden" role="dialog" aria-modal="true" aria-label="Restart investigation">
  <div class="ev-box">
    <div class="ev-head">
      <h2>🗂️ Restart Investigation?</h2>
      <button type="button" class="ev-close" onclick="closeResetConfirm()" aria-label="Close">✕</button>
    </div>
    <div class="ev-body">
      <div class="ev-card">
        <div class="ev-tag"><span class="ev-tag-no">&#9888;</span><span class="ev-tag-text">CASE FILE</span></div>
        <div class="ev-card-title">Are you sure you want to restart the investigation?</div>
        <div class="ev-card-body">
          <p>All of your progress will be wiped and you&rsquo;ll begin the case from the very start.</p>
          <div class="reset-actions">
            <button type="button" class="btn-keep" onclick="closeResetConfirm()">Keep investigating</button>
            <button type="button" class="btn-restart-confirm" onclick="confirmReset()">↺ Restart Investigation</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  var modal = document.getElementById('reset-modal');
  if (!modal) return;
  var returnFocus = null;
  function onKey(e) {
    if (e.key === 'Escape') { window.closeResetConfirm(); }
    else if (typeof trapFocus === 'function') { trapFocus(modal, e); }
  }
  window.openResetConfirm = function () {
    returnFocus = document.activeElement;
    modal.classList.remove('hidden');
    document.documentElement.classList.add('ev-open');
    var b = modal.querySelector('.btn-restart-confirm');
    if (b) b.focus();
    document.addEventListener('keydown', onKey);
  };
  window.closeResetConfirm = function () {
    modal.classList.add('hidden');
    document.documentElement.classList.remove('ev-open');
    document.removeEventListener('keydown', onKey);
    if (returnFocus && typeof returnFocus.focus === 'function') returnFocus.focus();
  };
  window.confirmReset = function () {
    var f = document.getElementById('reset-form');
    if (f) { f.submit(); }
  };
  modal.addEventListener('click', function (e) { if (e.target === modal) window.closeResetConfirm(); });
})();
</script>
<?php endif; ?>

</body>
</html>

<?php

function renderClueCard(array $clue, int $num, bool $unlocked, bool $fresh, bool $collapsible = false, bool $defaultOpen = true, bool $showHint = false): string
{
    $title = $clue['title'] ? e($clue['title']) : 'Clue ' . $num;
    $anim  = $fresh ? 'card-animate-in clue-fresh' : '';
    // Stable per-clue anchor so the puzzle page's "Back to Puzzle" link can focus
    // the exact clue (#clue-N) it was opened from.
    $tag   = $collapsible ? 'details' : 'div';
    $cls   = 'content-card clue-card ' . ($collapsible ? 'clue-collapsible ' : '') . $anim;
    $openAttr = ($collapsible && $defaultOpen) ? ' open' : '';
    $html  = "<$tag class=\"" . trim($cls) . "\" id=\"clue-$num\"" . $openAttr . '>';
    $header = '<div class="card-accent-bar"></div>'
            . "<div class=\"card-label\">Clue $num</div>"
            . "<h2 class=\"clue-title\">$title</h2>";
    if ($collapsible) {
        $html .= '<summary class="clue-collapse-summary">' . $header
              .  '<span class="clue-collapse-hint">Tap to reveal</span>'
              .  '<span class="clue-chevron" aria-hidden="true">▾</span></summary>';
    } else {
        $html .= $header;
    }

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
            'inbox'      => 'View Email',
            'sms'        => 'View Texts',
            'invoice'    => 'View Invoice',
            'receipt'    => 'View Receipt',
            'map'        => 'View Map',
            'calendar'   => 'View Calendar',
            'access_log' => 'View Log',
            default      => 'View Site',
        };
        $linkIcon  = match ($pageType) {
            'inbox'      => '&#9993;',
            'sms'        => '&#128172;',
            'invoice'    => '&#129534;',
            'receipt'    => '&#129534;',
            'map'        => '&#128506;',
            'calendar'   => '&#128197;',
            'access_log' => '&#128221;',
            default      => '&#127760;',
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
               . '<a href="' . $puzzleUrl . '" target="_blank" class="site-link-card" data-puzzle-link="1">'
               . '<span class="site-link-icon">&#129513;</span>'
               . '<span class="site-link-text"><strong>Solve Puzzle</strong><span class="site-link-hint">Opens in a new tab</span></span>'
               . '<span class="site-link-arrow">&#8599;</span>'
               . '</a></div>';
    }

    if ($showHint) {
        $html .= renderHint(
            $clue['hint_text'] ?? null,
            $clue['hint_file_path'] ?? null,
            $clue['hint_file_type'] ?? null,
            $clue['hint_original_filename'] ?? null,
            'clue:' . $num
        );
    }

    $html .= "</$tag>";
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
function renderHint(?string $html, ?string $filePath, ?string $fileType, ?string $fileName, string $token = '', bool $open = false): string
{
    static $hintSeq = 0;
    $hasText = richHasContent($html);
    $hasFile = !empty($filePath);
    if (!$hasText && !$hasFile) {
        return '';
    }
    $hintSeq++;
    $hintId = 'hint-content-' . $hintSeq;
    // $token (e.g. 'clue:2', 'finale') lets the group host broadcast this hint to
    // everyone; $open renders it already-expanded (used when a joiner is shown the
    // hint the host just revealed).
    $tokenAttr = $token !== '' ? ' data-hint-token="' . htmlspecialchars($token, ENT_QUOTES) . '"' : '';
    $out  = '<div class="hint-toggle">';
    $out .= '<button type="button" class="btn-hint" onclick="toggleHint(this)"'
          . $tokenAttr
          . ' aria-expanded="' . ($open ? 'true' : 'false') . '" aria-controls="' . $hintId . '">'
          . '&#128161; ' . ($open ? 'Hide hint' : 'Need a hint?') . '</button>';
    $out .= '<div class="hint-content' . ($open ? '' : ' hidden') . '" id="' . $hintId . '">';
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
