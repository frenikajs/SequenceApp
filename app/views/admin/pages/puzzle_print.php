<?php
/**
 * Printer-friendly player sheet for a puzzle (no answers). Admin-only.
 * Vars: $clue, $puzzle, $slug, $type, $data, $title, $prompt, $clues, $items,
 *       $cipher, $phoneClue, $accessLen, $elimHeading, $wsGrid, $wsSize,
 *       $wsList, $matchLeft, $matchRight, $hsImage, $hsCount
 */
$backUrl = url('admin/clues/' . (int)$clue['id'] . '/puzzle');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Print — <?= e($title) ?></title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',system-ui,Segoe UI,Arial,sans-serif;background:#e9e6df;color:#1a1a1a;padding:1.5rem;line-height:1.55}
.pp-toolbar{display:flex;gap:1rem;align-items:center;flex-wrap:wrap;max-width:760px;margin:0 auto 1.25rem}
.pp-toolbar a,.pp-toolbar button{font:inherit;font-size:.9rem;font-weight:600;padding:.55rem 1rem;border-radius:8px;border:1px solid #c3bdb0;background:#fff;color:#1a1a1a;text-decoration:none;cursor:pointer}
.pp-toolbar button{background:#6c63ff;color:#fff;border-color:#6c63ff}
.sheet{max-width:760px;margin:0 auto;background:#fff;border:1px solid #ccc;border-radius:6px;padding:2.5rem;box-shadow:0 10px 30px rgba(0,0,0,.2)}
.pp-title{font-size:1.8rem;font-weight:800;letter-spacing:-.01em;margin-bottom:.35rem}
.pp-prompt{color:#444;font-size:1rem;margin-bottom:1.5rem}
.pp-h{font-size:.8rem;text-transform:uppercase;letter-spacing:.12em;color:#666;font-weight:700;margin:1.5rem 0 .6rem}
.pp-clues{padding-left:1.3rem}
.pp-clues li{margin-bottom:.4rem}
.pp-items{list-style:none;padding:0}
.pp-items li{padding:.5rem 0;border-bottom:1px solid #e3e3e3;font-size:1.05rem}
.pp-blank{display:inline-block;width:2rem;border-bottom:1.5px solid #888;margin-right:.7rem;text-align:center;color:#888}
.pp-check{display:inline-block;width:1.1rem;height:1.1rem;border:1.5px solid #888;border-radius:3px;margin-right:.7rem;vertical-align:-2px}
.pp-cipher{font-family:'Courier New',monospace;font-size:1.4rem;letter-spacing:.16em;word-break:break-word;background:#f4f4f4;border:1px solid #ddd;border-radius:8px;padding:1rem 1.25rem;line-height:1.7;margin:.4rem 0}
.pp-answer{margin-top:1.3rem}
.pp-answer .line{display:block;border-bottom:1.5px solid #999;height:1.6rem;margin-top:.3rem}
.pp-boxes{display:flex;gap:.5rem;flex-wrap:wrap;margin:.5rem 0}
.pp-box{width:42px;height:52px;border:2px solid #555;border-radius:6px}
.pp-keypad{display:grid;grid-template-columns:repeat(3,auto);gap:.3rem .9rem;font-size:.85rem;color:#444;margin:.5rem 0}
.pp-grid{display:inline-grid;gap:0;border:2px solid #333;margin:.5rem 0}
.pp-grid .r{display:flex}
.pp-cell{width:30px;height:30px;display:flex;align-items:center;justify-content:center;font-family:'Courier New',monospace;font-weight:700;font-size:1.05rem;border:1px solid #ccc}
.pp-words{list-style:none;padding:0;display:flex;flex-wrap:wrap;gap:.4rem .9rem}
.pp-words li{font-family:'Courier New',monospace;text-transform:uppercase;letter-spacing:.05em;font-weight:600}
.pp-match{display:grid;grid-template-columns:1fr 1fr;gap:.5rem 3rem;margin-top:.4rem}
.pp-match .col{display:flex;flex-direction:column;gap:.55rem}
.pp-mitem{display:flex;align-items:center;gap:.6rem;border:1px solid #bbb;border-radius:8px;padding:.55rem .8rem}
.pp-col-r .pp-mitem{flex-direction:row-reverse;text-align:right}
.pp-dot{width:11px;height:11px;border-radius:50%;border:2px solid #555;flex-shrink:0}
.pp-img{max-width:100%;border:1px solid #ccc;border-radius:6px;display:block;margin:.5rem 0}
.pp-note{font-size:.85rem;color:#666;font-style:italic;margin-top:.4rem}
.pp-foot{text-align:center;color:#999;font-size:.72rem;margin-top:2rem;letter-spacing:.06em}
@media print{
  @page{ size: letter portrait; margin: 0.6in; }
  html,body{background:#fff;padding:0;margin:0}
  .no-print{display:none!important}
  .sheet{box-shadow:none;border:none;border-radius:0;max-width:100%;padding:0}
  .pp-cell{border-color:#999}
}
</style>
</head>
<body>
<div class="pp-toolbar no-print">
  <a href="<?= e($backUrl) ?>">&larr; Back</a>
  <button type="button" onclick="window.print()">🖨 Print / Save PDF</button>
</div>

<div class="sheet">
  <h1 class="pp-title"><?= e($title) ?></h1>
  <?php if ($prompt !== ''): ?><p class="pp-prompt"><?= e($prompt) ?></p><?php endif; ?>

  <?php if ($type === 'order'): ?>
    <?php if (!empty($clues)): ?>
    <div class="pp-h">Clues</div>
    <ol class="pp-clues"><?php foreach ($clues as $c): ?><li><?= e($c) ?></li><?php endforeach; ?></ol>
    <?php endif; ?>
    <div class="pp-h">Put these in the correct order</div>
    <ul class="pp-items"><?php foreach ($items as $it): ?><li><span class="pp-blank">&nbsp;</span><?= e($it) ?></li><?php endforeach; ?></ul>

  <?php elseif ($type === 'elim'): ?>
    <?php if (!empty($clues)): ?>
    <div class="pp-h">Clues</div>
    <ol class="pp-clues"><?php foreach ($clues as $c): ?><li><?= e($c) ?></li><?php endforeach; ?></ol>
    <?php endif; ?>
    <div class="pp-h"><?= e($elimHeading) ?></div>
    <ul class="pp-items"><?php foreach ($items as $it): ?><li><span class="pp-check"></span><?= e($it) ?></li><?php endforeach; ?></ul>

  <?php elseif ($type === 'caesar'): ?>
    <div class="pp-h">Decode this message</div>
    <div class="pp-cipher"><?= e($cipher) ?></div>
    <div class="pp-answer"><div class="pp-h">Your answer</div><span class="line"></span></div>

  <?php elseif ($type === 'phone'): ?>
    <div class="pp-h">Decode using a phone keypad</div>
    <div class="pp-cipher" style="text-align:center"><?= e($phoneClue) ?></div>
    <div class="pp-keypad">
      <span>2 = ABC</span><span>3 = DEF</span><span>4 = GHI</span>
      <span>5 = JKL</span><span>6 = MNO</span><span>7 = PQRS</span>
      <span>8 = TUV</span><span>9 = WXYZ</span><span>0 = space</span>
    </div>
    <div class="pp-answer"><div class="pp-h">Your answer</div><span class="line"></span></div>

  <?php elseif ($type === 'access'): ?>
    <?php if (!empty($clues)): ?>
    <div class="pp-h">Clues</div>
    <ol class="pp-clues"><?php foreach ($clues as $c): ?><li><?= e($c) ?></li><?php endforeach; ?></ol>
    <?php endif; ?>
    <div class="pp-h">Access code</div>
    <div class="pp-boxes"><?php for ($i = 0; $i < max(1, (int)$accessLen); $i++): ?><span class="pp-box"></span><?php endfor; ?></div>

  <?php elseif ($type === 'wordsearch'): ?>
    <div class="pp-h">Find these words</div>
    <ul class="pp-words"><?php foreach ($wsList as $w): ?><li><?= e($w) ?></li><?php endforeach; ?></ul>
    <div class="pp-h">Word search</div>
    <div class="pp-grid">
      <?php foreach ($wsGrid as $row): ?>
      <div class="r"><?php foreach ($row as $ch): ?><span class="pp-cell"><?= e((string)$ch) ?></span><?php endforeach; ?></div>
      <?php endforeach; ?>
    </div>
    <div class="pp-answer"><div class="pp-h">Hidden phrase (leftover letters)</div><span class="line"></span></div>

  <?php elseif ($type === 'match'): ?>
    <?php if (!empty($clues)): ?>
    <div class="pp-h">Clues</div>
    <ol class="pp-clues"><?php foreach ($clues as $c): ?><li><?= e($c) ?></li><?php endforeach; ?></ol>
    <?php endif; ?>
    <div class="pp-h">Connect each item on the left to its match on the right</div>
    <div class="pp-match">
      <div class="col pp-col-l">
        <?php foreach ($matchLeft as $l): ?>
        <div class="pp-mitem"><span><?= e($l) ?></span><span class="pp-dot"></span></div>
        <?php endforeach; ?>
      </div>
      <div class="col pp-col-r">
        <?php foreach ($matchRight as $rr): ?>
        <div class="pp-mitem"><span><?= e($rr['v']) ?></span><span class="pp-dot"></span></div>
        <?php endforeach; ?>
      </div>
    </div>

  <?php elseif ($type === 'hotspot'): ?>
    <?php if (!empty($clues)): ?>
    <div class="pp-h">Clues</div>
    <ol class="pp-clues"><?php foreach ($clues as $c): ?><li><?= e($c) ?></li><?php endforeach; ?></ol>
    <?php endif; ?>
    <div class="pp-h">Mark the <?= (int)$hsCount ?> spot<?= $hsCount === 1 ? '' : 's' ?> on the image</div>
    <?php if ($hsImage !== ''): ?>
    <img class="pp-img" src="<?= e(UPLOAD_URL . '/' . $hsImage) ?>" alt="">
    <?php else: ?>
    <p class="pp-note">No image attached to this puzzle.</p>
    <?php endif; ?>
  <?php endif; ?>

  <div class="pp-foot">Simply Creative Games</div>
</div>
</body>
</html>
