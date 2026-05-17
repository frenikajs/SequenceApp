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
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label>Start Code <span class="req">*</span></label>
              <input type="text" name="start_code" required value="<?= e($sequence['start_code']) ?>">
            </div>
            <div class="form-group flex-1">
              <label>Finale Code</label>
              <input type="text" name="finale_code" value="<?= e($sequence['finale_code'] ?? '') ?>">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label class="checkbox-label">
                <input type="checkbox" name="finale_requires_code" value="1"
                  <?= $sequence['finale_requires_code'] ? 'checked' : '' ?>>
                Finale requires a code
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

      <!-- Introduction -->
      <div class="card mt-4">
        <div class="card-header"><h2>Introduction</h2></div>
        <div class="card-body">
          <div class="form-group">
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
        </div>
      </div>

      <!-- Finale -->
      <div class="card mt-4">
        <div class="card-header"><h2>Finale</h2></div>
        <div class="card-body">
          <div class="form-group">
            <div class="quill-editor" id="finale-editor"></div>
            <input type="hidden" name="finale_content" id="finale-content">
          </div>
          <div class="form-group">
            <label>Attached Media</label>
            <?php if ($sequence['finale_file_path']): ?>
            <div class="current-media" id="finale-current-media">
              <?= renderMediaWidget($sequence, 'finale', $seqId, 'sequence') ?>
            </div>
            <?php else: ?>
            <div class="upload-area" id="finale-upload-area">
              <div class="upload-placeholder">
                <span>📁 Drag & drop or click to upload</span>
                <span class="small muted">PNG, JPG, PDF, MP3, WAV, OGG — max 10MB</span>
              </div>
              <input type="file" name="finale_file" id="finale-file" class="upload-input"
                     accept=".png,.jpg,.jpeg,.pdf,.mp3,.wav,.ogg,.m4a,.mov">
            </div>
            <div id="finale-preview" class="media-preview hidden"></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Theme column -->
    <div class="form-col-sm">
      <div class="card sticky-top">
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
const INTRO_CONTENT  = <?= json_encode($sequence['introduction_content'] ?? '') ?>;
const FINALE_CONTENT = <?= json_encode($sequence['finale_content'] ?? '') ?>;
const SEQ_ID         = <?= $seqId ?>;
const CSRF_TOKEN     = <?= json_encode(Security::generateCsrfToken()) ?>;

document.addEventListener('DOMContentLoaded', function() {
  const introQ  = initQuill('#intro-editor', INTRO_CONTENT);
  const finaleQ = initQuill('#finale-editor', FINALE_CONTENT);

  document.getElementById('sequence-form').addEventListener('submit', function() {
    document.getElementById('intro-content').value  = introQ.root.innerHTML;
    document.getElementById('finale-content').value = finaleQ.root.innerHTML;
  });

  setupLocalPreview('intro-file',  'intro-preview',  'intro-upload-area');
  setupLocalPreview('finale-file', 'finale-preview', 'finale-upload-area');
});

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

