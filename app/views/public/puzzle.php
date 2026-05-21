<?php
/** @var array $puzzle  @var array $items  @var array $clues  @var bool $solved  @var bool $wrong */
$pzSlug   = (string)$puzzle['slug'];
$pzTitle  = (string)($puzzle['title'] ?: 'Puzzle');
$pzPrompt = trim((string)($puzzle['prompt'] ?? ''));
$pzReward = (string)($puzzle['reward_content'] ?? '');
$pzRType  = (string)($puzzle['reward_file_type'] ?? '');
$pzRPath  = (string)($puzzle['reward_file_path'] ?? '');
$pzRName  = (string)($puzzle['reward_original_filename'] ?? '');
$pzRCap   = (string)($puzzle['reward_file_caption'] ?? '');
$pzRUrl   = $pzRPath !== '' ? UPLOAD_URL . '/' . $pzRPath : '';

function pzMedia(string $type, string $url): string
{
    $u = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    return match ($type) {
        'image' => "<img src=\"$u\" alt=\"\" class=\"pz-img\" loading=\"lazy\">",
        'audio' => "<audio controls preload=\"metadata\" style=\"width:100%\"><source src=\"$u\"></audio>",
        'video' => "<video controls preload=\"metadata\" playsinline class=\"pz-vid\"><source src=\"$u\"></video>",
        'pdf'   => "<a href=\"$u\" target=\"_blank\" class=\"pz-file\">&#128196; Open PDF</a>",
        default => "<a href=\"$u\" class=\"pz-file\" download>&#128206; Download file</a>",
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pzTitle, ENT_QUOTES, 'UTF-8') ?></title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',system-ui,Segoe UI,Arial,sans-serif;background:#0f0f1a;color:#e6e6ef;line-height:1.6;padding:2rem 1rem 4rem}
.pz-wrap{max-width:920px;margin:0 auto}
.pz-title{font-size:1.9rem;font-weight:700;letter-spacing:-.02em;margin-bottom:.4rem}
.pz-prompt{color:rgba(255,255,255,.6);font-size:.98rem;margin-bottom:1.75rem}
.pz-grid{display:grid;grid-template-columns:1fr 1.2fr;gap:1.5rem;align-items:start}
.pz-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09);border-radius:14px;padding:1.4rem}
.pz-card h2{font-size:.74rem;text-transform:uppercase;letter-spacing:.14em;color:rgba(255,255,255,.4);font-weight:700;margin-bottom:1rem}
.pz-clues{counter-reset:c;list-style:none}
.pz-clues li{counter-increment:c;position:relative;padding:.55rem 0 .55rem 2.2rem;border-bottom:1px solid rgba(255,255,255,.06);font-size:.95rem}
.pz-clues li:last-child{border-bottom:none}
.pz-clues li::before{content:counter(c);position:absolute;left:0;top:.5rem;width:1.5rem;height:1.5rem;border-radius:50%;background:rgba(108,99,255,.18);color:#b3acff;font-size:.78rem;font-weight:700;display:flex;align-items:center;justify-content:center}
.pz-sortable{list-style:none;display:flex;flex-direction:column;gap:.55rem}
.pz-item{display:flex;align-items:center;gap:.75rem;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:.85rem 1rem;cursor:grab;font-size:.98rem;transition:border-color .15s,background .15s;user-select:none}
.pz-item:hover{border-color:rgba(108,99,255,.5)}
.pz-item.dragging{opacity:.45;cursor:grabbing}
.pz-item .grip{color:rgba(255,255,255,.35);font-size:1.05rem;flex-shrink:0}
.pz-cipher{font-family:'Courier New',monospace;font-size:1.4rem;letter-spacing:.18em;word-break:break-word;background:rgba(0,0,0,.25);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:1.1rem 1.25rem;line-height:1.7}
.pz-lbl{display:block;margin:1.25rem 0 .4rem;font-size:.85rem;color:rgba(255,255,255,.55)}
.pz-input{width:100%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.14);border-radius:10px;padding:.85rem 1rem;color:#e6e6ef;font-size:1.05rem;font-family:inherit}
.pz-input:focus{outline:none;border-color:#6c63ff;box-shadow:0 0 0 3px rgba(108,99,255,.2)}
.pz-elim{list-style:none;display:flex;flex-direction:column;gap:.55rem;margin-bottom:.5rem}
.pz-eitem{display:flex;align-items:center;gap:.85rem;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:.85rem 1rem;cursor:pointer;font-size:.98rem;transition:all .15s;user-select:none}
.pz-eitem:hover{border-color:rgba(239,68,68,.5)}
.pz-eitem .pz-emark{display:inline-flex;align-items:center;justify-content:center;width:1.5rem;height:1.5rem;border-radius:50%;border:1.5px solid rgba(255,255,255,.25);color:transparent;font-weight:700;font-size:.85rem;flex-shrink:0;transition:all .15s}
.pz-eitem .pz-etext{flex:1}
.pz-eitem.struck{background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.45);color:rgba(255,255,255,.45)}
.pz-eitem.struck .pz-etext{text-decoration:line-through}
.pz-eitem.struck .pz-emark{background:#ef4444;border-color:#ef4444;color:#fff}
.pz-eitem:focus{outline:none;border-color:#6c63ff;box-shadow:0 0 0 3px rgba(108,99,255,.22)}
.pz-access{display:flex;justify-content:center;flex-wrap:wrap;gap:.55rem;margin:.5rem 0 1.1rem}
.pz-box{width:54px;height:64px;text-align:center;font-family:'Courier New',monospace;font-size:1.7rem;font-weight:700;background:rgba(0,0,0,.3);border:1px solid rgba(108,99,255,.3);border-radius:10px;color:#e6e6ef;caret-color:#6c63ff}
.pz-box:focus{outline:none;border-color:#6c63ff;box-shadow:0 0 0 3px rgba(108,99,255,.22)}
.pz-box.filled{background:rgba(108,99,255,.18)}
@media(max-width:480px){.pz-box{width:42px;height:54px;font-size:1.35rem}}
.pz-readout{margin:1.1rem 0;min-height:3.2rem;display:flex;align-items:center;justify-content:center;font-family:'Courier New',monospace;font-size:1.6rem;letter-spacing:.22em;text-transform:uppercase;background:rgba(0,0,0,.3);border:1px solid rgba(108,99,255,.25);border-radius:10px;padding:.8rem 1rem;word-break:break-word;text-align:center}
.pz-readout .pz-ro-empty{font-family:'Inter',system-ui,sans-serif;font-size:.92rem;letter-spacing:normal;text-transform:none;color:rgba(255,255,255,.35)}
.pz-readout .pz-pending{color:#b3acff;border-bottom:2px solid #6c63ff}
.pz-pad{display:grid;grid-template-columns:repeat(3,1fr);gap:.65rem;max-width:340px;margin:0 auto}
.pz-key{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.1rem;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.14);border-radius:12px;color:#e6e6ef;padding:.7rem 0;cursor:pointer;font-family:inherit;transition:transform .08s,background .15s,border-color .15s}
.pz-key:hover{border-color:rgba(108,99,255,.6)}
.pz-key:active,.pz-key.tapped{transform:scale(.94);background:rgba(108,99,255,.28)}
.pz-key .d{font-size:1.35rem;font-weight:700;line-height:1}
.pz-key .l{font-size:.62rem;letter-spacing:.12em;color:rgba(255,255,255,.45);min-height:.8rem}
.pz-key-fn .d{font-size:1rem}
.pz-actions{margin-top:1.5rem;display:flex;gap:1rem;align-items:center;flex-wrap:wrap}
.pz-btn{background:#6c63ff;color:#fff;border:none;border-radius:10px;padding:.8rem 1.8rem;font-size:1rem;font-weight:600;cursor:pointer;font-family:inherit}
.pz-btn:hover{filter:brightness(1.12)}
.pz-msg{padding:.85rem 1.1rem;border-radius:10px;font-size:.92rem;margin-bottom:1.25rem}
.pz-msg.err{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#fca5a5}
.pz-msg.ok{background:rgba(16,185,129,.14);border:1px solid rgba(16,185,129,.35);color:#6ee7b7}
.pz-reward{margin-top:2rem;background:rgba(255,255,255,.05);border:1px solid rgba(108,99,255,.3);border-radius:16px;padding:2rem;position:relative;overflow:hidden}
.pz-reward .badge{display:inline-flex;align-items:center;gap:.5rem;background:rgba(16,185,129,.14);border:1px solid rgba(16,185,129,.35);color:#6ee7b7;font-size:.8rem;font-weight:600;padding:.4rem .85rem;border-radius:20px;margin-bottom:1rem}
.pz-rich{line-height:1.8}
.pz-rich p{margin-bottom:.85rem}
.pz-rich h1,.pz-rich h2,.pz-rich h3{margin:1.1rem 0 .5rem}
.pz-rich ul,.pz-rich ol{padding-left:1.5rem;margin-bottom:.85rem}
.pz-rich a{color:#b3acff}
.pz-media{margin-top:1.25rem}
.pz-img,.pz-vid{width:100%;max-height:60vh;object-fit:contain;border-radius:10px;display:block}
.pz-file{display:inline-block;padding:.7rem 1.1rem;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:10px;color:#e6e6ef;text-decoration:none}
.pz-cap{font-size:.82rem;color:rgba(255,255,255,.4);margin-top:.5rem;text-align:center}
.pz-foot{text-align:center;margin-top:3rem;font-size:.78rem;color:rgba(255,255,255,.22)}
@media(max-width:680px){.pz-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="pz-wrap">
  <h1 class="pz-title"><?= htmlspecialchars($pzTitle, ENT_QUOTES, 'UTF-8') ?></h1>
  <?php if ($pzPrompt !== ''): ?>
  <p class="pz-prompt"><?= htmlspecialchars($pzPrompt, ENT_QUOTES, 'UTF-8') ?></p>
  <?php elseif ($puzzleType === 'phone'): ?>
  <p class="pz-prompt">Use the keypad to translate the code below, then press Submit.</p>
  <?php elseif ($puzzleType === 'access'): ?>
  <p class="pz-prompt">Use the clues to figure out the access code, then enter it below and press Submit.</p>
  <?php elseif ($puzzleType === 'elim'): ?>
  <p class="pz-prompt">Use the clues to eliminate the wrong items. Click an item to cross it off &mdash; click again to bring it back. When you&rsquo;re sure, press Submit.</p>
  <?php elseif ($puzzleType === 'caesar'): ?>
  <p class="pz-prompt">Decode the message below, then press Submit.</p>
  <?php else: ?>
  <p class="pz-prompt">Drag the items into the correct order, then press Submit.</p>
  <?php endif; ?>

  <?php if ($wrong): ?>
  <div class="pz-msg err">&#9888; <?php
      if ($puzzleType === 'caesar') {
          echo "That's not the right answer &mdash; try decoding it again.";
      } elseif ($puzzleType === 'phone') {
          echo "That's not the right code &mdash; check the keypad and try again.";
      } elseif ($puzzleType === 'access') {
          echo "That's not the right access code &mdash; check the clues and try again.";
      } elseif ($puzzleType === 'elim') {
          echo "Those aren't the right items to eliminate &mdash; re-read the clues and try again.";
      } else {
          echo "That's not the right order yet. Rearrange the items and try again.";
      } ?></div>
  <?php endif; ?>

  <?php if (!$solved && $puzzleType === 'elim'): ?>
  <div class="pz-grid">
    <?php if (!empty($clues)): ?>
    <div class="pz-card">
      <h2>Clues</h2>
      <ol class="pz-clues">
        <?php foreach ($clues as $c): ?>
        <li><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ol>
    </div>
    <?php endif; ?>
    <div class="pz-card" <?= empty($clues) ? 'style="grid-column:1/-1"' : '' ?>>
      <h2>Eliminate the wrong items</h2>
      <ul class="pz-elim" id="pz-elim">
        <?php foreach ($items as $it): ?>
        <li class="pz-eitem" data-val="<?= htmlspecialchars($it, ENT_QUOTES, 'UTF-8') ?>" role="button" tabindex="0" aria-pressed="false">
          <span class="pz-emark" aria-hidden="true">&#10005;</span>
          <span class="pz-etext"><?= htmlspecialchars($it, ENT_QUOTES, 'UTF-8') ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <form method="POST" action="<?= htmlspecialchars(url('z/' . $pzSlug), ENT_QUOTES, 'UTF-8') ?>" id="pz-form">
        <?= csrf_field() ?>
        <input type="hidden" name="eliminated" id="pz-eliminated">
        <div class="pz-actions">
          <button type="submit" class="pz-btn">Submit</button>
        </div>
      </form>
    </div>
  </div>
  <?php elseif (!$solved && $puzzleType === 'access'): ?>
  <div class="pz-grid">
    <?php if (!empty($clues)): ?>
    <div class="pz-card">
      <h2>Clues</h2>
      <ol class="pz-clues">
        <?php foreach ($clues as $c): ?>
        <li><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ol>
    </div>
    <?php endif; ?>
    <div class="pz-card" <?= empty($clues) ? 'style="grid-column:1/-1"' : '' ?>>
      <h2>Access Code</h2>
      <form method="POST" action="<?= htmlspecialchars(url('z/' . $pzSlug), ENT_QUOTES, 'UTF-8') ?>" id="pz-form">
        <?= csrf_field() ?>
        <div class="pz-access" id="pz-access" data-len="<?= (int)$accessLen ?>">
          <?php for ($i = 0; $i < $accessLen; $i++): ?>
          <input type="text" inputmode="numeric" pattern="[0-9]" maxlength="1"
                 class="pz-box" autocomplete="off" aria-label="Digit <?= $i + 1 ?>">
          <?php endfor; ?>
        </div>
        <input type="hidden" name="answer" id="pz-answer-h">
        <div class="pz-actions">
          <button type="submit" class="pz-btn">Submit</button>
        </div>
      </form>
    </div>
  </div>
  <?php elseif (!$solved && $puzzleType === 'phone'): ?>
  <div class="pz-card" style="grid-column:1/-1">
    <h2>Coded Message</h2>
    <div class="pz-cipher" style="text-align:center"><?= htmlspecialchars($phoneClue, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="pz-readout" id="pz-readout"><span class="pz-ro-empty">Tap the keys to spell it out…</span></div>
    <div class="pz-pad" id="pz-pad">
      <button type="button" class="pz-key" data-d="1"><span class="d">1</span><span class="l">&nbsp;</span></button>
      <button type="button" class="pz-key" data-d="2" data-l="ABC"><span class="d">2</span><span class="l">ABC</span></button>
      <button type="button" class="pz-key" data-d="3" data-l="DEF"><span class="d">3</span><span class="l">DEF</span></button>
      <button type="button" class="pz-key" data-d="4" data-l="GHI"><span class="d">4</span><span class="l">GHI</span></button>
      <button type="button" class="pz-key" data-d="5" data-l="JKL"><span class="d">5</span><span class="l">JKL</span></button>
      <button type="button" class="pz-key" data-d="6" data-l="MNO"><span class="d">6</span><span class="l">MNO</span></button>
      <button type="button" class="pz-key" data-d="7" data-l="PQRS"><span class="d">7</span><span class="l">PQRS</span></button>
      <button type="button" class="pz-key" data-d="8" data-l="TUV"><span class="d">8</span><span class="l">TUV</span></button>
      <button type="button" class="pz-key" data-d="9" data-l="WXYZ"><span class="d">9</span><span class="l">WXYZ</span></button>
      <button type="button" class="pz-key pz-key-fn" id="pz-clear"><span class="d">&#9003;</span><span class="l">CLEAR</span></button>
      <button type="button" class="pz-key" data-d="0" data-l="space"><span class="d">0</span><span class="l">&#9251;</span></button>
      <button type="button" class="pz-key pz-key-fn" id="pz-back"><span class="d">&#11013;</span><span class="l">DELETE</span></button>
    </div>
    <form method="POST" action="<?= htmlspecialchars(url('z/' . $pzSlug), ENT_QUOTES, 'UTF-8') ?>" id="pz-form">
      <?= csrf_field() ?>
      <input type="hidden" name="answer" id="pz-answer-h">
      <div class="pz-actions">
        <button type="submit" class="pz-btn">Submit</button>
      </div>
    </form>
  </div>
  <?php elseif (!$solved && $puzzleType === 'caesar'): ?>
  <div class="pz-card" style="grid-column:1/-1">
    <h2>Encoded Message</h2>
    <div class="pz-cipher"><?= htmlspecialchars($cipher, ENT_QUOTES, 'UTF-8') ?></div>
    <form method="POST" action="<?= htmlspecialchars(url('z/' . $pzSlug), ENT_QUOTES, 'UTF-8') ?>" id="pz-form">
      <?= csrf_field() ?>
      <label class="pz-lbl" for="pz-answer">Your decoded answer</label>
      <input type="text" name="answer" id="pz-answer" class="pz-input" autocomplete="off"
             autocorrect="off" spellcheck="false" placeholder="Type the decoded phrase…">
      <div class="pz-actions">
        <button type="submit" class="pz-btn">Submit</button>
      </div>
    </form>
  </div>
  <?php elseif (!$solved): ?>
  <div class="pz-grid">
    <?php if (!empty($clues)): ?>
    <div class="pz-card">
      <h2>Order Clues</h2>
      <ol class="pz-clues">
        <?php foreach ($clues as $c): ?>
        <li><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ol>
    </div>
    <?php endif; ?>
    <div class="pz-card" <?= empty($clues) ? 'style="grid-column:1/-1"' : '' ?>>
      <h2>Arrange in Order</h2>
      <ul class="pz-sortable" id="pz-sortable">
        <?php foreach ($items as $it): ?>
        <li class="pz-item" draggable="true" data-val="<?= htmlspecialchars($it, ENT_QUOTES, 'UTF-8') ?>">
          <span class="grip">&#9776;</span>
          <span><?= htmlspecialchars($it, ENT_QUOTES, 'UTF-8') ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <form method="POST" action="<?= htmlspecialchars(url('z/' . $pzSlug), ENT_QUOTES, 'UTF-8') ?>" id="pz-form">
        <?= csrf_field() ?>
        <input type="hidden" name="order" id="pz-order">
        <div class="pz-actions">
          <button type="submit" class="pz-btn">Submit</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($solved): ?>
  <div class="pz-msg ok">&#9989; Correct! You solved the puzzle.</div>
  <div class="pz-reward">
    <div class="badge">&#129513; Puzzle Solved</div>
    <?php if (trim(strip_tags(str_replace('&nbsp;', ' ', $pzReward))) !== ''): ?>
    <div class="pz-rich"><?= $pzReward ?></div>
    <?php endif; ?>
    <?php if ($pzRUrl !== ''): ?>
    <div class="pz-media">
      <?= pzMedia($pzRType, $pzRUrl) ?>
      <?php if ($pzRCap !== ''): ?><div class="pz-cap"><?= htmlspecialchars($pzRCap, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if (trim(strip_tags(str_replace('&nbsp;', ' ', $pzReward))) === '' && $pzRUrl === ''): ?>
    <p class="pz-rich">Well done — you cracked it!</p>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="pz-foot">Powered by <a href="https://simplycreativegames.etsy.com" target="_blank" rel="noopener" style="color:#b3acff;text-decoration:none">Simply Creative Games</a></div>
</div>

<?php if (!$solved): ?>
<script>
(function () {
  var list = document.getElementById('pz-sortable');
  var form = document.getElementById('pz-form');
  if (!list || !form) return;
  var dragged = null;

  list.addEventListener('dragstart', function (e) {
    var li = e.target.closest('.pz-item');
    if (!li) return;
    dragged = li;
    li.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
  });
  list.addEventListener('dragend', function () {
    if (dragged) dragged.classList.remove('dragging');
    dragged = null;
  });
  list.addEventListener('dragover', function (e) {
    e.preventDefault();
    if (!dragged) return;
    var after = getAfter(list, e.clientY);
    if (after == null) list.appendChild(dragged);
    else list.insertBefore(dragged, after);
  });

  function getAfter(container, y) {
    var els = [].slice.call(container.querySelectorAll('.pz-item:not(.dragging)'));
    var closest = { offset: -Infinity, el: null };
    els.forEach(function (child) {
      var box = child.getBoundingClientRect();
      var offset = y - box.top - box.height / 2;
      if (offset < 0 && offset > closest.offset) closest = { offset: offset, el: child };
    });
    return closest.el;
  }

  form.addEventListener('submit', function () {
    var order = [].slice.call(list.querySelectorAll('.pz-item'))
      .map(function (li) { return li.getAttribute('data-val'); });
    document.getElementById('pz-order').value = JSON.stringify(order);
  });
})();

(function () {
  var pad = document.getElementById('pz-pad');
  var readout = document.getElementById('pz-readout');
  var form = document.getElementById('pz-form');
  var hidden = document.getElementById('pz-answer-h');
  if (!pad || !readout || !form || !hidden) return;

  var TAP_MS = 900;
  var committed = '';
  var pendingKey = null;   // letters string of the key being cycled
  var pendingChar = '';    // currently shown letter
  var pendingIdx = 0;
  var timer = null;

  function render() {
    if (committed === '' && pendingChar === '') {
      readout.innerHTML = '<span class="pz-ro-empty">Tap the keys to spell it out…</span>';
      return;
    }
    var safe = committed.replace(/&/g, '&amp;').replace(/</g, '&lt;');
    var html = safe.replace(/ /g, '&middot;');
    if (pendingChar !== '') {
      html += '<span class="pz-pending">' + pendingChar + '</span>';
    }
    readout.innerHTML = html;
  }

  function commitPending() {
    if (pendingChar !== '') { committed += pendingChar; }
    pendingKey = null; pendingChar = ''; pendingIdx = 0;
    if (timer) { clearTimeout(timer); timer = null; }
  }

  pad.addEventListener('click', function (e) {
    var btn = e.target.closest('.pz-key');
    if (!btn) return;
    btn.classList.add('tapped');
    setTimeout(function () { btn.classList.remove('tapped'); }, 120);

    if (btn.id === 'pz-clear') {
      commitPending(); committed = ''; pendingChar = ''; render(); return;
    }
    if (btn.id === 'pz-back') {
      if (pendingChar !== '') { pendingKey = null; pendingChar = ''; pendingIdx = 0; if (timer) { clearTimeout(timer); timer = null; } }
      else { committed = committed.slice(0, -1); }
      render(); return;
    }

    var d = btn.getAttribute('data-d');
    if (d === '0') { commitPending(); committed += ' '; render(); return; }
    var letters = btn.getAttribute('data-l');
    if (!letters || letters === 'space') { commitPending(); render(); return; }
    letters = letters.toUpperCase();

    if (pendingKey === letters) {
      pendingIdx = (pendingIdx + 1) % letters.length;
      pendingChar = letters.charAt(pendingIdx);
    } else {
      commitPending();
      pendingKey = letters; pendingIdx = 0; pendingChar = letters.charAt(0);
    }
    if (timer) { clearTimeout(timer); }
    timer = setTimeout(commitPending, TAP_MS);
    render();
  });

  form.addEventListener('submit', function () {
    commitPending();
    hidden.value = committed;
  });
})();

(function () {
  var wrap = document.getElementById('pz-access');
  if (!wrap) return;
  var form = document.getElementById('pz-form');
  var hidden = document.getElementById('pz-answer-h');
  var boxes = [].slice.call(wrap.querySelectorAll('.pz-box'));
  if (!boxes.length || !form || !hidden) return;

  function focusBox(i) {
    if (i >= 0 && i < boxes.length) { boxes[i].focus(); boxes[i].select(); }
  }

  boxes.forEach(function (b, i) {
    b.addEventListener('input', function () {
      var v = b.value.replace(/\D/g, '');
      b.value = v ? v.charAt(v.length - 1) : '';
      b.classList.toggle('filled', b.value !== '');
      if (b.value) focusBox(i + 1);
    });
    b.addEventListener('keydown', function (e) {
      if (e.key === 'Backspace' && b.value === '' && i > 0) {
        e.preventDefault();
        boxes[i - 1].value = '';
        boxes[i - 1].classList.remove('filled');
        focusBox(i - 1);
      } else if (e.key === 'ArrowLeft' && i > 0) { e.preventDefault(); focusBox(i - 1); }
      else if (e.key === 'ArrowRight' && i < boxes.length - 1) { e.preventDefault(); focusBox(i + 1); }
    });
    b.addEventListener('paste', function (e) {
      var txt = (e.clipboardData || window.clipboardData).getData('text') || '';
      var digits = txt.replace(/\D/g, '');
      if (!digits) return;
      e.preventDefault();
      for (var k = 0; k < digits.length && (i + k) < boxes.length; k++) {
        boxes[i + k].value = digits.charAt(k);
        boxes[i + k].classList.add('filled');
      }
      focusBox(Math.min(i + digits.length, boxes.length - 1));
    });
  });

  focusBox(0);

  form.addEventListener('submit', function () {
    hidden.value = boxes.map(function (b) { return b.value || ''; }).join('');
  });
})();

(function () {
  var list = document.getElementById('pz-elim');
  if (!list) return;
  var form = document.getElementById('pz-form');
  var hidden = document.getElementById('pz-eliminated');
  if (!form || !hidden) return;

  function toggle(li) {
    var on = li.classList.toggle('struck');
    li.setAttribute('aria-pressed', on ? 'true' : 'false');
  }

  list.addEventListener('click', function (e) {
    var li = e.target.closest('.pz-eitem');
    if (li) toggle(li);
  });
  list.addEventListener('keydown', function (e) {
    if (e.key !== ' ' && e.key !== 'Enter') return;
    var li = e.target.closest('.pz-eitem');
    if (!li) return;
    e.preventDefault();
    toggle(li);
  });

  form.addEventListener('submit', function () {
    var picks = [].slice.call(list.querySelectorAll('.pz-eitem.struck'))
      .map(function (li) { return li.getAttribute('data-val'); });
    hidden.value = JSON.stringify(picks);
  });
})();
</script>
<?php endif; ?>
</body>
</html>
