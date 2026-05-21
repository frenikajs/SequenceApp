<?php
$pageTitle = 'Puzzle Page';
$activeNav = 'sequences';
ob_start();
$clueId = (int)$clue['id'];
$seqId  = (int)$clue['sequence_id'];
$isNew  = ($puzzle === null);
$inp    = $input ?? [];

$pSlug   = $inp['slug']   ?? ($puzzle['slug']   ?? '');
$pTitle  = $inp['title']  ?? ($puzzle['title']  ?? '');
$pPrompt = $inp['prompt'] ?? ($puzzle['prompt'] ?? '');
$pReward = $inp['reward_content'] ?? ($puzzle['reward_content'] ?? '');
$pRewardCap = $inp['reward_file_caption'] ?? ($puzzle['reward_file_caption'] ?? '');

$pType  = $inp['puzzle_type'] ?? ($puzzle['puzzle_type'] ?? 'order');
$pItems = array_fill(0, 6, '');
$pClues = array_fill(0, 10, '');
$pShift = 3;
$pPhrase = '';
$pCode = '';
$pAccess = '';
$pAClues = array_fill(0, 10, '');
$pEItems  = array_fill(0, 6, '');
$pEMark   = array_fill(0, 6, false);
$pEClues  = array_fill(0, 10, '');
if (!empty($inp)) {
    for ($i = 0; $i < 6; $i++)  { $pItems[$i] = $inp['item' . ($i + 1)] ?? ''; }
    for ($i = 0; $i < 10; $i++) { $pClues[$i] = $inp['oclue' . ($i + 1)] ?? ''; }
    $pShift  = (int)($inp['caesar_shift'] ?? 3);
    $pPhrase = $inp['caesar_phrase'] ?? '';
    $pCode   = $inp['phone_code'] ?? '';
    $pAccess = $inp['access_code'] ?? '';
    for ($i = 0; $i < 10; $i++) { $pAClues[$i] = $inp['aclue' . ($i + 1)] ?? ''; }
    $eMarkIn = $inp['elim_mark'] ?? [];
    for ($i = 0; $i < 6; $i++)  { $pEItems[$i] = $inp['item' . ($i + 1)] ?? ''; $pEMark[$i] = !empty($eMarkIn[$i + 1]); }
    for ($i = 0; $i < 10; $i++) { $pEClues[$i] = $inp['eclue' . ($i + 1)] ?? ''; }
} elseif ($puzzle) {
    $d = json_decode($puzzle['data_json'] ?? '{}', true) ?: [];
    foreach (($d['items'] ?? []) as $i => $v) { if ($i < 6)  { $pItems[$i] = (string)$v; } }
    foreach (($d['clues'] ?? []) as $i => $v) { if ($i < 10) { $pClues[$i] = (string)$v; } }
    $pShift  = (int)($d['shift'] ?? 3);
    $pPhrase = (string)($d['phrase'] ?? '');
    $pCode   = (string)($d['code'] ?? '');
    if (($puzzle['puzzle_type'] ?? '') === 'access') {
        $pAccess = (string)($d['code'] ?? '');
        foreach (($d['clues'] ?? []) as $i => $v) { if ($i < 10) { $pAClues[$i] = (string)$v; } }
    }
    if (($puzzle['puzzle_type'] ?? '') === 'elim') {
        $marked = $d['eliminate'] ?? [];
        foreach (($d['items'] ?? []) as $i => $v) {
            if ($i < 6) {
                $pEItems[$i] = (string)$v;
                $pEMark[$i]  = in_array((string)$v, array_map('strval', is_array($marked) ? $marked : []), true);
            }
        }
        foreach (($d['clues'] ?? []) as $i => $v) { if ($i < 10) { $pEClues[$i] = (string)$v; } }
    }
}
if ($pShift < 1 || $pShift > 25) { $pShift = 3; }
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
  <strong>Please fix the following:</strong>
  <ul><?php foreach ($errors as $e_msg): ?><li><?= e($e_msg) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="breadcrumb">
  <a href="<?= url('admin/sequences') ?>">Sequences</a> /
  <a href="<?= url('admin/sequences/' . $seqId . '/edit') ?>">Edit</a> /
  <a href="<?= url('admin/sequences/' . $seqId . '/clues') ?>">Clues</a> /
  <span>Puzzle Page</span>
</div>

<?php if (!$isNew): ?>
<div class="analytics-strip">
  <div class="analytic-item">
    <span class="analytic-icon">&#129513;</span>
    <span class="muted">Public URL</span>
    <a href="<?= url('z/' . $puzzle['slug']) ?>" target="_blank" class="btn btn-ghost btn-sm">
      /z/<?= e($puzzle['slug']) ?> &#8599;
    </a>
  </div>
</div>
<?php endif; ?>

<form method="POST" action="<?= url('admin/clues/' . $clueId . '/puzzle') ?>" id="puzzle-form">
  <?= csrf_field() ?>

  <div class="card">
    <div class="card-header"><h2>Puzzle Setup</h2></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group flex-1">
          <label>Puzzle Type</label>
          <select name="puzzle_type" id="puzzle-type" class="inv-field" onchange="pzTypeToggle()">
            <option value="order"  <?= $pType === 'order'  ? 'selected' : '' ?>>Order Puzzle</option>
            <option value="caesar" <?= $pType === 'caesar' ? 'selected' : '' ?>>Caesar Shift Cipher</option>
            <option value="phone"  <?= $pType === 'phone'  ? 'selected' : '' ?>>Phone Keypad</option>
            <option value="access" <?= $pType === 'access' ? 'selected' : '' ?>>Access Code</option>
            <option value="elim"   <?= $pType === 'elim'   ? 'selected' : '' ?>>Elimination</option>
          </select>
        </div>
        <div class="form-group flex-1">
          <label>URL Slug <span class="req">*</span></label>
          <div class="input-prefix">
            <span class="prefix-text">/z/</span>
            <input type="text" name="slug" id="slug-field" required
                   value="<?= e($pSlug) ?>" placeholder="order-the-events">
          </div>
        </div>
      </div>
      <div class="form-group">
        <label>Title <span class="req">*</span></label>
        <input type="text" name="title" id="title-field" class="inv-field"
               value="<?= e($pTitle) ?>" placeholder="Put the events in order">
      </div>
      <div class="form-group">
        <label>Prompt / Instructions (optional)</label>
        <textarea name="prompt" rows="2" class="sms-textarea"
                  placeholder="Drag the items into the correct order, then press Submit."><?= e($pPrompt) ?></textarea>
      </div>
    </div>
  </div>

  <div id="grp-order">
  <div class="card mt-4">
    <div class="card-header"><h2>Order Line Items</h2></div>
    <div class="card-body">
      <small class="inv-hint" style="margin-top:0;margin-bottom:.6rem">Enter the line items <strong>in the correct (solution) order</strong>. Up to 6. Players will see them shuffled and must drag them into this order.</small>
      <?php for ($i = 0; $i < 6; $i++): ?>
      <div class="map-marker-row">
        <span class="map-marker-num"><?= $i + 1 ?></span>
        <input type="text" name="item<?= $i + 1 ?>" class="inv-field"
               placeholder="Line item <?= $i + 1 ?>" value="<?= e($pItems[$i]) ?>">
      </div>
      <?php endfor; ?>
    </div>
  </div>

  <div class="card mt-4">
    <div class="card-header"><h2>Order Clues</h2></div>
    <div class="card-body">
      <small class="inv-hint" style="margin-top:0;margin-bottom:.6rem">Up to 10 clue strings, shown to the player as a numbered list beside the items.</small>
      <?php for ($i = 0; $i < 10; $i++): ?>
      <div class="map-marker-row">
        <span class="map-marker-num"><?= $i + 1 ?></span>
        <input type="text" name="oclue<?= $i + 1 ?>" class="inv-field"
               placeholder="Order clue <?= $i + 1 ?>" value="<?= e($pClues[$i]) ?>">
      </div>
      <?php endfor; ?>
    </div>
  </div>
  </div>

  <div id="grp-caesar">
  <div class="card mt-4">
    <div class="card-header"><h2>Caesar Shift Cipher</h2></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group" style="flex:0 0 160px">
          <label>Shift (letters)</label>
          <select name="caesar_shift" class="inv-field">
            <?php for ($s = 1; $s <= 25; $s++): ?>
            <option value="<?= $s ?>" <?= $pShift === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="form-group flex-1">
          <label>Phrase <span class="req">*</span></label>
          <input type="text" name="caesar_phrase" class="inv-field"
                 value="<?= e($pPhrase) ?>" placeholder="The secret message to encode">
          <small class="inv-hint">The plaintext answer. Players see it shifted by the chosen amount and must decode it.</small>
        </div>
      </div>
    </div>
  </div>
  </div>

  <div id="grp-elim">
  <div class="card mt-4">
    <div class="card-header"><h2>Elimination Items</h2></div>
    <div class="card-body">
      <small class="inv-hint" style="margin-top:0;margin-bottom:.6rem">Enter up to 6 items. Tick the <strong>Eliminate</strong> box for each item the player must eliminate (at most 5). The order players see is shuffled.</small>
      <?php for ($i = 0; $i < 6; $i++): ?>
      <div class="map-marker-row">
        <span class="map-marker-num"><?= $i + 1 ?></span>
        <input type="text" name="item<?= $i + 1 ?>" class="inv-field"
               placeholder="Item <?= $i + 1 ?>" value="<?= e($pEItems[$i]) ?>">
        <label class="elim-mark"><input type="checkbox" name="elim_mark[<?= $i + 1 ?>]" value="1" <?= $pEMark[$i] ? 'checked' : '' ?>> Eliminate</label>
      </div>
      <?php endfor; ?>
    </div>
  </div>

  <div class="card mt-4">
    <div class="card-header"><h2>Elimination Clues</h2></div>
    <div class="card-body">
      <small class="inv-hint" style="margin-top:0;margin-bottom:.6rem">Up to 10 clue strings shown beside the items to help players reason about which to eliminate.</small>
      <?php for ($i = 0; $i < 10; $i++): ?>
      <div class="map-marker-row">
        <span class="map-marker-num"><?= $i + 1 ?></span>
        <input type="text" name="eclue<?= $i + 1 ?>" class="inv-field"
               placeholder="Elimination clue <?= $i + 1 ?>" value="<?= e($pEClues[$i]) ?>">
      </div>
      <?php endfor; ?>
    </div>
  </div>
  </div>

  <div id="grp-access">
  <div class="card mt-4">
    <div class="card-header"><h2>Access Code</h2></div>
    <div class="card-body">
      <div class="form-group">
        <label>Access Code <span class="req">*</span></label>
        <input type="text" name="access_code" class="inv-field" inputmode="numeric"
               pattern="[0-9]*" maxlength="7"
               value="<?= e($pAccess) ?>" placeholder="e.g. 1234567">
        <small class="inv-hint">Digits only, up to 7. Each digit appears in its own square box on the puzzle page.</small>
      </div>
    </div>
  </div>

  <div class="card mt-4">
    <div class="card-header"><h2>Access Code Clues</h2></div>
    <div class="card-body">
      <small class="inv-hint" style="margin-top:0;margin-bottom:.6rem">Up to 10 clue strings shown beside the code boxes to help players figure out the access code.</small>
      <?php for ($i = 0; $i < 10; $i++): ?>
      <div class="map-marker-row">
        <span class="map-marker-num"><?= $i + 1 ?></span>
        <input type="text" name="aclue<?= $i + 1 ?>" class="inv-field"
               placeholder="Access clue <?= $i + 1 ?>" value="<?= e($pAClues[$i]) ?>">
      </div>
      <?php endfor; ?>
    </div>
  </div>
  </div>

  <div id="grp-phone">
  <div class="card mt-4">
    <div class="card-header"><h2>Phone Keypad</h2></div>
    <div class="card-body">
      <div class="form-group">
        <label>Code <span class="req">*</span></label>
        <input type="text" name="phone_code" class="inv-field"
               value="<?= e($pCode) ?>" placeholder="CAT">
        <small class="inv-hint">The answer word/phrase. Players see it as old-school multi-tap digits (e.g. <strong>CAT</strong> &rarr; <code>222 2 8</code>) and must key it back in. Letters &amp; spaces only; case-insensitive.</small>
      </div>
    </div>
  </div>
  </div>

  <div class="card mt-4">
    <div class="card-header"><h2>Reward (shown when solved)</h2></div>
    <div class="card-body">
      <p class="small muted" style="margin-bottom:.75rem">The clue content &amp; media revealed at the bottom of the puzzle once the player orders the items correctly.</p>
      <div class="form-group">
        <label>Clue Content</label>
        <div class="quill-editor" id="reward-editor"></div>
        <input type="hidden" name="reward_content" id="reward-content">
      </div>
      <div class="form-group">
        <label>Attached Media</label>
        <?php if (!$isNew && !empty($puzzle['reward_file_path'])): ?>
        <div class="current-media" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
          <?php $rurl = url('uploads/' . $puzzle['reward_file_path']); ?>
          <?php if (($puzzle['reward_file_type'] ?? '') === 'image'): ?>
            <img src="<?= e($rurl) ?>" class="media-thumb" style="max-height:90px;border-radius:6px">
          <?php else: ?>
            <a href="<?= e($rurl) ?>" target="_blank">&#128206; <?= e($puzzle['reward_original_filename'] ?? 'File') ?></a>
          <?php endif; ?>
          <label class="checkbox-label"><input type="checkbox" name="reward_file_remove" value="1"> Remove this file</label>
        </div>
        <?php endif; ?>
        <div class="upload-area" id="reward-upload-area">
          <div class="upload-placeholder">
            <span>&#128193; Drag &amp; drop or click to upload</span>
            <span class="small muted">PNG, JPG, PDF, MP3, WAV, OGG &mdash; max 10MB</span>
          </div>
          <input type="file" name="reward_file" id="reward-file" class="upload-input"
                 accept=".png,.jpg,.jpeg,.pdf,.mp3,.wav,.ogg,.m4a,.mov">
        </div>
        <div id="reward-preview" class="media-preview hidden"></div>
      </div>
      <div class="form-group">
        <label>Caption (optional)</label>
        <input type="text" name="reward_file_caption" class="inv-field"
               value="<?= e($pRewardCap) ?>" placeholder="Shown under the reward media">
      </div>
    </div>
  </div>

  <div class="form-actions">
    <a href="<?= url('admin/sequences/' . $seqId . '/clues') ?>" class="btn btn-ghost">&#8592; Back to Clues</a>
    <button type="submit" class="btn btn-primary"><?= $isNew ? 'Create Puzzle' : 'Save Puzzle' ?></button>
  </div>
</form>

<?php if (!$isNew): ?>
<div class="card mt-4 card-danger">
  <div class="card-header"><h2>Danger Zone</h2></div>
  <div class="card-body">
    <form method="POST" action="<?= url('admin/clues/' . $clueId . '/puzzle/delete') ?>">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-danger btn-full"
        onclick="return confirm('Delete this puzzle page? This cannot be undone.')">
        &#10005; Delete Puzzle Page
      </button>
    </form>
  </div>
</div>
<?php endif; ?>

<style>
.inv-field{width:100%;padding:.55rem .75rem;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);border-radius:7px;color:inherit;font-family:inherit;font-size:.9rem}
.inv-field:focus{outline:none;border-color:rgba(255,255,255,.3)}
.sms-textarea{width:100%;padding:.55rem .75rem;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);border-radius:7px;color:inherit;font-family:inherit;font-size:.9rem;resize:vertical;line-height:1.5}
.inv-hint{display:block;font-size:.78rem;color:rgba(255,255,255,.45)}
.map-marker-row{display:flex;gap:.5rem;margin-bottom:.5rem;align-items:center}
.map-marker-num{width:24px;height:24px;flex:none;border-radius:50%;background:#6c63ff;color:#fff;font-size:.78rem;font-weight:700;display:flex;align-items:center;justify-content:center}
.map-marker-row .inv-field{flex:1}
.input-prefix{display:flex;align-items:center}
.input-prefix .prefix-text{padding:.55rem .6rem;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-right:none;border-radius:7px 0 0 7px;color:rgba(255,255,255,.55);font-size:.85rem}
.input-prefix .inv-field,.input-prefix input{border-radius:0 7px 7px 0}
.elim-mark{flex:0 0 auto;display:flex;align-items:center;gap:.35rem;font-size:.78rem;color:rgba(255,255,255,.65);background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.12);border-radius:7px;padding:.4rem .6rem;cursor:pointer;white-space:nowrap;user-select:none}
.elim-mark input{accent-color:#ef4444}
</style>

<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
const REWARD_CONTENT = <?= json_encode($pReward) ?>;
document.addEventListener('DOMContentLoaded', function () {
  const rewQ = initQuill('#reward-editor', REWARD_CONTENT);
  document.getElementById('puzzle-form').addEventListener('submit', function () {
    document.getElementById('reward-content').value = rewQ.root.innerHTML;
  });

  const titleInput = document.getElementById('title-field');
  const slugField  = document.getElementById('slug-field');
  titleInput.addEventListener('input', function () {
    if (slugField.value === '') {
      slugField.value = this.value.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-').substring(0, 80);
    }
  });

  if (typeof setupLocalPreview === 'function') {
    setupLocalPreview('reward-file', 'reward-preview', 'reward-upload-area');
  }

  pzTypeToggle();
});

function pzTypeToggle() {
  var t = document.getElementById('puzzle-type').value;
  document.getElementById('grp-order').style.display  = (t === 'order')  ? '' : 'none';
  document.getElementById('grp-caesar').style.display = (t === 'caesar') ? '' : 'none';
  document.getElementById('grp-phone').style.display  = (t === 'phone')  ? '' : 'none';
  document.getElementById('grp-access').style.display = (t === 'access') ? '' : 'none';
  document.getElementById('grp-elim').style.display   = (t === 'elim')   ? '' : 'none';
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
