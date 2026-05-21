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
    <a href="<?= url('s/' . $sequence['slug']) ?>" target="_blank" class="btn btn-ghost btn-sm">
      View Live ↗
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
    <a href="<?= url('admin/sequences/' . $seqId . '/clues') ?>" class="btn btn-secondary btn-sm">
      Manage Clues
    </a>
  </div>
</div>

<form method="POST" action="<?= url('admin/sequences/' . $seqId . '/edit') ?>" enctype="multipart/form-data" id="sequence-form">
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
              <input type="text" name="slug" value="<?= e($sequence['slug']) ?>">
            </div>
            <small>Change carefully — existing links will break.</small>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3"><?= e($sequence['description'] ?? '') ?></textarea>
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label>Type</label>
              <select name="type">
                <option value="sequential" <?= $sequence['type'] === 'sequential' ? 'selected' : '' ?>>Sequential</option>
                <option value="open" <?= $sequence['type'] === 'open' ? 'selected' : '' ?>>Open</option>
                <option value="gameboard" <?= $sequence['type'] === 'gameboard' ? 'selected' : '' ?>>Game Board</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label>Start Code <span class="req">*</span></label>
              <input type="text" name="start_code" class="code-upper" required value="<?= e($sequence['start_code']) ?>">
              <small>Players enter this to access the mystery.</small>
            </div>
            <div class="form-group flex-1">
              <label>Solution Code</label>
              <input type="text" name="finale_code" class="code-upper" value="<?= e($sequence['finale_code'] ?? '') ?>">
              <small>Entered on &ldquo;Ready to Solve?&rdquo; to reveal the Solution.</small>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label class="checkbox-label">
                <input type="checkbox" name="finale_requires_code" value="1"
                  <?= $sequence['finale_requires_code'] ? 'checked' : '' ?>>
                Require Solution Code
              </label>
            </div>
            <div class="form-group flex-1">
              <label class="checkbox-label">
                <input type="checkbox" name="published" value="1"
                  <?= $sequence['published'] ? 'checked' : '' ?>>
                Published
              </label>
            </div>
          </div>
          <div class="form-group">
            <label>Expires At</label>
            <input type="datetime-local" name="expires_at"
                   value="<?= $sequence['expires_at'] ? date('Y-m-d\TH:i', strtotime($sequence['expires_at'])) : '' ?>">
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
      <details class="card mt-4 seq-section">
        <summary class="card-header"><h2>Solution</h2><span class="seq-chevron">▾</span></summary>
        <div class="card-body">
          <p class="small muted" style="margin-bottom:.75rem">Revealed after the Solution Code is entered.</p>
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
    </div>

    <!-- Theme column -->
    <div class="form-col-sm">
      <div class="sticky-top">
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
                'Inter, sans-serif'        => 'Inter',
                "'Courier New', monospace" => 'Courier New (Mono)',
                'Georgia, serif'           => 'Georgia (Serif)',
                "'Cinzel', serif"          => 'Cinzel (Dramatic)',
                "'Special Elite', cursive" => 'Special Elite (Typewriter)',
              ];
              foreach ($fonts as $val => $label): ?>
              <option value="<?= e($val) ?>" <?= $t['font_family'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
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
const SEQ_ID         = <?= $seqId ?>;
const CSRF_TOKEN     = <?= json_encode(Security::generateCsrfToken()) ?>;

document.addEventListener('DOMContentLoaded', function() {
  const introQ      = initQuill('#intro-editor', INTRO_CONTENT);
  const introHintQ  = initQuill('#intro-hint-editor', INTRO_HINT_CONTENT);
  const finaleQ     = initQuill('#finale-editor', FINALE_CONTENT);
  const finaleHintQ = initQuill('#finale-hint-editor', FINALE_HINT_CONTENT);
  const solutionQ   = initQuill('#solution-editor', SOLUTION_CONTENT);
  const thankyouQ   = initQuill('#thankyou-editor', THANKYOU_CONTENT);

  document.getElementById('sequence-form').addEventListener('submit', function() {
    document.getElementById('intro-content').value       = introQ.root.innerHTML;
    document.getElementById('intro-hint-content').value  = introHintQ.root.innerHTML;
    document.getElementById('finale-content').value      = finaleQ.root.innerHTML;
    document.getElementById('finale-hint-content').value = finaleHintQ.root.innerHTML;
    document.getElementById('solution-content').value    = solutionQ.root.innerHTML;
    document.getElementById('thankyou-content').value    = thankyouQ.root.innerHTML;
  });

  setupLocalPreview('intro-file',       'intro-preview',       'intro-upload-area');
  setupLocalPreview('intro-hint-file',  'intro-hint-preview',  'intro-hint-upload-area');
  setupLocalPreview('finale-hint-file', 'finale-hint-preview', 'finale-hint-upload-area');
  setupLocalPreview('solution-file',    'solution-preview',    'solution-upload-area');
});

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

