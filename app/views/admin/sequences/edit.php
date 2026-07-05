<?php
$pageTitle = 'Edit: ' . ($sequence['title'] ?? '');
$activeNav = 'sequences';
ob_start();
$seqId = (int)$sequence['id'];
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
  <strong>Please fix the following:</strong>
  <ul><?php foreach ($errors as $e_msg): ?><li><?= e($e_msg) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<!-- Analytics strip -->
<div class="analytics-strip">
  <div class="analytic-item">
    <span class="analytic-icon">👁</span>
    <strong><?= number_format((int)$sequence['view_count']) ?></strong>
    <span class="muted">Views</span>
  </div>
  <div class="analytic-item">
    <span class="analytic-icon">🏆</span>
    <strong><?= number_format((int)$sequence['completion_count']) ?></strong>
    <span class="muted">Completions</span>
  </div>
  <div class="analytic-item">
    <span class="analytic-icon">⏱</span>
    <strong><?= gmdate('H:i:s', (int)($analytics['avg_time'] ?? 0)) ?></strong>
    <span class="muted">Avg time</span>
  </div>
  <div class="analytic-item">
    <a href="<?= url('s/' . $sequence['slug']) ?>" target="_blank" class="btn btn-ghost btn-sm"
       title="Opens the live page. As an admin your views &amp; completions aren't counted, and the codes are shown so you can walk the whole sequence.">
      🛠️ Play-test ↗
    </a>
  </div>
  <div class="analytic-item">
    <button type="button" class="btn btn-ghost btn-sm" id="copy-url-btn"
            data-url="<?= e(url('s/' . $sequence['slug'])) ?>"
            onclick="copySequenceUrl(this)">
      📋 Copy URL
    </button>
  </div>
  <div class="analytic-item">
    <a href="<?= url('admin/sequences/' . $seqId . '/print') ?>" target="_blank" class="btn btn-ghost btn-sm"
       title="Printable QR codes for the start link, puzzles and pages">
      🔳 QR Kit ↗
    </a>
  </div>
  <div class="analytic-item">
    <a href="<?= url('admin/sequences/' . $seqId . '/answer-guide') ?>" target="_blank" class="btn btn-ghost btn-sm"
       title="Download a PDF answer key: every code and puzzle answer in play order">
      📥 Download Answer Guide
    </a>
  </div>
  <div class="analytic-item">
    <a href="<?= url('admin/sequences/' . $seqId . '/overview') ?>" class="btn btn-ghost btn-sm"
       title="See the whole mystery flow at a glance">
      🗺 Overview
    </a>
  </div>
  <div class="analytic-item">
    <a href="<?= url('admin/sequences/' . $seqId . '/clues') ?>" class="btn btn-secondary btn-sm js-leave-guard">
      Manage Clues
    </a>
  </div>
</div>

<form method="POST" action="<?= url('admin/sequences/' . $seqId . '/edit') ?>" enctype="multipart/form-data" id="sequence-form" data-autosave="seq-<?= $seqId ?>">
  <?= csrf_field() ?>

  <div class="form-grid">
    <div class="form-col">
      <!-- Details -->
      <div class="card">
        <div class="card-header"><h2>Sequence Details</h2></div>
        <div class="card-body">
          <div class="form-group">
            <label>Title <span class="req">*</span></label>
            <input type="text" name="title" required value="<?= e($sequence['title']) ?>">
          </div>
          <div class="form-group">
            <label>Slug</label>
            <div class="input-prefix">
              <span class="prefix-text">/s/</span>
              <input type="text" name="slug" value="<?= e($sequence['slug']) ?>"
                     placeholder="leave blank to use the title">
            </div>
            <small>Change carefully — existing links will break. Leave blank to regenerate from the title.</small>
          </div>
          <div class="form-group">
            <label>Description</label>
            <div class="quill-editor" id="desc-editor"></div>
            <input type="hidden" name="description" id="desc-content">
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label>Type</label>
              <select name="type" id="seq-type">
                <option value="sequential" <?= $sequence['type'] === 'sequential' ? 'selected' : '' ?>>Sequential</option>
                <option value="open" <?= $sequence['type'] === 'open' ? 'selected' : '' ?>>Open</option>
                <option value="gameboard" <?= $sequence['type'] === 'gameboard' ? 'selected' : '' ?>>Game Board</option>
                <option value="whodunit" <?= $sequence['type'] === 'whodunit' ? 'selected' : '' ?>>Who-dun-it (Deduction)</option>
                <option value="interactive" <?= $sequence['type'] === 'interactive' ? 'selected' : '' ?>>Interactive (Group / Suspects)</option>
              </select>
            </div>
          </div>
          <div class="form-row" id="gb-theme-row"<?= ($sequence['type'] ?? '') === 'gameboard' ? '' : ' style="display:none"' ?>>
            <div class="form-group flex-1">
              <label>Game Board Theme</label>
              <?php $gbTheme = $sequence['gameboard_theme'] ?? 'candyland'; ?>
              <select name="gameboard_theme" id="seq-gb-theme">
                <option value="candyland" <?= $gbTheme === 'candyland' ? 'selected' : '' ?>>Candy Land</option>
                <option value="winter" <?= $gbTheme === 'winter' ? 'selected' : '' ?>>Winter Wonderland</option>
                <option value="spooky" <?= $gbTheme === 'spooky' ? 'selected' : '' ?>>Scary Spooky Night</option>
                <option value="pool" <?= $gbTheme === 'pool' ? 'selected' : '' ?>>Summer Time Pool Party</option>
                <option value="birthday" <?= $gbTheme === 'birthday' ? 'selected' : '' ?>>Birthday Party</option>
              </select>
              <small>Styles the board, tiles, start/finish and clue cards to match the theme.</small>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label>Start Code <span class="req">*</span></label>
              <input type="text" name="start_code" class="code-upper" required value="<?= e($sequence['start_code']) ?>">
              <small>Players enter this to access the mystery.</small>
            </div>
<?php $noSolCode = in_array($sequence['type'] ?? '', ['whodunit', 'interactive'], true); ?>
            <div class="form-group flex-1" id="solcode-field"<?= $noSolCode ? ' style="display:none"' : '' ?>>
              <label>Solution Code</label>
              <input type="text" name="finale_code" class="code-upper" value="<?= e($sequence['finale_code'] ?? '') ?>">
              <small>Entered on &ldquo;Ready to Solve?&rdquo; to reveal the Solution.</small>
            </div>
          </div>
          <div class="form-row" id="reqcode-row"<?= $noSolCode ? ' style="display:none"' : '' ?>>
            <div class="form-group flex-1">
              <label class="checkbox-label">
                <input type="checkbox" name="finale_requires_code" value="1"
                  <?= $sequence['finale_requires_code'] ? 'checked' : '' ?>>
                Require Solution Code
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Introduction (collapsible) -->
      <details class="card mt-4 seq-section">
        <summary class="card-header"><h2>Introduction</h2><span class="seq-chevron">▾</span></summary>
        <div class="card-body">
          <div class="form-group">
            <label>Introduction Access Code</label>
            <input type="text" name="intro_access_code" class="code-upper" value="<?= e($sequence['intro_access_code'] ?? '') ?>">
            <small>Entered on the Introduction to unlock Clue 1.</small>
          </div>
          <div class="form-group">
            <label>Gate Instruction</label>
            <textarea name="intro_instruction" rows="2" class="form-input"
                      placeholder="Shown above the code box on the Introduction (e.g. &ldquo;Enter the code to unlock Clue 1&rdquo;). Leave blank for none."><?= e($sequence['intro_instruction'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label>Content</label>
            <div class="quill-editor" id="intro-editor"></div>
            <input type="hidden" name="introduction_content" id="intro-content">
          </div>
          <div class="form-group">
            <label>Attached Media</label>
            <?php if ($sequence['intro_file_path']): ?>
            <div class="current-media" id="intro-current-media">
              <?= renderMediaWidget($sequence, 'intro', $seqId, 'sequence') ?>
            </div>
            <?php else: ?>
            <div class="upload-area" id="intro-upload-area">
              <div class="upload-placeholder">
                <span>📁 Drag & drop or click to upload</span>
                <span class="small muted">PNG, JPG, PDF, MP3, WAV, OGG — max 10MB</span>
              </div>
              <input type="file" name="intro_file" id="intro-file" class="upload-input"
                     accept=".png,.jpg,.jpeg,.pdf,.mp3,.wav,.ogg,.m4a,.mov">
            </div>
            <div id="intro-preview" class="media-preview hidden"></div>
            <?php endif; ?>
          </div>
          <h3 class="seq-subhead" style="margin-top:1.5rem">Introduction Hint</h3>
          <p class="small muted" style="margin-bottom:.75rem">Optional. Shown on the &ldquo;Unlock Clue 1&rdquo; screen behind a &ldquo;Need a hint?&rdquo; toggle &mdash; helps players work out the Introduction Access Code.</p>
          <div class="form-group">
            <div class="quill-editor" id="intro-hint-editor"></div>
            <input type="hidden" name="intro_hint_text" id="intro-hint-content">
          </div>
          <div class="form-group">
            <label>Attached Media</label>
            <?php if ($sequence['intro_hint_file_path']): ?>
            <div class="current-media" id="intro-hint-current-media">
              <?= renderMediaWidget($sequence, 'intro_hint', $seqId, 'sequence') ?>
            </div>
            <?php else: ?>
            <div class="upload-area" id="intro-hint-upload-area">
              <div class="upload-placeholder">
                <span>📁 Drag & drop or click to upload</span>
                <span class="small muted">PNG, JPG, PDF, MP3, WAV, OGG — max 10MB</span>
              </div>
              <input type="file" name="intro_hint_file" id="intro-hint-file" class="upload-input"
                     accept=".png,.jpg,.jpeg,.pdf,.mp3,.wav,.ogg,.m4a,.mov">
            </div>
            <div id="intro-hint-preview" class="media-preview hidden"></div>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label>Caption (optional)</label>
            <input type="text" name="intro_hint_caption" class="form-input"
                   value="<?= e($sequence['intro_hint_caption'] ?? '') ?>"
                   placeholder="Shown under the hint media">
          </div>
        </div>
      </details>

      <!-- Finale (collapsible) -->
      <details class="card mt-4 seq-section">
        <summary class="card-header"><h2>Finale</h2><span class="seq-chevron">▾</span></summary>
        <div class="card-body">
          <div class="form-group">
            <label>Content</label>
            <div class="quill-editor" id="finale-editor"></div>
            <input type="hidden" name="finale_content" id="finale-content">
            <small>Rich text shown under the &ldquo;Ready to Solve?&rdquo; heading. Leave blank for none.</small>
          </div>
          <div class="form-group">
            <label>Gate Instruction</label>
            <textarea name="finale_instruction" rows="2" class="form-input"
                      placeholder="Shown above the Solution Code box (e.g. &ldquo;Enter the Solution Code to reveal the solution&rdquo;). Leave blank for none."><?= e($sequence['finale_instruction'] ?? '') ?></textarea>
          </div>
          <h3 class="seq-subhead" style="margin-top:1.5rem">Finale Hint</h3>
          <p class="small muted" style="margin-bottom:.75rem">Optional. Shown on the &ldquo;Ready to Solve?&rdquo; screen behind a &ldquo;Need a hint?&rdquo; toggle &mdash; helps players work out the Solution Code.</p>
          <div class="form-group">
            <div class="quill-editor" id="finale-hint-editor"></div>
            <input type="hidden" name="finale_hint_text" id="finale-hint-content">
          </div>
          <div class="form-group">
            <label>Attached Media</label>
            <?php if ($sequence['finale_hint_file_path']): ?>
            <div class="current-media" id="finale-hint-current-media">
              <?= renderMediaWidget($sequence, 'finale_hint', $seqId, 'sequence') ?>
            </div>
            <?php else: ?>
            <div class="upload-area" id="finale-hint-upload-area">
              <div class="upload-placeholder">
                <span>📁 Drag & drop or click to upload</span>
                <span class="small muted">PNG, JPG, PDF, MP3, WAV, OGG — max 10MB</span>
              </div>
              <input type="file" name="finale_hint_file" id="finale-hint-file" class="upload-input"
                     accept=".png,.jpg,.jpeg,.pdf,.mp3,.wav,.ogg,.m4a,.mov">
            </div>
            <div id="finale-hint-preview" class="media-preview hidden"></div>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label>Caption (optional)</label>
            <input type="text" name="finale_hint_caption" class="form-input"
                   value="<?= e($sequence['finale_hint_caption'] ?? '') ?>"
                   placeholder="Shown under the hint media">
          </div>
        </div>
      </details>

      <!-- Solution (collapsible) -->
      <details class="card mt-4 seq-section" id="solution-card">
        <summary class="card-header"><h2>Solution</h2><span class="seq-chevron">▾</span></summary>
        <div class="card-body">
          <p class="small muted" style="margin-bottom:.75rem">Revealed when the mystery is solved — after the Solution Code (or, for Who-dun-it, a correct accusation).</p>
          <div class="form-group">
            <label>Content</label>
            <div class="quill-editor" id="solution-editor"></div>
            <input type="hidden" name="solution_content" id="solution-content">
          </div>
          <div class="form-group">
            <label>Attached Media</label>
            <?php if ($sequence['solution_file_path']): ?>
            <div class="current-media" id="solution-current-media">
              <?= renderMediaWidget($sequence, 'solution', $seqId, 'sequence') ?>
            </div>
            <?php else: ?>
            <div class="upload-area" id="solution-upload-area">
              <div class="upload-placeholder">
                <span>📁 Drag & drop or click to upload</span>
                <span class="small muted">PNG, JPG, PDF, MP3, WAV, OGG — max 10MB</span>
              </div>
              <input type="file" name="solution_file" id="solution-file" class="upload-input"
                     accept=".png,.jpg,.jpeg,.pdf,.mp3,.wav,.ogg,.m4a,.mov">
            </div>
            <div id="solution-preview" class="media-preview hidden"></div>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label>Caption (optional)</label>
            <input type="text" name="solution_caption" class="form-input"
                   value="<?= e($sequence['solution_caption'] ?? '') ?>"
                   placeholder="Shown under the solution media">
          </div>
        </div>
      </details>

      <!-- Thank You (collapsible) -->
      <details class="card mt-4 seq-section">
        <summary class="card-header"><h2>Thank You</h2><span class="seq-chevron">▾</span></summary>
        <div class="card-body">
          <p class="small muted" style="margin-bottom:.75rem">Appears as a pop-up 10 seconds after the Solution is revealed, then stays under the Solution.</p>
          <div class="form-group">
            <div class="quill-editor" id="thankyou-editor"></div>
            <input type="hidden" name="thank_you_content" id="thankyou-content">
          </div>
        </div>
      </details>

      <!-- Survey Link (collapsible) -->
      <details class="card mt-4 seq-section">
        <summary class="card-header"><h2>Survey Link</h2><span class="seq-chevron">▾</span></summary>
        <div class="card-body">
          <p class="small muted" style="margin-bottom:.75rem">If set, players will see an invitation to take a survey after the solution is revealed. Leave blank to hide it.</p>
          <div class="form-group">
            <label for="survey-link-input">Survey URL</label>
            <input type="url" id="survey-link-input" name="survey_link"
                   value="<?= e($sequence['survey_link'] ?? '') ?>"
                   placeholder="https://forms.example.com/your-survey"
                   style="width:100%">
          </div>
        </div>
      </details>
    </div>

    <!-- Theme column -->
    <div class="form-col-sm">
      <div class="sticky-top">

      <?php $isPub = (bool)$sequence['published']; ?>
      <button type="button" id="publish-btn" class="btn btn-full pub-form <?= $isPub ? 'btn-unpublish' : 'btn-publish' ?>" onclick="togglePublishSeq()">
        <?= $isPub ? '⏸ Unpublish' : '🚀 Publish' ?>
      </button>
      <div class="pub-status <?= $isPub ? 'is-pub' : 'is-draft' ?>">
        <?php if ($isPub): ?>
          Published<?= !empty($sequence['published_at']) ? ' &middot; ' . e(formatDate($sequence['published_at'], 'M j, Y')) : '' ?>
        <?php else: ?>
          Draft created &middot; <?= e(formatDate($sequence['created_at'], 'M j, Y')) ?>
        <?php endif; ?>
      </div>
      <div class="form-group pub-expires">
        <label>Expires At</label>
        <input type="datetime-local" name="expires_at"
               value="<?= $sequence['expires_at'] ? date('Y-m-d\TH:i', strtotime($sequence['expires_at'])) : '' ?>">
        <small class="muted">Leave blank for no expiry.</small>
      </div>

      <?php
      $health = $health ?? [];
      $hHasError = (bool)array_filter($health, static fn ($x) => $x['level'] === 'error');
      $hState = !empty($health) ? ($hHasError ? 'error' : 'warn') : 'ok';
      $hSummary = count($health) . ' ' . ($hHasError ? 'issue' : 'suggestion') . (count($health) === 1 ? '' : 's');
      ?>
      <?php if (empty($health)): ?>
      <div class="card hc-card hc-ok">
        <div class="card-header">
          <h2>🩺 Health Check</h2>
          <span class="hc-badge hc-badge-ok">Ready to play</span>
        </div>
        <div class="card-body">
          <p class="hc-ok-msg">✓ Everything checks out — this sequence is ready for players.</p>
        </div>
      </div>
      <?php else: ?>
      <details class="hc hc-<?= $hState ?>"<?= $hHasError ? ' open' : '' ?>>
        <summary>🩺 Health check &middot; <span class="hc-sum"><?= $hSummary ?></span></summary>
        <ul class="hc-list">
          <?php foreach ($health as $h): ?>
          <li class="hc-<?= e($h['level']) ?>"><?= $h['level'] === 'error' ? '⛔' : '⚠️' ?> <?= e($h['msg']) ?></li>
          <?php endforeach; ?>
        </ul>
      </details>
      <?php endif; ?>
      <style>
      /* Full card for the all-clear state */
      .hc-card{margin-bottom:1.5rem;border-left:4px solid #4cc9a0}
      .hc-badge{font-size:.72rem;font-weight:700;padding:.25rem .7rem;border-radius:20px;text-transform:uppercase;letter-spacing:.06em}
      .hc-badge-ok{background:rgba(76,201,160,.18);color:#4cc9a0}
      .hc-ok-msg{color:#86e7c1}
      /* Compact collapsible for issues / suggestions */
      .hc{margin-bottom:1.25rem;border:1px solid rgba(255,255,255,.12);border-left:3px solid #fbbf24;border-radius:8px;background:rgba(255,255,255,.03)}
      .hc.hc-error{border-left-color:#ef4444}
      .hc>summary{cursor:pointer;padding:.5rem .85rem;list-style:none;color:rgba(255,255,255,.78);font-weight:600;font-size:.86rem}
      .hc>summary::-webkit-details-marker{display:none}
      .hc-warn .hc-sum{color:#fbbf24}
      .hc-error .hc-sum{color:#f87171}
      .hc-list{list-style:none;margin:0;padding:.15rem .85rem .7rem;display:flex;flex-direction:column;gap:.35rem}
      .hc-list li{font-size:.83rem;line-height:1.35;color:rgba(255,255,255,.8)}
      .hc-list li.hc-error{color:#fca5a5}
      </style>

      <a href="<?= url('admin/sequences/' . $seqId . '/whodunnit') ?>" id="whodunnit-setup-btn"
         class="btn btn-blue btn-full js-leave-guard" style="margin-bottom:1.25rem;<?= ($sequence['type'] ?? '') === 'whodunit' ? '' : 'display:none' ?>">
        🕵️ Whodunnit Setup
      </a>

      <a href="<?= url('admin/sequences/' . $seqId . '/interactive') ?>" id="interactive-setup-btn"
         class="btn btn-blue btn-full js-leave-guard" style="margin-bottom:1.25rem;<?= ($sequence['type'] ?? '') === 'interactive' ? '' : 'display:none' ?>">
        🎭 Interactive Setup
      </a>

      <div class="card">
        <div class="card-header"><h2>Theme</h2></div>
        <div class="card-body">
          <?php $t = $theme; ?>
          <div class="color-grid">
            <?php
            $colorFields = [
              'bg_color'       => ['Background', $t['bg_color']],
              'text_color'     => ['Text', $t['text_color']],
              'button_color'   => ['Button', $t['button_color']],
              'btn_text_color' => ['Button Text', $t['btn_text_color']],
              'accent_color'   => ['Accent', $t['accent_color']],
            ];
            foreach ($colorFields as $name => [$label, $val]): ?>
            <div class="form-group">
              <label><?= $label ?></label>
              <div class="color-input-wrap">
                <input type="color" name="<?= $name ?>" value="<?= e($val) ?>" class="color-swatch" id="<?= $name ?>">
                <input type="text" class="color-hex" value="<?= e($val) ?>" data-for="<?= $name ?>">
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="form-group">
            <label>Font Family</label>
            <select name="font_family">
              <?php
              $fonts = [
                'Inter, sans-serif'           => 'Inter',
                "'Courier New', monospace"    => 'Courier New (Mono)',
                'Georgia, serif'              => 'Georgia (Serif)',
                "'Cinzel', serif"             => 'Cinzel (Dramatic)',
                "'Special Elite', cursive"    => 'Special Elite (Typewriter)',
                "'Bangers', cursive"          => 'Bangers (Comic)',
                "'Permanent Marker', cursive" => 'Permanent Marker (Hand-lettered)',
                "'Knewave', cursive"          => 'Knewave (Bold script)',
                "'Another Danger', sans-serif"=> 'Another Danger (Display)',
                "'Brush King', cursive"       => 'Brush King (Brush)',
                "'Playlist Script', cursive"  => 'Playlist Script (Script)',
              ];
              foreach ($fonts as $val => $label): ?>
              <option value="<?= e($val) ?>" <?= $t['font_family'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Title Font <span class="muted">(the sequence title)</span></label>
            <select name="title_font">
              <option value="" <?= empty($t['title_font']) ? 'selected' : '' ?>>— Same as body font —</option>
              <?php foreach ($fonts as $val => $label): ?>
              <option value="<?= e($val) ?>" <?= ($t['title_font'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <?php $titleColorVal = $t['title_color'] ?: ($t['text_color'] ?? '#ffffff'); ?>
            <label>Title Color <span class="muted">(defaults to the body text colour)</span></label>
            <div class="color-input-wrap">
              <input type="color" name="title_color" value="<?= e($titleColorVal) ?>" class="color-swatch" id="title_color">
              <input type="text" class="color-hex" value="<?= e($titleColorVal) ?>" data-for="title_color">
            </div>
          </div>
          <div class="form-group">
            <label>Container Width</label>
            <select name="container_width">
              <?php foreach (['640px' => '640px (Narrow)', '800px' => '800px (Default)', '960px' => '960px (Wide)', '100%' => 'Full Width'] as $val => $label): ?>
              <option value="<?= e($val) ?>" <?= $t['container_width'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Custom CSS</label>
            <textarea name="custom_css" rows="5"><?= e($t['custom_css'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <div class="card mt-4">
        <div class="card-header"><h2>Statistics</h2></div>
        <div class="card-body">
          <p class="small muted" style="margin-bottom:.75rem">Reset this sequence's views, completions, and average time to zero.</p>
          <button type="button" class="btn btn-danger btn-full" id="reset-stats-btn"
            onclick="resetStats('<?= url('admin/sequences/' . $seqId . '/reset-stats') ?>')">
            &#8635; Reset Statistics
          </button>
        </div>
      </div>
      </div>

    </div>
  </div>

  <div class="form-actions">
    <a href="<?= url('admin/sequences') ?>" class="btn btn-ghost">← Back</a>
    <button type="submit" class="btn btn-primary">Save Changes</button>
  </div>
</form>

<!-- Danger zone lives OUTSIDE the main form to avoid nested-form breakage -->
<div class="card mt-4 card-danger">
  <div class="card-header"><h2>Danger Zone</h2></div>
  <div class="card-body">
    <form method="POST" action="<?= url('admin/sequences/' . $seqId . '/duplicate') ?>">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-secondary btn-full mb-2">⊕ Duplicate Sequence</button>
    </form>
    <button class="btn btn-danger btn-full"
      onclick="confirmDelete('<?= url('admin/sequences/' . $seqId . '/delete') ?>','Delete this sequence permanently? All clues and progress will be lost.')">
      ✕ Delete Sequence
    </button>
  </div>
</div>

<?php
function renderMediaWidget(array $row, string $prefix, int $targetId, string $target): string {
    $path     = $row[$prefix . '_file_path'] ?? '';
    $type     = $row[$prefix . '_file_type'] ?? '';
    $name     = $row[$prefix . '_original_filename'] ?? 'File';
    $size     = (int)($row[$prefix . '_file_size'] ?? 0);
    $url      = UPLOAD_URL . '/' . htmlspecialchars($path);
    $csrf     = Security::generateCsrfToken();
    $slot     = $prefix;

    $preview = match ($type) {
        'image' => "<img src=\"$url\" alt=\"$name\" class=\"media-thumb\">",
        'audio' => "<audio controls src=\"$url\" class=\"media-audio\"></audio>",
        'video' => "<video controls src=\"$url\" class=\"media-video\"></video>",
        'pdf'   => "<a href=\"$url\" target=\"_blank\" class=\"media-pdf-link\">📄 View PDF</a>",
        default => "<a href=\"$url\" target=\"_blank\">📎 $name</a>",
    };

    return <<<HTML
    <div class="media-widget">
      $preview
      <div class="media-meta">
        <span class="media-name">$name</span>
        <span class="media-size muted">{$size}B</span>
      </div>
      <button type="button" class="btn btn-danger btn-xs"
        onclick="deleteMedia('$target', $targetId, '$slot', this.closest('.current-media'))">
        Remove
      </button>
    </div>
    HTML;
}
?>

<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
const INTRO_CONTENT       = <?= json_encode($sequence['introduction_content'] ?? '') ?>;
const INTRO_HINT_CONTENT  = <?= json_encode($sequence['intro_hint_text'] ?? '') ?>;
const FINALE_CONTENT      = <?= json_encode($sequence['finale_content'] ?? '') ?>;
const FINALE_HINT_CONTENT = <?= json_encode($sequence['finale_hint_text'] ?? '') ?>;
const SOLUTION_CONTENT    = <?= json_encode($sequence['solution_content'] ?? '') ?>;
const THANKYOU_CONTENT    = <?= json_encode($sequence['thank_you_content'] ?? '') ?>;
const DESC_CONTENT        = <?= json_encode($sequence['description'] ?? '') ?>;
const SEQ_ID         = <?= $seqId ?>;
const CSRF_TOKEN     = <?= json_encode(Security::generateCsrfToken()) ?>;

document.addEventListener('DOMContentLoaded', function() {
  const introQ      = initQuill('#intro-editor', INTRO_CONTENT);
  const introHintQ  = initQuill('#intro-hint-editor', INTRO_HINT_CONTENT);
  const finaleQ     = initQuill('#finale-editor', FINALE_CONTENT);
  const finaleHintQ = initQuill('#finale-hint-editor', FINALE_HINT_CONTENT);
  const solutionQ   = initQuill('#solution-editor', SOLUTION_CONTENT);
  const thankyouQ   = initQuill('#thankyou-editor', THANKYOU_CONTENT);
  const descQ       = initQuill('#desc-editor', DESC_CONTENT);

  const seqForm = document.getElementById('sequence-form');
  seqForm.addEventListener('submit', function() {
    document.getElementById('desc-content').value        = descQ.root.innerHTML;
    document.getElementById('intro-content').value       = introQ.root.innerHTML;
    document.getElementById('intro-hint-content').value  = introHintQ.root.innerHTML;
    document.getElementById('finale-content').value      = finaleQ.root.innerHTML;
    document.getElementById('finale-hint-content').value = finaleHintQ.root.innerHTML;
    document.getElementById('solution-content').value    = solutionQ.root.innerHTML;
    document.getElementById('thankyou-content').value    = thankyouQ.root.innerHTML;
    window._seqDirty = false; // saving — don't warn
  });

  setupLocalPreview('intro-file',       'intro-preview',       'intro-upload-area');
  setupLocalPreview('intro-hint-file',  'intro-hint-preview',  'intro-hint-upload-area');
  setupLocalPreview('finale-hint-file', 'finale-hint-preview', 'finale-hint-upload-area');
  setupLocalPreview('solution-file',    'solution-preview',    'solution-upload-area');

  // ── Unsaved-changes guard ──
  // Mark the form dirty on a real edit, then warn before following links that leave
  // the page without saving (Manage Clues, Whodunnit Setup). We arm the tracker only
  // after init settles, and ignore programmatic Quill changes (initQuill setting the
  // editors' starting content fires text-change), so it never fires with no edits.
  window._seqDirty = false;
  let _dirtyArmed = false;
  const markDirty = function () { if (_dirtyArmed) window._seqDirty = true; };
  seqForm.addEventListener('input', markDirty);
  seqForm.addEventListener('change', markDirty);
  [introQ, introHintQ, finaleQ, finaleHintQ, solutionQ, thankyouQ, descQ].forEach(function (q) {
    if (q) q.on('text-change', function (delta, oldDelta, source) { if (source === 'user') markDirty(); });
  });
  setTimeout(function () { _dirtyArmed = true; }, 600);
  document.querySelectorAll('.js-leave-guard').forEach(function (a) {
    a.addEventListener('click', function (e) {
      if (window._seqDirty &&
          !confirm('You have unsaved changes to this sequence. Leave without saving them?')) {
        e.preventDefault();
      }
    });
  });

  seqTypeToggle();
  const _seqType = document.getElementById('seq-type');
  if (_seqType) _seqType.addEventListener('change', seqTypeToggle);
});

// For Who-dun-it sequences the accusation replaces the Solution Code, so hide the
// code fields and surface the dedicated Whodunnit Setup button.
function seqTypeToggle() {
  const type = document.getElementById('seq-type');
  if (!type) return;
  const isWd = (type.value === 'whodunit');
  const isInteractive = (type.value === 'interactive');
  const show = function (id, on) { const el = document.getElementById(id); if (el) el.style.display = on ? '' : 'none'; };
  // Who-dun-it and Interactive replace the Solution Code with an accusation/vote,
  // so they have no Solution Code — that gate is the accusation itself.
  show('solcode-field', !isWd && !isInteractive);
  show('reqcode-row', !isWd && !isInteractive);
  show('whodunnit-setup-btn', isWd);
  show('interactive-setup-btn', isInteractive);
  // The Game Board Theme picker is only relevant for the gameboard type.
  show('gb-theme-row', type.value === 'gameboard');
}

// Publish toggle — uses fetch (the control sits inside the main sequence form,
// so it can't be its own nested <form>).
function togglePublishSeq() {
  const btn = document.getElementById('publish-btn');
  const csrf = document.querySelector('input[name="csrf_token"]')?.value
            || (typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '');
  if (btn) { btn.disabled = true; btn.textContent = '…'; }
  fetch('<?= url('admin/sequences/' . $seqId . '/publish') ?>', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ csrf_token: csrf, redirect_to: 'edit' }),
  }).then(function () { location.reload(); })
    .catch(function () { location.reload(); });
}

function resetStats(url) {
  if (!confirm('Reset views, completions and average time for this sequence? This cannot be undone.')) return;
  const btn = document.getElementById('reset-stats-btn');
  const reset = (msg) => { alert(msg); if (btn) { btn.disabled = false; btn.innerHTML = '↺ Reset Statistics'; } };
  const csrf = document.querySelector('input[name="csrf_token"]')?.value
            || (typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '');
  if (btn) { btn.disabled = true; btn.textContent = 'Resetting…'; }
  fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
    body: new URLSearchParams({ csrf_token: csrf }),
  })
    .then(async (r) => {
      if (r.redirected || !r.ok) { reset('Reset failed (' + r.status + '). Try reloading the page and signing in again.'); return; }
      let d;
      try { d = await r.json(); } catch (e) { reset('Reset failed: unexpected response.'); return; }
      if (d && d.success) { location.reload(); }
      else { reset((d && d.error) || 'Reset failed.'); }
    })
    .catch(() => reset('Reset failed: network error.'));
}

function copySequenceUrl(btn) {
  const url = btn.dataset.url;
  const done = () => {
    const original = btn.innerHTML;
    btn.innerHTML = '✅ Copied!';
    setTimeout(() => { btn.innerHTML = original; }, 1800);
  };
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(url).then(done).catch(() => fallbackCopy(url, done));
  } else {
    fallbackCopy(url, done);
  }
}
function fallbackCopy(text, cb) {
  const ta = document.createElement('textarea');
  ta.value = text;
  ta.style.position = 'fixed';
  ta.style.opacity = '0';
  document.body.appendChild(ta);
  ta.select();
  try { document.execCommand('copy'); cb(); } catch (e) { prompt('Copy this URL:', text); }
  document.body.removeChild(ta);
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';

