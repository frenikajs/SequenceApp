<?php
$pageTitle = 'Overview: ' . ($sequence['title'] ?? '');
$activeNav = 'sequences';
$seqId     = (int)$sequence['id'];
ob_start();

$puzzleLabels = [
    'order' => 'Order', 'caesar' => 'Caesar Cipher', 'phone' => 'Phone Keypad',
    'access' => 'Access Code', 'elim' => 'Elimination', 'wordsearch' => 'Word Search',
    'match' => 'Matching', 'hotspot' => 'Hotspot',
];
$pageLabels = [
    'news' => 'News', 'inbox' => 'Email', 'sms' => 'Texts', 'invoice' => 'Invoice',
    'receipt' => 'Receipt', 'map' => 'Map', 'calendar' => 'Calendar', 'blog' => 'Blog',
    'corporate' => 'Corporate', 'archive' => 'Archive',
];
$dash = '<span class="ov-no">&mdash;</span>';
?>

<div class="breadcrumb">
  <a href="<?= url('admin/sequences') ?>">Sequences</a> /
  <a href="<?= url('admin/sequences/' . $seqId . '/edit') ?>"><?= e($sequence['title']) ?></a> /
  Overview
</div>

<div class="ov-head">
  <h1 class="ov-title"><?= e($sequence['title']) ?></h1>
  <div class="ov-meta">
    <span class="badge <?= $sequence['published'] ? 'badge-green' : 'badge-gray' ?>"><?= $sequence['published'] ? 'Published' : 'Draft' ?></span>
    <span class="badge badge-blue"><?= e(ucfirst($sequence['type'])) ?></span>
    <span class="muted"><?= count($clues) ?> clue<?= count($clues) === 1 ? '' : 's' ?></span>
  </div>
  <div class="ov-actions">
    <a href="<?= url('admin/sequences/' . $seqId . '/clues') ?>" class="btn btn-secondary btn-sm">Manage Clues</a>
    <a href="<?= url('admin/sequences/' . $seqId . '/edit') ?>" class="btn btn-ghost btn-sm">Edit Sequence</a>
    <a href="<?= url('s/' . $sequence['slug']) ?>" target="_blank" class="btn btn-ghost btn-sm">🛠 Play-test ↗</a>
  </div>
</div>

<!-- Flow -->
<div class="ov-flow">

  <!-- Start -->
  <div class="ov-stage ov-stage-start">
    <div class="ov-stage-icon">🔑</div>
    <div class="ov-stage-body">
      <div class="ov-stage-name">Start</div>
      <div class="ov-chips">
        <span class="ov-chip">Start code: <code><?= e($sequence['start_code'] ?: '(none)') ?></code></span>
        <?php if (trim((string)($sequence['intro_access_code'] ?? '')) !== ''): ?>
        <span class="ov-chip">Intro code: <code><?= e($sequence['intro_access_code']) ?></code></span>
        <?php endif; ?>
        <?php if (richHasContent($sequence['introduction_content'] ?? null) || !empty($sequence['intro_file_path'])): ?>
        <span class="ov-chip ov-yes">Intro content ✓</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Clues -->
  <?php foreach ($clues as $i => $clue):
    $cid     = (int)$clue['id'];
    $pz      = $puzzles[$cid] ?? null;
    $pg      = $pages[$cid] ?? null;
    $hasHint = richHasContent($clue['hint_text'] ?? null) || !empty($clue['hint_file_path']);
    $hasMedia = !empty($clue['file_path']);
    $hasContent = richHasContent($clue['content'] ?? null);
    $hasReward = richHasContent($clue['reward_content'] ?? null);
    $isGameboard = ($sequence['type'] ?? '') === 'gameboard';
  ?>
  <div class="ov-stage">
    <div class="ov-stage-icon"><?= $i + 1 ?></div>
    <div class="ov-stage-body">
      <div class="ov-stage-name"><?= e($clue['title'] ?: 'Clue ' . ($i + 1)) ?></div>
      <div class="ov-chips">
        <span class="ov-chip">Code: <code><?= e($clue['access_code'] ?: '(none)') ?></code></span>
        <?= $hasContent ? '<span class="ov-chip ov-yes">Content ✓</span>' : '' ?>
        <?php if ($isGameboard): ?>
          <?= $hasReward ? '<span class="ov-chip ov-yes">Reward ✓</span>' : '<span class="ov-chip ov-warn">No reward</span>' ?>
        <?php endif; ?>
        <?= $hasHint ? '<span class="ov-chip ov-yes">Hint ✓</span>' : '' ?>
        <?= $hasMedia ? '<span class="ov-chip ov-yes">Media ✓</span>' : '' ?>
        <?php if ($pz): ?>
        <a class="ov-chip ov-link" href="<?= url('admin/clues/' . $cid . '/puzzle') ?>">🧩 <?= e($puzzleLabels[$pz['puzzle_type']] ?? ucfirst($pz['puzzle_type'])) ?></a>
        <?php endif; ?>
        <?php if ($pg): ?>
        <a class="ov-chip ov-link" href="<?= url('admin/clues/' . $cid . '/page') ?>">🌐 <?= e($pageLabels[$pg['site_type']] ?? ucfirst($pg['site_type'])) ?></a>
        <?php endif; ?>
      </div>
    </div>
    <div class="ov-stage-act">
      <a href="<?= url('admin/sequences/' . $seqId . '/clues') ?>#clue-<?= $cid ?>" class="btn btn-ghost btn-xs">Edit</a>
      <a href="<?= url('admin/clues/' . $cid . '/puzzle') ?>" class="btn btn-ghost btn-xs"><?= $pz ? 'Puzzle' : '+ Puzzle' ?></a>
      <a href="<?= url('admin/clues/' . $cid . '/page') ?>" class="btn btn-ghost btn-xs"><?= $pg ? 'Page' : '+ Page' ?></a>
    </div>
  </div>
  <?php endforeach; ?>

  <?php if (empty($clues)): ?>
  <div class="ov-empty">No clues yet. <a href="<?= url('admin/sequences/' . $seqId . '/clues') ?>">Add the first one →</a></div>
  <?php endif; ?>

  <!-- Finale -->
  <div class="ov-stage ov-stage-finale">
    <div class="ov-stage-icon">🏁</div>
    <div class="ov-stage-body">
      <div class="ov-stage-name">Finale &amp; Solution</div>
      <div class="ov-chips">
        <?php if (!empty($sequence['finale_requires_code'])): ?>
        <span class="ov-chip">Solution code: <code><?= e($sequence['finale_code'] ?: '(none set)') ?></code></span>
        <?php else: ?>
        <span class="ov-chip">No code required</span>
        <?php endif; ?>
        <?= richHasContent($sequence['solution_content'] ?? null) ? '<span class="ov-chip ov-yes">Solution ✓</span>' : '<span class="ov-chip ov-warn">No solution</span>' ?>
        <?= !empty($sequence['solution_file_path']) ? '<span class="ov-chip ov-yes">Summary PDF ✓</span>' : '' ?>
        <?= richHasContent($sequence['thank_you_content'] ?? null) ? '<span class="ov-chip ov-yes">Thank-you ✓</span>' : '' ?>
      </div>
    </div>
  </div>
</div>

<style>
.ov-head{display:flex;align-items:center;flex-wrap:wrap;gap:.6rem 1rem;margin-bottom:1.5rem}
.ov-title{font-size:1.4rem;font-weight:800;margin-right:.4rem}
.ov-meta{display:flex;align-items:center;gap:.5rem}
.ov-actions{display:flex;gap:.5rem;flex-wrap:wrap;margin-left:auto}
.ov-flow{display:flex;flex-direction:column;gap:.6rem;position:relative}
.ov-stage{display:flex;align-items:flex-start;gap:1rem;background:#161b27;border:1px solid #2a3242;border-radius:12px;padding:.9rem 1.1rem}
.ov-stage-start{border-left:4px solid #4cc9a0}
.ov-stage-finale{border-left:4px solid #fbbf24}
.ov-stage-icon{flex:none;width:34px;height:34px;border-radius:50%;background:#6c63ff;color:#fff;font-weight:800;font-size:.95rem;display:flex;align-items:center;justify-content:center}
.ov-stage-start .ov-stage-icon,.ov-stage-finale .ov-stage-icon{background:transparent;font-size:1.3rem}
.ov-stage-body{flex:1;min-width:0}
.ov-stage-name{font-weight:700;color:#f3f4f6;margin-bottom:.45rem}
.ov-chips{display:flex;flex-wrap:wrap;gap:.4rem}
.ov-chip{font-size:.76rem;color:#cbd2dd;background:#1f2937;border:1px solid #374151;border-radius:20px;padding:.22rem .65rem;white-space:nowrap;text-decoration:none}
.ov-chip code{font-family:'Courier New',monospace;color:#ffe9a8}
.ov-chip.ov-yes{background:rgba(76,201,160,.14);border-color:rgba(76,201,160,.4);color:#86e7c1}
.ov-chip.ov-warn{background:rgba(251,191,36,.14);border-color:rgba(251,191,36,.4);color:#fbbf24}
.ov-chip.ov-link{background:rgba(108,99,255,.16);border-color:rgba(108,99,255,.4);color:#c4b5fd}
.ov-chip.ov-link:hover{filter:brightness(1.15)}
.ov-no{color:#6b7280}
.ov-stage-act{flex:none;display:flex;flex-direction:column;gap:.35rem;align-items:flex-end}
.ov-empty{padding:1.5rem;text-align:center;color:#9ca3af;background:#161b27;border:1px dashed #374151;border-radius:12px}
@media(max-width:640px){.ov-stage{flex-wrap:wrap}.ov-stage-act{flex-direction:row;width:100%;justify-content:flex-start;margin-top:.4rem}}
</style>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
