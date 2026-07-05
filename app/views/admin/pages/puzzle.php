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
$pEHeading = '';
$pWsWords = array_fill(0, 8, '');
$pWsPhrase = '';
$pMLeft  = array_fill(0, 10, '');
$pMRight = array_fill(0, 10, '');
$pMClues = array_fill(0, 10, '');
$pHsImage  = '';
$pHsSpots  = '[]';
$pHsRadius = 8;
$pHsClues  = array_fill(0, 10, '');
$pFbText       = '';
$pFbDifficulty = 'easy';
$pFbClues      = array_fill(0, 10, '');
if (!empty($inp)) {
    for ($i = 0; $i < 6; $i++)  { $pItems[$i] = $inp['item' . ($i + 1)] ?? ''; }
    for ($i = 0; $i < 10; $i++) { $pClues[$i] = $inp['oclue' . ($i + 1)] ?? ''; }
    $pShift  = (int)($inp['caesar_shift'] ?? 3);
    $pPhrase = $inp['caesar_phrase'] ?? '';
    $pCode   = $inp['phone_code'] ?? '';
    $pAccess = $inp['access_code'] ?? '';
    for ($i = 0; $i < 10; $i++) { $pAClues[$i] = $inp['aclue' . ($i + 1)] ?? ''; }
    $eMarkIn = $inp['elim_mark'] ?? [];
    for ($i = 0; $i < 6; $i++)  { $pEItems[$i] = $inp['eitem' . ($i + 1)] ?? ''; $pEMark[$i] = !empty($eMarkIn[$i + 1]); }
    for ($i = 0; $i < 10; $i++) { $pEClues[$i] = $inp['eclue' . ($i + 1)] ?? ''; }
    $pEHeading = $inp['elim_heading'] ?? '';
    for ($i = 0; $i < 8; $i++)  { $pWsWords[$i] = $inp['wsword' . ($i + 1)] ?? ''; }
    $pWsPhrase = $inp['ws_phrase'] ?? '';
    for ($i = 0; $i < 10; $i++) { $pMLeft[$i] = $inp['mleft' . ($i + 1)] ?? ''; $pMRight[$i] = $inp['mright' . ($i + 1)] ?? ''; }
    for ($i = 0; $i < 10; $i++) { $pMClues[$i] = $inp['mclue' . ($i + 1)] ?? ''; }
    $pHsImage  = $inp['_hotspot_uploaded'] ?? '';
    $pHsSpots  = $inp['hotspot_spots'] ?? '[]';
    $pHsRadius = $inp['hotspot_radius'] ?? 8;
    for ($i = 0; $i < 10; $i++) { $pHsClues[$i] = $inp['hsclue' . ($i + 1)] ?? ''; }
    $pFbText       = (string)($inp['fb_text'] ?? '');
    $pFbDifficulty = (($inp['fb_difficulty'] ?? '') === 'hard') ? 'hard' : 'easy';
    for ($i = 0; $i < 10; $i++) { $pFbClues[$i] = $inp['fbclue' . ($i + 1)] ?? ''; }
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
        $pEHeading = (string)($d['heading'] ?? '');
    }
    if (($puzzle['puzzle_type'] ?? '') === 'wordsearch') {
        foreach (($d['words'] ?? []) as $i => $v) { if ($i < 8) { $pWsWords[$i] = (string)$v; } }
        $pWsPhrase = (string)($d['phrase'] ?? '');
    }
    if (($puzzle['puzzle_type'] ?? '') === 'match') {
        foreach (($d['pairs'] ?? []) as $i => $p) {
            if ($i < 10) {
                $pMLeft[$i]  = (string)($p['l'] ?? '');
                $pMRight[$i] = (string)($p['r'] ?? '');
            }
        }
        foreach (($d['clues'] ?? []) as $i => $v) { if ($i < 10) { $pMClues[$i] = (string)$v; } }
    }
    if (($puzzle['puzzle_type'] ?? '') === 'hotspot') {
        $pHsImage  = (string)($d['image'] ?? '');
        $pHsSpots  = json_encode(array_values($d['spots'] ?? []));
        $pHsRadius = (float)($d['radius'] ?? 8);
        foreach (($d['clues'] ?? []) as $i => $v) { if ($i < 10) { $pHsClues[$i] = (string)$v; } }
    }
    if (($puzzle['puzzle_type'] ?? '') === 'fillblank') {
        $pFbText       = (string)($d['text'] ?? '');
        $pFbDifficulty = (($d['difficulty'] ?? '') === 'hard') ? 'hard' : 'easy';
        foreach (($d['clues'] ?? []) as $i => $v) { if ($i < 10) { $pFbClues[$i] = (string)$v; } }
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
  <div class="analytic-item">
    <a href="<?= url('admin/clues/' . (int)$clue['id'] . '/puzzle/print') ?>" target="_blank" class="btn btn-ghost btn-sm"
       title="Printer-friendly player sheet (no answers)">
      🖨 Print / Save Sheet ↗
    </a>
  </div>
</div>
<?php endif; ?>

<form method="POST" action="<?= url('admin/clues/' . $clueId . '/puzzle') ?>" id="puzzle-form" enctype="multipart/form-data" data-autosave="puzzle-<?= $clueId ?>">
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
            <option value="wordsearch" <?= $pType === 'wordsearch' ? 'selected' : '' ?>>Word Search</option>
            <option value="match"  <?= $pType === 'match'  ? 'selected' : '' ?>>Matching / Connections</option>
            <option value="hotspot" <?= $pType === 'hotspot' ? 'selected' : '' ?>>Hotspot (Mark the Spot)</option>
            <option value="fillblank" <?= $pType === 'fillblank' ? 'selected' : '' ?>>Fill in the Blank</option>
          </select>
        </div>
        <div class="form-group flex-1">
          <label>URL Slug</label>
          <div class="input-prefix">
            <span class="prefix-text">/z/</span>
            <input type="text" name="slug" id="slug-field"
                   value="<?= e($pSlug) ?>" placeholder="leave blank to use the title">
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

  <div id="grp-hotspot">
  <div class="card mt-4">
    <div class="card-header"><h2>Hotspot Image</h2></div>
    <div class="card-body">
      <small class="inv-hint" style="margin-top:0;margin-bottom:.6rem">Upload an image, then click on it to place up to 5 target spots. Drag the slider to set how close a player&rsquo;s click must be. Use the <strong>Prompt</strong> field above to tell players what to look for.</small>
      <div class="form-group">
        <label>Puzzle Image <span class="req">*</span></label>
        <input type="file" name="hotspot_image" id="hs-file" class="inv-field"
               accept=".png,.jpg,.jpeg,.webp,.gif">
        <?php if ($pHsImage !== ''): ?>
        <small class="inv-hint">Current image loaded below. Upload a new file to replace it.</small>
        <?php endif; ?>
      </div>
      <div class="form-group" style="max-width:320px">
        <label>Click tolerance: <span id="hs-radius-val"><?= (float)$pHsRadius ?>%</span></label>
        <input type="range" name="hotspot_radius" id="hs-radius" min="3" max="20" step="1"
               value="<?= (float)$pHsRadius ?>" style="width:100%">
      </div>
      <div class="hs-edit-wrap" id="hs-wrap">
        <div class="hs-edit" id="hs-edit">
          <img id="hs-img" class="hs-edit-img" alt=""
               <?php if ($pHsImage !== ''): ?>src="<?= e(url('uploads/' . $pHsImage)) ?>"<?php else: ?>style="display:none"<?php endif; ?>>
          <div class="hs-edit-layer" id="hs-layer"></div>
        </div>
        <p class="inv-hint" style="margin-top:.5rem">Spots placed: <span id="hs-count">0</span>/5 &mdash; click the image to add a spot, click a marker to remove it.</p>
        <button type="button" class="btn btn-ghost btn-sm" id="hs-clear">Clear spots</button>
      </div>
      <input type="hidden" name="hotspot_spots" id="hs-spots" value="<?= e($pHsSpots) ?>">
    </div>
  </div>

  <div class="card mt-4">
    <div class="card-header"><h2>Hotspot Clues</h2></div>
    <div class="card-body">
      <small class="inv-hint" style="margin-top:0;margin-bottom:.6rem">Up to 10 clue strings shown beside the image to help players figure out where the spots are.</small>
      <?php for ($i = 0; $i < 10; $i++): ?>
      <div class="map-marker-row">
        <span class="map-marker-num"><?= $i + 1 ?></span>
        <input type="text" name="hsclue<?= $i + 1 ?>" class="inv-field"
               placeholder="Hotspot clue <?= $i + 1 ?>" value="<?= e($pHsClues[$i]) ?>">
      </div>
      <?php endfor; ?>
    </div>
  </div>
  </div>

  <div id="grp-match">
  <div class="card mt-4">
    <div class="card-header"><h2>Matching Pairs</h2></div>
    <div class="card-body">
      <small class="inv-hint" style="margin-top:0;margin-bottom:.6rem">Enter up to 10 pairs. The player sees the left items in order and the right items shuffled, and must connect each left to its correct right. Both sides of a pair are required.</small>
      <?php for ($i = 0; $i < 10; $i++): ?>
      <div class="match-pair-row">
        <span class="map-marker-num"><?= $i + 1 ?></span>
        <input type="text" name="mleft<?= $i + 1 ?>" class="inv-field"
               placeholder="Left <?= $i + 1 ?> (e.g. Suspect)" value="<?= e($pMLeft[$i]) ?>">
        <span class="match-pair-link" aria-hidden="true">&harr;</span>
        <input type="text" name="mright<?= $i + 1 ?>" class="inv-field"
               placeholder="Right <?= $i + 1 ?> (e.g. Alibi)" value="<?= e($pMRight[$i]) ?>">
      </div>
      <?php endfor; ?>
    </div>
  </div>

  <div class="card mt-4">
    <div class="card-header"><h2>Matching Clues</h2></div>
    <div class="card-body">
      <small class="inv-hint" style="margin-top:0;margin-bottom:.6rem">Up to 10 clue strings shown beside the columns to help players deduce the connections.</small>
      <?php for ($i = 0; $i < 10; $i++): ?>
      <div class="map-marker-row">
        <span class="map-marker-num"><?= $i + 1 ?></span>
        <input type="text" name="mclue<?= $i + 1 ?>" class="inv-field"
               placeholder="Matching clue <?= $i + 1 ?>" value="<?= e($pMClues[$i]) ?>">
      </div>
      <?php endfor; ?>
    </div>
  </div>
  </div>

  <div id="grp-wordsearch">
  <div class="card mt-4">
    <div class="card-header"><h2>Word Search</h2></div>
    <div class="card-body">
      <small class="inv-hint" style="margin-top:0;margin-bottom:.6rem">Enter up to 8 words to hide (letters only, 2&ndash;12 characters each). They&rsquo;re placed across, down, and diagonally; the player drags across the grid to find them.</small>
      <?php for ($i = 0; $i < 8; $i++): ?>
      <div class="map-marker-row">
        <span class="map-marker-num"><?= $i + 1 ?></span>
        <input type="text" name="wsword<?= $i + 1 ?>" class="inv-field code-upper"
               maxlength="12" placeholder="Word <?= $i + 1 ?>" value="<?= e($pWsWords[$i]) ?>">
      </div>
      <?php endfor; ?>
      <div class="form-group" style="margin-top:1rem">
        <label>Hidden Phrase <span class="req">*</span></label>
        <input type="text" name="ws_phrase" class="inv-field" maxlength="100"
               value="<?= e($pWsPhrase) ?>" placeholder="The leftover letters spell this, e.g. THE BUTLER DID IT">
        <small class="inv-hint">After the player finds every word, the remaining grid letters spell this phrase (letters only, up to 80; spaces &amp; punctuation are ignored), followed by <strong>ZZ</strong> to mark where it ends. The player reads it and types it in to finish. Avoid phrases ending in &ldquo;Z&rdquo;.</small>
      </div>
    </div>
  </div>
  </div>

  <div id="grp-elim">
  <div class="card mt-4">
    <div class="card-header"><h2>Elimination Items</h2></div>
    <div class="card-body">
      <div class="form-group" style="margin-bottom:1rem">
        <label>List Heading</label>
        <input type="text" name="elim_heading" class="inv-field"
               value="<?= e($pEHeading) ?>" placeholder="Eliminate the wrong items">
        <small class="inv-hint">Shown above the list of items. Leave blank to use &ldquo;Eliminate the wrong items&rdquo;.</small>
      </div>
      <small class="inv-hint" style="margin-top:0;margin-bottom:.6rem">Enter up to 6 items. Tick the <strong>Eliminate</strong> box for each item the player must eliminate (at most 5). The order players see is shuffled.</small>
      <?php for ($i = 0; $i < 6; $i++): ?>
      <div class="map-marker-row">
        <span class="map-marker-num"><?= $i + 1 ?></span>
        <input type="text" name="eitem<?= $i + 1 ?>" class="inv-field"
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

  <div id="grp-fillblank">
  <div class="card mt-4">
    <div class="card-header"><h2>Fill-in-the-Blank Text</h2></div>
    <div class="card-body">
      <div class="form-group">
        <label>Difficulty</label>
        <div style="display:flex;gap:1.25rem;flex-wrap:wrap;align-items:center;margin-top:.35rem">
          <label class="checkbox-label" style="cursor:pointer">
            <input type="radio" name="fb_difficulty" value="easy" <?= $pFbDifficulty === 'easy' ? 'checked' : '' ?>>
            <span><strong>Easy</strong> &mdash; word bank shown; players drag words into blanks</span>
          </label>
          <label class="checkbox-label" style="cursor:pointer">
            <input type="radio" name="fb_difficulty" value="hard" <?= $pFbDifficulty === 'hard' ? 'checked' : '' ?>>
            <span><strong>Hard</strong> &mdash; no word bank; players type the missing words</span>
          </label>
        </div>
      </div>
      <div class="form-group">
        <label>Puzzle Text <span class="req">*</span></label>
        <textarea name="fb_text" class="inv-field" rows="6"
                  placeholder="Write your passage. Wrap each blank word in braces, e.g.&#10;The quick {brown} fox jumps over the {lazy} dog."
                  style="font-family:'Courier New',Courier,monospace"><?= e($pFbText) ?></textarea>
        <small class="inv-hint">Wrap each word that should become a blank in <code>{curly braces}</code>. Up to <strong>10 blanks</strong> are kept; extras are ignored. Matching is case-insensitive.</small>
      </div>
    </div>
  </div>
  <div class="card mt-4">
    <div class="card-header"><h2>Fill-in-the-Blank Clues</h2></div>
    <div class="card-body">
      <small class="inv-hint" style="margin-top:0;margin-bottom:.6rem">Up to 10 clues shown beside the puzzle to help players figure out the missing words.</small>
      <?php for ($i = 0; $i < 10; $i++): ?>
      <div class="map-marker-row">
        <span class="map-marker-num"><?= $i + 1 ?></span>
        <input type="text" name="fbclue<?= $i + 1 ?>" class="inv-field"
               placeholder="Clue <?= $i + 1 ?>" value="<?= e($pFbClues[$i]) ?>">
      </div>
      <?php endfor; ?>
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
.match-pair-row{display:flex;gap:.5rem;margin-bottom:.5rem;align-items:center}
.match-pair-row .inv-field{flex:1;min-width:0}
.match-pair-link{flex:0 0 auto;color:#6c63ff;font-size:1.1rem;font-weight:700}
.hs-edit{position:relative;display:inline-block;max-width:100%;line-height:0;border:1px solid rgba(255,255,255,.14);border-radius:8px;overflow:hidden}
.hs-edit-img{display:block;max-width:100%;height:auto}
.hs-edit-layer{position:absolute;inset:0;cursor:crosshair}
.hs-spot{position:absolute;transform:translate(-50%,-50%);width:24px;height:24px;border-radius:50%;border:2px solid #fff;background:#6c63ff;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.72rem;font-weight:700;cursor:pointer;z-index:2;box-shadow:0 1px 4px rgba(0,0,0,.4)}
.hs-radius{position:absolute;transform:translate(-50%,-50%);border-radius:50%;border:1px dashed rgba(108,99,255,.7);background:rgba(108,99,255,.14);pointer-events:none;z-index:1}
</style>

<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
const REWARD_CONTENT = <?= json_encode($pReward) ?>;
const SEQ_SLUG = <?= json_encode(slugify(is_array($sequence ?? null) ? ($sequence['title'] ?? '') : '')) ?>;
const IS_NEW   = <?= $isNew ? 'true' : 'false' ?>;
let slugEdited = false;

// For a new puzzle, default the slug to "<sequence-title>-<puzzle-type>",
// keeping it in sync with the type until the admin edits the slug themselves.
function autoPageSlug() {
  if (!IS_NEW || slugEdited) return;
  const t = document.getElementById('puzzle-type').value;
  document.getElementById('slug-field').value = (SEQ_SLUG ? SEQ_SLUG + '-' : '') + t;
}

document.addEventListener('DOMContentLoaded', function () {
  const rewQ = initQuill('#reward-editor', REWARD_CONTENT);
  document.getElementById('puzzle-form').addEventListener('submit', function () {
    document.getElementById('reward-content').value = rewQ.root.innerHTML;
  });

  const slugField = document.getElementById('slug-field');
  slugField.addEventListener('input', function () { slugEdited = true; });

  if (typeof setupLocalPreview === 'function') {
    setupLocalPreview('reward-file', 'reward-preview', 'reward-upload-area');
  }

  autoPageSlug();
  pzTypeToggle();
});

function pzTypeToggle() {
  autoPageSlug();
  var t = document.getElementById('puzzle-type').value;
  document.getElementById('grp-order').style.display  = (t === 'order')  ? '' : 'none';
  document.getElementById('grp-caesar').style.display = (t === 'caesar') ? '' : 'none';
  document.getElementById('grp-phone').style.display  = (t === 'phone')  ? '' : 'none';
  document.getElementById('grp-access').style.display = (t === 'access') ? '' : 'none';
  document.getElementById('grp-elim').style.display   = (t === 'elim')   ? '' : 'none';
  document.getElementById('grp-wordsearch').style.display = (t === 'wordsearch') ? '' : 'none';
  document.getElementById('grp-match').style.display  = (t === 'match')  ? '' : 'none';
  document.getElementById('grp-hotspot').style.display = (t === 'hotspot') ? '' : 'none';
  document.getElementById('grp-fillblank').style.display = (t === 'fillblank') ? '' : 'none';
}

// ── Hotspot spot-placement editor ─────────────────────────────────────────
(function () {
  var wrap = document.getElementById('hs-wrap');
  if (!wrap) return;
  var fileInput  = document.getElementById('hs-file');
  var img        = document.getElementById('hs-img');
  var layer      = document.getElementById('hs-layer');
  var radiusEl   = document.getElementById('hs-radius');
  var radiusVal  = document.getElementById('hs-radius-val');
  var spotsInput = document.getElementById('hs-spots');
  var countEl    = document.getElementById('hs-count');
  var clearBtn   = document.getElementById('hs-clear');
  var form       = document.getElementById('puzzle-form');
  var MAX = 5;

  var spots = [];
  try { spots = JSON.parse(spotsInput.value || '[]') || []; } catch (e) { spots = []; }

  function radius() { return parseFloat(radiusEl.value) || 8; }

  function render() {
    layer.innerHTML = '';
    spots.forEach(function (s, i) {
      var ring = document.createElement('div');
      ring.className = 'hs-radius';
      ring.style.left = s.x + '%'; ring.style.top = s.y + '%';
      ring.style.width = (radius() * 2) + '%'; ring.style.height = (radius() * 2) + '%';
      layer.appendChild(ring);
      var dot = document.createElement('div');
      dot.className = 'hs-spot';
      dot.style.left = s.x + '%'; dot.style.top = s.y + '%';
      dot.textContent = (i + 1);
      dot.setAttribute('data-i', i);
      layer.appendChild(dot);
    });
    if (countEl) { countEl.textContent = spots.length; }
    spotsInput.value = JSON.stringify(spots);
  }

  layer.addEventListener('click', function (e) {
    var sp = e.target.closest('.hs-spot');
    if (sp) { spots.splice(parseInt(sp.getAttribute('data-i'), 10), 1); render(); return; }
    if (!img.getAttribute('src') || img.style.display === 'none') { return; }
    if (spots.length >= MAX) { return; }
    var rect = layer.getBoundingClientRect();
    var x = (e.clientX - rect.left) / rect.width * 100;
    var y = (e.clientY - rect.top) / rect.height * 100;
    spots.push({ x: Math.round(Math.max(0, Math.min(100, x)) * 100) / 100,
                 y: Math.round(Math.max(0, Math.min(100, y)) * 100) / 100 });
    render();
  });

  radiusEl.addEventListener('input', function () { if (radiusVal) { radiusVal.textContent = radius() + '%'; } render(); });
  if (clearBtn) { clearBtn.addEventListener('click', function () { spots = []; render(); }); }

  fileInput.addEventListener('change', function () {
    var f = fileInput.files && fileInput.files[0];
    if (!f) { return; }
    var rd = new FileReader();
    rd.onload = function (ev) { img.src = ev.target.result; img.style.display = 'block'; render(); };
    rd.readAsDataURL(f);
  });

  if (form) { form.addEventListener('submit', function () { spotsInput.value = JSON.stringify(spots); }); }
  render();
})();
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
