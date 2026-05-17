<?php
$pageTitle = 'New Sequence';
$activeNav = 'create';
$input     = $input ?? [];
ob_start();
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
  <strong>Please fix the following:</strong>
  <ul><?php foreach ($errors as $e_msg): ?><li><?= e($e_msg) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="POST" action="<?= url('admin/sequences/create') ?>" enctype="multipart/form-data" id="sequence-form">
  <?= csrf_field() ?>

  <div class="form-grid">
    <!-- Left column: core settings -->
    <div class="form-col">
      <div class="card">
        <div class="card-header"><h2>Sequence Details</h2></div>
        <div class="card-body">
          <div class="form-group">
            <label>Title <span class="req">*</span></label>
            <input type="text" name="title" id="seq-title" required
                   value="<?= e($input['title'] ?? '') ?>" placeholder="The Lost Archive">
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3" placeholder="Brief description for admin referenceâ€¦"><?= e($input['description'] ?? '') ?></textarea>
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label>Type <span class="req">*</span></label>
              <select name="type" id="seq-type">
                <option value="sequential" <?= ($input['type'] ?? 'sequential') === 'sequential' ? 'selected' : '' ?>>Sequential (one clue at a time)</option>
                <option value="open" <?= ($input['type'] ?? '') === 'open' ? 'selected' : '' ?>>Open (all clues visible)</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label>Start Code <span class="req">*</span></label>
              <input type="text" name="start_code" required
                     value="<?= e($input['start_code'] ?? '') ?>" placeholder="BEGIN2024">
              <small>Case-insensitive. Users enter this to start.</small>
            </div>
            <div class="form-group flex-1">
              <label>Finale Code</label>
              <input type="text" name="finale_code"
                     value="<?= e($input['finale_code'] ?? '') ?>" placeholder="ENDGAME">
              <small>Leave blank to auto-reveal finale.</small>
            </div>
          </div>
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" name="finale_requires_code" value="1"
                <?= !isset($input['finale_requires_code']) || $input['finale_requires_code'] ? 'checked' : '' ?>>
              Finale requires a code
            </label>
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label>Expires At</label>
              <input type="datetime-local" name="expires_at" value="<?= e($input['expires_at'] ?? '') ?>">
            </div>
            <div class="form-group flex-1 align-end">
              <label class="checkbox-label">
                <input type="checkbox" name="published" value="1" <?= !empty($input['published']) ? 'checked' : '' ?>>
                Publish immediately
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Introduction -->
      <div class="card mt-4">
        <div class="card-header"><h2>Introduction</h2><small>Shown after start code is entered</small></div>
        <div class="card-body">
          <div class="form-group">
            <label>Content</label>
            <div class="quill-editor" id="intro-editor"></div>
            <input type="hidden" name="introduction_content" id="intro-content">
          </div>
          <div class="form-group">
            <label>Attached Media</label>
            <div class="upload-area" id="intro-upload-area">
              <div class="upload-placeholder">
                <span>ðŸ" Drag & drop or click to upload</span>
                <span class="small muted">PNG, JPG, PDF, MP3, WAV, OGG â€" max 10MB</span>
              </div>
              <input type="file" name="intro_file" id="intro-file" class="upload-input"
                     accept=".png,.jpg,.jpeg,.pdf,.mp3,.wav,.ogg,.m4a,.mov">
            </div>
            <div id="intro-preview" class="media-preview hidden"></div>
          </div>
        </div>
      </div>

      <!-- Finale -->
      <div class="card mt-4">
        <div class="card-header"><h2>Finale</h2><small>Shown when sequence is completed</small></div>
        <div class="card-body">
          <div class="form-group">
            <label>Content</label>
            <div class="quill-editor" id="finale-editor"></div>
            <input type="hidden" name="finale_content" id="finale-content">
          </div>
          <div class="form-group">
            <label>Attached Media</label>
            <div class="upload-area" id="finale-upload-area">
              <div class="upload-placeholder">
                <span>ðŸ" Drag & drop or click to upload</span>
                <span class="small muted">PNG, JPG, PDF, MP3, WAV, OGG â€" max 10MB</span>
              </div>
              <input type="file" name="finale_file" id="finale-file" class="upload-input"
                     accept=".png,.jpg,.jpeg,.pdf,.mp3,.wav,.ogg,.m4a,.mov">
            </div>
            <div id="finale-preview" class="media-preview hidden"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right column: theme -->
    <div class="form-col-sm">
      <div class="card sticky-top">
        <div class="card-header"><h2>Theme</h2></div>
        <div class="card-body">
          <div class="color-grid">
            <div class="form-group">
              <label>Background</label>
              <div class="color-input-wrap">
                <input type="color" name="bg_color" value="#0f0f1a" class="color-swatch" id="bg_color">
                <input type="text" class="color-hex" value="#0f0f1a" data-for="bg_color">
              </div>
            </div>
            <div class="form-group">
              <label>Text</label>
              <div class="color-input-wrap">
                <input type="color" name="text_color" value="#e0e0e0" class="color-swatch" id="text_color">
                <input type="text" class="color-hex" value="#e0e0e0" data-for="text_color">
              </div>
            </div>
            <div class="form-group">
              <label>Button</label>
              <div class="color-input-wrap">
                <input type="color" name="button_color" value="#6c63ff" class="color-swatch" id="button_color">
                <input type="text" class="color-hex" value="#6c63ff" data-for="button_color">
              </div>
            </div>
            <div class="form-group">
              <label>Button Text</label>
              <div class="color-input-wrap">
                <input type="color" name="btn_text_color" value="#ffffff" class="color-swatch" id="btn_text_color">
                <input type="text" class="color-hex" value="#ffffff" data-for="btn_text_color">
              </div>
            </div>
            <div class="form-group">
              <label>Accent</label>
              <div class="color-input-wrap">
                <input type="color" name="accent_color" value="#ff6b6b" class="color-swatch" id="accent_color">
                <input type="text" class="color-hex" value="#ff6b6b" data-for="accent_color">
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Font Family</label>
            <select name="font_family">
              <option value="Inter, sans-serif">Inter</option>
              <option value="'Courier New', monospace">Courier New (Mono)</option>
              <option value="Georgia, serif">Georgia (Serif)</option>
              <option value="'Cinzel', serif">Cinzel (Dramatic)</option>
              <option value="'Special Elite', cursive">Special Elite (Typewriter)</option>
            </select>
          </div>
          <div class="form-group">
            <label>Container Width</label>
            <select name="container_width">
              <option value="640px">640px (Narrow)</option>
              <option value="800px" selected>800px (Default)</option>
              <option value="960px">960px (Wide)</option>
              <option value="100%">Full Width</option>
            </select>
          </div>
          <div class="form-group">
            <label>Custom CSS</label>
            <textarea name="custom_css" rows="5" placeholder="/* Your custom CSS here */"><?= e($input['custom_css'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="form-actions">
    <a href="<?= url('admin/sequences') ?>" class="btn btn-ghost">Cancel</a>
    <button type="submit" class="btn btn-primary">Create Sequence & Add Clues â†’</button>
  </div>
</form>

<!-- Quill.js -->
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Title â†’ slug preview
  const titleEl = document.getElementById('seq-title');

  // Quill editors
  const introQ  = initQuill('#intro-editor', '<?= e(addslashes($input['introduction_content'] ?? '')) ?>');
  const finaleQ = initQuill('#finale-editor', '<?= e(addslashes($input['finale_content'] ?? '')) ?>');

  document.getElementById('sequence-form').addEventListener('submit', function() {
    document.getElementById('intro-content').value  = introQ.root.innerHTML;
    document.getElementById('finale-content').value = finaleQ.root.innerHTML;
  });

  // File upload previews
  setupLocalPreview('intro-file', 'intro-preview', 'intro-upload-area');
  setupLocalPreview('finale-file', 'finale-preview', 'finale-upload-area');
});
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';

